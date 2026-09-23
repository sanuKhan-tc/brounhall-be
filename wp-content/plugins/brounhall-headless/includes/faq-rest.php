<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'brounhall/v1', '/faqs', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_faqs', 'permission_callback' => '__return_true' ) );
	register_rest_route( 'brounhall/v1', '/faqs/(?P<slug>[^/]+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_faq', 'permission_callback' => '__return_true' ) );
} );

function brounhall_faq_response( $post ) {
	$data = brounhall_normalize_faq_data( json_decode( get_post_meta( $post->ID, '_brounhall_faq_data', true ), true ) );
	return array(
		'id'         => (int) $post->ID,
		'slug'       => $post->post_name,
		'title'      => get_the_title( $post ),
		'help'       => $data['help'],
		'categories' => $data['categories'],
		'items'      => $data['items'],
	);
}

function brounhall_rest_faqs() {
	$query = new WP_Query( array( 'post_type' => 'bh_faq', 'post_status' => 'publish', 'posts_per_page' => 20, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
	return rest_ensure_response( array( 'items' => array_map( 'brounhall_faq_response', $query->posts ) ) );
}

function brounhall_rest_faq( WP_REST_Request $request ) {
	$slug = (string) $request['slug'];
	if ( ! brounhall_faq_slug_is_valid( $slug ) ) {
		return new WP_Error( 'brounhall_invalid_faq_slug', 'Invalid FAQ slug', array( 'status' => 400 ) );
	}
	$post = get_page_by_path( $slug, OBJECT, 'bh_faq' );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return new WP_Error( 'brounhall_faq_not_found', 'FAQ not found', array( 'status' => 404 ) );
	}
	return rest_ensure_response( brounhall_faq_response( $post ) );
}
