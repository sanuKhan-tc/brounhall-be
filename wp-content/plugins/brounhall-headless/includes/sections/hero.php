<?php
/**
 * BrounHall Hero field group.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_hero_field_group' );

foreach ( array( 'eyebrow', 'title', 'subtitle', 'description' ) as $field_name ) {
	add_filter( 'acf/validate_value/key=field_brounhall_hero_' . $field_name, 'brounhall_validate_hero_plain_text', 10, 4 );
}

/**
 * Build the reusable Hero field group for approved post types.
 *
 * @param string[] $post_types Post types that should receive the group.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_hero_field_group( $post_types = array( 'page' ) ) {
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {
		return new WP_Error( 'brounhall_invalid_hero_post_types', 'Hero field group post types must be a non-empty array.' );
	}

	$locations = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || ! preg_match( '/^[a-z0-9_]+$/', $post_type ) ) {
			return new WP_Error( 'brounhall_invalid_hero_post_type', 'Hero field group post types must be safe identifiers.' );
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
		'key'                                   => 'group_brounhall_hero',
		'title'                                 => 'BrounHall Hero',
		'fields'                                => brounhall_hero_fields(),
		'location'                              => $locations,
		'menu_order'                            => 0,
		'position'                              => 'normal',
		'style'                                 => 'default',
		'label_placement'                       => 'top',
		'instruction_placement'                => 'label',
		'hide_on_screen'                        => '',
		'active'                                => true,
		'show_in_graphql'                       => true,
		'graphql_field_name'                    => 'hero',
		'map_graphql_types_from_location_rules' => true,
	);
}

/**
 * Return the flat Hero field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function brounhall_hero_fields() {
	return array(
		array(
			'key'         => 'field_brounhall_hero_eyebrow',
			'label'       => 'Eyebrow',
			'name'        => 'hero_eyebrow',
			'type'        => 'text',
			'instructions' => 'Optional short label above the Hero title.',
			'maxlength'   => 200,
		),
		array(
			'key'         => 'field_brounhall_hero_title',
			'label'       => 'Title',
			'name'        => 'hero_title',
			'type'        => 'text',
			'instructions' => 'Hero heading. Presentation styling remains frontend-owned.',
			'maxlength'   => 200,
		),
		array(
			'key'         => 'field_brounhall_hero_subtitle',
			'label'       => 'Subtitle',
			'name'        => 'hero_subtitle',
			'type'        => 'text',
			'maxlength'   => 200,
		),
		array(
			'key'         => 'field_brounhall_hero_description',
			'label'       => 'Description',
			'name'        => 'hero_description',
			'type'        => 'textarea',
			'maxlength'   => 500,
			'rows'        => 4,
		),
		brounhall_acf_image_field(
			'field_brounhall_hero_image',
			'hero_image',
			'Hero Image',
			array( 'instructions' => 'Optional WordPress Media Library image.' )
		),
		brounhall_acf_link_field(
			'field_brounhall_hero_primary_cta',
			'hero_primary_cta',
			'Primary CTA'
		),
		brounhall_acf_link_field(
			'field_brounhall_hero_secondary_cta',
			'hero_secondary_cta',
			'Secondary CTA'
		),
	);
}

/**
 * Register Hero for native Pages only at this stage.
 *
 * @return void
 */
function brounhall_register_hero_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field_group = brounhall_hero_field_group( array( 'page' ) );

	if ( ! is_wp_error( $field_group ) ) {
		acf_add_local_field_group( $field_group );
	}
}

/**
 * Reject executable markup in Hero text fields.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_hero_plain_text( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'This field accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}
