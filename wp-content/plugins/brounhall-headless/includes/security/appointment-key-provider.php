<?php
defined( 'ABSPATH' ) || exit;

interface Brounhall_Appointment_Key_Provider {
	public function wrap_key( $dek, $key_id = '' );
	public function unwrap_key( $wrapped_dek, $key_id );
	public function blind_index( $field, $value, $version = 1 );
}

final class Brounhall_Appointment_Env_Key_Provider implements Brounhall_Appointment_Key_Provider {
	private function key( $key_id = '' ) {
		$current_id = (string) getenv( 'BOURNHALL_APPOINTMENT_KEK_ID' );
		$current_id = $current_id ? $current_id : 'appointment-kek-v1';
		$env_name = $key_id && $key_id !== $current_id
			? 'BOURNHALL_APPOINTMENT_KEK_' . strtoupper( preg_replace( '/[^A-Za-z0-9]+/', '_', $key_id ) )
			: 'BOURNHALL_APPOINTMENT_KEK';
		$raw = getenv( $env_name );
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			throw new RuntimeException( 'Appointment key material is unavailable' );
		}
		$key = base64_decode( trim( $raw ), true );
		if ( false === $key || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES !== strlen( $key ) ) {
			throw new RuntimeException( 'Appointment key material is invalid' );
		}
		return $key;
	}

	private function index_key( $field, $version ) {
		$base = 'email' === $field ? 'BOURNHALL_APPOINTMENT_EMAIL_INDEX_KEY' : ( 'phone' === $field ? 'BOURNHALL_APPOINTMENT_PHONE_INDEX_KEY' : 'BOURNHALL_APPOINTMENT_DUPLICATE_KEY' );
		$name = 1 === (int) $version ? $base : $base . '_V' . absint( $version );
		$raw = getenv( $name );
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) { throw new RuntimeException( 'Appointment index key material is unavailable' ); }
		$key = base64_decode( trim( $raw ), true );
		if ( false === $key || strlen( $key ) < 32 ) { throw new RuntimeException( 'Appointment index key material is invalid' ); }
		return $key;
	}

	public function wrap_key( $dek, $key_id = '' ) {
		$key_id = $key_id ? $key_id : (string) getenv( 'BOURNHALL_APPOINTMENT_KEK_ID' );
		$key_id = $key_id ? $key_id : 'appointment-kek-v1';
		$nonce = random_bytes( SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES );
		$ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt( $dek, 'brounhall:appointment:dek:' . $key_id, $nonce, $this->key( $key_id ) );
		return base64_encode( wp_json_encode( array( 'v' => 1, 'n' => base64_encode( $nonce ), 'c' => base64_encode( $ciphertext ) ) ) );
	}

	public function unwrap_key( $wrapped_dek, $key_id ) {
		$envelope = json_decode( base64_decode( $wrapped_dek, true ), true );
		if ( ! is_array( $envelope ) || 1 !== (int) ( $envelope['v'] ?? 0 ) ) { throw new RuntimeException( 'Wrapped appointment key is invalid' ); }
		$nonce = base64_decode( (string) ( $envelope['n'] ?? '' ), true );
		$ciphertext = base64_decode( (string) ( $envelope['c'] ?? '' ), true );
		if ( false === $nonce || false === $ciphertext || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES !== strlen( $nonce ) ) { throw new RuntimeException( 'Wrapped appointment key is invalid' ); }
		$dek = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt( $ciphertext, 'brounhall:appointment:dek:' . $key_id, $nonce, $this->key( $key_id ) );
		if ( false === $dek || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES !== strlen( $dek ) ) { throw new RuntimeException( 'Appointment key unwrap failed' ); }
		return $dek;
	}

	public function blind_index( $field, $value, $version = 1 ) {
		$normalized = 'email' === $field ? strtolower( trim( (string) $value ) ) : ( 'phone' === $field ? preg_replace( '/\D+/', '', (string) $value ) : trim( (string) $value ) );
		return hash_hmac( 'sha256', 'brounhall:appointment:' . $field . ':v' . absint( $version ) . '|' . $normalized, $this->index_key( $field, $version ) );
	}
}

function brounhall_appointment_key_provider() {
	static $provider;
	if ( ! $provider ) { $provider = new Brounhall_Appointment_Env_Key_Provider(); }
	return $provider;
}
