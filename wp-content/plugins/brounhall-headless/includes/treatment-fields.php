<?php
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_treatment_data', __( 'Treatment content', 'brounhall-headless' ), 'brounhall_treatment_meta_box', 'bh_treatment', 'normal' ); } );
add_action( 'save_post_bh_treatment', 'brounhall_save_treatment_data', 10, 2 );

function brounhall_treatment_meta_box( $post ) {
	wp_nonce_field( 'brounhall_save_treatment', 'brounhall_treatment_nonce' );
	$value = get_post_meta( $post->ID, '_brounhall_treatment_data', true );
	$value = $value ? wp_json_encode( json_decode( $value, true ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : "{\n  \"template\": \"treatment-detail\",\n  \"hero\": {},\n  \"navigation\": [],\n  \"sections\": [],\n  \"relatedTreatments\": {\"items\": []}\n}";
	echo '<p>' . esc_html__( 'Enter the controlled treatment schema as JSON. HTML and executable URLs are rejected.', 'brounhall-headless' ) . '</p><textarea name="brounhall_treatment_data" rows="28" style="width:100%;font-family:monospace">' . esc_textarea( $value ) . '</textarea>';
}

function brounhall_save_treatment_data( $post_id, $post ) {
	if ( ! isset( $_POST['brounhall_treatment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_treatment_nonce'] ) ), 'brounhall_save_treatment' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$raw = isset( $_POST['brounhall_treatment_data'] ) ? wp_unslash( $_POST['brounhall_treatment_data'] ) : '';
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || false !== strpos( $raw, '<' ) ) { return; }
	update_post_meta( $post_id, '_brounhall_treatment_data', wp_json_encode( brounhall_normalize_treatment_data( $data ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}
