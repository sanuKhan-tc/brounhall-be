<?php

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route(
		'brounhall/v1',
		'/health',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'brounhall_health_check',
			'permission_callback' => '__return_true',
		)
	);
} );

function brounhall_health_check( WP_REST_Request $request ) {
	$response = rest_ensure_response( array( 'status' => 'healthy', 'timestamp' => gmdate( 'c' ) ) );
	$response->header( 'Cache-Control', 'no-store, max-age=0' );
	$response->header( 'X-Request-ID', brounhall_observability_request_id( $request ) );
	brounhall_log( 'info', 'health_check_completed', 'WordPress health check completed', array( 'route' => '/wp-json/brounhall/v1/health', 'status_code' => 200, 'request_id' => brounhall_observability_request_id( $request ) ) );
	return $response;
}
