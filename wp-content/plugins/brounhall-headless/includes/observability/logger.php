<?php

defined( 'ABSPATH' ) || exit;

function brounhall_observability_environment() {
	$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : '';
	return $environment ? sanitize_key( $environment ) : 'production';
}

function brounhall_observability_request_id( $request = null ) {
	$value = '';
	if ( is_object( $request ) && method_exists( $request, 'get_header' ) ) {
		$value = (string) $request->get_header( 'x-request-id' );
		if ( '' === $value ) {
			$value = (string) $request->get_header( 'x-bourn-hall-request-id' );
		}
	}
	return preg_match( '/^req_[A-Za-z0-9_-]{8,96}$/', $value ) ? $value : 'req_' . wp_generate_uuid4();
}

function brounhall_observability_sanitize_context( $context ) {
	$allowed = array( 'request_id', 'trace_id', 'operation', 'method', 'route', 'status_code', 'duration_ms', 'cache_status', 'error_type', 'attempt', 'response_bytes', 'retry_after', 'content_type', 'locale' );
	$safe    = array();
	foreach ( (array) $context as $key => $value ) {
		if ( in_array( $key, $allowed, true ) && ( is_scalar( $value ) || null === $value ) ) {
			$safe[ $key ] = $value;
		}
	}
	return $safe;
}

function brounhall_observability_log_level_enabled( $level ) {
	$levels    = array( 'debug', 'info', 'warn', 'error' );
	$configured = strtolower( (string) getenv( 'BROUNHALL_LOG_LEVEL' ) );
	if ( ! in_array( $configured, $levels, true ) ) {
		$configured = 'info';
	}
	if ( 'debug' === $level && 'production' === brounhall_observability_environment() && 'debug' !== $configured ) {
		return false;
	}
	return array_search( $level, $levels, true ) >= array_search( $configured, $levels, true );
}

function brounhall_log( $level, $event, $message, $context = array() ) {
	$level = strtolower( (string) $level );
	if ( ! in_array( $level, array( 'debug', 'info', 'warn', 'error' ), true ) || ! brounhall_observability_log_level_enabled( $level ) ) {
		return;
	}
	$entry = array_merge(
		array(
			'timestamp'   => gmdate( 'c' ),
			'level'       => $level,
			'service'     => 'brounhall-wordpress',
			'environment' => brounhall_observability_environment(),
			'event'       => sanitize_key( str_replace( '.', '_', (string) $event ) ),
			'message'     => sanitize_text_field( (string) $message ),
		),
		brounhall_observability_sanitize_context( $context )
	);
	error_log( wp_json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
}
