<?php

require dirname( __DIR__ ) . '/wp-load.php';

$safe = brounhall_observability_sanitize_context(
	array(
		'status_code'   => 502,
		'request_id'    => 'req_safe1234',
		'email'         => 'patient@example.test',
		'authorization' => 'Bearer secret',
		'message'       => 'sensitive patient message',
	)
);

if ( 502 !== $safe['status_code'] || 'req_safe1234' !== $safe['request_id'] || isset( $safe['email'] ) || isset( $safe['authorization'] ) || isset( $safe['message'] ) ) {
	fwrite( STDERR, "observability sanitizer failed\n" );
	exit( 1 );
}

$request = new WP_REST_Request( 'GET', '/brounhall/v1/health' );
$request->set_header( 'X-Request-ID', 'req_safe1234' );
if ( 'req_safe1234' !== brounhall_observability_request_id( $request ) || ! preg_match( '/^req_[A-Za-z0-9_-]{8,96}$/', brounhall_observability_request_id() ) ) {
	fwrite( STDERR, "observability request id failed\n" );
	exit( 1 );
}

echo "observability logger ok\n";
