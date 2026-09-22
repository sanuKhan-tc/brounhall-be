<?php
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_doctor_data', __( 'Doctor content', 'brounhall-headless' ), 'brounhall_doctor_meta_box', 'bh_doctor', 'normal' ); } );
add_action( 'save_post_bh_doctor', 'brounhall_save_doctor_data', 10, 2 );

function brounhall_doctor_meta_box( $post ) {
	wp_nonce_field( 'brounhall_save_doctor', 'brounhall_doctor_nonce' );
	$value = get_post_meta( $post->ID, '_brounhall_doctor_data', true );
	$value = $value ? wp_json_encode( json_decode( $value, true ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : "{\n  \"type\": \"doctor\",\n  \"role\": \"\",\n  \"headline\": \"\",\n  \"specialty\": \"\",\n  \"clinic\": \"Dubai\",\n  \"imageId\": 0,\n  \"imageAlt\": \"\",\n  \"nationality\": \"\",\n  \"languages\": \"\",\n  \"areasOfInterest\": \"\",\n  \"education\": \"\",\n  \"bio\": \"\"\n}";
	echo '<p>' . esc_html__( 'Enter the controlled doctor schema as JSON. HTML is rejected; clinic must be Dubai, Abu Dhabi, or Al Ain.', 'brounhall-headless' ) . '</p><textarea name="brounhall_doctor_data" rows="25" style="width:100%;font-family:monospace">' . esc_textarea( $value ) . '</textarea>';
}

function brounhall_save_doctor_data( $post_id, $post ) {
	if ( ! isset( $_POST['brounhall_doctor_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_doctor_nonce'] ) ), 'brounhall_save_doctor' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$raw = isset( $_POST['brounhall_doctor_data'] ) ? wp_unslash( $_POST['brounhall_doctor_data'] ) : '';
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || false !== strpos( $raw, '<' ) ) { return; }
	update_post_meta( $post_id, '_brounhall_doctor_data', wp_json_encode( brounhall_normalize_doctor_data( $data ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}
