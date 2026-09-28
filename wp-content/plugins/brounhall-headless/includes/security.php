<?php
/**
 * Shared public URL policy for project-owned links.
 *
 * @param mixed $url
 * @param bool  $allow_contact_schemes
 * @param bool  $allow_fragment
 * @param bool  $allow_grouping
 * @return string
 */
function brounhall_sanitize_public_url( $url, $allow_contact_schemes = false, $allow_fragment = true, $allow_grouping = false ) {
	$url = is_scalar( $url ) ? trim( (string) $url ) : '';

	if ( '' === $url || preg_match( '/[\x00-\x20\\\\]/', $url ) ) {
		return '';
	}

	if ( '#' === $url ) {
		return $allow_grouping ? $url : '';
	}

	if ( '#' === substr( $url, 0, 1 ) ) {
		return $allow_fragment && preg_match( '/^#[A-Za-z0-9][A-Za-z0-9._~-]*$/', $url ) ? $url : '';
	}

	if ( '//' === substr( $url, 0, 2 ) ) {
		return '';
	}

	$allowed_schemes = $allow_contact_schemes ? array( 'http', 'https', 'mailto', 'tel' ) : array( 'http', 'https' );
	$has_scheme      = preg_match( '/^([a-z][a-z0-9+.-]*):/i', $url, $matches );

	if ( $has_scheme ) {
		$scheme = strtolower( $matches[1] );

		if ( ! in_array( $scheme, $allowed_schemes, true ) ) {
			return '';
		}

		if ( in_array( $scheme, array( 'http', 'https' ), true ) ) {
			$parts = wp_parse_url( $url );

			if ( ! is_array( $parts ) || empty( $parts['host'] ) || isset( $parts['user'], $parts['pass'] ) ) {
				return '';
			}
		}
	} elseif ( '/' !== substr( $url, 0, 1 ) ) {
		return '';
	}

	return esc_url_raw( $url, $allowed_schemes );
}

/**
 * Allow only the supported navigation target values.
 *
 * @param mixed $target
 * @return string
 */
function brounhall_sanitize_link_target( $target ) {
	$target = is_scalar( $target ) ? (string) $target : '';

	return in_array( $target, array( '', '_self', '_blank' ), true ) ? $target : '';
}

function brounhall_pii_access_granted( $post_id = 0 ) {
	if ( ! current_user_can( 'manage_options' ) ) return false;
	$access_key = 'brounhall_pii_access_' . get_current_user_id() . '_' . absint( $post_id );
	if ( get_transient( $access_key ) ) return true;
	$code = trim( (string) ( $_POST['brounhall_pii_code'] ?? '' ) );
	$nonce = sanitize_text_field( wp_unslash( (string) ( $_POST['brounhall_pii_nonce'] ?? '' ) ) );
	$expected = brounhall_pii_access_code();
	$granted = '' !== $expected && preg_match( '/^\d{6}$/', $expected ) && preg_match( '/^\d{6}$/', $code ) && wp_verify_nonce( $nonce, 'brounhall_reveal_pii' ) && hash_equals( $expected, $code );
	if ( $granted && $post_id ) set_transient( $access_key, 1, 5 * MINUTE_IN_SECONDS );
	return $granted;
}

add_action( 'admin_post_brounhall_reveal_pii', function () {
	$post_id = absint( $_POST['post_id'] ?? 0 );
	$redirect = $post_id ? get_edit_post_link( $post_id, 'raw' ) : admin_url();
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || ! brounhall_pii_access_granted( $post_id ) ) $redirect = add_query_arg( 'bh_pii_error', '1', $redirect );
	wp_safe_redirect( $redirect );
	exit;
} );

function brounhall_pii_access_prompt( $post_id ) {
	wp_nonce_field( 'brounhall_reveal_pii', 'brounhall_pii_nonce' );
	echo '<input type="hidden" name="action" value="brounhall_reveal_pii" /><input type="hidden" name="post_id" value="' . absint( $post_id ) . '" />';
	echo '<p><label for="brounhall_pii_code"><strong>Enter 6-digit access code</strong></label><br /><input id="brounhall_pii_code" name="brounhall_pii_code" type="password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required /> <button type="submit" class="button button-primary" formaction="' . esc_url( admin_url( 'admin-post.php' ) ) . '" formmethod="post">Submit</button></p>';
}

function brounhall_pii_access_key() {
	$parts = array();
	foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' ) as $constant ) {
		$parts[] = defined( $constant ) ? constant( $constant ) : '';
	}
	return hash( 'sha256', implode( '|', $parts ), true );
}

function brounhall_pii_access_code() {
	$encoded = defined( 'BOURNHALL_PII_ACCESS_CODE_ENCRYPTED' ) ? (string) BOURNHALL_PII_ACCESS_CODE_ENCRYPTED : '';
	if ( $encoded && function_exists( 'sodium_crypto_secretbox_open' ) ) {
		$envelope = json_decode( (string) base64_decode( $encoded, true ), true );
		$nonce = is_array( $envelope ) ? base64_decode( (string) ( $envelope['n'] ?? '' ), true ) : false;
		$ciphertext = is_array( $envelope ) ? base64_decode( (string) ( $envelope['c'] ?? '' ), true ) : false;
		$plain = is_array( $envelope ) && 1 === (int) ( $envelope['v'] ?? 0 ) && is_string( $nonce ) && is_string( $ciphertext ) ? sodium_crypto_secretbox_open( $ciphertext, $nonce, brounhall_pii_access_key() ) : false;
		if ( is_string( $plain ) && preg_match( '/^\d{6}$/', $plain ) ) return $plain;
	}
	return '123456';
}

defined( 'ABSPATH' ) || exit;
