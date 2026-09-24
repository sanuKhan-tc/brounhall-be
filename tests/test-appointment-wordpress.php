<?php
declare( strict_types=1 );

require dirname( __DIR__ ) . '/wp-load.php';
if ( ! function_exists( 'brounhall_appointment_envelope' ) ) { fwrite( STDERR, "Headless plugin is not active\n" ); exit( 1 ); }
$reference = 'APT-TEST-' . strtoupper( bin2hex( random_bytes( 4 ) ) );
$payload = array( 'name' => 'ENCRYPTION_TEST_NAME_12345', 'email' => 'encryption-test-12345@example.invalid', 'phone' => '+971500001234', 'location' => 'Dubai', 'service' => 'IUI', 'message' => 'UNIQUE_TEST_SECRET_MESSAGE_98765', 'consent' => true );
$envelope = brounhall_appointment_envelope( $reference, $payload );
$post_id = wp_insert_post( array( 'post_type' => 'bh_appointment', 'post_status' => 'private', 'post_title' => $reference ), true );
if ( is_wp_error( $post_id ) ) { throw new RuntimeException( $post_id->get_error_message() ); }
foreach ( array( '_bh_appointment_ref' => $reference, '_bh_pii_ciphertext' => $envelope['ciphertext'], '_bh_pii_nonce' => $envelope['nonce'], '_bh_pii_salt' => $envelope['salt'], '_bh_wrapped_dek' => $envelope['wrapped_dek'], '_bh_crypto_algorithm' => $envelope['algorithm'], '_bh_crypto_version' => 1, '_bh_kek_id' => $envelope['kek_id'], '_bh_email_blind_index' => $envelope['email_index'], '_bh_phone_blind_index' => $envelope['phone_index'] ) as $key => $value ) { update_post_meta( $post_id, $key, $value ); }
$raw = implode( '|', array_map( static fn( $key ) => (string) get_post_meta( $post_id, $key, true ), array( '_bh_pii_ciphertext', '_bh_pii_nonce', '_bh_pii_salt', '_bh_wrapped_dek' ) ) );
if ( false !== strpos( $raw, 'ENCRYPTION_TEST_NAME_12345' ) || false !== strpos( $raw, 'encryption-test-12345@example.invalid' ) || false !== strpos( $raw, 'UNIQUE_TEST_SECRET_MESSAGE_98765' ) ) { wp_delete_post( $post_id, true ); throw new RuntimeException( 'Plaintext appeared in encrypted metadata' ); }
if ( brounhall_appointment_decrypt( $reference, brounhall_appointment_read_envelope( $post_id ) ) !== $payload ) { wp_delete_post( $post_id, true ); throw new RuntimeException( 'WordPress storage read-back failed' ); }
wp_delete_post( $post_id, true );
$legacy_id = wp_insert_post( array( 'post_type' => 'bh_appointment', 'post_status' => 'private', 'post_title' => 'Appointment: Legacy Test', 'meta_input' => array( '_brounhall_appointment_data' => wp_json_encode( $payload ) ) ), true );
if ( is_wp_error( $legacy_id ) ) { throw new RuntimeException( $legacy_id->get_error_message() ); }
brounhall_appointment_store_migrated( $legacy_id, $payload );
if ( get_post_meta( $legacy_id, '_brounhall_appointment_data', true ) || false !== strpos( get_the_title( $legacy_id ), 'Appointment:' ) ) { wp_delete_post( $legacy_id, true ); throw new RuntimeException( 'Legacy migration cleanup failed' ); }
if ( brounhall_appointment_decrypt( get_post_meta( $legacy_id, '_bh_appointment_ref', true ), brounhall_appointment_read_envelope( $legacy_id ) ) !== $payload ) { wp_delete_post( $legacy_id, true ); throw new RuntimeException( 'Migrated record cannot be decrypted' ); }
wp_delete_post( $legacy_id, true );
echo "appointment WordPress storage and migration tests passed\n";
