<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'brounhall_register_appointment_post_type' );

function brounhall_register_appointment_post_type() {
	register_post_type( 'bh_appointment', array(
		'labels' => array( 'name' => __( 'Appointments', 'brounhall-headless' ), 'singular_name' => __( 'Appointment', 'brounhall-headless' ), 'menu_name' => __( 'Appointments', 'brounhall-headless' ), 'add_new' => __( 'Create Appointment', 'brounhall-headless' ), 'add_new_item' => __( 'Create Appointment', 'brounhall-headless' ), 'new_item' => __( 'New Appointment', 'brounhall-headless' ) ),
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true,
		'show_in_rest' => false,
		'rewrite' => false,
		'query_var' => false,
		'supports' => array( 'title' ),
		'capability_type' => 'post',
		'map_meta_cap' => true,
	) );
}
