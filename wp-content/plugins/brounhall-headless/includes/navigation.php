<?php
/**
 * Register the project-owned primary navigation location.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'brounhall_register_navigation' );

function brounhall_register_navigation() {
	register_nav_menu(
		'primary-navigation',
		__( 'Primary Navigation', 'brounhall-headless' )
	);
}
