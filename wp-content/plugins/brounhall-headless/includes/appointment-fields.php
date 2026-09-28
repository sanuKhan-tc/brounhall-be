<?php
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_appointment_data', __( 'Appointment request', 'brounhall-headless' ), 'brounhall_appointment_meta_box', 'bh_appointment', 'normal', 'high' ); } );
add_filter( 'manage_bh_appointment_posts_columns', function () { return array( 'cb' => '<input type="checkbox" />', 'title' => 'Reference', 'status' => 'Status', 'date' => 'Submitted' ); } );
add_action( 'manage_bh_appointment_posts_custom_column', function ( $column, $post_id ) { if ( 'status' === $column ) { $status = get_post_meta( $post_id, '_bh_record_status', true ); echo esc_html( brounhall_record_statuses()[ $status ] ?? 'New' ); } }, 10, 2 );

function brounhall_appointment_meta_box( $post ) {
	if ( ! brounhall_pii_access_granted( $post->ID ) ) { brounhall_pii_access_prompt( $post->ID ); return; }
	$data = brounhall_appointment_get_data( $post->ID, true );
	if ( is_wp_error( $data ) ) { echo '<p>' . esc_html( $data->get_error_message() ) . '</p>'; return; }
	$fields = array( 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'location' => 'Clinic', 'service' => 'Treatment', 'message' => 'Message' );
	foreach ( $fields as $key => $label ) echo '<p><strong>' . esc_html( $label ) . '</strong><br />' . nl2br( esc_html( $data[ $key ] ?? '' ) ) . '</p>';
}
