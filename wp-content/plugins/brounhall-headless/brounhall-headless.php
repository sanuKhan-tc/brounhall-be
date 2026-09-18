<?php
/**
 * Plugin Name: BrounHall Headless
 * Description: Project-owned foundation for the BrounHall headless WordPress integration.
 * Version: 0.1.0
 * Author: BrounHall
 * Text Domain: brounhall-headless
 */

defined( 'ABSPATH' ) || exit;

define( 'BROUNHALL_HEADLESS_VERSION', '0.1.0' );
define( 'BROUNHALL_HEADLESS_DIR', plugin_dir_path( __FILE__ ) );

foreach ( array(
	'fields.php',
	'content-types.php',
	'taxonomies.php',
	'graphql.php',
	'settings.php',
	'navigation.php',
	'seo.php',
	'sections/hero.php',
	'sections/rich-text.php',
	'sections/text-image.php',
	'preview.php',
	'revalidation.php',
	'security.php',
) as $module ) {
	require_once BROUNHALL_HEADLESS_DIR . 'includes/' . $module;
}
