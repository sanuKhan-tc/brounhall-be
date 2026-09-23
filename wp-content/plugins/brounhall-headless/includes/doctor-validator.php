<?php
defined( 'ABSPATH' ) || exit;

function brounhall_doctor_slug_is_valid( $slug ) {
	return is_string( $slug ) && (bool) preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug );
}

function brounhall_doctor_text( $value, $limit = 8000 ) {
	$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
	return substr( $value, 0, $limit );
}

function brounhall_doctor_type_is_valid( $type ) {
	return in_array( $type, array( 'doctor', 'embryologist' ), true );
}

function brounhall_normalize_doctor_data( $data ) {
	$data = is_array( $data ) ? $data : array();
	$type = brounhall_doctor_type_is_valid( $data['type'] ?? '' ) ? $data['type'] : 'doctor';
	$clinic = $data['clinic'] ?? '';
	$clinic = in_array( $clinic, array( 'Dubai', 'Abu Dhabi', 'Al Ain' ), true ) ? $clinic : '';
	return array(
		'type'             => $type,
		'role'             => brounhall_doctor_text( $data['role'] ?? '', 200 ),
		'headline'         => brounhall_doctor_text( $data['headline'] ?? '', 300 ),
		'specialty'        => brounhall_doctor_text( $data['specialty'] ?? '', 200 ),
		'clinic'           => $clinic,
		'imageId'          => absint( $data['imageId'] ?? 0 ),
		'imageAlt'         => brounhall_doctor_text( $data['imageAlt'] ?? '', 200 ),
		'nationality'      => brounhall_doctor_text( $data['nationality'] ?? '', 200 ),
		'languages'        => brounhall_doctor_text( $data['languages'] ?? '', 300 ),
		'areasOfInterest'  => brounhall_doctor_text( $data['areasOfInterest'] ?? '' ),
		'education'        => brounhall_doctor_text( $data['education'] ?? '' ),
		'bio'              => brounhall_doctor_text( $data['bio'] ?? '' ),
	);
}
