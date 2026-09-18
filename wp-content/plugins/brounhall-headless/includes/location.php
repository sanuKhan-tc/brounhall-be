<?php
/**
 * BrounHall Location/Clinic fields.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_location_field_group' );

foreach ( array( 'name', 'phone', 'hours' ) as $field_name ) {
	add_filter( 'acf/validate_value/key=field_brounhall_location_' . $field_name, 'brounhall_validate_location_plain_text', 10, 4 );
}

add_filter( 'acf/update_value/key=field_brounhall_location_name', 'brounhall_sanitize_location_name', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_location_phone', 'brounhall_sanitize_location_phone', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_location_hours', 'brounhall_sanitize_location_hours', 10, 3 );

/**
 * Build the Location field group.
 *
 * @param string[] $post_types Post types that should receive the group.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_location_field_group( $post_types = array( 'location' ) ) {
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {
		return new WP_Error( 'brounhall_invalid_location_post_types', 'Location post types must be a non-empty array.' );
	}

	$locations = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || ! preg_match( '/^[a-z0-9_]+$/', $post_type ) ) {
			return new WP_Error( 'brounhall_invalid_location_post_type', 'Location post types must be safe identifiers.' );
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
		'key'                                   => 'group_brounhall_location',
		'title'                                 => 'BrounHall Location',
		'fields'                                => brounhall_location_fields(),
		'location'                              => $locations,
		'menu_order'                            => 0,
		'position'                              => 'normal',
		'style'                                 => 'default',
		'label_placement'                       => 'top',
		'instruction_placement'                 => 'label',
		'active'                                => true,
		'show_in_graphql'                       => true,
		'graphql_field_name'                    => 'locationDetails',
		'map_graphql_types_from_location_rules' => true,
	);
}

/**
 * Return the flat Location field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function brounhall_location_fields() {
	return array(
		array(
			'key'          => 'field_brounhall_location_name',
			'label'        => 'Display Name',
			'name'         => 'location_name',
			'type'         => 'text',
			'instructions' => 'Short name used on clinic cards, such as Dubai.',
			'maxlength'    => 100,
		),
		array(
			'key'          => 'field_brounhall_location_phone',
			'label'        => 'Clinic Phone',
			'name'         => 'location_phone',
			'type'         => 'text',
			'instructions' => 'Location-specific public phone number, if applicable.',
			'maxlength'    => 100,
		),
		array(
			'key'          => 'field_brounhall_location_hours',
			'label'        => 'Opening Hours',
			'name'         => 'location_hours',
			'type'         => 'textarea',
			'instructions' => 'Plain-text public opening-hours display.',
			'maxlength'    => 500,
			'rows'         => 3,
		),
	);
}

/**
 * Register the Location field group for the Location post type only.
 *
 * @return void
 */
function brounhall_register_location_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field_group = brounhall_location_field_group( array( 'location' ) );

	if ( ! is_wp_error( $field_group ) ) {
		acf_add_local_field_group( $field_group );
	}
}

/**
 * Reject markup in Location text fields.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_location_plain_text( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'This field accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}

function brounhall_sanitize_location_name( $value, $post_id, $field ) {
	return is_string( $value ) ? substr( sanitize_text_field( $value ), 0, 100 ) : '';
}

function brounhall_sanitize_location_phone( $value, $post_id, $field ) {
	return is_string( $value ) ? substr( sanitize_text_field( $value ), 0, 100 ) : '';
}

function brounhall_sanitize_location_hours( $value, $post_id, $field ) {
	return is_string( $value ) ? substr( sanitize_textarea_field( $value ), 0, 500 ) : '';
}
