<?php
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'brounhall_faq_data', __( 'FAQ page content', 'brounhall-headless' ), 'brounhall_render_faq_data_box', 'bh_faq', 'normal', 'default' );
} );

function brounhall_render_faq_data_box( $post ) {
	wp_nonce_field( 'brounhall_save_faq_data', 'brounhall_faq_nonce' );
	$data = get_post_meta( $post->ID, '_brounhall_faq_data', true );
	echo '<p>' . esc_html__( 'Enter validated JSON containing help, categories, and question-and-answer items. HTML and scripts are not supported.', 'brounhall-headless' ) . '</p>';
	echo '<textarea name="brounhall_faq_data" rows="28" style="width:100%;font-family:monospace">' . esc_textarea( $data ) . '</textarea>';
}

add_action( 'save_post_bh_faq', 'brounhall_save_faq_data', 10, 2 );

function brounhall_save_faq_data( $post_id, $post ) {
	if ( ! isset( $_POST['brounhall_faq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_faq_nonce'] ) ), 'brounhall_save_faq_data' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$raw  = isset( $_POST['brounhall_faq_data'] ) ? wp_unslash( $_POST['brounhall_faq_data'] ) : '';
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || false !== strpos( $raw, '<' ) ) {
		return;
	}
	update_post_meta( $post_id, '_brounhall_faq_data', wp_json_encode( brounhall_normalize_faq_data( $data ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}
