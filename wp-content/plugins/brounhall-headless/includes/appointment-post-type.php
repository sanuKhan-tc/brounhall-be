<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'brounhall_register_appointment_post_type' );
add_action( 'admin_menu', function () {
	if ( ! current_user_can( 'edit_appointments' ) && ! current_user_can( 'edit_complaints' ) ) return;
	$parent = 'brounhall-records';
	add_menu_page( 'Patient Records', 'Patient Records', 'edit_appointments', $parent, function () { wp_safe_redirect( admin_url( 'edit.php?post_type=bh_appointment' ) ); exit; }, 'dashicons-clipboard', 26 );
	if ( current_user_can( 'edit_appointments' ) ) add_submenu_page( $parent, 'Appointments', 'Appointments', 'edit_appointments', 'edit.php?post_type=bh_appointment' );
	if ( current_user_can( 'edit_complaints' ) ) add_submenu_page( $parent, 'Patient Complaints', 'Patient Complaints', 'edit_complaints', 'edit.php?post_type=bh_complaint' );
}, 30 );
add_action( 'init', function () {
	$role = get_role( 'administrator' );
	if ( $role ) {
		foreach ( array( 'edit_appointment', 'read_appointment', 'delete_appointment', 'edit_appointments', 'edit_others_appointments', 'edit_private_appointments', 'edit_published_appointments', 'publish_appointments', 'read_private_appointments', 'delete_appointments', 'delete_private_appointments', 'delete_published_appointments', 'delete_others_appointments' ) as $capability ) {
			$role->add_cap( $capability );
		}
		$role->add_cap( 'brounhall_view_appointment_pii' );
		$role->add_cap( 'brounhall_view_complaint_pii' );
	}
}, 20 );
add_action( 'init', function () { remove_post_type_support( 'bh_appointment', 'editor' ); }, 20 );

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
		'supports' => array(),
		'capability_type' => array( 'appointment', 'appointments' ),
		'map_meta_cap' => true,
	) );
}
