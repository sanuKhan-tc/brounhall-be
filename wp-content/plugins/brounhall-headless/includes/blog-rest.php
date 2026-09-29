<?php
/** Public read-only blog endpoints backed by native WordPress posts. */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'brounhall/v1',
			'/blogs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'brounhall_rest_blogs',
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'brounhall/v1',
			'/blogs/(?P<slug>[a-z0-9]+(?:-[a-z0-9]+)*)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'brounhall_rest_blog',
				'permission_callback' => '__return_true',
			)
		);
	}
);

function brounhall_blog_data( WP_Post $post ) {
	$data = json_decode( (string) get_post_meta( $post->ID, '_brounhall_blog_data', true ), true );
	return is_array( $data ) ? $data : array();
}

function brounhall_blog_response( WP_Post $post ) {
	$data = brounhall_blog_data( $post );
	$data['slug'] = brounhall_base_locale_slug( $post->post_name );
	$data['title'] = get_the_title( $post );
	return $data;
}

function brounhall_rest_blogs( WP_REST_Request $request ) {
	$locale = function_exists( 'brounhall_request_locale' ) ? brounhall_request_locale( $request ) : 'en';
	$query  = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => array( 'date' => 'DESC', 'ID' => 'DESC' ),
			'no_found_rows'  => true,
			'meta_key'       => '_brounhall_blog_data',
		)
	);
	$items = array_filter(
		$query->posts,
		function ( $post ) use ( $locale ) {
			$is_arabic = (bool) preg_match( '/_ar$/', $post->post_name );
			return 'ar' === $locale ? $is_arabic : ! $is_arabic;
		}
	);
	return rest_ensure_response( array( 'items' => array_map( 'brounhall_blog_response', array_values( $items ) ) ) );
}

function brounhall_rest_blog( WP_REST_Request $request ) {
	$slug  = brounhall_localized_slug( (string) $request['slug'], brounhall_request_locale( $request ) );
	$post  = get_page_by_path( $slug, OBJECT, 'post' );
	if ( ! $post || 'publish' !== $post->post_status || ! get_post_meta( $post->ID, '_brounhall_blog_data', true ) ) {
		return new WP_Error( 'brounhall_blog_not_found', 'Blog not found', array( 'status' => 404 ) );
	}
	return rest_ensure_response( brounhall_blog_response( $post ) );
}
