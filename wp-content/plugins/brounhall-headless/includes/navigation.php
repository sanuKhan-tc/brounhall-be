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
