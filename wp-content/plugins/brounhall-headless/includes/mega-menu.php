<?php
/**
 * Top-level mega-menu configuration for primary navigation items.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_mega_menu_field_group' );
add_filter( 'acf/validate_value/key=field_brounhall_mega_menu_enable', 'brounhall_validate_mega_menu_scope', 10, 4 );
add_filter( 'acf/validate_value/key=field_brounhall_mega_menu_layout', 'brounhall_validate_mega_menu_layout', 10, 4 );
add_filter( 'acf/update_value/key=field_brounhall_mega_menu_enable', 'brounhall_sanitize_mega_menu_enable', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_mega_menu_layout', 'brounhall_sanitize_mega_menu_layout', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_mega_menu_featured_image', 'brounhall_sanitize_mega_menu_image', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_mega_menu_featured_label', 'brounhall_sanitize_mega_menu_label', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_mega_menu_featured_link', 'brounhall_sanitize_mega_menu_link', 10, 3 );

function brounhall_register_mega_menu_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_brounhall_mega_menu',
			'title'                 => 'BrounHall Mega Menu',
			'fields'                => array(
				array(
					'key'           => 'field_brounhall_mega_menu_enable',
					'label'         => 'Enable Mega Menu',
					'name'          => 'enable_mega_menu',
					'type'          => 'true_false',
					'instructions'  => 'Enable the semantic mega-menu configuration for this top-level primary navigation item.',
					'default_value' => 0,
					'ui'            => 1,
				),
				array(
					'key'               => 'field_brounhall_mega_menu_layout',
					'label'             => 'Mega Menu Layout',
					'name'              => 'mega_menu_layout',
					'type'              => 'select',
					'instructions'      => 'Choose the approved semantic layout for this mega menu.',
					'choices'           => brounhall_get_mega_menu_layouts(),
					'allow_null'        => 1,
					'return_format'     => 'value',
					'required'          => 1,
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_brounhall_mega_menu_enable',
								'operator' => '==',
								'value'    => '1',
							),
						),
					),
				),
				brounhall_acf_image_field(
					'field_brounhall_mega_menu_featured_image',
					'featured_image',
					'Featured Image',
					array(
						'instructions'      => 'Optional image for the featured treatment panel.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_brounhall_mega_menu_enable',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					)
				),
				array(
					'key'               => 'field_brounhall_mega_menu_featured_label',
					'label'             => 'Featured Label',
					'name'              => 'featured_label',
					'type'              => 'text',
					'instructions'      => 'Short label for the featured treatment panel.',
					'maxlength'         => 200,
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_brounhall_mega_menu_enable',
								'operator' => '==',
								'value'    => '1',
							),
						),
					),
				),
				brounhall_acf_link_field(
					'field_brounhall_mega_menu_featured_link',
					'featured_link',
					'Featured Link',
					array(
						'instructions'      => 'Optional safe link for the featured treatment panel.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_brounhall_mega_menu_enable',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					)
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'nav_menu_item',
						'operator' => '==',
						'value'    => 'all',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
			'show_in_graphql'       => true,
			'graphql_field_name'    => 'megaMenu',
		)
	);
}

function brounhall_nav_menu_item_id( $post_id ) {
	if ( is_numeric( $post_id ) ) {
		return absint( $post_id );
	}

	if ( is_string( $post_id ) && preg_match( '/^nav_menu_item_(\d+)$/', $post_id, $matches ) ) {
		return absint( $matches[1] );
	}

	return 0;
}

function brounhall_is_primary_top_level_menu_item( $post_id ) {
	$item_id  = brounhall_nav_menu_item_id( $post_id );
	$locations = get_nav_menu_locations();
	$primary  = isset( $locations['primary-navigation'] ) ? (int) $locations['primary-navigation'] : 0;
	$menus    = wp_get_object_terms( $item_id, 'nav_menu', array( 'fields' => 'ids' ) );

	return $item_id > 0
		&& 'nav_menu_item' === get_post_type( $item_id )
		&& 0 === absint( get_post_meta( $item_id, '_menu_item_menu_item_parent', true ) )
		&& $primary > 0
		&& ! is_wp_error( $menus )
		&& in_array( $primary, array_map( 'intval', $menus ), true );
}

function brounhall_validate_mega_menu_scope( $valid, $value, $field, $input ) {
	if ( true !== $valid || empty( $value ) ) {
		return $valid;
	}

	return brounhall_is_primary_top_level_menu_item( $field['post_id'] ?? 0 )
		? $valid
		: __( 'Mega-menu settings are only available to top-level Primary Navigation items.', 'brounhall-headless' );
}

function brounhall_validate_mega_menu_layout( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! brounhall_is_mega_menu_layout( $value ) ) {
		return __( 'Select an approved mega-menu layout.', 'brounhall-headless' );
	}

	return brounhall_is_primary_top_level_menu_item( $field['post_id'] ?? 0 )
		? $valid
		: __( 'Mega-menu settings are only available to top-level Primary Navigation items.', 'brounhall-headless' );
}

function brounhall_sanitize_mega_menu_enable( $value, $post_id, $field ) {
	return brounhall_is_primary_top_level_menu_item( $post_id ) ? (int) (bool) $value : 0;
}

function brounhall_sanitize_mega_menu_layout( $value, $post_id, $field ) {
	return brounhall_is_primary_top_level_menu_item( $post_id ) && brounhall_is_mega_menu_layout( $value ) ? $value : '';
}

function brounhall_sanitize_mega_menu_image( $value, $post_id, $field ) {
	$id = absint( $value );
	return brounhall_is_primary_top_level_menu_item( $post_id ) && $id && 'attachment' === get_post_type( $id ) ? $id : 0;
}

function brounhall_sanitize_mega_menu_label( $value, $post_id, $field ) {
	return brounhall_is_primary_top_level_menu_item( $post_id ) ? substr( sanitize_text_field( (string) $value ), 0, 200 ) : '';
}

function brounhall_sanitize_mega_menu_link( $value, $post_id, $field ) {
	return brounhall_is_primary_top_level_menu_item( $post_id ) ? brounhall_sanitize_global_cta_link( $value ) : array();
}
