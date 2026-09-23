<?php
defined( 'ABSPATH' ) || exit;

function brounhall_faq_slug_is_valid( $slug ) {
	return is_string( $slug ) && (bool) preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug );
}

function brounhall_faq_text( $value, $limit = 8000 ) {
	$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $limit ) : substr( $value, 0, $limit );
}

function brounhall_faq_href( $value ) {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';
	if ( '' === $value || preg_match( '/[\x00-\x1F\x7F]/', $value ) || 0 === strpos( $value, '//' ) ) {
		return '';
	}
	if ( '/' === substr( $value, 0, 1 ) ) {
		return esc_url_raw( $value );
	}
	$url = wp_parse_url( $value );
	if ( ! is_array( $url ) || empty( $url['scheme'] ) || ! in_array( strtolower( $url['scheme'] ), array( 'http', 'https' ), true ) ) {
		return '';
	}
	return esc_url_raw( $value );
}

function brounhall_normalize_faq_data( $value ) {
	$value = is_array( $value ) ? $value : array();
	$output = array(
		'help'       => array(
			'body' => brounhall_faq_text( $value['help']['body'] ?? '', 1000 ),
			'cta'  => array(
				'label' => sanitize_text_field( $value['help']['cta']['label'] ?? '' ),
				'href'  => brounhall_faq_href( $value['help']['cta']['href'] ?? '' ),
			),
		),
		'categories' => array(),
		'items'      => array(),
	);

	foreach ( array_slice( is_array( $value['categories'] ?? null ) ? $value['categories'] : array(), 0, 20 ) as $category ) {
		if ( ! is_array( $category ) ) {
			continue;
		}
		$title = sanitize_text_field( $category['title'] ?? '' );
		$body  = brounhall_faq_text( $category['body'] ?? '', 2000 );
		if ( $title && $body ) {
			$output['categories'][] = array( 'title' => $title, 'body' => $body );
		}
	}

	foreach ( array_slice( is_array( $value['items'] ?? null ) ? $value['items'] : array(), 0, 100 ) as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$question = sanitize_text_field( $item['question'] ?? '' );
		$answer   = brounhall_faq_text( $item['answer'] ?? '', 8000 );
		if ( $question && $answer ) {
			$output['items'][] = array( 'question' => $question, 'answer' => $answer );
		}
	}

	return $output;
}
