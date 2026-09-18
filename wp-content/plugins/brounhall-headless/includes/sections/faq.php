<?php
/**
 * BrounHall FAQ Item and FAQ section field groups.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'brounhall_register_faq_field_groups' );

add_filter( 'acf/validate_value/key=field_brounhall_faq_answer', 'brounhall_validate_faq_plain_text', 10, 4 );
add_filter( 'acf/update_value/key=field_brounhall_faq_answer', 'brounhall_sanitize_faq_answer', 10, 3 );
add_filter( 'acf/validate_value/key=field_brounhall_faq_title', 'brounhall_validate_faq_plain_text', 10, 4 );
add_filter( 'acf/validate_value/key=field_brounhall_faq_description', 'brounhall_validate_faq_plain_text', 10, 4 );
add_filter( 'acf/validate_value/key=field_brounhall_faq_help_text', 'brounhall_validate_faq_plain_text', 10, 4 );
add_filter( 'acf/validate_value/key=field_brounhall_faq_items', 'brounhall_validate_faq_items', 10, 4 );
add_filter( 'acf/update_value/key=field_brounhall_faq_title', 'brounhall_sanitize_faq_text', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_faq_description', 'brounhall_sanitize_faq_textarea', 10, 3 );
add_filter( 'acf/update_value/key=field_brounhall_faq_help_text', 'brounhall_sanitize_faq_textarea', 10, 3 );

function brounhall_register_faq_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                => 'group_brounhall_faq_item',
			'title'              => 'BrounHall FAQ Item',
			'fields'             => array(
				array(
					'key'          => 'field_brounhall_faq_answer',
					'label'        => 'Answer',
					'name'         => 'faq_answer',
					'type'         => 'textarea',
					'instructions' => 'Plain-text answer for this FAQ question.',
					'maxlength'    => 5000,
					'rows'         => 8,
				),
			),
			'location'           => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'faq',
					),
				),
			),
			'show_in_graphql'    => true,
			'graphql_field_name' => 'faqDetails',
		)
	);

	acf_add_local_field_group(
		array(
			'key'                                   => 'group_brounhall_faq',
			'title'                                 => 'BrounHall FAQ',
			'fields'                                => brounhall_faq_fields(),
			'location'                              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'page',
					),
				),
			),
			'show_in_graphql'                       => true,
			'graphql_field_name'                    => 'faqSection',
			'map_graphql_types_from_location_rules' => true,
		)
	);
}

function brounhall_faq_fields() {
	return array(
		array(
			'key'          => 'field_brounhall_faq_title',
			'label'        => 'Title',
			'name'         => 'faq_title',
			'type'         => 'text',
			'instructions' => 'Optional heading for this FAQ section.',
			'maxlength'    => 200,
		),
		array(
			'key'          => 'field_brounhall_faq_description',
			'label'        => 'Description',
			'name'         => 'faq_description',
			'type'         => 'textarea',
			'instructions' => 'Optional plain-text introduction for this FAQ section.',
			'maxlength'    => 1000,
			'rows'         => 5,
		),
		array(
			'key'          => 'field_brounhall_faq_help_text',
			'label'        => 'Help Text',
			'name'         => 'faq_help_text',
			'type'         => 'textarea',
			'instructions' => 'Optional plain-text help copy shown with the FAQ contact prompt.',
			'maxlength'    => 1000,
			'rows'         => 5,
		),
		brounhall_acf_link_field(
			'field_brounhall_faq_cta',
			'faq_cta',
			'FAQ Contact Link',
			array( 'instructions' => 'Optional contact link shown with the FAQ help copy.' )
		),
		array(
			'key'           => 'field_brounhall_faq_items',
			'label'         => 'FAQ Items',
			'name'          => 'faq_items',
			'type'          => 'relationship',
			'instructions'  => 'Select and order the questions shown in this section.',
			'post_type'     => array( 'faq' ),
			'filters'       => array( 'search', 'post_type' ),
			'return_format' => 'object',
			'min'           => 0,
		),
	);
}

function brounhall_validate_faq_plain_text( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}

	if ( ! is_string( $value ) || wp_strip_all_tags( $value ) !== $value ) {
		return __( 'This field accepts plain text only.', 'brounhall-headless' );
	}

	return $valid;
}

function brounhall_validate_faq_items( $valid, $value, $field, $input ) {
	if ( true !== $valid || empty( $value ) ) {
		return $valid;
	}

	$items = is_array( $value ) ? $value : array( $value );

	foreach ( $items as $item ) {
		$id = is_object( $item ) && isset( $item->ID ) ? $item->ID : $item;

		if ( 'faq' !== get_post_type( absint( $id ) ) ) {
			return __( 'Only FAQ Items can be selected.', 'brounhall-headless' );
		}
	}

	return $valid;
}

function brounhall_sanitize_faq_answer( $value, $post_id, $field ) {
	return is_string( $value ) ? substr( sanitize_textarea_field( $value ), 0, 5000 ) : '';
}

function brounhall_sanitize_faq_text( $value, $post_id, $field ) {
	return is_string( $value ) ? substr( sanitize_text_field( $value ), 0, 200 ) : '';
}

function brounhall_sanitize_faq_textarea( $value, $post_id, $field ) {
	return is_string( $value ) ? substr( sanitize_textarea_field( $value ), 0, 1000 ) : '';
}
