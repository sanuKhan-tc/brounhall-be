<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'brounhall/v1', '/clinics', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_clinics', 'permission_callback' => '__return_true' ) );
	register_rest_route( 'brounhall/v1', '/clinics/(?P<slug>[^/]+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_clinic', 'permission_callback' => '__return_true' ) );
} );

function brounhall_clinic_response( $post, $full = true ) {
	$data = brounhall_normalize_clinic_data( json_decode( get_post_meta( $post->ID, '_brounhall_clinic_data', true ), true ) );
	$response = array( 'id' => (int) $post->ID, 'slug' => $post->post_name, 'name' => get_the_title( $post ) );
	if ( ! $full ) { return $response; }
	$image = array( 'imageId' => $data['imageId'], 'alt' => $data['imageAlt'] );
	$media = brounhall_media_response( $data['imageId'], $data['imageAlt'] );
	if ( $media ) { $image = array_merge( $image, $media ); }
	return array_merge( $response, array(
		'title'       => $data['title'] ? $data['title'] : get_the_title( $post ),
		'address'     => $data['address'],
		'phone'       => $data['phone'],
		'hours'       => $data['hours'],
		'description' => $data['description'],
		'image'       => $image,
	) );
}

function brounhall_rest_clinics() {
	$query = new WP_Query( array( 'post_type' => 'bh_clinic', 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
	return rest_ensure_response( array( 'items' => array_map( function ( $post ) { return brounhall_clinic_response( $post, false ); }, $query->posts ) ) );
}

function brounhall_rest_clinic( WP_REST_Request $request ) {
	$slug = (string) $request['slug'];
	if ( ! brounhall_clinic_slug_is_valid( $slug ) ) { return new WP_Error( 'brounhall_invalid_clinic_slug', 'Invalid clinic slug', array( 'status' => 400 ) ); }
	$post = get_page_by_path( $slug, OBJECT, 'bh_clinic' );
	if ( ! $post || 'publish' !== $post->post_status ) { return new WP_Error( 'brounhall_clinic_not_found', 'Clinic not found', array( 'status' => 404 ) ); }
	return rest_ensure_response( brounhall_clinic_response( $post ) );
}
