<?php
/**
 * Public read-only treatment endpoints for the headless frontend.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'brounhall/v1',
			'/treatments',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'brounhall_rest_treatments',
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'brounhall/v1',
			'/treatments/(?P<slug>[a-z0-9]+(?:-[a-z0-9]+)*)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'brounhall_rest_treatment',
				'permission_callback' => '__return_true',
			)
		);
	}
);

function brounhall_treatment_response( WP_Post $post ) {
	$data = json_decode( (string) $post->post_content, true );
	if ( ! is_array( $data ) ) {
		$data = json_decode( (string) get_post_meta( $post->ID, '_brounhall_legacy_data', true ), true );
	}
	if ( ! is_array( $data ) ) {
		$data = array();
	}

	return array_merge(
		array(
			'id'   => (int) $post->ID,
			'slug' => $post->post_name,
			'title' => get_the_title( $post ),
		),
		$data
	);
}

function brounhall_rest_treatments() {
	$query = new WP_Query(
		array(
			'post_type'      => 'service',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'  => true,
		)
	);

	return rest_ensure_response(
		array(
			'items' => array_map(
				function ( $post ) {
					return array(
						'slug'  => $post->post_name,
						'title' => get_the_title( $post ),
					);
				},
				$query->posts
			),
		)
	);
}

function brounhall_rest_treatment( WP_REST_Request $request ) {
	$post = get_page_by_path( (string) $request['slug'], OBJECT, 'service' );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return new WP_Error( 'brounhall_treatment_not_found', 'Treatment not found', array( 'status' => 404 ) );
	}

	return rest_ensure_response( brounhall_treatment_response( $post ) );
}
