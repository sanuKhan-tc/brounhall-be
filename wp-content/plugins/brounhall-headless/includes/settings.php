<?php
/**
 * BrounHall global settings.
 */

defined( 'ABSPATH' ) || exit;

define( 'BROUNHALL_HEADLESS_SETTINGS_OPTION', 'brounhall_global_settings' );

/**
 * @return array<string, string>
 */
function brounhall_global_settings_defaults() {
	return array(
		'public_contact_phone'   => '',
		'public_contact_email'   => '',
		'public_contact_address' => '',
		'footer_copyright_text'  => '',
	);
}

/**
 * @return array<string, string>
 */
function brounhall_get_global_settings() {
	$defaults = brounhall_global_settings_defaults();
	$stored   = get_option( BROUNHALL_HEADLESS_SETTINGS_OPTION, array() );

	if ( ! is_array( $stored ) ) {
		return $defaults;
	}

	return brounhall_sanitize_global_settings( $stored );
}

/**
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function brounhall_get_global_setting( $key, $default = null ) {
	$settings = brounhall_get_global_settings();

	return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
}

/**
 * @param mixed $input
 * @return array<string, string>
 */
function brounhall_sanitize_global_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$settings = brounhall_global_settings_defaults();
	$phone    = is_scalar( $input['public_contact_phone'] ?? null ) ? (string) $input['public_contact_phone'] : '';
	$email    = is_scalar( $input['public_contact_email'] ?? null ) ? (string) $input['public_contact_email'] : '';
	$address  = is_scalar( $input['public_contact_address'] ?? null ) ? (string) $input['public_contact_address'] : '';
	$copyright = is_scalar( $input['footer_copyright_text'] ?? null ) ? (string) $input['footer_copyright_text'] : '';
	$email    = sanitize_email( $email );

	$settings['public_contact_phone']   = substr( sanitize_text_field( $phone ), 0, 100 );
	$settings['public_contact_email']   = is_email( $email ) ? $email : '';
	$settings['public_contact_address'] = substr( sanitize_textarea_field( $address ), 0, 1000 );
	$settings['footer_copyright_text']  = substr( sanitize_text_field( $copyright ), 0, 255 );

	return $settings;
}

add_action( 'admin_menu', 'brounhall_register_settings_page' );
add_action( 'admin_init', 'brounhall_register_settings' );

function brounhall_register_settings_page() {
	add_options_page(
		__( 'BrounHall Settings', 'brounhall-headless' ),
		__( 'BrounHall Settings', 'brounhall-headless' ),
		'manage_options',
		'brounhall-settings',
		'brounhall_render_settings_page'
	);
}

function brounhall_register_settings() {
	register_setting(
		'brounhall_global_settings',
		BROUNHALL_HEADLESS_SETTINGS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'brounhall_sanitize_global_settings',
			'default'           => brounhall_global_settings_defaults(),
			'show_in_rest'      => false,
		)
	);

	add_settings_section( 'brounhall_contact_section', __( 'Contact Information', 'brounhall-headless' ), 'brounhall_render_contact_section', 'brounhall-settings' );
	add_settings_section( 'brounhall_footer_section', __( 'Footer', 'brounhall-headless' ), '__return_false', 'brounhall-settings' );

	add_settings_field( 'public_contact_phone', __( 'Public Phone', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_contact_section', array( 'setting_key' => 'public_contact_phone', 'type' => 'text' ) );
	add_settings_field( 'public_contact_email', __( 'Public Email', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_contact_section', array( 'setting_key' => 'public_contact_email', 'type' => 'email' ) );
	add_settings_field( 'public_contact_address', __( 'Public Address', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_contact_section', array( 'setting_key' => 'public_contact_address', 'type' => 'textarea' ) );
	add_settings_field( 'footer_copyright_text', __( 'Copyright Text', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_footer_section', array( 'setting_key' => 'footer_copyright_text', 'type' => 'text' ) );
}

function brounhall_render_contact_section() {
	echo '<p>' . esc_html__( 'Public contact details used by the BrounHall website.', 'brounhall-headless' ) . '</p>';
}

/**
 * @param array<string, string> $args
 */
function brounhall_render_setting_field( $args ) {
	$key   = $args['setting_key'];
	$value = brounhall_get_global_setting( $key, '' );
	$type  = $args['type'] ?? 'text';
	$name  = BROUNHALL_HEADLESS_SETTINGS_OPTION . '[' . $key . ']';

	if ( 'textarea' === $type ) {
		printf( '<textarea name="%1$s" rows="4" cols="50" class="large-text">%2$s</textarea>', esc_attr( $name ), esc_textarea( $value ) );
		return;
	}

	printf( '<input type="%1$s" name="%2$s" value="%3$s" class="regular-text" />', esc_attr( $type ), esc_attr( $name ), esc_attr( $value ) );
}

function brounhall_render_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'BrounHall Settings', 'brounhall-headless' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'brounhall_global_settings' );
			do_settings_sections( 'brounhall-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
