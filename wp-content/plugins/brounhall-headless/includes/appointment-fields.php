<?php
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_appointment_data', __( 'Appointment request', 'brounhall-headless' ), 'brounhall_appointment_meta_box', 'bh_appointment', 'normal', 'high' ); } );
add_filter( 'manage_bh_appointment_posts_columns', function () { return array( 'cb' => '<input type="checkbox" />', 'title' => 'Request', 'appointment_email' => 'Email', 'appointment_phone' => 'Phone', 'appointment_location' => 'Clinic', 'appointment_service' => 'Treatment', 'date' => 'Submitted' ); } );
add_action( 'manage_bh_appointment_posts_custom_column', function ( $column, $post_id ) {
	$data = json_decode( get_post_meta( $post_id, '_brounhall_appointment_data', true ), true );
	$data = is_array( $data ) ? $data : array();
	$map = array( 'appointment_email' => 'email', 'appointment_phone' => 'phone', 'appointment_location' => 'location', 'appointment_service' => 'service' );
	if ( isset( $map[ $column ] ) ) { echo esc_html( $data[ $map[ $column ] ] ?? '' ); }
}, 10, 2 );

function brounhall_appointment_meta_box( $post ) {
	$data = json_decode( get_post_meta( $post->ID, '_brounhall_appointment_data', true ), true );
	$data = is_array( $data ) ? $data : array();
	echo '<dl>';
	foreach ( array( 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'location' => 'Clinic', 'service' => 'Treatment', 'message' => 'Message', 'submittedAt' => 'Submitted' ) as $key => $label ) {
		$value = isset( $data[ $key ] ) ? (string) $data[ $key ] : '';
		echo '<dt><strong>' . esc_html( $label ) . '</strong></dt><dd>' . nl2br( esc_html( $value ) ) . '</dd>';
	}
	echo '</dl>';
}
