<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'brounhall/v1', '/complaints', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'brounhall_create_complaint', 'permission_callback' => 'brounhall_appointment_permission' ) );
	register_rest_route( 'brounhall/v1', '/complaints/nonce', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_complaint_nonce', 'permission_callback' => 'brounhall_appointment_permission' ) );
} );
add_filter( 'rest_post_dispatch', function ( $response, $server, $request ) {
	if ( 0 === strpos( $request->get_route(), '/brounhall/v1/complaints' ) ) { $response->header( 'Cache-Control', 'no-store, private, max-age=0' ); $response->header( 'Pragma', 'no-cache' ); $response->header( 'X-Robots-Tag', 'noindex' ); }
	return $response;
}, 10, 3 );

function brounhall_complaint_validate_payload( $payload ) {
	if ( ! is_array( $payload ) ) return new WP_Error( 'brounhall_invalid_complaint', 'Invalid complaint request', array( 'status' => 400 ) );
	$allowed = array( 'name', 'phone', 'email', 'message', 'website', 'startedAt', 'clientToken', 'wpNonce' );
	if ( array_diff( array_keys( $payload ), $allowed ) ) return new WP_Error( 'brounhall_invalid_complaint', 'Invalid complaint request', array( 'status' => 400 ) );
	$name = brounhall_appointment_text( $payload['name'] ?? '', 100 ); $phone = brounhall_appointment_text( $payload['phone'] ?? '', 30 ); $email = sanitize_email( (string) ( $payload['email'] ?? '' ) ); $message = brounhall_appointment_text( $payload['message'] ?? '', 2000 ); $website = brounhall_appointment_text( $payload['website'] ?? '', 100 ); $started_at = absint( $payload['startedAt'] ?? 0 ); $client_token = sanitize_text_field( (string) ( $payload['clientToken'] ?? '' ) );
	if ( $website || ! preg_match( '/^[a-f0-9-]{16,100}$/', $client_token ) || ! $started_at || ! preg_match( '/^[\p{L}\p{M} .\',-]{2,100}$/u', $name ) || ! preg_match( '/^\+?[0-9 ()-]{7,25}$/', $phone ) || ! is_email( $email ) || ! $message || strlen( (string) ( $payload['message'] ?? '' ) ) > 2000 || time() * 1000 - $started_at < 1000 || time() * 1000 - $started_at > 86400000 ) return new WP_Error( 'brounhall_invalid_complaint', 'Invalid complaint request', array( 'status' => 400 ) );
	return array( 'name' => $name, 'phone' => $phone, 'email' => $email, 'message' => $message, 'submittedAt' => current_time( 'mysql' ), 'clientToken' => $client_token );
}
function brounhall_complaint_nonce() { return rest_ensure_response( array( 'nonce' => wp_create_nonce( 'brounhall_create_complaint' ) ) ); }
function brounhall_create_complaint( WP_REST_Request $request ) {
	$payload = json_decode( $request->get_body(), true );
	if ( ! is_array( $payload ) || ! wp_verify_nonce( sanitize_text_field( (string) ( $payload['wpNonce'] ?? '' ) ), 'brounhall_create_complaint' ) ) return new WP_Error( 'brounhall_complaint_nonce_invalid', 'Request rejected', array( 'status' => 403 ) );
	$data = brounhall_complaint_validate_payload( $payload ); if ( is_wp_error( $data ) ) { brounhall_log( 'warn', 'complaint_validation_failed', 'Complaint validation failed', array( 'request_id' => brounhall_observability_request_id( $request ), 'error_type' => 'validation_failure' ) ); return $data; }
	$token_key = 'brounhall_complaint_token_' . md5( $data['clientToken'] ); if ( false !== get_transient( $token_key ) ) return rest_ensure_response( array( 'success' => true, 'duplicate' => true ) );
	set_transient( $token_key, 'processing', 10 * MINUTE_IN_SECONDS );
	$reference = 'CMP-' . strtoupper( bin2hex( random_bytes( 5 ) ) ); $secure = $data; unset( $secure['clientToken'] );
	try { $envelope = brounhall_appointment_envelope( $reference, $secure ); } catch ( Throwable $error ) { delete_transient( $token_key ); return new WP_Error( 'brounhall_complaint_crypto_unavailable', 'Complaint could not be secured', array( 'status' => 503 ) ); }
	$meta = array( '_bh_complaint_ref' => $reference, '_bh_pii_ciphertext' => $envelope['ciphertext'], '_bh_pii_nonce' => $envelope['nonce'], '_bh_pii_salt' => $envelope['salt'], '_bh_wrapped_dek' => $envelope['wrapped_dek'], '_bh_crypto_algorithm' => $envelope['algorithm'], '_bh_crypto_version' => $envelope['crypto_version'], '_bh_kek_id' => $envelope['kek_id'], '_bh_email_blind_index' => $envelope['email_index'], '_bh_email_index_version' => $envelope['email_index_version'], '_bh_phone_blind_index' => $envelope['phone_index'], '_bh_phone_index_version' => $envelope['phone_index_version'] );
	$post_id = wp_insert_post( array( 'post_type' => 'bh_complaint', 'post_status' => 'private', 'post_title' => $reference, 'meta_input' => $meta ), true );
	if ( is_wp_error( $post_id ) ) { delete_transient( $token_key ); return new WP_Error( 'brounhall_complaint_failed', 'Complaint could not be saved', array( 'status' => 500 ) ); }
	set_transient( $token_key, (int) $post_id, 10 * MINUTE_IN_SECONDS );
	$recipients = preg_split( '/\s+/', (string) get_option( 'brounhall_appointment_recipients', '' ), -1, PREG_SPLIT_NO_EMPTY ); $recipients = array_values( array_filter( $recipients, 'is_email' ) );
	if ( $recipients && function_exists( 'brounhall_appointment_is_local' ) && ! brounhall_appointment_is_local() ) wp_mail( $recipients, 'New Bourn Hall patient complaint', "A new patient complaint was received.\n\nReference: {$reference}\nReceived: {$data['submittedAt']}\n\nOpen the secured WordPress admin area to view it.", array( 'Content-Type: text/plain; charset=UTF-8' ) );
	brounhall_log( 'info', 'complaint_accepted', 'Patient complaint accepted', array( 'request_id' => brounhall_observability_request_id( $request ) ) );
	return rest_ensure_response( array( 'success' => true ) );
}
