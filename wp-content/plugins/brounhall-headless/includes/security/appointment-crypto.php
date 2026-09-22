<?php
defined( 'ABSPATH' ) || exit;

function brounhall_appointment_crypto_version() { return 1; }
function brounhall_appointment_algorithm() { return 'xchacha20poly1305-ietf'; }
function brounhall_appointment_index_version() { return max( 1, absint( getenv( 'BOURNHALL_APPOINTMENT_INDEX_VERSION' ) ?: 1 ) ); }
function brounhall_appointment_reference() { return 'APT-' . strtoupper( bin2hex( random_bytes( 5 ) ) ); }

function brounhall_appointment_envelope( $reference, $payload ) {
	if ( ! function_exists( 'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' ) ) { throw new RuntimeException( 'Sodium is unavailable' ); }
	$provider = brounhall_appointment_key_provider();
	$dek = random_bytes( SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES );
	$salt = random_bytes( 32 );
	$nonce = random_bytes( SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES );
	$record_key = hash_hkdf( 'sha256', $dek, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES, 'brounhall:appointment:pii:v1', $salt );
	$aad = 'brounhall:appointment:' . $reference . ':v1';
	$plaintext = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR );
	$ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt( $plaintext, $aad, $nonce, $record_key );
	$key_id = (string) getenv( 'BOURNHALL_APPOINTMENT_KEK_ID' );
	$key_id = $key_id ? $key_id : 'appointment-kek-v1';
	$wrapped = $provider->wrap_key( $dek, $key_id );
	$email_index = $provider->blind_index( 'email', $payload['email'], brounhall_appointment_index_version() );
	$phone_index = $provider->blind_index( 'phone', $payload['phone'], brounhall_appointment_index_version() );
	sodium_memzero( $record_key );
	sodium_memzero( $dek );
	return array(
		'ciphertext' => base64_encode( $ciphertext ), 'nonce' => base64_encode( $nonce ), 'salt' => base64_encode( $salt ), 'wrapped_dek' => $wrapped,
		'algorithm' => brounhall_appointment_algorithm(), 'crypto_version' => 1, 'kek_id' => $key_id,
		'email_index' => $email_index, 'email_index_version' => brounhall_appointment_index_version(), 'phone_index' => $phone_index, 'phone_index_version' => brounhall_appointment_index_version(),
	);
}

function brounhall_appointment_decrypt( $reference, $envelope ) {
	if ( ! is_array( $envelope ) || 1 !== (int) ( $envelope['crypto_version'] ?? 0 ) || brounhall_appointment_algorithm() !== (string) ( $envelope['algorithm'] ?? '' ) ) { throw new RuntimeException( 'Appointment crypto version is unsupported' ); }
	$nonce = base64_decode( (string) ( $envelope['nonce'] ?? '' ), true );
	$salt = base64_decode( (string) ( $envelope['salt'] ?? '' ), true );
	$ciphertext = base64_decode( (string) ( $envelope['ciphertext'] ?? '' ), true );
	if ( false === $nonce || false === $salt || false === $ciphertext || 32 !== strlen( $salt ) || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES !== strlen( $nonce ) ) { throw new RuntimeException( 'Appointment envelope is incomplete' ); }
	$dek = brounhall_appointment_key_provider()->unwrap_key( (string) ( $envelope['wrapped_dek'] ?? '' ), (string) ( $envelope['kek_id'] ?? '' ) );
	$record_key = hash_hkdf( 'sha256', $dek, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES, 'brounhall:appointment:pii:v1', $salt );
	$plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt( $ciphertext, 'brounhall:appointment:' . $reference . ':v1', $nonce, $record_key );
	sodium_memzero( $record_key );
	sodium_memzero( $dek );
	if ( false === $plaintext ) { throw new RuntimeException( 'Appointment decryption failed' ); }
	$payload = json_decode( $plaintext, true );
	if ( ! is_array( $payload ) ) { throw new RuntimeException( 'Appointment payload is invalid' ); }
	return $payload;
}

function brounhall_appointment_encrypt_meta( $post_id, $reference, $payload ) {
	$envelope = brounhall_appointment_envelope( $reference, $payload );
	$meta = array(
		'_bh_pii_ciphertext' => $envelope['ciphertext'], '_bh_pii_nonce' => $envelope['nonce'], '_bh_pii_salt' => $envelope['salt'], '_bh_wrapped_dek' => $envelope['wrapped_dek'],
		'_bh_crypto_algorithm' => $envelope['algorithm'], '_bh_crypto_version' => $envelope['crypto_version'], '_bh_kek_id' => $envelope['kek_id'],
		'_bh_email_blind_index' => $envelope['email_index'], '_bh_email_index_version' => $envelope['email_index_version'], '_bh_phone_blind_index' => $envelope['phone_index'], '_bh_phone_index_version' => $envelope['phone_index_version'],
	);
	foreach ( $meta as $key => $value ) { update_post_meta( $post_id, $key, $value ); }
	return $envelope;
}

function brounhall_appointment_read_envelope( $post_id ) {
	$keys = array( 'ciphertext' => '_bh_pii_ciphertext', 'nonce' => '_bh_pii_nonce', 'salt' => '_bh_pii_salt', 'wrapped_dek' => '_bh_wrapped_dek', 'algorithm' => '_bh_crypto_algorithm', 'crypto_version' => '_bh_crypto_version', 'kek_id' => '_bh_kek_id' );
	$envelope = array();
	foreach ( $keys as $name => $meta_key ) { $envelope[ $name ] = get_post_meta( $post_id, $meta_key, true ); }
	return $envelope;
}

function brounhall_appointment_get_data( $post_id, $allow_legacy = false ) {
	if ( ! current_user_can( 'brounhall_view_appointment_pii' ) ) { return new WP_Error( 'brounhall_appointment_forbidden', 'Sensitive appointment data is restricted', array( 'status' => 403 ) ); }
	$envelope = brounhall_appointment_read_envelope( $post_id );
	if ( ! empty( $envelope['ciphertext'] ) ) {
		try { return brounhall_appointment_decrypt( get_post_meta( $post_id, '_bh_appointment_ref', true ), $envelope ); } catch ( Throwable $error ) { brounhall_appointment_security_event( 'appointment.pii.decrypt_failed', $post_id, (string) $envelope['crypto_version'] ); return new WP_Error( 'brounhall_appointment_decrypt_failed', 'Sensitive appointment data could not be decrypted' ); }
	}
	if ( $allow_legacy ) {
		$legacy = json_decode( get_post_meta( $post_id, '_brounhall_appointment_data', true ), true );
		return is_array( $legacy ) ? $legacy : new WP_Error( 'brounhall_appointment_data_missing', 'Appointment data is unavailable' );
	}
	return new WP_Error( 'brounhall_appointment_data_missing', 'Appointment data is unavailable' );
}

function brounhall_appointment_security_event( $event, $post_id = 0, $version = '' ) {
	error_log( wp_json_encode( array( 'event' => $event, 'appointmentId' => absint( $post_id ), 'cryptoVersion' => sanitize_text_field( $version ) ) ) );
}
