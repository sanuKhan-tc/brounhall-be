<?php
/**
 * Project content-type registrations.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'brounhall_register_service_post_type' );
add_action( 'init', 'brounhall_register_doctor_post_type' );
add_action( 'init', 'brounhall_register_faq_post_type' );

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

/**
 * Register the reusable Doctor entity.
 *
 * @return void
 */
function brounhall_register_doctor_post_type() {
	register_post_type(
		'doctor',
		array(
			'labels'              => array(
				'name'               => __( 'Doctors', 'brounhall-headless' ),
				'singular_name'      => __( 'Doctor', 'brounhall-headless' ),
				'add_new'            => __( 'Add Doctor', 'brounhall-headless' ),
				'add_new_item'       => __( 'Add Doctor', 'brounhall-headless' ),
				'edit_item'          => __( 'Edit Doctor', 'brounhall-headless' ),
				'new_item'           => __( 'New Doctor', 'brounhall-headless' ),
				'view_item'          => __( 'View Doctor', 'brounhall-headless' ),
				'search_items'       => __( 'Search Doctors', 'brounhall-headless' ),
				'not_found'          => __( 'No doctors found.', 'brounhall-headless' ),
				'menu_name'          => __( 'Doctors', 'brounhall-headless' ),
			),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'doctors',
				'with_front' => false,
			),
			'query_var'           => true,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_graphql'     => true,
			'graphql_single_name' => 'Doctor',
			'graphql_plural_name' => 'Doctors',
		)
	);
}

/**
 * Register supporting FAQ items without creating standalone public pages.
 *
 * @return void
 */
function brounhall_register_faq_post_type() {
	register_post_type(
		'faq',
		array(
			'labels'              => array(
				'name'               => __( 'FAQ Items', 'brounhall-headless' ),
				'singular_name'      => __( 'FAQ Item', 'brounhall-headless' ),
				'add_new'            => __( 'Add FAQ Item', 'brounhall-headless' ),
				'add_new_item'       => __( 'Add FAQ Item', 'brounhall-headless' ),
				'edit_item'          => __( 'Edit FAQ Item', 'brounhall-headless' ),
				'new_item'           => __( 'New FAQ Item', 'brounhall-headless' ),
				'view_item'          => __( 'View FAQ Item', 'brounhall-headless' ),
				'search_items'       => __( 'Search FAQ Items', 'brounhall-headless' ),
				'not_found'          => __( 'No FAQ items found.', 'brounhall-headless' ),
				'menu_name'          => __( 'FAQ Items', 'brounhall-headless' ),
			),
			'public'              => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title', 'revisions' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_graphql'     => true,
			'graphql_single_name' => 'Faq',
			'graphql_plural_name' => 'Faqs',
		)
	);
}
