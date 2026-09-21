<?php
/**
 * Plugin Name: BrounHall Headless
 * Description: Project-owned headless WordPress content models and APIs.
 * Version: 0.2.0
 */

defined( 'ABSPATH' ) || exit;

define( 'BROUNHALL_HEADLESS_DIR', plugin_dir_path( __FILE__ ) );

require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-validator.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-post-type.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-fields.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-rest.php';
require_once BROUNHALL_HEADLESS_DIR . 'includes/treatment-seeder.php';

register_activation_hook( __FILE__, 'brounhall_seed_treatments' );
