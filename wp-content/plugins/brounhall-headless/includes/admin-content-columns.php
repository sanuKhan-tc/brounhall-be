<?php

defined( 'ABSPATH' ) || exit;

foreach ( array( 'page', 'post' ) as $post_type ) {
	add_filter( "manage_{$post_type}_posts_columns", 'brounhall_add_content_admin_columns' );
	add_action( "manage_{$post_type}_posts_custom_column", 'brounhall_render_content_admin_column', 10, 2 );
}

add_filter( 'posts_search', 'brounhall_search_content_slugs', 10, 2 );

function brounhall_add_content_admin_columns( $columns ) {
	$updated = array();

	foreach ( $columns as $key => $label ) {
		$updated[ $key ] = $label;
		if ( 'title' === $key ) {
			$updated['brounhall_slug']     = __( 'Slug', 'brounhall-headless' );
			$updated['brounhall_language'] = __( 'Language', 'brounhall-headless' );
		}
	}

	return $updated;
}

function brounhall_render_content_admin_column( $column, $post_id ) {
	if ( 'brounhall_slug' === $column ) {
		echo esc_html( get_post_field( 'post_name', $post_id ) );
		return;
	}

	if ( 'brounhall_language' === $column ) {
		$slug = (string) get_post_field( 'post_name', $post_id );
		echo esc_html( preg_match( '/_ar$/', $slug ) ? __( 'Arabic', 'brounhall-headless' ) : __( 'English', 'brounhall-headless' ) );
	}
}

function brounhall_search_content_slugs( $search, $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || ! $query->is_search() || ! in_array( $query->get( 'post_type' ), array( 'page', 'post' ), true ) || '' === trim( (string) $query->get( 's' ) ) ) {
		return $search;
	}

	global $wpdb;
	$search = preg_replace( '/^\s*AND\s+/', 'AND (', $search, 1 );
	return $search . $wpdb->prepare( " OR {$wpdb->posts}.post_name LIKE %s)", '%' . $wpdb->esc_like( $query->get( 's' ) ) . '%' );
}
