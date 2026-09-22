<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () { add_options_page( 'Bourn Hall Appointments', 'Bourn Hall Appointments', 'manage_options', 'brounhall-appointments', 'brounhall_appointment_settings_page' ); } );
add_action( 'admin_init', function () {
	register_setting( 'brounhall_appointments', 'brounhall_appointment_recipients', array( 'sanitize_callback' => 'brounhall_sanitize_appointment_recipients' ) );
	register_setting( 'brounhall_appointments', 'brounhall_appointment_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
} );

function brounhall_sanitize_appointment_recipients( $value ) {
	$emails = preg_split( '/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
	$emails = array_values( array_filter( array_map( 'sanitize_email', $emails, ), 'is_email' ) );
	return implode( "\n", array_unique( $emails ) );
}

function brounhall_appointment_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$recipients = get_option( 'brounhall_appointment_recipients', '' );
	$key = get_option( 'brounhall_appointment_api_key', '' );
	?>
	<div class="wrap"><h1>Bourn Hall Appointments</h1><form method="post" action="options.php">
		<?php settings_fields( 'brounhall_appointments' ); ?>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="brounhall_appointment_recipients">Notification recipients</label></th><td><textarea class="large-text" rows="5" id="brounhall_appointment_recipients" name="brounhall_appointment_recipients"><?php echo esc_textarea( $recipients ); ?></textarea><p class="description">One email address per line. New appointment notifications are sent to these addresses.</p></td></tr>
			<tr><th scope="row"><label for="brounhall_appointment_api_key">Proxy API key</label></th><td><input class="regular-text" type="password" autocomplete="new-password" id="brounhall_appointment_api_key" name="brounhall_appointment_api_key" value="<?php echo esc_attr( $key ); ?>" /><p class="description">Set the same secret as WORDPRESS_APPOINTMENT_API_KEY in Next.js. Never expose it to the browser.</p></td></tr>
		</table>
		<?php submit_button(); ?>
	</form></div>
	<?php
}
