<?php
/**
 * Locale-aware content helpers and an explicit Arabic migration command.
 *
 * The command is dry-run by default. It only writes when --execute=1 is
 * supplied, so local seeding and remote migration use the same safe path.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', 'brounhall_register_locale_migration_command', 20 );

function brounhall_supported_locale( $value ) {
	return in_array( (string) $value, array( 'en', 'ar' ), true ) ? (string) $value : 'en';
}

function brounhall_request_locale( $request = null ) {
	$value = $request instanceof WP_REST_Request ? $request->get_param( 'locale' ) : '';
	return brounhall_supported_locale( $value );
}

function brounhall_localized_slug( $slug, $locale = 'en' ) {
	$slug = sanitize_title( $slug );
	if ( 'ar' === brounhall_supported_locale( $locale ) && ! preg_match( '/_ar$/', $slug ) ) {
		$slug .= '_ar';
	}
	return $slug;
}

function brounhall_base_locale_slug( $slug ) {
	return preg_replace( '/_ar$/', '', sanitize_title( $slug ) );
}

function brounhall_locale_seed_status( $item ) {
	$status = isset( $item['status'] ) ? sanitize_key( $item['status'] ) : 'draft';
	return in_array( $status, array( 'draft', 'publish' ), true ) ? $status : 'draft';
}

function brounhall_register_locale_migration_command() {
	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_command( 'brounhall locale migrate', 'brounhall_locale_migrate_command' );
	}
}

function brounhall_locale_seed_file( $path ) {
	if ( ! is_string( $path ) || '' === $path || ! is_readable( $path ) ) {
		WP_CLI::error( 'A readable --file JSON seed document is required.' );
	}
	$data = json_decode( (string) file_get_contents( $path ), true );
	if ( ! is_array( $data ) ) {
		WP_CLI::error( 'The locale seed document must contain a JSON object.' );
	}
	return $data;
}

function brounhall_locale_migrate_page( $item, $execute ) {
	$slug = isset( $item['slug'] ) ? brounhall_localized_slug( $item['slug'], 'ar' ) : '';
	if ( '' === $slug || empty( $item['data'] ) || ! is_array( $item['data'] ) ) return 'skipped';
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $execute ) return $existing ? 'existing' : 'planned';
	$post_id = $existing ? $existing->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => brounhall_locale_seed_status( $item ), 'post_title' => sanitize_text_field( $item['title'] ?? $slug ), 'post_name' => $slug ) );
	if ( is_wp_error( $post_id ) ) return 'failed';
	$data = $item['data'];
	$data['version'] = 1;
	update_post_meta( $post_id, BROUNHALL_PAGE_DATA_META, wp_slash( wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
	return $existing ? 'updated' : 'created';
}

function brounhall_locale_migrate_treatment( $item, $execute ) {
	$slug = isset( $item['slug'] ) ? brounhall_localized_slug( $item['slug'], 'ar' ) : '';
	if ( '' === $slug || empty( $item['data'] ) || ! is_array( $item['data'] ) ) return 'skipped';
	$existing = get_page_by_path( $slug, OBJECT, array( 'service', 'bh_treatment' ) );
	if ( ! $execute ) return $existing ? 'existing' : 'planned';
	$post_id = $existing ? $existing->ID : wp_insert_post( array( 'post_type' => 'service', 'post_status' => brounhall_locale_seed_status( $item ), 'post_title' => sanitize_text_field( $item['title'] ?? $slug ), 'post_name' => $slug ) );
	if ( is_wp_error( $post_id ) ) return 'failed';
	update_post_meta( $post_id, BROUNHALL_ENTITY_DATA_META, wp_slash( wp_json_encode( $item['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
	return $existing ? 'updated' : 'created';
}

function brounhall_locale_migrate_doctor( $item, $execute ) {
	$slug = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
	$arabic = isset( $item['arabic'] ) && is_array( $item['arabic'] ) ? $item['arabic'] : array();
	if ( '' === $slug || empty( $arabic ) ) return 'skipped';
	$doctor = get_page_by_path( $slug, OBJECT, 'doctor' );
	if ( ! $doctor ) return 'missing';
	if ( ! $execute ) return 'planned';
	$data = json_decode( (string) get_post_meta( $doctor->ID, BROUNHALL_ENTITY_DATA_META, true ), true );
	$data = is_array( $data ) ? $data : array();
	$data['locales']['ar'] = $arabic;
	update_post_meta( $doctor->ID, BROUNHALL_ENTITY_DATA_META, wp_slash( wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
	return 'updated';
}

function brounhall_locale_migrate_command( $args, $assoc_args ) {
	$data = brounhall_locale_seed_file( $assoc_args['file'] ?? '' );
	$execute = ! empty( $assoc_args['execute'] );
	$counts = array( 'pages' => array(), 'treatments' => array(), 'doctors' => array() );
	foreach ( (array) ( $data['pages'] ?? array() ) as $item ) { $result = brounhall_locale_migrate_page( $item, $execute ); $counts['pages'][ $result ] = ( $counts['pages'][ $result ] ?? 0 ) + 1; }
	foreach ( (array) ( $data['treatments'] ?? array() ) as $item ) { $result = brounhall_locale_migrate_treatment( $item, $execute ); $counts['treatments'][ $result ] = ( $counts['treatments'][ $result ] ?? 0 ) + 1; }
	foreach ( (array) ( $data['doctors'] ?? array() ) as $item ) { $result = brounhall_locale_migrate_doctor( $item, $execute ); $counts['doctors'][ $result ] = ( $counts['doctors'][ $result ] ?? 0 ) + 1; }
	WP_CLI::success( ( $execute ? 'Arabic migration completed. ' : 'Dry run only. Nothing was written. ' ) . wp_json_encode( $counts ) );
}
