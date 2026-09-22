<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'brounhall_register_appointment_post_type' );
add_action( 'admin_menu', function () {
	if ( current_user_can( 'manage_options' ) ) {
		add_menu_page( __( 'Appointments', 'brounhall-headless' ), __( 'Appointments', 'brounhall-headless' ), 'manage_options', 'edit.php?post_type=bh_appointment', '', 'dashicons-calendar-alt', 26 );
	}
}, 30 );
add_action( 'init', function () {
	$role = get_role( 'administrator' );
	if ( $role ) { $role->add_cap( 'brounhall_view_appointment_pii' ); }
}, 20 );

function brounhall_register_appointment_post_type() {
	register_post_type( 'bh_appointment', array(
		'labels' => array( 'name' => __( 'Appointments', 'brounhall-headless' ), 'singular_name' => __( 'Appointment', 'brounhall-headless' ), 'menu_name' => __( 'Appointments', 'brounhall-headless' ), 'add_new' => __( 'Create Appointment', 'brounhall-headless' ), 'add_new_item' => __( 'Create Appointment', 'brounhall-headless' ), 'new_item' => __( 'New Appointment', 'brounhall-headless' ) ),
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true,
		'show_in_menu' => false,
		'show_in_rest' => false,
		'rewrite' => false,
		'query_var' => false,
		'supports' => array( 'title' ),
		'capability_type' => 'appointment',
		'capabilities' => array(
			'edit_post' => 'manage_options', 'read_post' => 'manage_options', 'delete_post' => 'manage_options',
			'edit_posts' => 'manage_options', 'edit_others_posts' => 'manage_options', 'publish_posts' => 'manage_options',
			'read_private_posts' => 'manage_options', 'delete_posts' => 'manage_options', 'delete_private_posts' => 'manage_options',
			'delete_published_posts' => 'manage_options', 'delete_others_posts' => 'manage_options',
		),
		'map_meta_cap' => true,
	) );
}
