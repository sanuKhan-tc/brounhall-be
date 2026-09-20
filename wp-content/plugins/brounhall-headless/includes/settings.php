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
		'success_rate'           => '',
		'live_births'            => '',
		'years_of_trusted_care'   => '',
		'primary_cta_title'       => '',
		'primary_cta_description' => '',
		'primary_cta_link'        => array(
			'title'  => '',
			'url'    => '',
			'target' => '',
		),
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
	$stored   = get_option( BROUNHALL_HEADLESS_SETTINGS_OPTION, array() );
	$stored   = is_array( $stored ) ? $stored : array();
	$settings = brounhall_global_settings_defaults();
	$phone    = is_scalar( $input['public_contact_phone'] ?? ( $stored['public_contact_phone'] ?? null ) ) ? (string) ( $input['public_contact_phone'] ?? $stored['public_contact_phone'] ) : '';
	$email    = is_scalar( $input['public_contact_email'] ?? ( $stored['public_contact_email'] ?? null ) ) ? (string) ( $input['public_contact_email'] ?? $stored['public_contact_email'] ) : '';
	$address  = is_scalar( $input['public_contact_address'] ?? ( $stored['public_contact_address'] ?? null ) ) ? (string) ( $input['public_contact_address'] ?? $stored['public_contact_address'] ) : '';
	$copyright = is_scalar( $input['footer_copyright_text'] ?? ( $stored['footer_copyright_text'] ?? null ) ) ? (string) ( $input['footer_copyright_text'] ?? $stored['footer_copyright_text'] ) : '';
	$success_rate = is_scalar( $input['success_rate'] ?? ( $stored['success_rate'] ?? null ) ) ? (string) ( $input['success_rate'] ?? $stored['success_rate'] ) : '';
	$live_births = is_scalar( $input['live_births'] ?? ( $stored['live_births'] ?? null ) ) ? (string) ( $input['live_births'] ?? $stored['live_births'] ) : '';
	$years_of_trusted_care = is_scalar( $input['years_of_trusted_care'] ?? ( $stored['years_of_trusted_care'] ?? null ) ) ? (string) ( $input['years_of_trusted_care'] ?? $stored['years_of_trusted_care'] ) : '';
	$primary_cta_title = is_scalar( $input['primary_cta_title'] ?? ( $stored['primary_cta_title'] ?? null ) ) ? (string) ( $input['primary_cta_title'] ?? $stored['primary_cta_title'] ) : '';
	$primary_cta_description = is_scalar( $input['primary_cta_description'] ?? ( $stored['primary_cta_description'] ?? null ) ) ? (string) ( $input['primary_cta_description'] ?? $stored['primary_cta_description'] ) : '';
	$stored_cta_link = is_array( $stored['primary_cta_link'] ?? null ) ? $stored['primary_cta_link'] : array();
	$input_cta_link = is_array( $input['primary_cta_link'] ?? null ) ? $input['primary_cta_link'] : array();
	$email    = sanitize_email( $email );

	$settings['public_contact_phone']   = substr( sanitize_text_field( $phone ), 0, 100 );
	$settings['public_contact_email']   = is_email( $email ) ? $email : '';
	$settings['public_contact_address'] = substr( sanitize_textarea_field( $address ), 0, 1000 );
	$settings['footer_copyright_text']  = substr( sanitize_text_field( $copyright ), 0, 255 );
	$settings['success_rate']           = substr( sanitize_text_field( $success_rate ), 0, 50 );
	$settings['live_births']            = substr( sanitize_text_field( $live_births ), 0, 50 );
	$settings['years_of_trusted_care']  = substr( sanitize_text_field( $years_of_trusted_care ), 0, 50 );
	$settings['primary_cta_title']       = substr( sanitize_text_field( $primary_cta_title ), 0, 200 );
	$settings['primary_cta_description'] = substr( sanitize_textarea_field( $primary_cta_description ), 0, 1000 );
	$settings['primary_cta_link']        = brounhall_sanitize_global_cta_link( array_merge( $stored_cta_link, $input_cta_link ) );

	return $settings;
}

/**
 * Sanitize the public primary CTA link.
 *
 * @param mixed $link Submitted link value.
 * @return array<string, string>
 */
function brounhall_sanitize_global_cta_link( $link ) {
	$link   = is_array( $link ) ? $link : array();
	$title     = is_scalar( $link['title'] ?? null ) ? trim( (string) $link['title'] ) : '';
	$raw_url   = is_scalar( $link['url'] ?? null ) ? trim( (string) $link['url'] ) : '';
	$target = is_scalar( $link['target'] ?? null ) ? (string) $link['target'] : '';
	$url    = brounhall_sanitize_public_url( $raw_url, true, true, false );

	if ( ( '' !== $raw_url && '' === $url ) || ( '' === $raw_url && ( '' !== $title || '' !== $target ) ) ) {
		return array();
	}

	return array(
		'title'  => substr( sanitize_text_field( $title ), 0, 200 ),
		'url'    => $url,
		'target' => brounhall_sanitize_link_target( $target ),
	);
}

/**
 * Allow only safe internal and public link schemes.
 *
 * @param string $url Submitted URL.
 * @return string
 */
function brounhall_sanitize_global_cta_url( $url ) {
	return brounhall_sanitize_public_url( $url, true, true, false );
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
	add_settings_section( 'brounhall_statistics_section', __( 'Organization Statistics', 'brounhall-headless' ), 'brounhall_render_statistics_section', 'brounhall-settings' );
	add_settings_section( 'brounhall_primary_cta_section', __( 'Primary Call to Action', 'brounhall-headless' ), 'brounhall_render_primary_cta_section', 'brounhall-settings' );

	add_settings_field( 'public_contact_phone', __( 'Public Phone', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_contact_section', array( 'setting_key' => 'public_contact_phone', 'type' => 'text' ) );
	add_settings_field( 'public_contact_email', __( 'Public Email', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_contact_section', array( 'setting_key' => 'public_contact_email', 'type' => 'email' ) );
	add_settings_field( 'public_contact_address', __( 'Public Address', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_contact_section', array( 'setting_key' => 'public_contact_address', 'type' => 'textarea' ) );
	add_settings_field( 'footer_copyright_text', __( 'Copyright Text', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_footer_section', array( 'setting_key' => 'footer_copyright_text', 'type' => 'text' ) );
	add_settings_field( 'success_rate', __( 'Success Rate', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_statistics_section', array( 'setting_key' => 'success_rate', 'type' => 'text', 'description' => __( 'Display value, for example 80%.', 'brounhall-headless' ) ) );
	add_settings_field( 'live_births', __( 'Live Births', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_statistics_section', array( 'setting_key' => 'live_births', 'type' => 'text', 'description' => __( 'Display value, for example 7,230+.', 'brounhall-headless' ) ) );
	add_settings_field( 'years_of_trusted_care', __( 'Years of Trusted Care', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_statistics_section', array( 'setting_key' => 'years_of_trusted_care', 'type' => 'text', 'description' => __( 'Display value, for example 40+.', 'brounhall-headless' ) ) );
	add_settings_field( 'primary_cta_title', __( 'CTA Title', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_primary_cta_section', array( 'setting_key' => 'primary_cta_title', 'type' => 'text' ) );
	add_settings_field( 'primary_cta_description', __( 'CTA Description', 'brounhall-headless' ), 'brounhall_render_setting_field', 'brounhall-settings', 'brounhall_primary_cta_section', array( 'setting_key' => 'primary_cta_description', 'type' => 'textarea' ) );
	add_settings_field( 'primary_cta_link', __( 'CTA Link', 'brounhall-headless' ), 'brounhall_render_primary_cta_link_field', 'brounhall-settings', 'brounhall_primary_cta_section' );
}

function brounhall_render_contact_section() {
	echo '<p>' . esc_html__( 'Public contact details used by the BrounHall website.', 'brounhall-headless' ) . '</p>';
}

function brounhall_render_statistics_section() {
	echo '<p>' . esc_html__( 'Organization-wide display values. Confirm final production figures with the content owner.', 'brounhall-headless' ) . '</p>';
}

function brounhall_render_primary_cta_section() {
	echo '<p>' . esc_html__( 'Shared primary call to action used across the website.', 'brounhall-headless' ) . '</p>';
}

function brounhall_render_primary_cta_link_field() {
	$link = brounhall_get_global_setting( 'primary_cta_link', array() );
	$link = is_array( $link ) ? $link : array();
	$base = BROUNHALL_HEADLESS_SETTINGS_OPTION . '[primary_cta_link]';

	printf( '<p><label>%1$s<br><input type="text" name="%2$s[title]" value="%3$s" class="regular-text" /></label></p>', esc_html__( 'Link title', 'brounhall-headless' ), esc_attr( $base ), esc_attr( $link['title'] ?? '' ) );
	printf( '<p><label>%1$s<br><input type="text" name="%2$s[url]" value="%3$s" class="regular-text" /></label></p>', esc_html__( 'URL', 'brounhall-headless' ), esc_attr( $base ), esc_attr( $link['url'] ?? '' ) );
	printf( '<p><label>%1$s<br><select name="%2$s[target]"><option value="">%3$s</option><option value="_self"%4$s>_self</option><option value="_blank"%5$s>_blank</option></select></label></p>', esc_html__( 'Target', 'brounhall-headless' ), esc_attr( $base ), esc_html__( 'Same window', 'brounhall-headless' ), selected( $link['target'] ?? '', '_self', false ), selected( $link['target'] ?? '', '_blank', false ) );
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
	} else {
		printf( '<input type="%1$s" name="%2$s" value="%3$s" class="regular-text" />', esc_attr( $type ), esc_attr( $name ), esc_attr( $value ) );
	}

	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
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
