<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'brounhall/v1', '/appointments', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'brounhall_create_appointment', 'permission_callback' => 'brounhall_appointment_permission' ) );
	register_rest_route( 'brounhall/v1', '/appointments/nonce', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_appointment_nonce', 'permission_callback' => 'brounhall_appointment_permission' ) );
} );

function brounhall_appointment_permission( WP_REST_Request $request ) {
	$key = (string) get_option( 'brounhall_appointment_api_key', '' );
	$timestamp = (string) $request->get_header( 'x-bourn-hall-timestamp' );
	$signature = (string) $request->get_header( 'x-bourn-hall-signature' );
	$request_id = sanitize_text_field( (string) $request->get_header( 'x-bourn-hall-request-id' ) );
	$body = $request->get_body();
	if ( 'POST' === strtoupper( $request->get_method() ) ) {
		$content_type = strtolower( trim( explode( ';', (string) $request->get_header( 'content-type' ), 2 )[0] ) );
		if ( 'application/json' !== $content_type ) { return new WP_Error( 'brounhall_appointment_unsupported_media', 'Unsupported media type', array( 'status' => 415 ) ); }
		if ( strlen( $body ) > 16 * KB_IN_BYTES ) { return new WP_Error( 'brounhall_appointment_body_too_large', 'Invalid request', array( 'status' => 413 ) ); }
	}
	if ( ! $key || ! $timestamp || ! ctype_digit( $timestamp ) || abs( time() - (int) $timestamp ) > 300 || ! preg_match( '/^[a-f0-9]{64}$/', $signature ) || ! hash_equals( hash_hmac( 'sha256', $timestamp . '.' . $body, $key ), $signature ) ) { return new WP_Error( 'brounhall_appointment_unauthorized', 'Unauthorized', array( 'status' => 401 ) ); }
	if ( ! $request_id || get_transient( 'brounhall_appointment_request_' . md5( $request_id ) ) ) { return new WP_Error( 'brounhall_appointment_replayed', 'Request rejected', array( 'status' => 409 ) ); }
	set_transient( 'brounhall_appointment_request_' . md5( $request_id ), 1, 10 * MINUTE_IN_SECONDS );
	if ( 'POST' !== strtoupper( $request->get_method() ) ) { return true; }
	$count = (int) get_transient( 'brounhall_appointment_rate_global' );
	if ( $count >= 300 ) { return new WP_Error( 'brounhall_appointment_rate_limited', 'Too many requests', array( 'status' => 429, 'retry_after' => 900 ) ); }
	set_transient( 'brounhall_appointment_rate_global', $count + 1, 15 * MINUTE_IN_SECONDS );
	return true;
}

function brounhall_create_appointment( WP_REST_Request $request ) {
	$payload = json_decode( $request->get_body(), true );
	if ( ! is_array( $payload ) || ! wp_verify_nonce( sanitize_text_field( (string) ( $payload['wpNonce'] ?? '' ) ), 'brounhall_create_appointment' ) ) { return new WP_Error( 'brounhall_appointment_nonce_invalid', 'Request rejected', array( 'status' => 403 ) ); }
	$data = brounhall_appointment_validate_payload( $payload );
	if ( is_wp_error( $data ) ) { brounhall_appointment_security_log( 'appointment.validation_failed', $request ); return $data; }
	$token_key = 'brounhall_appointment_token_' . md5( $data['clientToken'] );
	$token_state = get_transient( $token_key );
	if ( is_numeric( $token_state ) && (int) $token_state > 0 ) { brounhall_appointment_security_log( 'appointment.duplicate', $request ); return rest_ensure_response( array( 'success' => true, 'duplicate' => true ) ); }
	if ( false !== $token_state ) { brounhall_appointment_security_log( 'appointment.duplicate', $request ); return new WP_Error( 'brounhall_appointment_duplicate', 'Request already received', array( 'status' => 409 ) ); }
	try { $fingerprint = brounhall_appointment_key_provider()->blind_index( 'duplicate', strtolower( $data['email'] ) . '|' . preg_replace( '/\D+/', '', $data['phone'] ) . '|' . $data['location'] . '|' . $data['service'] . '|' . strtolower( $data['message'] ) ); } catch ( Throwable $error ) { return new WP_Error( 'brounhall_appointment_crypto_unavailable', 'Appointment could not be secured', array( 'status' => 503 ) ); }
	$fingerprint_key = 'brounhall_appointment_fingerprint_' . $fingerprint;
	if ( false !== get_transient( $fingerprint_key ) ) { brounhall_appointment_security_log( 'appointment.duplicate', $request ); return new WP_Error( 'brounhall_appointment_duplicate', 'A similar request was recently received', array( 'status' => 409 ) ); }
	set_transient( $token_key, 'processing', 10 * MINUTE_IN_SECONDS );
	set_transient( $fingerprint_key, 1, DAY_IN_SECONDS );
	$data['submittedAt'] = current_time( 'mysql' );
	$reference = brounhall_appointment_reference();
	$payload = $data;
	unset( $payload['clientToken'] );
	try { $envelope = brounhall_appointment_envelope( $reference, $payload ); } catch ( Throwable $error ) { delete_transient( $token_key ); delete_transient( $fingerprint_key ); return new WP_Error( 'brounhall_appointment_crypto_unavailable', 'Appointment could not be secured', array( 'status' => 503 ) ); }
	$meta = array(
		'_bh_appointment_ref' => $reference, '_bh_appointment_status' => 'new', '_bh_created_at' => current_time( 'mysql' ), '_bh_migration_version' => 1,
		'_bh_pii_ciphertext' => $envelope['ciphertext'], '_bh_pii_nonce' => $envelope['nonce'], '_bh_pii_salt' => $envelope['salt'], '_bh_wrapped_dek' => $envelope['wrapped_dek'],
		'_bh_crypto_algorithm' => $envelope['algorithm'], '_bh_crypto_version' => $envelope['crypto_version'], '_bh_kek_id' => $envelope['kek_id'],
		'_bh_email_blind_index' => $envelope['email_index'], '_bh_email_index_version' => $envelope['email_index_version'], '_bh_phone_blind_index' => $envelope['phone_index'], '_bh_phone_index_version' => $envelope['phone_index_version'],
	);
	$post_id = wp_insert_post( array( 'post_type' => 'bh_appointment', 'post_status' => 'private', 'post_title' => $reference, 'meta_input' => $meta ), true );
	if ( is_wp_error( $post_id ) ) { delete_transient( $token_key ); delete_transient( $fingerprint_key ); return new WP_Error( 'brounhall_appointment_failed', 'Appointment could not be saved', array( 'status' => 500 ) ); }
	try { $verified = brounhall_appointment_decrypt( $reference, brounhall_appointment_read_envelope( $post_id ) ); if ( $verified !== $payload ) { throw new RuntimeException( 'Appointment read-back verification failed' ); } } catch ( Throwable $error ) { wp_delete_post( $post_id, true ); delete_transient( $token_key ); delete_transient( $fingerprint_key ); return new WP_Error( 'brounhall_appointment_failed', 'Appointment could not be saved', array( 'status' => 500 ) ); }
	set_transient( $token_key, (int) $post_id, 10 * MINUTE_IN_SECONDS );
	$recipients = preg_split( '/\s+/', (string) get_option( 'brounhall_appointment_recipients', '' ), -1, PREG_SPLIT_NO_EMPTY );
	$recipients = array_values( array_filter( $recipients, 'is_email' ) );
	if ( $recipients && ! brounhall_appointment_is_local() ) {
		$subject = 'New Bourn Hall appointment request';
		$body = "A new appointment request was received.\n\nReference: {$reference}\nReceived: {$data['submittedAt']}\n\nOpen the secured WordPress admin area to view the appointment.";
		wp_mail( $recipients, $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
	}
	brounhall_appointment_security_log( 'appointment.accepted', $request );
	return rest_ensure_response( array( 'success' => true ) );
}

function brounhall_appointment_security_log( $event, WP_REST_Request $request ) {
	error_log( wp_json_encode( array( 'event' => $event, 'requestId' => sanitize_text_field( (string) $request->get_header( 'x-bourn-hall-request-id' ) ) ) ) );
}

function brounhall_appointment_nonce() {
	return rest_ensure_response( array( 'nonce' => wp_create_nonce( 'brounhall_create_appointment' ) ) );
}

function brounhall_appointment_is_local() {
	$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : '';
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	return 'local' === $environment || in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) || (bool) preg_match( '/\.local$/i', $host );
}
