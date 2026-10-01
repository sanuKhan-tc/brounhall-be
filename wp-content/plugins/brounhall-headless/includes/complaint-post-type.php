<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'bh_complaint', array(
		'labels' => array( 'name' => 'Patient Complaints', 'singular_name' => 'Patient Complaint', 'menu_name' => 'Patient Complaints' ),
		'public' => false, 'publicly_queryable' => false, 'show_ui' => true, 'show_in_menu' => false, 'show_in_rest' => false,
		'rewrite' => false, 'query_var' => false, 'supports' => array(), 'capability_type' => array( 'complaint', 'complaints' ), 'map_meta_cap' => true,
	) );
	$role = get_role( 'administrator' );
	if ( $role ) foreach ( array( 'edit_complaint', 'read_complaint', 'delete_complaint', 'edit_complaints', 'edit_others_complaints', 'edit_private_complaints', 'publish_complaints', 'read_private_complaints', 'delete_complaints', 'delete_private_complaints', 'delete_others_complaints' ) as $capability ) $role->add_cap( $capability );
} );
add_action( 'init', function () { remove_post_type_support( 'bh_complaint', 'editor' ); }, 20 );

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_complaint_data', 'Patient Complaint', 'brounhall_complaint_meta_box', 'bh_complaint', 'normal', 'high' ); } );
add_filter( 'manage_bh_complaint_posts_columns', function () { return array( 'cb' => '<input type="checkbox" />', 'title' => 'Reference', 'status' => 'Status', 'date' => 'Submitted' ); } );
add_action( 'manage_bh_complaint_posts_custom_column', function ( $column, $post_id ) { if ( 'status' === $column ) { $status = get_post_meta( $post_id, '_bh_record_status', true ); echo esc_html( brounhall_record_statuses()[ $status ] ?? 'New' ); } }, 10, 2 );
function brounhall_complaint_data( $post_id ) {
	if ( ! current_user_can( 'brounhall_view_complaint_pii' ) ) return new WP_Error( 'brounhall_complaint_forbidden', 'Sensitive complaint data is restricted' );
	try { return brounhall_appointment_decrypt( get_post_meta( $post_id, '_bh_complaint_ref', true ), brounhall_appointment_read_envelope( $post_id ) ); } catch ( Throwable $error ) { return new WP_Error( 'brounhall_complaint_decrypt_failed', 'Sensitive complaint data could not be decrypted' ); }
}
function brounhall_complaint_meta_box( $post ) {
	brounhall_form_storage_admin_warning();
	if ( ! brounhall_pii_access_granted( $post->ID ) ) { brounhall_pii_access_prompt( $post->ID ); return; }
	$data = brounhall_complaint_data( $post->ID );
	if ( is_wp_error( $data ) ) { echo '<p>' . esc_html( $data->get_error_message() ) . '</p>'; return; }
	foreach ( array( 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone' ) as $key => $label ) echo '<p><strong>' . esc_html( $label ) . '</strong><br />' . esc_html( $data[ $key ] ?? '' ) . '</p>';
	echo '<p><strong>Message</strong><br />' . nl2br( esc_html( $data['message'] ?? '' ) ) . '</p>';
}
