<?php
defined( 'ABSPATH' ) || exit;

function brounhall_appointment_allowed_locations() {
	return array( 'Dubai', 'Abu Dhabi', 'Al Ain' );
}

function brounhall_appointment_allowed_services() {
	return array( 'IVF Treatment', 'ICSI', 'IUI', 'Egg Freezing', 'Genetic Testing', 'Fertility Preservation', 'Male Fertility' );
}

function brounhall_appointment_text( $value, $limit ) {
	$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
	return substr( $value, 0, $limit );
}

function brounhall_appointment_validate_payload( $payload ) {
	if ( ! is_array( $payload ) ) { return new WP_Error( 'brounhall_invalid_appointment', 'Invalid appointment request', array( 'status' => 400 ) ); }
	$name = brounhall_appointment_text( $payload['name'] ?? '', 100 );
	$phone = brounhall_appointment_text( $payload['phone'] ?? '', 30 );
	$email = sanitize_email( (string) ( $payload['email'] ?? '' ) );
	$location = brounhall_appointment_text( $payload['location'] ?? '', 50 );
	$service = brounhall_appointment_text( $payload['service'] ?? '', 100 );
	$message = brounhall_appointment_text( $payload['message'] ?? '', 2000 );
	$consent = $payload['consent'] ?? false;
	$website = brounhall_appointment_text( $payload['website'] ?? '', 100 );
	$started_at = isset( $payload['startedAt'] ) ? absint( $payload['startedAt'] ) : 0;
	$client_token = sanitize_text_field( (string) ( $payload['clientToken'] ?? '' ) );

	if ( $website || true !== $consent || ! preg_match( '/^[a-f0-9-]{16,100}$/', $client_token ) || ! $started_at || ! preg_match( '/^[\p{L}\p{M} .\',-]{2,100}$/u', $name ) || ! preg_match( '/^\+?[0-9 ()-]{7,25}$/', $phone ) || ! is_email( $email ) || ! in_array( $location, brounhall_appointment_allowed_locations(), true ) || ! in_array( $service, brounhall_appointment_allowed_services(), true ) || time() * 1000 - $started_at < 1000 || time() * 1000 - $started_at > 86400000 ) {
		return new WP_Error( 'brounhall_invalid_appointment', 'Invalid appointment request', array( 'status' => 400 ) );
	}

	return array( 'name' => $name, 'phone' => $phone, 'email' => $email, 'location' => $location, 'service' => $service, 'message' => $message, 'consent' => true, 'clientToken' => $client_token );
}
