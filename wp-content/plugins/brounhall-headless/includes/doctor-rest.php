<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'brounhall/v1', '/doctors', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_doctors', 'permission_callback' => '__return_true' ) );
	register_rest_route( 'brounhall/v1', '/doctors/(?P<slug>[^/]+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_doctor', 'permission_callback' => '__return_true' ) );
} );

function brounhall_doctor_response( $post, $full = true ) {
	$data = json_decode( get_post_meta( $post->ID, '_brounhall_doctor_data', true ), true );
	$data = brounhall_normalize_doctor_data( $data );
	$response = array( 'id' => (int) $post->ID, 'slug' => $post->post_name, 'name' => get_the_title( $post ), 'type' => $data['type'], 'role' => $data['role'], 'clinic' => $data['clinic'] );
	if ( ! $full ) { return $response; }
	$media = brounhall_media_response( $data['imageId'], $data['imageAlt'] );
	$image = array( 'imageId' => $data['imageId'], 'alt' => $data['imageAlt'] );
	if ( $media ) { $image = array_merge( $image, $media ); }
	return array_merge( $response, array( 'headline' => $data['headline'], 'specialty' => $data['specialty'], 'image' => $image, 'nationality' => $data['nationality'], 'languages' => $data['languages'], 'areasOfInterest' => $data['areasOfInterest'], 'education' => $data['education'], 'bio' => $data['bio'] ) );
}

function brounhall_rest_doctors() {
	$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
	if ( $type && ! brounhall_doctor_type_is_valid( $type ) ) { return new WP_Error( 'brounhall_invalid_doctor_type', 'Invalid doctor type', array( 'status' => 400 ) ); }
	$meta_query = $type ? array( array( 'key' => '_brounhall_doctor_data', 'value' => '"type":"' . $type . '"', 'compare' => 'LIKE' ) ) : array();
	$query = new WP_Query( array( 'post_type' => 'bh_doctor', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true, 'meta_query' => $meta_query ) );
	return rest_ensure_response( array( 'items' => array_map( function ( $post ) { return brounhall_doctor_response( $post, false ); }, $query->posts ) ) );
}

function brounhall_rest_doctor( WP_REST_Request $request ) {
	$slug = (string) $request['slug'];
	if ( ! brounhall_doctor_slug_is_valid( $slug ) ) { return new WP_Error( 'brounhall_invalid_doctor_slug', 'Invalid doctor slug', array( 'status' => 400 ) ); }
	$post = get_page_by_path( $slug, OBJECT, 'bh_doctor' );
	if ( ! $post || 'publish' !== $post->post_status ) { return new WP_Error( 'brounhall_doctor_not_found', 'Doctor not found', array( 'status' => 404 ) ); }
	return rest_ensure_response( brounhall_doctor_response( $post ) );
}
