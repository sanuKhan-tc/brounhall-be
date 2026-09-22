<?php
defined( 'ABSPATH' ) || exit;

function brounhall_media_response( $attachment_id, $alt = '' ) {
	$attachment_id = absint( $attachment_id );
	$aliases       = get_option( 'brounhall_media_aliases', array() );
	if ( isset( $aliases[ $attachment_id ] ) ) {
		$attachment_id = absint( $aliases[ $attachment_id ] );
	}
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
		return null;
	}
	$url = wp_get_attachment_image_src( $attachment_id, 'full' );
	if ( ! is_array( $url ) || empty( $url[0] ) ) {
		return null;
	}
	$mime = get_post_mime_type( $attachment_id );
	if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif' ), true ) ) {
		return null;
	}
	$stored_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	return array(
		'id'     => $attachment_id,
		'src'    => esc_url_raw( $url[0] ),
		'alt'    => sanitize_text_field( $alt ? $alt : $stored_alt ),
		'width'  => absint( $url[1] ),
		'height' => absint( $url[2] ),
		'mime'   => $mime,
	);
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'brounhall/v1', '/media/(?P<id>[0-9]+)', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'brounhall_rest_media',
		'permission_callback' => '__return_true',
	) );
} );

function brounhall_rest_media( WP_REST_Request $request ) {
	$media = brounhall_media_response( absint( $request['id'] ) );
	if ( ! $media ) {
		return new WP_Error( 'brounhall_media_not_found', 'Media not found', array( 'status' => 404 ) );
	}
	return rest_ensure_response( $media );
}
