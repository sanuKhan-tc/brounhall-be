<?php
/**
 * Register the project-owned primary navigation location.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'brounhall_register_navigation' );

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
