<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'brounhall_register_clinic_post_type' );

function brounhall_register_clinic_post_type() {
	register_post_type(
		'bh_clinic',
		array(
			'labels' => array(
				'name'          => __( 'Clinics', 'brounhall-headless' ),
				'singular_name' => __( 'Clinic', 'brounhall-headless' ),
				'add_new_item'   => __( 'Add Clinic', 'brounhall-headless' ),
				'edit_item'      => __( 'Edit Clinic', 'brounhall-headless' ),
				'menu_name'     => __( 'Clinics', 'brounhall-headless' ),
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
