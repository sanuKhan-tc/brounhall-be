<?php
/**
 * BrounHall Related Content field group.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_related_content_field_group' );

add_filter( 'acf/validate_value/key=field_brounhall_related_content_title', 'brounhall_validate_related_content_plain_text', 10, 4 );
add_filter( 'acf/validate_value/key=field_brounhall_related_content_description', 'brounhall_validate_related_content_plain_text', 10, 4 );
add_filter( 'acf/validate_value/key=field_brounhall_related_content_posts', 'brounhall_validate_related_content_posts', 10, 4 );

add_filter( 'acf/update_value/key=field_brounhall_related_content_title', 'brounhall_sanitize_related_content_title', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_related_content_description', 'brounhall_sanitize_related_content_description', 10, 3 );

/**
 * Build the Related Content field group for approved post types.
 *
 * @param string[] $post_types Post types that should receive the group.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_related_content_field_group( $post_types = array( 'page' ) ) {
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {
		return new WP_Error( 'brounhall_invalid_related_content_post_types', 'Related Content post types must be a non-empty array.' );
	}

	$locations = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || ! preg_match( '/^[a-z0-9_]+$/', $post_type ) ) {
			return new WP_Error( 'brounhall_invalid_related_content_post_type', 'Related Content post types must be safe identifiers.' );
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
		'key'                                   => 'group_brounhall_related_content',
		'title'                                 => 'BrounHall Related Content',
		'fields'                                => brounhall_related_content_fields(),
		'location'                              => $locations,
		'menu_order'                            => 0,
		'position'                              => 'normal',
		'style'                                 => 'default',
		'label_placement'                       => 'top',
		'instruction_placement'                 => 'label',
		'active'                                => true,
		'show_in_graphql'                       => true,
		'graphql_field_name'                    => 'relatedContent',
		'map_graphql_types_from_location_rules' => true,
	);
}

/**
 * Return the flat Related Content field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function brounhall_related_content_fields() {
	return array(
		array(
			'key'          => 'field_brounhall_related_content_title',
			'label'        => 'Title',
			'name'         => 'related_content_title',
			'type'         => 'text',
			'instructions' => 'Optional heading for the selected Posts.',
			'maxlength'    => 200,
		),
		array(
			'key'          => 'field_brounhall_related_content_description',
			'label'        => 'Description',
			'name'         => 'related_content_description',
			'type'         => 'textarea',
			'instructions' => 'Optional plain-text introduction for this section.',
			'maxlength'    => 1000,
			'rows'         => 5,
		),
		brounhall_acf_link_field(
			'field_brounhall_related_content_cta',
			'related_content_cta',
			'Section Link',
			array( 'instructions' => 'Optional link displayed with this section.' )
		),
		array(
			'key'          => 'field_brounhall_related_content_posts',
			'label'        => 'Posts',
			'name'         => 'related_content_posts',
			'type'         => 'relationship',
			'instructions' => 'Select and order the Posts shown in this section.',
			'post_type'    => array( 'post' ),
			'filters'      => array( 'search', 'post_type' ),
			'return_format' => 'object',
			'min'          => 0,
		),
	);
}

/**
 * Register Related Content for native Pages only.
 *
 * @return void
 */
function brounhall_register_related_content_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field_group = brounhall_related_content_field_group( array( 'page' ) );

	if ( ! is_wp_error( $field_group ) ) {
		acf_add_local_field_group( $field_group );
	}
}

/**
 * Reject markup in Related Content text fields.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_related_content_plain_text( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'This field accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}

/**
 * Ensure the relationship contains only native Posts.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_related_content_posts( $valid, $value, $field, $input ) {
	if ( true !== $valid || empty( $value ) ) {
		return $valid;
	}

	$ids = is_array( $value ) ? $value : array( $value );

	foreach ( $ids as $id ) {
		if ( 'post' !== get_post_type( absint( $id ) ) ) {
			return __( 'Only Posts can be selected.', 'brounhall-headless' );
		}
	}

	return $valid;
}

/**
 * Sanitize the Related Content title at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_related_content_title( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_text_field( $value ) : '';
}

/**
 * Sanitize the Related Content description at the storage boundary.
 *
 * @param mixed $value Submitted value.
 * @param mixed $post_id Post identifier.
 * @param array $field ACF field definition.
 * @return string
 */
function brounhall_sanitize_related_content_description( $value, $post_id, $field ) {
	return is_string( $value ) ? sanitize_textarea_field( $value ) : '';
}
