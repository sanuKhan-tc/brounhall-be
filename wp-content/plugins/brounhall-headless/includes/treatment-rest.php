<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'brounhall/v1', '/treatments', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_treatments', 'permission_callback' => '__return_true' ) );
	register_rest_route( 'brounhall/v1', '/treatments/(?P<slug>[^/]+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_treatment', 'permission_callback' => '__return_true' ) );
} );

function brounhall_rest_treatments() {
	$query = new WP_Query( array( 'post_type' => 'bh_treatment', 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
	$items = array();
	foreach ( $query->posts as $post ) { $items[] = array( 'slug' => $post->post_name, 'title' => get_the_title( $post ) ); }
	return rest_ensure_response( array( 'items' => $items ) );
}

function brounhall_rest_treatment( WP_REST_Request $request ) {
	$slug = (string) $request['slug'];
	if ( ! brounhall_treatment_slug_is_valid( $slug ) ) { return new WP_Error( 'brounhall_invalid_treatment_slug', 'Invalid treatment slug', array( 'status' => 400 ) ); }
	$post = get_page_by_path( $slug, OBJECT, 'bh_treatment' );
	if ( ! $post || 'publish' !== $post->post_status ) { return new WP_Error( 'brounhall_treatment_not_found', 'Treatment not found', array( 'status' => 404 ) ); }
	$data = json_decode( get_post_meta( $post->ID, '_brounhall_treatment_data', true ), true );
	$data = brounhall_normalize_treatment_data( $data );
	$data['id'] = (int) $post->ID;
	$data['slug'] = $post->post_name;
	$data['title'] = get_the_title( $post );
	if ( ! empty( $data['hero']['media']['imageId'] ) ) {
		$media = brounhall_media_response( $data['hero']['media']['imageId'], $data['hero']['media']['alt'] ?? '' );
		if ( $media ) { $data['hero']['media'] = array_merge( $data['hero']['media'], $media ); }
	}
	foreach ( $data['relatedTreatments']['items'] ?? array() as $index => $item ) {
		$image_id = $item['media']['imageId'] ?? 0;
		$media = brounhall_media_response( $image_id, $item['media']['alt'] ?? '' );
		if ( $media ) { $data['relatedTreatments']['items'][ $index ]['media'] = array_merge( $item['media'], $media ); }
	}
	return rest_ensure_response( array_merge( array( 'id' => $data['id'], 'slug' => $data['slug'], 'title' => $data['title'] ), $data ) );
}
