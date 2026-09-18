<?php
/**
 * BrounHall Text + Image field group.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_text_image_field_group' );

foreach ( array( 'eyebrow', 'title', 'body' ) as $field_name ) {
	add_filter( 'acf/validate_value/key=field_brounhall_text_image_' . $field_name, 'brounhall_validate_text_image_plain_text', 10, 4 );
}

add_filter( 'acf/update_value/key=field_brounhall_text_image_eyebrow', 'brounhall_sanitize_text_image_eyebrow', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_text_image_title', 'brounhall_sanitize_text_image_title', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_text_image_body', 'brounhall_sanitize_text_image_body', 10, 3 );

/**
 * Build the reusable Text + Image field group for approved post types.
 *
 * @param string[] $post_types Post types that should receive the group.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_text_image_field_group( $post_types = array( 'page' ) ) {
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {
		return new WP_Error( 'brounhall_invalid_text_image_post_types', 'Text + Image field group post types must be a non-empty array.' );
	}

	$locations = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || ! preg_match( '/^[a-z0-9_]+$/', $post_type ) ) {
			return new WP_Error( 'brounhall_invalid_text_image_post_type', 'Text + Image field group post types must be safe identifiers.' );
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
		'key'                                   => 'group_brounhall_text_image',
		'title'                                 => 'BrounHall Text + Image',
		'fields'                                => brounhall_text_image_fields(),
		'location'                              => $locations,
		'menu_order'                            => 0,
		'position'                              => 'normal',
		'style'                                 => 'default',
		'label_placement'                       => 'top',
		'instruction_placement'                => 'label',
		'hide_on_screen'                        => '',
		'active'                                => true,
		'show_in_graphql'                       => true,
		'graphql_field_name'                    => 'textImage',
		'map_graphql_types_from_location_rules' => true,
	);
}

/**
 * Return the flat Text + Image field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function brounhall_text_image_fields() {
	return array(
		array(
			'key'         => 'field_brounhall_text_image_eyebrow',
			'label'       => 'Eyebrow',
			'name'        => 'text_image_eyebrow',
			'type'        => 'text',
			'instructions' => 'Optional short label above the section title.',
			'maxlength'   => 200,
		),
		array(
			'key'         => 'field_brounhall_text_image_title',
			'label'       => 'Title',
			'name'        => 'text_image_title',
			'type'        => 'text',
			'instructions' => 'Section heading.',
			'maxlength'   => 200,
		),
		array(
			'key'         => 'field_brounhall_text_image_body',
			'label'       => 'Body',
			'name'        => 'text_image_body',
			'type'        => 'textarea',
			'instructions' => 'Plain text only. Use blank lines to separate paragraphs.',
			'maxlength'   => 10000,
			'rows'        => 10,
		),
		brounhall_acf_image_field(
			'field_brounhall_text_image_image',
			'text_image_image',
			'Image',
			array( 'instructions' => 'Optional WordPress Media Library image.' )
		),
	);
}

/**
 * Register Text + Image for native Pages only at this stage.
 *
 * @return void
 */
function brounhall_register_text_image_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field_group = brounhall_text_image_field_group( array( 'page' ) );

	if ( ! is_wp_error( $field_group ) ) {
		acf_add_local_field_group( $field_group );
	}
}

/**
 * Reject markup in Text + Image text fields.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_text_image_plain_text( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'This field accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}

/**
 * Sanitize the Text + Image eyebrow at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_text_image_eyebrow( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_text_field( $value ) : '';
}

/**
 * Sanitize the Text + Image title at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_text_image_title( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_text_field( $value ) : '';
}

/**
 * Sanitize the Text + Image body at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_text_image_body( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_textarea_field( $value ) : '';
}
