<?php
defined( 'ABSPATH' ) || exit;

function brounhall_clinic_slug_is_valid( $slug ) {
	return is_string( $slug ) && (bool) preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug );
}

function brounhall_clinic_text( $value, $limit = 8000 ) {
	$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
	return substr( $value, 0, $limit );
}

function brounhall_normalize_clinic_data( $data ) {
	$data = is_array( $data ) ? $data : array();
	return array(
		'title'       => brounhall_clinic_text( $data['title'] ?? '', 200 ),
		'address'     => brounhall_clinic_text( $data['address'] ?? '', 500 ),
		'phone'       => brounhall_clinic_text( $data['phone'] ?? '', 100 ),
		'hours'       => brounhall_clinic_text( $data['hours'] ?? '', 200 ),
		'description' => brounhall_clinic_text( $data['description'] ?? '' ),
		'imageId'     => absint( $data['imageId'] ?? 0 ),
		'imageAlt'    => brounhall_clinic_text( $data['imageAlt'] ?? '', 200 ),
	);
}
