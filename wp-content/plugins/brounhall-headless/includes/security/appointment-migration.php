<?php
defined( 'ABSPATH' ) || exit;

function brounhall_appointment_legacy_payload( $post_id ) {
	$legacy = json_decode( get_post_meta( $post_id, '_brounhall_appointment_data', true ), true );
	if ( ! is_array( $legacy ) ) { return new WP_Error( 'legacy_missing', 'Legacy appointment data is missing' ); }
	$payload = array(
		'name' => brounhall_appointment_text( $legacy['name'] ?? '', 100 ), 'email' => sanitize_email( (string) ( $legacy['email'] ?? '' ) ), 'phone' => brounhall_appointment_text( $legacy['phone'] ?? '', 30 ),
		'location' => brounhall_appointment_text( $legacy['location'] ?? '', 50 ), 'service' => brounhall_appointment_text( $legacy['service'] ?? '', 100 ), 'message' => brounhall_appointment_text( $legacy['message'] ?? '', 2000 ),
		'consent' => true === ( $legacy['consent'] ?? true ), 'submittedAt' => sanitize_text_field( (string) ( $legacy['submittedAt'] ?? get_post_time( 'mysql', true, $post_id ) ) ),
	);
	if ( ! $payload['name'] || ! is_email( $payload['email'] ) || ! $payload['phone'] || ! $payload['location'] || ! $payload['service'] ) { return new WP_Error( 'legacy_invalid', 'Legacy appointment data failed validation' ); }
	return $payload;
}

function brounhall_appointment_store_migrated( $post_id, $payload ) {
	$reference = (string) get_post_meta( $post_id, '_bh_appointment_ref', true );
	$reference = $reference ? $reference : brounhall_appointment_reference();
	$created_at = $payload['submittedAt'] ?? current_time( 'mysql' );
	$envelope = brounhall_appointment_envelope( $reference, $payload );
	$meta = array(
		'_bh_appointment_ref' => $reference, '_bh_appointment_status' => get_post_meta( $post_id, '_bh_appointment_status', true ) ?: 'new', '_bh_created_at' => get_post_meta( $post_id, '_bh_created_at', true ) ?: $created_at, '_bh_migration_version' => 1,
		'_bh_pii_ciphertext' => $envelope['ciphertext'], '_bh_pii_nonce' => $envelope['nonce'], '_bh_pii_salt' => $envelope['salt'], '_bh_wrapped_dek' => $envelope['wrapped_dek'], '_bh_crypto_algorithm' => $envelope['algorithm'], '_bh_crypto_version' => $envelope['crypto_version'], '_bh_kek_id' => $envelope['kek_id'],
		'_bh_email_blind_index' => $envelope['email_index'], '_bh_email_index_version' => $envelope['email_index_version'], '_bh_phone_blind_index' => $envelope['phone_index'], '_bh_phone_index_version' => $envelope['phone_index_version'],
	);
	foreach ( $meta as $key => $value ) { update_post_meta( $post_id, $key, $value ); }
	$verified = brounhall_appointment_decrypt( $reference, brounhall_appointment_read_envelope( $post_id ) );
	if ( $verified !== $payload ) { throw new RuntimeException( 'Migration read-back verification failed' ); }
	delete_post_meta( $post_id, '_brounhall_appointment_data' );
	wp_update_post( array( 'ID' => $post_id, 'post_title' => $reference ) );
	brounhall_appointment_security_event( 'appointment.crypto.migrated', $post_id, '1' );
}

function brounhall_appointment_ids( $args = array() ) {
	$query = array( 'post_type' => 'bh_appointment', 'post_status' => 'private', 'posts_per_page' => isset( $args['batch-size'] ) ? min( 100, max( 1, absint( $args['batch-size'] ) ) ) : 50, 'offset' => isset( $args['offset'] ) ? absint( $args['offset'] ) : 0, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' );
	if ( ! empty( $args['record'] ) ) { $query['post__in'] = array( absint( $args['record'] ) ); }
	if ( isset( $args['limit'] ) ) { $query['posts_per_page'] = min( $query['posts_per_page'], max( 1, absint( $args['limit'] ) ) ); }
	return get_posts( $query );
}

function brounhall_appointment_all_ids() {
	return get_posts( array( 'post_type' => 'bh_appointment', 'post_status' => 'private', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
}

function brounhall_appointment_cli_migrate( $args, $assoc_args ) {
	$dry_run = isset( $assoc_args['dry-run'] );
	$ids = brounhall_appointment_ids( $assoc_args );
	$counts = array( 'total' => count( brounhall_appointment_all_ids() ), 'batch' => count( $ids ), 'already_encrypted' => 0, 'legacy' => 0, 'eligible' => 0, 'migrated' => 0, 'failed' => 0 );
	foreach ( $ids as $post_id ) {
		$has_legacy = (bool) get_post_meta( $post_id, '_brounhall_appointment_data', true );
		if ( get_post_meta( $post_id, '_bh_pii_ciphertext', true ) && ! $has_legacy ) { $counts['already_encrypted']++; continue; }
		$counts['legacy']++;
		$payload = brounhall_appointment_legacy_payload( $post_id );
		if ( is_wp_error( $payload ) ) { $counts['failed']++; continue; }
		$counts['eligible']++;
		if ( $dry_run ) { continue; }
		try { brounhall_appointment_store_migrated( $post_id, $payload ); $counts['migrated']++; } catch ( Throwable $error ) { $counts['failed']++; }
	}
	foreach ( $counts as $key => $value ) { \WP_CLI::log( $key . ': ' . $value ); }
	if ( ! $dry_run && $counts['failed'] ) { \WP_CLI::warning( 'Some records require manual remediation; plaintext was retained for failed records.' ); }
}

function brounhall_appointment_cli_verify( $args, $assoc_args ) {
	$ids = brounhall_appointment_all_ids();
	$counts = array( 'checked' => 0, 'valid' => 0, 'encrypted_invalid' => 0, 'plaintext_remaining' => 0 );
	foreach ( $ids as $post_id ) {
		$counts['checked']++;
		if ( get_post_meta( $post_id, '_brounhall_appointment_data', true ) ) { $counts['plaintext_remaining']++; }
		$envelope = brounhall_appointment_read_envelope( $post_id );
		if ( empty( $envelope['ciphertext'] ) ) { continue; }
		try { brounhall_appointment_decrypt( (string) get_post_meta( $post_id, '_bh_appointment_ref', true ), $envelope ); $counts['valid']++; } catch ( Throwable $error ) { $counts['encrypted_invalid']++; }
	}
	foreach ( $counts as $key => $value ) { \WP_CLI::log( $key . ': ' . $value ); }
	if ( $counts['encrypted_invalid'] || $counts['plaintext_remaining'] ) { \WP_CLI::error( 'Appointment crypto verification failed.' ); }
}

function brounhall_appointment_cli_rewrap( $args, $assoc_args ) {
	$from = sanitize_key( $assoc_args['from-key'] ?? '' );
	$to = sanitize_key( $assoc_args['to-key'] ?? '' );
	if ( ! $from || ! $to ) { \WP_CLI::error( '--from-key and --to-key are required.' ); }
	$dry_run = isset( $assoc_args['dry-run'] );
	$count = 0;
	foreach ( brounhall_appointment_all_ids() as $post_id ) {
		$envelope = brounhall_appointment_read_envelope( $post_id );
		if ( $from !== (string) $envelope['kek_id'] ) { continue; }
		$count++;
		if ( $dry_run ) { continue; }
		try {
			$dek = brounhall_appointment_key_provider()->unwrap_key( $envelope['wrapped_dek'], $from );
			$envelope['wrapped_dek'] = brounhall_appointment_key_provider()->wrap_key( $dek, $to );
			$envelope['kek_id'] = $to;
			sodium_memzero( $dek );
			update_post_meta( $post_id, '_bh_wrapped_dek', $envelope['wrapped_dek'] );
			update_post_meta( $post_id, '_bh_kek_id', $to );
			brounhall_appointment_decrypt( get_post_meta( $post_id, '_bh_appointment_ref', true ), $envelope );
			brounhall_appointment_security_event( 'appointment.crypto.rewrapped', $post_id, '1' );
		} catch ( Throwable $error ) { \WP_CLI::warning( 'Rewrap failed for appointment ID ' . absint( $post_id ) ); }
	}
	\WP_CLI::log( 'matched: ' . $count );
}

function brounhall_appointment_cli_reencrypt( $args, $assoc_args ) {
	$dry_run = isset( $assoc_args['dry-run'] );
	$count = 0;
	foreach ( brounhall_appointment_all_ids() as $post_id ) {
		$reference = (string) get_post_meta( $post_id, '_bh_appointment_ref', true );
		$envelope = brounhall_appointment_read_envelope( $post_id );
		if ( ! $reference || empty( $envelope['ciphertext'] ) ) { continue; }
		$count++;
		if ( $dry_run ) { continue; }
		try {
			$payload = brounhall_appointment_decrypt( $reference, $envelope );
			$new = brounhall_appointment_envelope( $reference, $payload );
			foreach ( array( '_bh_pii_ciphertext' => $new['ciphertext'], '_bh_pii_nonce' => $new['nonce'], '_bh_pii_salt' => $new['salt'], '_bh_wrapped_dek' => $new['wrapped_dek'], '_bh_crypto_algorithm' => $new['algorithm'], '_bh_crypto_version' => $new['crypto_version'], '_bh_kek_id' => $new['kek_id'], '_bh_email_blind_index' => $new['email_index'], '_bh_email_index_version' => $new['email_index_version'], '_bh_phone_blind_index' => $new['phone_index'], '_bh_phone_index_version' => $new['phone_index_version'] ) as $key => $value ) { update_post_meta( $post_id, $key, $value ); }
			brounhall_appointment_decrypt( $reference, brounhall_appointment_read_envelope( $post_id ) );
		} catch ( Throwable $error ) { \WP_CLI::warning( 'Re-encryption failed for appointment ID ' . absint( $post_id ) ); }
	}
	\WP_CLI::log( 'matched: ' . $count );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'brounhall appointments encrypt-migrate', 'brounhall_appointment_cli_migrate' );
	\WP_CLI::add_command( 'brounhall appointments crypto-verify', 'brounhall_appointment_cli_verify' );
	\WP_CLI::add_command( 'brounhall appointments crypto-rewrap', 'brounhall_appointment_cli_rewrap' );
	\WP_CLI::add_command( 'brounhall appointments crypto-reencrypt', 'brounhall_appointment_cli_reencrypt' );
}
