<?php
defined( 'ABSPATH' ) || exit;

function brounhall_record_statuses() { return array( 'new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed' ); }

function brounhall_record_workflow_box( $post ) {
	$status = (string) get_post_meta( $post->ID, '_bh_record_status', true );
	$status = isset( brounhall_record_statuses()[ $status ] ) ? $status : 'new';
	$notes = (string) get_post_meta( $post->ID, '_bh_internal_notes', true );
	wp_nonce_field( 'brounhall_save_record_workflow', 'brounhall_record_workflow_nonce' );
	echo '<p><label for="brounhall_record_status"><strong>Status</strong></label><br /><select id="brounhall_record_status" name="brounhall_record_status">';
	foreach ( brounhall_record_statuses() as $value => $label ) echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
	echo '</select></p>';
	if ( $notes ) echo '<p><strong>Internal notes</strong><br /><pre style="white-space:pre-wrap;max-height:240px;overflow:auto;background:#f6f7f7;padding:8px">' . esc_html( $notes ) . '</pre></p>';
	echo '<p><label for="brounhall_internal_note"><strong>Add internal note</strong></label><br /><textarea id="brounhall_internal_note" name="brounhall_internal_note" class="widefat" rows="4" maxlength="2000" placeholder="Add a private note for the care team"></textarea></p>';
}

function brounhall_save_record_workflow( $post_id, $post ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['brounhall_record_workflow_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_record_workflow_nonce'] ) ), 'brounhall_save_record_workflow' ) ) return;
	$statuses = brounhall_record_statuses();
	$status = sanitize_key( (string) ( $_POST['brounhall_record_status'] ?? 'new' ) );
	if ( ! isset( $statuses[ $status ] ) ) $status = 'new';
	update_post_meta( $post_id, '_bh_record_status', $status );
	$note = sanitize_textarea_field( (string) ( $_POST['brounhall_internal_note'] ?? '' ) );
	if ( '' !== trim( $note ) ) {
		$user = wp_get_current_user();
		$entry = '[' . current_time( 'mysql' ) . '] ' . ( $user->display_name ?: $user->user_login ) . ': ' . trim( $note );
		$history = trim( (string) get_post_meta( $post_id, '_bh_internal_notes', true ) );
		update_post_meta( $post_id, '_bh_internal_notes', substr( $history ? $history . "\n" . $entry : $entry, -20000 ) );
	}
}

add_action( 'add_meta_boxes', function () { add_meta_box( 'brounhall_record_workflow', 'Record workflow', 'brounhall_record_workflow_box', array( 'bh_appointment', 'bh_complaint' ), 'side', 'high' ); } );
add_action( 'save_post_bh_appointment', 'brounhall_save_record_workflow', 20, 2 );
add_action( 'save_post_bh_complaint', 'brounhall_save_record_workflow', 20, 2 );
