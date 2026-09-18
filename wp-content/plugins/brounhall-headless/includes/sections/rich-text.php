<?php
/**
 * BrounHall Rich Text field group.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_rich_text_field_group' );

foreach ( array( 'title', 'body' ) as $field_name ) {
	add_filter( 'acf/validate_value/key=field_brounhall_rich_text_' . $field_name, 'brounhall_validate_rich_text_plain_text', 10, 4 );
}

add_filter( 'acf/update_value/key=field_brounhall_rich_text_title', 'brounhall_sanitize_rich_text_title', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_rich_text_body', 'brounhall_sanitize_rich_text_body', 10, 3 );

/**
 * Build the reusable Rich Text field group for approved post types.
 *
 * @param string[] $post_types Post types that should receive the group.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_rich_text_field_group( $post_types = array( 'page' ) ) {
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {
		return new WP_Error( 'brounhall_invalid_rich_text_post_types', 'Rich Text field group post types must be a non-empty array.' );
	}

	$locations = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || ! preg_match( '/^[a-z0-9_]+$/', $post_type ) ) {
			return new WP_Error( 'brounhall_invalid_rich_text_post_type', 'Rich Text field group post types must be safe identifiers.' );
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
		'key'                                   => 'group_brounhall_rich_text',
		'title'                                 => 'BrounHall Rich Text',
		'fields'                                => brounhall_rich_text_fields(),
		'location'                              => $locations,
		'menu_order'                            => 0,
		'position'                              => 'normal',
		'style'                                 => 'default',
		'label_placement'                       => 'top',
		'instruction_placement'                => 'label',
		'hide_on_screen'                        => '',
		'active'                                => true,
		'show_in_graphql'                       => true,
		'graphql_field_name'                    => 'richText',
		'map_graphql_types_from_location_rules' => true,
	);
}

/**
 * Return the flat plain-text Rich Text field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function brounhall_rich_text_fields() {
	return array(
		array(
			'key'         => 'field_brounhall_rich_text_title',
			'label'       => 'Title',
			'name'        => 'rich_text_title',
			'type'        => 'text',
			'instructions' => 'Optional section heading.',
			'maxlength'   => 200,
		),
		array(
			'key'         => 'field_brounhall_rich_text_body',
			'label'       => 'Body',
			'name'        => 'rich_text_body',
			'type'        => 'textarea',
			'instructions' => 'Plain text only. Use blank lines to separate paragraphs.',
			'maxlength'   => 10000,
			'rows'        => 10,
		),
	);
}

/**
 * Register Rich Text for native Pages only at this stage.
 *
 * @return void
 */
function brounhall_register_rich_text_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field_group = brounhall_rich_text_field_group( array( 'page' ) );

	if ( ! is_wp_error( $field_group ) ) {
		acf_add_local_field_group( $field_group );
	}
}

/**
 * Reject markup in Rich Text fields because the current frontend consumes plain text.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_rich_text_plain_text( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'This field accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}

/**
 * Sanitize the Rich Text title at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_rich_text_title( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_text_field( $value ) : '';
}

/**
 * Sanitize the Rich Text body at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_rich_text_body( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_textarea_field( $value ) : '';
}
