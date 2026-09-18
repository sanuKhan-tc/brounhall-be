<?php
/**
 * Reusable ACF Free field definitions.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build a reusable BrounHall image field definition.
 *
 * @param string               $key   Deterministic ACF field key.
 * @param string               $name  Deterministic field name.
 * @param string               $label Editorial label.
 * @param array<string, mixed> $args  Safe presentation/configuration overrides.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_acf_image_field( $key, $name, $label, $args = array() ) {
	return brounhall_acf_field_definition(
		$key,
		$name,
		$label,
		array(
			'type'          => 'image',
			'return_format' => 'id',
			'required'      => false,
			'preview_size'  => 'medium',
			'library'       => 'all',
		),
		$args,
		array( 'required', 'instructions', 'preview_size', 'conditional_logic', 'wrapper' )
	);
}

/**
 * Build a reusable BrounHall public link field definition.
 *
 * @param string               $key   Deterministic ACF field key.
 * @param string               $name  Deterministic field name.
 * @param string               $label Editorial label.
 * @param array<string, mixed> $args  Safe presentation/configuration overrides.
 * @return array<string, mixed>|WP_Error
 */
function brounhall_acf_link_field( $key, $name, $label, $args = array() ) {
	return brounhall_acf_field_definition(
		$key,
		$name,
		$label,
		array(
			'type'          => 'link',
			'return_format' => 'array',
			'required'      => false,
		),
		$args,
		array( 'required', 'instructions', 'conditional_logic', 'wrapper' )
	);
}

/**
 * Build and validate a stable field definition.
 *
 * @param mixed                $key
 * @param mixed                $name
 * @param mixed                $label
 * @param array<string, mixed> $defaults
 * @param mixed                $args
 * @param string[]             $allowed_overrides
 * @return array<string, mixed>|WP_Error
 */
function brounhall_acf_field_definition( $key, $name, $label, $defaults, $args, $allowed_overrides ) {
	if ( ! is_string( $key ) || ! preg_match( '/^field_brounhall_[a-z0-9_]+$/', $key ) ) {
		return new WP_Error( 'brounhall_invalid_field_key', 'BrounHall field keys must use the field_brounhall_ namespace.' );
	}

	if ( ! is_string( $name ) || ! preg_match( '/^brounhall_[a-z0-9_]+$/', $name ) ) {
		return new WP_Error( 'brounhall_invalid_field_name', 'BrounHall field names must use the brounhall_ namespace.' );
	}

	if ( ! is_string( $label ) || '' === trim( $label ) || sanitize_text_field( $label ) !== trim( $label ) ) {
		return new WP_Error( 'brounhall_invalid_field_label', 'BrounHall field labels must be non-empty plain text.' );
	}

	if ( ! is_array( $args ) ) {
		return new WP_Error( 'brounhall_invalid_field_args', 'BrounHall field overrides must be an array.' );
	}

	$overrides = array();

	foreach ( $allowed_overrides as $override ) {
		if ( ! array_key_exists( $override, $args ) ) {
			continue;
		}

		switch ( $override ) {
			case 'required':
				$overrides[ $override ] = (bool) $args[ $override ];
				break;
			case 'instructions':
				if ( is_scalar( $args[ $override ] ) ) {
					$overrides[ $override ] = sanitize_text_field( (string) $args[ $override ] );
				}
				break;
			case 'preview_size':
				if ( in_array( $args[ $override ], array( 'thumbnail', 'medium', 'large', 'full' ), true ) ) {
					$overrides[ $override ] = $args[ $override ];
				}
				break;
			case 'conditional_logic':
			case 'wrapper':
				if ( is_array( $args[ $override ] ) ) {
					$overrides[ $override ] = $args[ $override ];
				}
				break;
		}
	}

	$definition = array_merge( $defaults, $overrides );
	$definition['key']   = $key;
	$definition['name']  = $name;
	$definition['label'] = $label;
	$definition['type']  = $defaults['type'];

	if ( isset( $defaults['return_format'] ) ) {
		$definition['return_format'] = $defaults['return_format'];
	}

	return $definition;
}
