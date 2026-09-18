<?php
/**
 * BrounHall SEO field group.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_seo_field_group' );

foreach ( array(
	'meta_title',
	'meta_description',
	'canonical_url',
	'open_graph_title',
	'open_graph_description',
	'twitter_title',
	'twitter_description',
	'breadcrumb_title',
) as $field_name ) {
	add_filter( 'acf/validate_value/key=field_brounhall_seo_' . $field_name, 'brounhall_validate_seo_plain_text', 10, 4 );
}

add_filter( 'acf/validate_value/key=field_brounhall_seo_canonical_url', 'brounhall_validate_seo_canonical_url', 10, 4 );

/**
 * Build the reusable SEO field group for approved post types.
 *
 * @param string[] $post_types Post types that should receive the group.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_seo_field_group( $post_types = array( 'page' ) ) {
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {
		return new WP_Error( 'brounhall_invalid_seo_post_types', 'SEO field group post types must be a non-empty array.' );
	}

	$locations = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || ! preg_match( '/^[a-z0-9_]+$/', $post_type ) ) {
			return new WP_Error( 'brounhall_invalid_seo_post_type', 'SEO field group post types must be safe identifiers.' );
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
		'key'                                  => 'group_brounhall_seo',
		'title'                                => 'BrounHall SEO',
		'fields'                               => brounhall_seo_fields(),
		'location'                             => $locations,
		'menu_order'                           => 0,
		'position'                             => 'normal',
		'style'                                => 'default',
		'label_placement'                      => 'top',
		'instruction_placement'               => 'label',
		'hide_on_screen'                       => '',
		'active'                               => true,
		'show_in_graphql'                      => true,
		'graphql_field_name'                   => 'seo',
		'map_graphql_types_from_location_rules' => true,
	);
}

/**
 * Return the flat SEO field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function brounhall_seo_fields() {
	return array(
		array(
			'key'         => 'field_brounhall_seo_meta_title',
			'label'       => 'Meta Title',
			'name'        => 'meta_title',
			'type'        => 'text',
			'instructions' => 'Optional page title override. Leave empty to use the frontend fallback.',
			'maxlength'   => 200,
		),
		array(
			'key'         => 'field_brounhall_seo_meta_description',
			'label'       => 'Meta Description',
			'name'        => 'meta_description',
			'type'        => 'textarea',
			'instructions' => 'Optional plain-text description. Leave empty to use the frontend fallback.',
			'maxlength'   => 500,
			'rows'        => 4,
		),
		array(
			'key'         => 'field_brounhall_seo_canonical_url',
			'label'       => 'Canonical URL Override',
			'name'        => 'canonical_url',
			'type'        => 'url',
			'instructions' => 'Optional public http or https URL override.',
		),
		array(
			'key'          => 'field_brounhall_seo_robots_index',
			'label'        => 'Allow Indexing',
			'name'         => 'robots_index',
			'type'         => 'true_false',
			'instructions' => 'Allow search engines to index this content.',
			'default_value' => 1,
			'ui'           => 1,
		),
		array(
			'key'          => 'field_brounhall_seo_robots_follow',
			'label'        => 'Allow Following Links',
			'name'         => 'robots_follow',
			'type'         => 'true_false',
			'instructions' => 'Allow search engines to follow links on this content.',
			'default_value' => 1,
			'ui'           => 1,
		),
		array(
			'key'       => 'field_brounhall_seo_open_graph_title',
			'label'     => 'Open Graph Title',
			'name'      => 'open_graph_title',
			'type'      => 'text',
			'maxlength' => 200,
		),
		array(
			'key'       => 'field_brounhall_seo_open_graph_description',
			'label'     => 'Open Graph Description',
			'name'      => 'open_graph_description',
			'type'      => 'textarea',
			'maxlength' => 500,
			'rows'      => 4,
		),
		brounhall_acf_image_field(
			'field_brounhall_seo_open_graph_image',
			'open_graph_image',
			'Open Graph Image'
		),
		array(
			'key'       => 'field_brounhall_seo_twitter_title',
			'label'     => 'Twitter Title',
			'name'      => 'twitter_title',
			'type'      => 'text',
			'maxlength' => 200,
		),
		array(
			'key'       => 'field_brounhall_seo_twitter_description',
			'label'     => 'Twitter Description',
			'name'      => 'twitter_description',
			'type'      => 'textarea',
			'maxlength' => 500,
			'rows'      => 4,
		),
		brounhall_acf_image_field(
			'field_brounhall_seo_twitter_image',
			'twitter_image',
			'Twitter Image'
		),
		array(
			'key'       => 'field_brounhall_seo_breadcrumb_title',
			'label'     => 'Breadcrumb Title',
			'name'      => 'breadcrumb_title',
			'type'      => 'text',
			'maxlength' => 200,
		),
	);
}

/**
 * Register SEO for native Pages only at this stage.
 *
 * @return void
 */
function brounhall_register_seo_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field_group = brounhall_seo_field_group( array( 'page' ) );

	if ( ! is_wp_error( $field_group ) ) {
		acf_add_local_field_group( $field_group );
	}
}

/**
 * Reject executable markup in plain-text SEO fields.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_seo_plain_text( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'This field accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}

/**
 * Require canonical overrides to be absolute http/https URLs.
 *
 * @param bool|string $valid Whether the value is valid so far.
 * @param mixed       $value Submitted value.
 * @param array       $field ACF field definition.
 * @param string      $input Input name.
 * @return bool|string
 */
function brounhall_validate_seo_canonical_url( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) ) {
		return __( 'Enter a valid public http or https URL.', 'brounhall-headless' );
	}

	$scheme = wp_parse_url( $value, PHP_URL_SCHEME );
	$host   = wp_parse_url( $value, PHP_URL_HOST );
	$valid_url = filter_var( $value, FILTER_VALIDATE_URL )
		&& in_array( strtolower( (string) $scheme ), array( 'http', 'https' ), true )
		&& ! empty( $host )
		&& '' !== esc_url_raw( $value, array( 'http', 'https' ) );

	return $valid_url ? $valid : __( 'Enter a valid public http or https URL.', 'brounhall-headless' );
}
