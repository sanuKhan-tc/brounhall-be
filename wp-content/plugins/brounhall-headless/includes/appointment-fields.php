<?php
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_appointment_data', __( 'Appointment request', 'brounhall-headless' ), 'brounhall_appointment_meta_box', 'bh_appointment', 'normal', 'high' ); } );
add_action( 'save_post_bh_appointment', 'brounhall_save_admin_appointment', 10, 2 );
add_filter( 'manage_bh_appointment_posts_columns', function () { return array( 'cb' => '<input type="checkbox" />', 'title' => 'Request', 'appointment_email' => 'Email', 'appointment_phone' => 'Phone', 'appointment_location' => 'Clinic', 'appointment_service' => 'Treatment', 'date' => 'Submitted' ); } );
add_action( 'manage_bh_appointment_posts_custom_column', function ( $column, $post_id ) {
	$data = json_decode( get_post_meta( $post_id, '_brounhall_appointment_data', true ), true );
	$data = is_array( $data ) ? $data : array();
	$map = array( 'appointment_email' => 'email', 'appointment_phone' => 'phone', 'appointment_location' => 'location', 'appointment_service' => 'service' );
	if ( isset( $map[ $column ] ) ) { echo esc_html( $data[ $map[ $column ] ] ?? '' ); }
}, 10, 2 );

function brounhall_appointment_meta_box( $post ) {
	wp_nonce_field( 'brounhall_save_admin_appointment', 'brounhall_admin_appointment_nonce' );
	$data = json_decode( get_post_meta( $post->ID, '_brounhall_appointment_data', true ), true );
	$data = is_array( $data ) ? $data : array();
	$fields = array( 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'location' => 'Clinic', 'service' => 'Treatment' );
	foreach ( $fields as $key => $label ) { echo '<p><label><strong>' . esc_html( $label ) . '</strong><br /><input class="widefat" type="text" name="brounhall_appointment[' . esc_attr( $key ) . ']" value="' . esc_attr( $data[ $key ] ?? '' ) . '" /></label></p>'; }
	echo '<p><label><strong>Message</strong><br /><textarea class="widefat" rows="6" name="brounhall_appointment[message]">' . esc_textarea( $data['message'] ?? '' ) . '</textarea></label></p>';
	echo '<p class="description">Saving an appointment here stores it only. Email notifications are sent for public appointment submissions.</p>';
}

function brounhall_save_admin_appointment( $post_id, $post ) {
	if ( ! isset( $_POST['brounhall_admin_appointment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_admin_appointment_nonce'] ) ), 'brounhall_save_admin_appointment' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$raw = isset( $_POST['brounhall_appointment'] ) && is_array( $_POST['brounhall_appointment'] ) ? wp_unslash( $_POST['brounhall_appointment'] ) : array();
	$existing = json_decode( get_post_meta( $post_id, '_brounhall_appointment_data', true ), true );
	$existing = is_array( $existing ) ? $existing : array();
	$data = array(
		'name' => brounhall_appointment_text( $raw['name'] ?? '', 100 ),
		'email' => sanitize_email( (string) ( $raw['email'] ?? '' ) ),
		'phone' => brounhall_appointment_text( $raw['phone'] ?? '', 30 ),
		'location' => brounhall_appointment_text( $raw['location'] ?? '', 50 ),
		'service' => brounhall_appointment_text( $raw['service'] ?? '', 100 ),
		'message' => brounhall_appointment_text( $raw['message'] ?? '', 2000 ),
		'consent' => true,
		'submittedAt' => $existing['submittedAt'] ?? current_time( 'mysql' ),
	);
	update_post_meta( $post_id, '_brounhall_appointment_data', wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	static $updating_title = false;
	if ( ! $updating_title && $data['name'] && 0 !== strpos( $post->post_title, 'Appointment:' ) ) {
		$updating_title = true;
		wp_update_post( array( 'ID' => $post_id, 'post_title' => 'Appointment: ' . $data['name'] ) );
		$updating_title = false;
	}
}
