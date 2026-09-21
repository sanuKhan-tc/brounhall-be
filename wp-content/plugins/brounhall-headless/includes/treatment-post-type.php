<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'brounhall_register_treatment_post_type' );

function brounhall_register_treatment_post_type() {
	register_post_type(
		'bh_treatment',
		array(
			'labels' => array(
				'name'          => __( 'Treatments', 'brounhall-headless' ),
				'singular_name' => __( 'Treatment', 'brounhall-headless' ),
				'add_new_item'   => __( 'Add Treatment', 'brounhall-headless' ),
				'edit_item'      => __( 'Edit Treatment', 'brounhall-headless' ),
				'menu_name'     => __( 'Treatments', 'brounhall-headless' ),
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
