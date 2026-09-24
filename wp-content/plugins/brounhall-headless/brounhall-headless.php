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
	'page-editor.php',
	'content-types.php',
	'treatment-rest.php',
	'directory-rest.php',
	'doctor.php',
	'location.php',
	'taxonomies.php',
	'graphql.php',
	'settings.php',
	'navigation.php',
	'mega-menu.php',
	'seo.php',
	'sections/hero.php',
	'sections/rich-text.php',
	'sections/text-image.php',
	'sections/service-grid.php',
	'sections/doctor-grid.php',
	'sections/faq.php',
	'sections/related-content.php',
	'preview.php',
	'revalidation.php',
	'security.php',
	'appointment-validator.php',
	'appointment-post-type.php',
	'security/appointment-key-provider.php',
	'security/appointment-crypto.php',
	'security/appointment-migration.php',
	'appointment-fields.php',
	'appointment-settings.php',
	'appointment-rest.php',
) as $module ) {
	require_once BROUNHALL_HEADLESS_DIR . 'includes/' . $module;
}
