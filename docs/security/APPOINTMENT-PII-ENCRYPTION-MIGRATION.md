# Appointment PII encryption migration

## Scope

The `bh_appointment` model protects `name`, `email`, `phone`, `location`, `service`, `message`, consent and submission time in a single encrypted payload. The post title is an opaque `APT-*` reference. Status, reference, created time and migration metadata remain operational metadata.

## Cryptography

- XChaCha20-Poly1305-IETF authenticated encryption.
- A random 32-byte DEK per appointment.
- A random 32-byte salt and 24-byte nonce per envelope.
- HKDF-SHA-256 record key with context `brounhall:appointment:pii:v1`.
- AAD binds the envelope to the opaque appointment reference and crypto version.
- The DEK is wrapped with the configured server-side KEK provider and identified by `kek_id`.
- Email and phone use separate HMAC-SHA-256 blind-index keys and version metadata.

Keys are read from the server environment only. They are never written to options, post meta, REST responses, logs or Git. Production KMS/HSM integration remains an infrastructure requirement.

The local provider reads the active KEK from `BOURNHALL_APPOINTMENT_KEK`; a non-active key ID is resolved from `BOURNHALL_APPOINTMENT_KEK_<KEY_ID>` with non-alphanumeric characters converted to underscores. This is only a provider seam for local/staging validation, not a claim of KMS equivalence.

## Migration

The migration is dual-read and batchable. It detects existing `_brounhall_appointment_data`, normalizes it, writes the encrypted envelope, reads it back, decrypts it, compares it with the normalized source and only then removes the legacy meta. A failed record retains its plaintext for manual remediation; it is not silently marked complete.

```text
wp brounhall appointments encrypt-migrate --dry-run
wp brounhall appointments encrypt-migrate --batch-size=50
wp brounhall appointments crypto-verify
```

Run a database backup and restore test before any non-local migration. Do not run the migration automatically on plugin activation or through REST.

## Rotation

`crypto-rewrap` unwraps a DEK with the old KEK and wraps the same DEK with a new KEK; the appointment payload is not decrypted for ordinary KEK rotation. `crypto-reencrypt` decrypts and creates a new envelope, also allowing blind-index version rotation.

```text
wp brounhall appointments crypto-rewrap --from-key=appointment-kek-v1 --to-key=appointment-kek-v2 --dry-run
wp brounhall appointments crypto-reencrypt --dry-run
```

After rotation, run `crypto-verify` and confirm the old key remains available until every record using it has been rewrapped.

## Admin and failure behavior

Only users with `brounhall_view_appointment_pii` can decrypt records; the capability is granted to administrators. The CPT uses native appointment capabilities such as `edit_appointments` and `edit_private_appointments`. AEAD or key failures show a generic admin error and never fall back to plaintext for encrypted records. Audit events contain only event name, post ID and crypto version.

## phpMyAdmin verification

Select the WordPress database in phpMyAdmin and replace `YOUR_APPOINTMENT_ID` with an appointment post ID:

```sql
SELECT ID, post_title, post_status, post_date
FROM wp_posts
WHERE post_type = 'bh_appointment'
ORDER BY ID DESC;
```

Inspect the appointment metadata:

```sql
SELECT post_id, meta_key, LEFT(meta_value, 120) AS value_preview
FROM wp_postmeta
WHERE post_id = YOUR_APPOINTMENT_ID
ORDER BY meta_key;
```

Expected protected fields include:

```text
_bh_pii_ciphertext
_bh_pii_nonce
_bh_pii_salt
_bh_wrapped_dek
_bh_crypto_algorithm
_bh_crypto_version
_bh_kek_id
_bh_email_blind_index
_bh_phone_blind_index
```

The post title should contain only an opaque reference such as `APT-ABC1234567`. Check for legacy plaintext storage with:

```sql
SELECT post_id, meta_key
FROM wp_postmeta
WHERE meta_key = '_brounhall_appointment_data';
```

After migration, this query should return no records for successfully migrated appointments. Encryption keys must not appear in `wp_posts`, `wp_postmeta`, options or any other database table; they remain in server-side environment or KMS configuration.

## Production checklist

- [ ] Sodium is enabled in the production PHP runtime.
- [ ] Approved KMS/key provider is configured.
- [ ] KEK and separate email, phone and duplicate-index keys are provisioned and access-restricted.
- [ ] Backup and restore are confirmed.
- [ ] Migration dry run and count review are approved.
- [ ] Migration and `crypto-verify` complete successfully.
- [ ] Plaintext appointment meta scan is zero.
- [ ] Authorized and unauthorized admin access are tested.
- [ ] Retention, deletion, mail recipients and backup handling have privacy-owner approval.

Production migration was not performed by this change.
