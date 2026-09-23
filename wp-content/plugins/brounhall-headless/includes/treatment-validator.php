<?php
defined( 'ABSPATH' ) || exit;

function brounhall_treatment_slug_is_valid( $slug ) {
	return is_string( $slug ) && (bool) preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug );
}

function brounhall_treatment_text( $value, $limit = 8000 ) {
	$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
	return substr( $value, 0, $limit );
}

function brounhall_treatment_url( $value ) {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';
	if ( '' === $value || preg_match( '/[\x00-\x20]/', $value ) || false !== strpos( $value, '\\' ) ) {
		return '';
	}
	if ( '/' === substr( $value, 0, 1 ) && '//' !== substr( $value, 0, 2 ) ) {
		return esc_url_raw( $value, array( 'http', 'https' ) );
	}
	$parts = wp_parse_url( $value );
	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) || isset( $parts['user'], $parts['pass'] ) ) {
		return '';
	}
	return in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ? esc_url_raw( $value, array( 'http', 'https' ) ) : '';
}

function brounhall_treatment_id( $value ) {
	return absint( $value );
}

function brounhall_treatment_list( $value, $max, $item_callback ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	$output = array();
	foreach ( array_slice( $value, 0, $max ) as $item ) {
		$normalized = call_user_func( $item_callback, $item );
		if ( null !== $normalized ) {
			$output[] = $normalized;
		}
	}
	return $output;
}

function brounhall_normalize_treatment_data( $data ) {
	$data = is_array( $data ) ? $data : array();
	$hero = is_array( $data['hero'] ?? null ) ? $data['hero'] : array();
	$media = is_array( $hero['media'] ?? null ) ? $hero['media'] : array();
	$related = is_array( $data['relatedTreatments'] ?? null ) ? $data['relatedTreatments'] : array();

	$actions = brounhall_treatment_list( $hero['actions'] ?? array(), 10, function ( $item ) {
		if ( ! is_array( $item ) || ! in_array( $item['style'] ?? '', array( 'primary', 'secondary' ), true ) ) {
			return null;
		}
		$href = brounhall_treatment_url( $item['href'] ?? '' );
		return '' === $href ? null : array( 'label' => brounhall_treatment_text( $item['label'] ?? '', 200 ), 'href' => $href, 'style' => $item['style'] );
	} );

	$navigation = array();
	foreach ( array_slice( is_array( $data['navigation'] ?? null ) ? $data['navigation'] : array(), 0, 20 ) as $item ) {
		$key = is_array( $item ) ? sanitize_title( $item['key'] ?? '' ) : '';
		$target = is_array( $item ) ? sanitize_title( $item['target'] ?? '' ) : '';
		if ( brounhall_treatment_slug_is_valid( $key ) && brounhall_treatment_slug_is_valid( $target ) && ! isset( $navigation[ $key ] ) ) {
			$navigation[ $key ] = array( 'key' => $key, 'label' => brounhall_treatment_text( $item['label'] ?? '', 200 ), 'target' => $target );
		}
	}

	$sections = array();
	foreach ( array_slice( is_array( $data['sections'] ?? null ) ? $data['sections'] : array(), 0, 50 ) as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$id = sanitize_title( $section['id'] ?? '' );
		$type = $section['type'] ?? '';
		if ( ! brounhall_treatment_slug_is_valid( $id ) || isset( $sections[ $id ] ) || ! in_array( $type, brounhall_treatment_section_types(), true ) ) {
			continue;
		}
		$normalized = array( 'id' => $id, 'type' => $type, 'enabled' => rest_sanitize_boolean( $section['enabled'] ?? false ) );
		foreach ( array( 'title', 'eyebrow', 'introduction', 'closingText', 'heading' ) as $key ) {
			if ( array_key_exists( $key, $section ) ) {
				$normalized[ $key ] = brounhall_treatment_text( $section[ $key ] );
			}
		}
		foreach ( array( 'paragraphs', 'notes', 'closingParagraphs' ) as $key ) {
			if ( array_key_exists( $key, $section ) ) {
				$normalized[ $key ] = brounhall_treatment_list( $section[ $key ], 100, function ( $value ) { return brounhall_treatment_text( $value ); } );
			}
		}
		if ( isset( $section['items'] ) ) {
			$normalized['items'] = brounhall_treatment_list( $section['items'], 100, function ( $item ) {
				if ( ! is_array( $item ) ) { return null; }
				$key = sanitize_title( $item['key'] ?? '' );
				return brounhall_treatment_slug_is_valid( $key ) ? array( 'key' => $key, 'title' => brounhall_treatment_text( $item['title'] ?? '', 200 ), 'text' => brounhall_treatment_text( $item['text'] ?? '' ) ) : null;
			} );
		}
		$sections[ $id ] = $normalized;
	}

	$related_items = brounhall_treatment_list( $related['items'] ?? array(), 20, function ( $item ) {
		if ( ! is_array( $item ) ) { return null; }
		$media = is_array( $item['media'] ?? null ) ? $item['media'] : array();
		$action = is_array( $item['action'] ?? null ) ? $item['action'] : array();
		$variant = $item['presentation']['variant'] ?? '';
		if ( ! in_array( $variant, array( 'icon', 'image' ), true ) ) { return null; }
		$href = brounhall_treatment_url( $action['href'] ?? '' );
		return '' === $href ? null : array( 'key' => sanitize_title( $item['key'] ?? '' ), 'title' => brounhall_treatment_text( $item['title'] ?? '', 200 ), 'description' => brounhall_treatment_text( $item['description'] ?? '' ), 'media' => array( 'imageId' => brounhall_treatment_id( $media['imageId'] ?? 0 ), 'alt' => brounhall_treatment_text( $media['alt'] ?? '', 200 ) ), 'action' => array( 'label' => brounhall_treatment_text( $action['label'] ?? '', 200 ), 'href' => $href ), 'presentation' => array( 'variant' => $variant ) );
	} );

	return array(
		'template' => 'treatment-detail',
		'hero' => array( 'title' => brounhall_treatment_text( $hero['title'] ?? '', 200 ), 'titleHighlight' => brounhall_treatment_text( $hero['titleHighlight'] ?? '', 500 ), 'description' => brounhall_treatment_text( $hero['description'] ?? '' ), 'media' => array( 'imageId' => brounhall_treatment_id( $media['imageId'] ?? 0 ), 'videoId' => brounhall_treatment_id( $media['videoId'] ?? 0 ), 'posterImageId' => brounhall_treatment_id( $media['posterImageId'] ?? 0 ), 'alt' => brounhall_treatment_text( $media['alt'] ?? '', 200 ) ), 'actions' => $actions ),
		'navigation' => array_values( $navigation ),
		'sections' => array_values( $sections ),
		'relatedTreatments' => array( 'title' => brounhall_treatment_text( $related['title'] ?? '', 200 ), 'action' => array( 'label' => brounhall_treatment_text( $related['action']['label'] ?? '', 200 ), 'href' => brounhall_treatment_url( $related['action']['href'] ?? '' ) ), 'items' => $related_items ),
	);
}

function brounhall_treatment_section_types() {
	return array( 'rich_text', 'content_groups', 'eligibility_content', 'treatment_process', 'assessment_panel', 'feature_panel', 'genetic_testing', 'related_treatments' );
}
