<?php
/**
 * Plugin Name: BrounHall Headless
 * Description: Project-owned headless WordPress content models and APIs.
 * Version: 0.2.0
 */

defined( 'ABSPATH' ) || exit;

define( 'BROUNHALL_HEADLESS_DIR', plugin_dir_path( __FILE__ ) );

require_once BROUNHALL_HEADLESS_DIR . 'includes/media.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-validator.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-post-type.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-fields.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-rest.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-seeder.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/doctor-validator.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/doctor-post-type.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/doctor-fields.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/doctor-rest.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/doctor-seeder.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/clinic-validator.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/clinic-post-type.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/clinic-fields.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/clinic-rest.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/clinic-seeder.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/page-seeder.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/cost-seeder.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/blog-validator.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/blog-post-type.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/blog-rest.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/blog-seeder.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/faq-validator.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/faq-post-type.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/faq-fields.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/faq-rest.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/faq-seeder.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/appointment-validator.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/appointment-post-type.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/appointment-fields.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/appointment-settings.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/appointment-rest.php';

register_activation_hook( __FILE__, 'brounhall_seed_treatments' );
register_activation_hook( __FILE__, 'brounhall_seed_doctors' );
add_action( 'init', function () {
	if ( '1' !== get_option( 'brounhall_embryologists_seeded' ) ) {
		brounhall_seed_embryologists();
		update_option( 'brounhall_embryologists_seeded', '1', false );
	}
}, 20 );
register_activation_hook( __FILE__, 'brounhall_seed_clinics' );
register_activation_hook( __FILE__, 'brounhall_seed_faqs' );
add_action( 'init', function () {
	if ( '3' !== get_option( 'brounhall_blogs_seeded' ) ) {
		brounhall_seed_blogs();
		update_option( 'brounhall_blogs_seeded', '3', false );
	}
}, 20 );
