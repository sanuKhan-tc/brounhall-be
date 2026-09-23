<?php
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_clinic_data', __( 'Clinic content', 'brounhall-headless' ), 'brounhall_clinic_meta_box', 'bh_clinic', 'normal' ); } );
add_action( 'save_post_bh_clinic', 'brounhall_save_clinic_data', 10, 2 );

function brounhall_clinic_meta_box( $post ) {
	wp_nonce_field( 'brounhall_save_clinic', 'brounhall_clinic_nonce' );
	$value = get_post_meta( $post->ID, '_brounhall_clinic_data', true );
	$value = $value ? wp_json_encode( json_decode( $value, true ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : "{\n  \"title\": \"\",\n  \"address\": \"\",\n  \"phone\": \"\",\n  \"hours\": \"\",\n  \"description\": \"\",\n  \"imageId\": 0,\n  \"imageAlt\": \"\"\n}";
	echo '<p>' . esc_html__( 'Enter the controlled clinic schema as JSON. HTML and executable content are not allowed.', 'brounhall-headless' ) . '</p><textarea name="brounhall_clinic_data" rows="18" style="width:100%;font-family:monospace">' . esc_textarea( $value ) . '</textarea>';
}

function brounhall_save_clinic_data( $post_id, $post ) {
	if ( ! isset( $_POST['brounhall_clinic_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_clinic_nonce'] ) ), 'brounhall_save_clinic' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$raw = isset( $_POST['brounhall_clinic_data'] ) ? wp_unslash( $_POST['brounhall_clinic_data'] ) : '';
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || false !== strpos( $raw, '<' ) ) { return; }
	update_post_meta( $post_id, '_brounhall_clinic_data', wp_json_encode( brounhall_normalize_clinic_data( $data ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}
