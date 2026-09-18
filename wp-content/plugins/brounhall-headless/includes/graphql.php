<?php
/**
 * Public GraphQL fields for BrounHall global settings.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'graphql_register_types', 'brounhall_register_global_settings_graphql' );

function brounhall_register_global_settings_graphql() {
	register_graphql_object_type(
		'BrounHallGlobalSettings',
		array(
			'description' => __( 'Public BrounHall global settings.', 'brounhall-headless' ),
			'fields'      => array(
				'publicContactPhone'  => array( 'type' => 'String' ),
				'publicContactEmail'  => array( 'type' => 'String' ),
				'publicContactAddress' => array( 'type' => 'String' ),
				'footerCopyrightText' => array( 'type' => 'String' ),
				'successRate'         => array( 'type' => 'String' ),
				'liveBirths'          => array( 'type' => 'String' ),
				'yearsOfTrustedCare'  => array( 'type' => 'String' ),
			),
		)
	);

	register_graphql_field(
		'RootQuery',
		'brounhallGlobalSettings',
		array(
			'type'        => 'BrounHallGlobalSettings',
			'description' => __( 'Public BrounHall global settings and organization statistics.', 'brounhall-headless' ),
			'resolve'     => 'brounhall_resolve_global_settings_graphql',
		)
	);
}

function brounhall_resolve_global_settings_graphql() {
	$settings = brounhall_get_global_settings();

	return array(
		'publicContactPhone'   => $settings['public_contact_phone'],
		'publicContactEmail'   => $settings['public_contact_email'],
		'publicContactAddress' => $settings['public_contact_address'],
		'footerCopyrightText'  => $settings['footer_copyright_text'],
		'successRate'          => $settings['success_rate'],
		'liveBirths'           => $settings['live_births'],
		'yearsOfTrustedCare'   => $settings['years_of_trusted_care'],
	);
}
