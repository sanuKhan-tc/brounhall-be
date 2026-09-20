<?php
/**
 * Register the project-owned primary navigation location.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'brounhall_register_navigation' );
add_action( 'wp_update_nav_menu_item', 'brounhall_sanitize_nav_menu_item', 20, 3 );

function brounhall_register_navigation() {
	register_nav_menus(
		array(
			'primary-navigation' => __( 'Primary Navigation', 'brounhall-headless' ),
			'footer-navigation'  => __( 'Footer Navigation', 'brounhall-headless' ),
			'footer-services'    => __( 'Footer Services', 'brounhall-headless' ),
		)
	);
}

/**
 * Normalize project-owned native menu values after WordPress saves an item.
 * Core remains responsible for menu hierarchy and ordering.
 *
 * @param int   $menu_id
 * @param int   $menu_item_db_id
 * @param array $args
 * @return void
 */
function brounhall_sanitize_nav_menu_item( $menu_id, $menu_item_db_id, $args ) {
	$item_id = absint( $menu_item_db_id );

	if ( ! $item_id || 'nav_menu_item' !== get_post_type( $item_id ) ) {
		return;
	}

	$title = get_the_title( $item_id );
	$title = substr( trim( sanitize_text_field( (string) $title ) ), 0, 200 );

	if ( $title !== get_post_field( 'post_title', $item_id ) ) {
		wp_update_post(
			array(
				'ID'         => $item_id,
				'post_title' => $title,
			)
		);
	}

	$url = get_post_meta( $item_id, '_menu_item_url', true );
	update_post_meta( $item_id, '_menu_item_url', brounhall_sanitize_public_url( $url, false, true, true ) );
	update_post_meta( $item_id, '_menu_item_target', brounhall_sanitize_link_target( get_post_meta( $item_id, '_menu_item_target', true ) ) );
}

/**
 * Return the source-proven semantic mega-menu layout vocabulary.
 *
 * @return array<string, string>
 */
function brounhall_get_mega_menu_layouts() {
	return array(
		'mixed' => __( 'Columns with Featured Content', 'brounhall-headless' ),
	);
}

/**
 * Check whether a value is an approved mega-menu layout key.
 *
 * @param mixed $layout Candidate layout key.
 * @return bool
 */
function brounhall_is_mega_menu_layout( $layout ) {
	return is_string( $layout ) && array_key_exists( $layout, brounhall_get_mega_menu_layouts() );
}
