<?php
/**
 * BrounHall Doctor field group.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_doctor_field_group' );

add_filter( 'acf/validate_value/key=field_brounhall_doctor_role', 'brounhall_validate_doctor_role', 10, 4 );
add_filter( 'acf/update_value/key=field_brounhall_doctor_role', 'brounhall_sanitize_doctor_role', 10, 3 );

/**
 * Build the Doctor field group.
 *
 * @param string[] $post_types Post types that should receive the group.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_doctor_field_group( $post_types = array( 'doctor' ) ) {
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {
		return new WP_Error( 'brounhall_invalid_doctor_post_types', 'Doctor field group post types must be a non-empty array.' );
	}

	$locations = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || ! preg_match( '/^[a-z0-9_]+$/', $post_type ) ) {
			return new WP_Error( 'brounhall_invalid_doctor_post_type', 'Doctor field group post types must be safe identifiers.' );
		}

		$locations[] = array(
			array(
				'param'    => 'post_type',
				'operator' => '==',
				'value'    => $post_type,
			),
		);
	}

	return array(
		'key'                                   => 'group_brounhall_doctor',
		'title'                                 => 'BrounHall Doctor',
		'fields'                                => brounhall_doctor_fields(),
		'location'                              => $locations,
		'menu_order'                            => 0,
		'position'                              => 'normal',
		'style'                                 => 'default',
		'label_placement'                       => 'top',
		'instruction_placement'                 => 'label',
		'active'                                => true,
		'show_in_graphql'                       => true,
		'graphql_field_name'                    => 'doctorProfile',
		'map_graphql_types_from_location_rules' => true,
	);
}

/**
 * Return the flat Doctor field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function brounhall_doctor_fields() {
	return array(
		array(
			'key'          => 'field_brounhall_doctor_role',
			'label'        => 'Role / Title',
			'name'         => 'doctor_role',
			'type'         => 'text',
			'instructions' => 'The doctor’s editorial role or professional title.',
			'maxlength'    => 200,
		),
	);
}

/**
 * Register the Doctor field group.
 *
 * @return void
 */
function brounhall_register_doctor_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field_group = brounhall_doctor_field_group( array( 'doctor' ) );

	if ( ! is_wp_error( $field_group ) ) {
		acf_add_local_field_group( $field_group );
	}
}

/**
 * Reject markup in the Doctor role field.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_doctor_role( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'The role accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}

/**
 * Sanitize the Doctor role at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_doctor_role( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_text_field( $value ) : '';
}
