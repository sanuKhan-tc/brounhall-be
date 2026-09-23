<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'brounhall_register_faq_post_type' );

function brounhall_register_faq_post_type() {
	register_post_type(
		'bh_faq',
		array(
			'labels' => array(
				'name'          => __( 'FAQs', 'brounhall-headless' ),
				'singular_name' => __( 'FAQ', 'brounhall-headless' ),
				'add_new_item'   => __( 'Add FAQ page', 'brounhall-headless' ),
				'edit_item'      => __( 'Edit FAQ page', 'brounhall-headless' ),
				'menu_name'     => __( 'FAQs', 'brounhall-headless' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => false,
			'rewrite'            => false,
			'query_var'          => false,
			'supports'           => array( 'title', 'page-attributes', 'revisions' ),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		)
	);
}
