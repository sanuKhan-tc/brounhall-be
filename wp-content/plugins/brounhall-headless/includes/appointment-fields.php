<?php
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_appointment_data', __( 'Appointment request', 'brounhall-headless' ), 'brounhall_appointment_meta_box', 'bh_appointment', 'normal', 'high' ); } );
add_action( 'save_post_bh_appointment', 'brounhall_save_admin_appointment', 10, 2 );
add_filter( 'manage_bh_appointment_posts_columns', function () { return array( 'cb' => '<input type="checkbox" />', 'title' => 'Request', 'appointment_email' => 'Email', 'appointment_phone' => 'Phone', 'appointment_location' => 'Clinic', 'appointment_service' => 'Treatment', 'date' => 'Submitted' ); } );
add_action( 'manage_bh_appointment_posts_custom_column', function ( $column, $post_id ) {
	$data = brounhall_appointment_get_data( $post_id, true );
	$data = is_wp_error( $data ) ? array() : $data;
	$map = array( 'appointment_email' => 'email', 'appointment_phone' => 'phone', 'appointment_location' => 'location', 'appointment_service' => 'service' );
	if ( isset( $map[ $column ] ) ) { echo esc_html( $data[ $map[ $column ] ] ?? '' ); }
}, 10, 2 );

function brounhall_appointment_meta_box( $post ) {
	wp_nonce_field( 'brounhall_save_admin_appointment', 'brounhall_admin_appointment_nonce' );
	$data = brounhall_appointment_get_data( $post->ID, true );
	if ( is_wp_error( $data ) ) { echo '<p>' . esc_html( $data->get_error_message() ) . '</p>'; return; }
	$fields = array( 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'location' => 'Clinic', 'service' => 'Treatment' );
	foreach ( $fields as $key => $label ) { echo '<p><label><strong>' . esc_html( $label ) . '</strong><br /><input class="widefat" type="text" name="brounhall_appointment[' . esc_attr( $key ) . ']" value="' . esc_attr( $data[ $key ] ?? '' ) . '" /></label></p>'; }
	echo '<p><label><strong>Message</strong><br /><textarea class="widefat" rows="6" name="brounhall_appointment[message]">' . esc_textarea( $data['message'] ?? '' ) . '</textarea></label></p>';
	echo '<p class="description">Saving an appointment here stores it only. Email notifications are sent for public appointment submissions.</p>';
}

function brounhall_save_admin_appointment( $post_id, $post ) {
	if ( ! isset( $_POST['brounhall_admin_appointment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_admin_appointment_nonce'] ) ), 'brounhall_save_admin_appointment' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$raw = isset( $_POST['brounhall_appointment'] ) && is_array( $_POST['brounhall_appointment'] ) ? wp_unslash( $_POST['brounhall_appointment'] ) : array();
	$existing = brounhall_appointment_get_data( $post_id, true );
	if ( is_wp_error( $existing ) ) { return; }
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
	$reference = (string) get_post_meta( $post_id, '_bh_appointment_ref', true );
	if ( ! $reference ) { $reference = brounhall_appointment_reference(); }
	try { $envelope = brounhall_appointment_envelope( $reference, $data ); } catch ( Throwable $error ) { return; }
	foreach ( array( '_bh_appointment_ref' => $reference, '_bh_pii_ciphertext' => $envelope['ciphertext'], '_bh_pii_nonce' => $envelope['nonce'], '_bh_pii_salt' => $envelope['salt'], '_bh_wrapped_dek' => $envelope['wrapped_dek'], '_bh_crypto_algorithm' => $envelope['algorithm'], '_bh_crypto_version' => $envelope['crypto_version'], '_bh_kek_id' => $envelope['kek_id'], '_bh_email_blind_index' => $envelope['email_index'], '_bh_email_index_version' => $envelope['email_index_version'], '_bh_phone_blind_index' => $envelope['phone_index'], '_bh_phone_index_version' => $envelope['phone_index_version'], '_bh_migration_version' => 1 ) as $key => $value ) { update_post_meta( $post_id, $key, $value ); }
	delete_post_meta( $post_id, '_brounhall_appointment_data' );
	if ( $post->post_title !== $reference ) { wp_update_post( array( 'ID' => $post_id, 'post_title' => $reference ) ); }
}
