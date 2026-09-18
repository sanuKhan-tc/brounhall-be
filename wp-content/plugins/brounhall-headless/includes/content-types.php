<?php
/**
 * Project content-type registrations.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'brounhall_register_service_post_type' );

/**
 * Register the reusable Service entity. Editors see this as Treatments.
 *
 * @return void
 */
function brounhall_register_service_post_type() {
	register_post_type(
		'service',
		array(
			'labels'             => array(
				'name'               => __( 'Treatments', 'brounhall-headless' ),
				'singular_name'      => __( 'Treatment', 'brounhall-headless' ),
				'add_new'            => __( 'Add Treatment', 'brounhall-headless' ),
				'add_new_item'       => __( 'Add Treatment', 'brounhall-headless' ),
				'edit_item'          => __( 'Edit Treatment', 'brounhall-headless' ),
				'new_item'           => __( 'New Treatment', 'brounhall-headless' ),
				'view_item'          => __( 'View Treatment', 'brounhall-headless' ),
				'search_items'       => __( 'Search Treatments', 'brounhall-headless' ),
				'not_found'          => __( 'No treatments found.', 'brounhall-headless' ),
				'menu_name'          => __( 'Treatments', 'brounhall-headless' ),
			),
			'public'             => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'has_archive'        => false,
			'rewrite'            => array(
				'slug'       => 'services',
				'with_front' => false,
			),
			'query_var'          => true,
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'show_in_graphql'    => true,
			'graphql_single_name' => 'Service',
			'graphql_plural_name' => 'Services',
		)
	);
}
