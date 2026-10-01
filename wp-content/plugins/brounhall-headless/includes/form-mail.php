<?php
defined( 'ABSPATH' ) || exit;

function brounhall_store_form_submissions() {
	return defined( 'BOURNHALL_STORE_FORM_SUBMISSIONS' ) && filter_var( BOURNHALL_STORE_FORM_SUBMISSIONS, FILTER_VALIDATE_BOOLEAN );
}

function brounhall_form_storage_admin_warning() {
	if ( brounhall_store_form_submissions() ) {
		echo '<div class="notice notice-warning inline"><p><strong>Warning:</strong> form submission storage is enabled. This record contains persisted patient data in WordPress. Confirm retention, access, and deletion approvals before continuing.</p></div>';
		return;
	}
	echo '<div class="notice notice-warning inline"><p><strong>Notice:</strong> form submission storage has been disabled by the system administrator. New appointment and complaint submissions are emailed and are not stored in WordPress. Contact the system administrator to enable record storage if required.</p></div>';
}

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'edit' !== $screen->base || ! in_array( $screen->post_type, array( 'bh_appointment', 'bh_complaint' ), true ) ) return;
	brounhall_form_storage_admin_warning();
} );

function brounhall_form_recipients() {
	$recipients = preg_split( '/[\s,;]+/', (string) get_option( 'brounhall_appointment_recipients', '' ), -1, PREG_SPLIT_NO_EMPTY );
	return array_values( array_filter( $recipients, 'is_email' ) );
}

function brounhall_form_email_html( $title, $intro, $reference, $submitted_at, $fields ) {
	$rows = '';
	foreach ( $fields as $label => $value ) {
		$rows .= '<tr><td style="padding:12px 0;border-bottom:1px solid #eadfe5;color:#6d5d66;font-size:14px;width:35%">' . esc_html( $label ) . '</td><td style="padding:12px 0;border-bottom:1px solid #eadfe5;color:#241a28;font-size:15px">' . nl2br( esc_html( (string) $value ) ) . '</td></tr>';
	}

	return '<!doctype html><html><body style="margin:0;background:#f8f3f7;color:#241a28;font-family:Arial,Helvetica,sans-serif"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8f3f7;padding:32px 16px"><tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:20px;overflow:hidden"><tr><td style="background:#7d1551;padding:24px 32px;color:#ffffff;font-size:22px;font-weight:700">Bourn Hall</td></tr><tr><td style="padding:32px"><p style="margin:0 0 8px;color:#7d1551;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase">' . esc_html( $title ) . '</p><h1 style="margin:0 0 12px;font-size:28px;line-height:1.2;color:#241a28">New request received</h1><p style="margin:0 0 24px;color:#6d5d66;font-size:16px;line-height:1.6">' . esc_html( $intro ) . '</p><table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table><p style="margin:24px 0 0;color:#6d5d66;font-size:13px;line-height:1.5">Reference: ' . esc_html( $reference ) . '<br>Received: ' . esc_html( $submitted_at ) . '</p></td></tr><tr><td style="padding:18px 32px;background:#f3e9f0;color:#6d5d66;font-size:12px">Bourn Hall Fertility Clinic UAE</td></tr></table></td></tr></table></body></html>';
}

function brounhall_send_form_notification( $type, $data, $reference ) {
	$recipients = brounhall_form_recipients();
	if ( function_exists( 'brounhall_appointment_is_local' ) && brounhall_appointment_is_local() ) return true;
	if ( ! $recipients ) return false;

	$is_complaint = 'complaint' === $type;
	$title = $is_complaint ? 'Patient complaint' : 'Book a consultation';
	$subject = sprintf( 'New Bourn Hall %s request — %s', $is_complaint ? 'patient complaint' : 'appointment', $data['phone'] );
	$fields = $is_complaint
		? array( 'Name' => $data['name'], 'Phone' => $data['phone'], 'Email' => $data['email'], 'Complaint' => $data['message'] )
		: array( 'Name' => $data['name'], 'Phone' => $data['phone'], 'Email' => $data['email'], 'Clinic' => $data['location'], 'Treatment' => $data['service'], 'Message' => $data['message'] );
	$body = brounhall_form_email_html( $title, $is_complaint ? 'A patient complaint was submitted through the website.' : 'A consultation request was submitted through the website.', $reference, $data['submittedAt'], $fields );
	$headers = array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $data['email'] );
	return wp_mail( $recipients, $subject, $body, $headers );
}
