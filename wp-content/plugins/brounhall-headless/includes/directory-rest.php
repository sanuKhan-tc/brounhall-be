<?php
/**
 * Public read-only adapters for migrated directory content.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		register_rest_route( 'brounhall/v1', '/doctors', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_doctors', 'permission_callback' => '__return_true' ) );
		register_rest_route( 'brounhall/v1', '/doctors/(?P<slug>[a-z0-9]+(?:-[a-z0-9]+)*)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_doctor', 'permission_callback' => '__return_true' ) );
		register_rest_route( 'brounhall/v1', '/clinics', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_clinics', 'permission_callback' => '__return_true' ) );
		register_rest_route( 'brounhall/v1', '/clinics/(?P<slug>[a-z0-9]+(?:-[a-z0-9]+)*)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_clinic', 'permission_callback' => '__return_true' ) );
		register_rest_route( 'brounhall/v1', '/faqs/faq', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'brounhall_rest_faq', 'permission_callback' => '__return_true' ) );
	}
);

function brounhall_rest_image( $id, $alt = '' ) {
	$id = absint( $id );
	return array(
		'imageId' => $id,
		'src'     => $id ? (string) wp_get_attachment_image_url( $id, 'full' ) : '',
		'alt'     => $alt ?: (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
	);
}

function brounhall_rest_doctor_data( WP_Post $post ) {
	$data = json_decode( (string) get_post_meta( $post->ID, '_brounhall_entity_data', true ), true );
	if ( ! is_array( $data ) ) $data = json_decode( (string) get_post_meta( $post->ID, '_brounhall_legacy_data', true ), true );
	$data = is_array( $data ) ? $data : array();
	$location_id = absint( get_post_meta( $post->ID, 'doctor_location', true ) );
	return array(
		'id'             => (int) $post->ID,
		'slug'           => $post->post_name,
		'type'           => 'embryologist' === ( $data['type'] ?? '' ) ? 'embryologist' : 'doctor',
		'name'           => get_the_title( $post ),
		'role'           => (string) ( $data['role'] ?? get_post_meta( $post->ID, 'doctor_role', true ) ),
		'clinic'         => (string) ( $data['clinic'] ?? ( $location_id ? get_post_meta( $location_id, 'location_name', true ) : '' ) ),
		'headline'       => (string) ( $data['headline'] ?? get_post_meta( $post->ID, 'doctor_headline', true ) ),
		'specialty'      => (string) ( $data['specialty'] ?? get_post_meta( $post->ID, 'doctor_specialty', true ) ),
		'image'          => brounhall_rest_image( $data['imageId'] ?? get_post_meta( $post->ID, 'doctor_imageid', true ), $data['imageAlt'] ?? get_post_meta( $post->ID, 'doctor_imagealt', true ) ),
		'nationality'    => (string) ( $data['nationality'] ?? get_post_meta( $post->ID, 'doctor_nationality', true ) ),
		'languages'      => (string) ( $data['languages'] ?? get_post_meta( $post->ID, 'doctor_languages', true ) ),
		'areasOfInterest' => (string) ( $data['areasOfInterest'] ?? get_post_meta( $post->ID, 'doctor_areasofinterest', true ) ),
		'education'      => (string) ( $data['education'] ?? get_post_meta( $post->ID, 'doctor_education', true ) ),
		'bio'            => (string) ( $data['bio'] ?? $post->post_content ),
	);
}

function brounhall_rest_doctors( $request = null ) {
	$query = new WP_Query( array( 'post_type' => 'doctor', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
	$items = array_map( function ( $post ) { $item = brounhall_rest_doctor_data( $post ); return array( 'id' => $item['id'], 'slug' => $item['slug'], 'type' => $item['type'], 'name' => $item['name'], 'role' => $item['role'], 'clinic' => $item['clinic'] ); }, $query->posts );
	$type = $request instanceof WP_REST_Request ? (string) $request->get_param( 'type' ) : '';
	if ( in_array( $type, array( 'doctor', 'embryologist' ), true ) ) {
		$items = array_values( array_filter( $items, function ( $item ) use ( $type ) { return $item['type'] === $type; } ) );
	}
	return rest_ensure_response( array( 'items' => $items ) );
}

function brounhall_rest_doctor( WP_REST_Request $request ) {
	$post = get_page_by_path( (string) $request['slug'], OBJECT, 'doctor' );
	if ( ! $post || 'publish' !== $post->post_status ) return new WP_Error( 'brounhall_doctor_not_found', 'Doctor not found', array( 'status' => 404 ) );
	return rest_ensure_response( brounhall_rest_doctor_data( $post ) );
}

function brounhall_rest_clinic_data( WP_Post $post ) {
	$data = json_decode( (string) get_post_meta( $post->ID, '_brounhall_entity_data', true ), true );
	if ( ! is_array( $data ) ) $data = json_decode( (string) get_post_meta( $post->ID, '_brounhall_legacy_data', true ), true );
	$data = is_array( $data ) ? $data : array();
	$name = (string) ( get_post_meta( $post->ID, 'location_name', true ) ?: $post->post_title );
	return array(
		'id'          => (int) $post->ID,
		'slug'        => $post->post_name,
		'name'        => $name,
		'title'       => $name,
		'address'     => (string) ( $data['address'] ?? get_post_meta( $post->ID, 'location_address', true ) ),
		'phone'       => (string) ( $data['phone'] ?? get_post_meta( $post->ID, 'location_phone', true ) ),
		'hours'       => (string) ( $data['hours'] ?? get_post_meta( $post->ID, 'location_hours', true ) ),
		'description' => (string) ( $data['description'] ?? $post->post_excerpt ),
		'image'       => brounhall_rest_image( $data['imageId'] ?? get_post_meta( $post->ID, 'location_image_id', true ), $data['imageAlt'] ?? '' ),
	);
}

function brounhall_rest_clinics() {
	$query = new WP_Query( array( 'post_type' => 'location', 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
	return rest_ensure_response( array( 'items' => array_map( function ( $post ) { return brounhall_rest_clinic_data( $post ); }, $query->posts ) ) );
}

function brounhall_rest_clinic( WP_REST_Request $request ) {
	$post = get_page_by_path( (string) $request['slug'], OBJECT, 'location' );
	if ( ! $post || 'publish' !== $post->post_status ) return new WP_Error( 'brounhall_clinic_not_found', 'Clinic not found', array( 'status' => 404 ) );
	return rest_ensure_response( brounhall_rest_clinic_data( $post ) );
}

function brounhall_rest_faq() {
	$query = new WP_Query( array( 'post_type' => 'faq', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
	$items = array_map( function ( $post ) { return array( 'question' => get_the_title( $post ), 'answer' => (string) get_post_meta( $post->ID, 'faq_answer', true ) ); }, $query->posts );
	return rest_ensure_response( array( 'id' => $query->posts ? (int) $query->posts[0]->ID : 1, 'slug' => 'faq', 'title' => 'Frequently Asked Questions', 'help' => array( 'body' => '', 'cta' => array( 'label' => '', 'href' => '' ) ), 'categories' => array(), 'items' => $items ) );
}
