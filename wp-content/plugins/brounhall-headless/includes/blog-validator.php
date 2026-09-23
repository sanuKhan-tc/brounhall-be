<?php
defined( 'ABSPATH' ) || exit;

function brounhall_blog_slug_is_valid( $slug ) {
	return is_string( $slug ) && (bool) preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug );
}

function brounhall_blog_text( $value, $limit = 8000 ) {
	$value = sanitize_textarea_field( (string) $value );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $limit ) : substr( $value, 0, $limit );
}

function brounhall_blog_string_list( $value, $limit = 100 ) {
	if ( ! is_array( $value ) ) { return array(); }
	$output = array();
	foreach ( array_slice( $value, 0, $limit ) as $item ) { if ( is_string( $item ) ) { $output[] = brounhall_blog_text( $item ); } }
	return array_values( array_filter( $output ) );
}

function brounhall_blog_heading_text( $value ) {
	$value = (string) $value;
	$leading = preg_match( '/^\s*/', $value, $before ) ? $before[0] : '';
	$trailing = preg_match( '/\s*$/', $value, $after ) ? $after[0] : '';
	return $leading . trim( sanitize_textarea_field( $value ) ) . $trailing;
}

function brounhall_normalize_blog_data( $value ) {
	if ( ! is_array( $value ) ) { return array(); }
	$output = array( 'excerpt' => brounhall_blog_text( $value['excerpt'] ?? '', 1000 ), 'category' => sanitize_text_field( $value['category'] ?? '' ), 'author' => sanitize_text_field( $value['author'] ?? '' ), 'imageId' => absint( $value['imageId'] ?? 0 ), 'imageAlt' => brounhall_blog_text( $value['imageAlt'] ?? '', 300 ), 'sections' => array() );
	foreach ( array_slice( is_array( $value['sections'] ?? null ) ? $value['sections'] : array(), 0, 50 ) as $section ) {
		if ( ! is_array( $section ) || ! sanitize_text_field( $section['title'] ?? '' ) ) { continue; }
		$item = array( 'title' => sanitize_text_field( $section['title'] ), 'tone' => in_array( $section['tone'] ?? '', array( 'brand', 'ink' ), true ) ? $section['tone'] : 'ink', 'paragraphs' => brounhall_blog_string_list( $section['paragraphs'] ?? array() ), 'list' => array(), 'afterList' => brounhall_blog_string_list( $section['afterList'] ?? array() ), 'subsections' => array() );
		foreach ( array_slice( is_array( $section['list'] ?? null ) ? $section['list'] : array(), 0, 100 ) as $list_item ) { if ( is_array( $list_item ) && brounhall_blog_text( $list_item['text'] ?? '' ) ) { $item['list'][] = array( 'text' => brounhall_blog_text( $list_item['text'] ), 'children' => brounhall_blog_string_list( $list_item['children'] ?? array() ) ); } }
		foreach ( array_slice( is_array( $section['subsections'] ?? null ) ? $section['subsections'] : array(), 0, 50 ) as $subsection ) {
			if ( ! is_array( $subsection ) || ! sanitize_text_field( $subsection['title'] ?? '' ) ) { continue; }
			$sub = array( 'title' => sanitize_text_field( $subsection['title'] ), 'paragraphs' => brounhall_blog_string_list( $subsection['paragraphs'] ?? array() ), 'list' => array() );
			foreach ( array_slice( is_array( $subsection['list'] ?? null ) ? $subsection['list'] : array(), 0, 100 ) as $entry ) { if ( is_array( $entry ) && brounhall_blog_text( $entry['text'] ?? '' ) ) { $sub['list'][] = array( 'text' => brounhall_blog_text( $entry['text'] ), 'children' => brounhall_blog_string_list( $entry['children'] ?? array() ) ); } }
			$item['subsections'][] = $sub;
		}
		$output['sections'][] = $item;
	}
	if ( is_array( $value['relatedHeading'] ?? null ) ) { $output['relatedHeading'] = array( 'before' => brounhall_blog_heading_text( $value['relatedHeading']['before'] ?? '' ), 'accent' => brounhall_blog_heading_text( $value['relatedHeading']['accent'] ?? '' ), 'after' => brounhall_blog_heading_text( $value['relatedHeading']['after'] ?? '' ) ); }
	return $output;
}
