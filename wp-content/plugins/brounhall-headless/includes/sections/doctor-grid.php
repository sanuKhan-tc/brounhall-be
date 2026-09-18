<?php
/**
 * BrounHall Doctor Grid field group.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_doctor_grid_field_group' );

add_filter( 'acf/validate_value/key=field_brounhall_doctor_grid_title', 'brounhall_validate_doctor_grid_plain_text', 10, 4 );
add_filter( 'acf/validate_value/key=field_brounhall_doctor_grid_description', 'brounhall_validate_doctor_grid_plain_text', 10, 4 );
add_filter( 'acf/validate_value/key=field_brounhall_doctor_grid_doctors', 'brounhall_validate_doctor_grid_doctors', 10, 4 );

add_filter( 'acf/update_value/key=field_brounhall_doctor_grid_title', 'brounhall_sanitize_doctor_grid_title', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_doctor_grid_description', 'brounhall_sanitize_doctor_grid_description', 10, 3 );

/**
 * Build the Doctor Grid field group for approved post types.
 *
 * @param string[] $post_types Post types that should receive the group.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_doctor_grid_field_group( $post_types = array( 'page' ) ) {
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {
		return new WP_Error( 'brounhall_invalid_doctor_grid_post_types', 'Doctor Grid post types must be a non-empty array.' );
	}

	$locations = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || ! preg_match( '/^[a-z0-9_]+$/', $post_type ) ) {
			return new WP_Error( 'brounhall_invalid_doctor_grid_post_type', 'Doctor Grid post types must be safe identifiers.' );
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
		'key'                                   => 'group_brounhall_doctor_grid',
		'title'                                 => 'BrounHall Doctor Grid',
		'fields'                                => brounhall_doctor_grid_fields(),
		'location'                              => $locations,
		'menu_order'                            => 0,
		'position'                              => 'normal',
		'style'                                 => 'default',
		'label_placement'                       => 'top',
		'instruction_placement'                 => 'label',
		'active'                                => true,
		'show_in_graphql'                       => true,
		'graphql_field_name'                    => 'doctorGrid',
		'map_graphql_types_from_location_rules' => true,
	);
}

/**
 * Return the flat Doctor Grid field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function brounhall_doctor_grid_fields() {
	return array(
		array(
			'key'          => 'field_brounhall_doctor_grid_title',
			'label'        => 'Title',
			'name'         => 'doctor_grid_title',
			'type'         => 'text',
			'instructions' => 'Optional heading for the Doctors shown in this section.',
			'maxlength'    => 200,
		),
		array(
			'key'          => 'field_brounhall_doctor_grid_description',
			'label'        => 'Description',
			'name'         => 'doctor_grid_description',
			'type'         => 'textarea',
			'instructions' => 'Optional plain-text introduction for this Doctors section.',
			'maxlength'    => 1000,
			'rows'         => 5,
		),
		brounhall_acf_link_field(
			'field_brounhall_doctor_grid_cta',
			'doctor_grid_cta',
			'Section Link',
			array( 'instructions' => 'Optional link displayed with this Doctors section.' )
		),
		array(
			'key'           => 'field_brounhall_doctor_grid_doctors',
			'label'         => 'Doctors',
			'name'          => 'doctor_grid_doctors',
			'type'          => 'relationship',
			'instructions'  => 'Select and order the Doctors shown in this section.',
			'post_type'     => array( 'doctor' ),
			'filters'       => array( 'search', 'post_type' ),
			'return_format' => 'object',
			'min'           => 0,
		),
	);
}

/**
 * Register Doctor Grid for native Pages only at this stage.
 *
 * @return void
 */
function brounhall_register_doctor_grid_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field_group = brounhall_doctor_grid_field_group( array( 'page' ) );

	if ( ! is_wp_error( $field_group ) ) {
		acf_add_local_field_group( $field_group );
	}
}

/**
 * Reject markup in Doctor Grid text fields.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_doctor_grid_plain_text( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'This field accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}

/**
 * Ensure the relationship contains only Doctor post IDs.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_doctor_grid_doctors( $valid, $value, $field, $input ) {
	if ( true !== $valid || empty( $value ) ) {
		return $valid;
	}

	$ids = is_array( $value ) ? $value : array( $value );

	foreach ( $ids as $id ) {
		if ( 'doctor' !== get_post_type( absint( $id ) ) ) {
			return __( 'Only Doctors can be selected.', 'brounhall-headless' );
		}
	}

	return $valid;
}

/**
 * Sanitize the Doctor Grid title at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_doctor_grid_title( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_text_field( $value ) : '';
}

/**
 * Sanitize the Doctor Grid description at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_doctor_grid_description( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_textarea_field( $value ) : '';
}
