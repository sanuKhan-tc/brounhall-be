<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () { register_rest_route( 'brounhall/v1', '/appointments', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'brounhall_create_appointment', 'permission_callback' => 'brounhall_appointment_permission' ) ); } );

function brounhall_appointment_permission( WP_REST_Request $request ) {
	$key = (string) get_option( 'brounhall_appointment_api_key', '' );
	$timestamp = (string) $request->get_header( 'x-bourn-hall-timestamp' );
	$signature = (string) $request->get_header( 'x-bourn-hall-signature' );
	$request_id = sanitize_text_field( (string) $request->get_header( 'x-bourn-hall-request-id' ) );
	$body = $request->get_body();
	if ( ! $key || ! $timestamp || ! ctype_digit( $timestamp ) || abs( time() - (int) $timestamp ) > 300 || ! preg_match( '/^[a-f0-9]{64}$/', $signature ) || ! hash_equals( hash_hmac( 'sha256', $timestamp . '.' . $body, $key ), $signature ) ) { return new WP_Error( 'brounhall_appointment_unauthorized', 'Unauthorized', array( 'status' => 401 ) ); }
	if ( ! $request_id || get_transient( 'brounhall_appointment_request_' . md5( $request_id ) ) ) { return new WP_Error( 'brounhall_appointment_replayed', 'Request rejected', array( 'status' => 409 ) ); }
	set_transient( 'brounhall_appointment_request_' . md5( $request_id ), 1, 10 * MINUTE_IN_SECONDS );
	$count = (int) get_transient( 'brounhall_appointment_rate_global' );
	if ( $count >= 30 ) { return new WP_Error( 'brounhall_appointment_rate_limited', 'Too many requests', array( 'status' => 429 ) ); }
	set_transient( 'brounhall_appointment_rate_global', $count + 1, 15 * MINUTE_IN_SECONDS );
	return true;
}

function brounhall_create_appointment( WP_REST_Request $request ) {
	$payload = json_decode( $request->get_body(), true );
	$data = brounhall_appointment_validate_payload( $payload );
	if ( is_wp_error( $data ) ) { return $data; }
	$data['submittedAt'] = current_time( 'mysql' );
	$post_id = wp_insert_post( array( 'post_type' => 'bh_appointment', 'post_status' => 'private', 'post_title' => 'Appointment: ' . $data['name'], 'meta_input' => array( '_brounhall_appointment_data' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) ), true );
	if ( is_wp_error( $post_id ) ) { return new WP_Error( 'brounhall_appointment_failed', 'Appointment could not be saved', array( 'status' => 500 ) ); }
	$recipients = preg_split( '/\s+/', (string) get_option( 'brounhall_appointment_recipients', '' ), -1, PREG_SPLIT_NO_EMPTY );
	$recipients = array_values( array_filter( $recipients, 'is_email' ) );
	if ( $recipients ) {
		$subject = 'New Bourn Hall appointment request';
		$body = "Name: {$data['name']}\nEmail: {$data['email']}\nPhone: {$data['phone']}\nClinic: {$data['location']}\nTreatment: {$data['service']}\n\nMessage:\n{$data['message']}\n\nAppointment ID: {$post_id}";
		wp_mail( $recipients, $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
	}
	return rest_ensure_response( array( 'success' => true ) );
}
