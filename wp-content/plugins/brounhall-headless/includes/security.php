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

defined( 'ABSPATH' ) || exit;
