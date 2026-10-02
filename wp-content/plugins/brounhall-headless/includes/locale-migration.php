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
		WP_CLI::add_command( 'brounhall content migrate', 'brounhall_content_migrate_command' );
	}
}

function brounhall_content_migrate_english( $execute ) {
	$counts = array( 'updated' => 0, 'unchanged' => 0, 'skipped' => 0 );
	$pages  = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => -1 ) );
	foreach ( $pages as $page ) {
		if ( '' === trim( (string) $page->post_content ) ) {
			$counts['skipped']++;
			continue;
		}
		$data = brounhall_page_yaml_to_array( $page->post_content );
		if ( ! is_array( $data ) || empty( $data['sections'] ) || ! is_array( $data['sections'] ) ) {
			$counts['skipped']++;
			continue;
		}
		$data['version'] = 1;
		$next            = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$current         = (string) get_post_meta( $page->ID, BROUNHALL_PAGE_DATA_META, true );
		if ( $next === wp_unslash( $current ) ) {
			$counts['unchanged']++;
			continue;
		}
		if ( $execute ) {
			update_post_meta( $page->ID, BROUNHALL_PAGE_DATA_META, wp_slash( $next ) );
		}
		$counts['updated']++;
	}
	return $counts;
}

function brounhall_content_temporary_placeholders( $value, $locale ) {
	$placeholder = 'ar' === $locale
		? 'لوريم إيبسوم مؤقت إلى حين اعتماد المحتوى التحريري.'
		: 'Lorem ipsum placeholder content pending approved editorial copy.';
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $child ) {
			$value[ $key ] = brounhall_content_temporary_placeholders( $child, $locale );
		}
		return $value;
	}
	return '>' === $value ? $placeholder : $value;
}

function brounhall_content_migrate_command( $args, $assoc_args ) {
	$locale  = $assoc_args['locale'] ?? 'all';
	$locale  = 'all' === $locale ? 'all' : brounhall_supported_locale( $locale );
	$execute = ! empty( $assoc_args['execute'] );
	$temporary_placeholders = ! empty( $assoc_args['temporary-placeholders'] );
	$counts  = array();

	if ( in_array( $locale, array( 'en', 'all' ), true ) ) {
		$counts['en_pages'] = brounhall_content_migrate_english( $execute );
	}

	if ( in_array( $locale, array( 'ar', 'all' ), true ) ) {
		$file = $assoc_args['file'] ?? '';
		if ( '' === $file || ! is_readable( $file ) ) {
			WP_CLI::error( 'Arabic migration requires a readable --file JSON seed document.' );
		}
		$data = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'The content seed document must contain a JSON object.' );
		}
		$counts['ar'] = array( 'pages' => array(), 'treatments' => array(), 'doctors' => array(), 'faqs' => array() );
		foreach ( (array) ( $data['pages'] ?? array() ) as $item ) {
			if ( $temporary_placeholders ) {
				$item['data'] = brounhall_content_temporary_placeholders( $item['data'] ?? array(), 'ar' );
			}
			$result = brounhall_locale_migrate_page( $item, $execute );
			$counts['ar']['pages'][ $result ] = ( $counts['ar']['pages'][ $result ] ?? 0 ) + 1;
		}
		foreach ( (array) ( $data['treatments'] ?? array() ) as $item ) {
			if ( $temporary_placeholders ) {
				$item['data'] = brounhall_content_temporary_placeholders( $item['data'] ?? array(), 'ar' );
			}
			$result = brounhall_locale_migrate_treatment( $item, $execute );
			$counts['ar']['treatments'][ $result ] = ( $counts['ar']['treatments'][ $result ] ?? 0 ) + 1;
		}
		foreach ( (array) ( $data['doctors'] ?? array() ) as $item ) {
			$result = brounhall_locale_migrate_doctor( $item, $execute );
			$counts['ar']['doctors'][ $result ] = ( $counts['ar']['doctors'][ $result ] ?? 0 ) + 1;
		}
		foreach ( (array) ( $data['faqs'] ?? array() ) as $item ) {
			$result = brounhall_locale_migrate_faq( $item, $execute );
			$counts['ar']['faqs'][ $result ] = ( $counts['ar']['faqs'][ $result ] ?? 0 ) + 1;
		}
	}

	WP_CLI::success( ( $execute ? 'Content migration completed. ' : 'Dry run only. Nothing was written. ' ) . wp_json_encode( $counts ) );
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
	$doctor = get_page_by_path( $slug, OBJECT, array( 'doctor', 'bh_doctor' ) );
	if ( ! $doctor ) return 'missing';
	if ( ! $execute ) return 'planned';
	$data = json_decode( (string) get_post_meta( $doctor->ID, BROUNHALL_ENTITY_DATA_META, true ), true );
	$data = is_array( $data ) ? $data : array();
	$data['locales']['ar'] = $arabic;
	update_post_meta( $doctor->ID, BROUNHALL_ENTITY_DATA_META, wp_slash( wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
	return 'updated';
}

function brounhall_locale_migrate_faq( $item, $execute ) {
	$slug = isset( $item['slug'] ) ? brounhall_localized_slug( $item['slug'], 'ar' ) : '';
	$title = sanitize_text_field( $item['title'] ?? '' );
	$answer = sanitize_textarea_field( $item['answer'] ?? '' );
	if ( '' === $slug || '' === $title || '' === $answer ) return 'skipped';
	$existing = get_page_by_path( $slug, OBJECT, 'faq' );
	if ( ! $execute ) return $existing ? 'existing' : 'planned';
	$post_id = $existing ? $existing->ID : wp_insert_post( array( 'post_type' => 'faq', 'post_status' => brounhall_locale_seed_status( $item ), 'post_title' => $title, 'post_name' => $slug ) );
	if ( is_wp_error( $post_id ) ) return 'failed';
	wp_update_post( array( 'ID' => $post_id, 'post_status' => brounhall_locale_seed_status( $item ), 'post_title' => $title, 'post_name' => $slug ) );
	update_post_meta( $post_id, 'faq_answer', $answer );
	return $existing ? 'updated' : 'created';
}

function brounhall_locale_clear_blogs( $execute ) {
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_key' => '_brounhall_blog_data' ) );
	if ( ! $execute ) return count( $posts );
	foreach ( $posts as $post ) wp_delete_post( $post->ID, true );
	return count( $posts );
}

function brounhall_locale_migrate_blog( $item, $execute ) {
	$locale = brounhall_supported_locale( $item['locale'] ?? 'en' );
	$slug   = isset( $item['slug'] ) ? brounhall_localized_slug( $item['slug'], $locale ) : '';
	$data   = isset( $item['data'] ) && is_array( $item['data'] ) ? $item['data'] : array();
	if ( '' === $slug || empty( $data ) ) return 'skipped';
	$existing = get_page_by_path( $slug, OBJECT, 'post' );
	if ( ! $execute ) return $existing ? 'existing' : 'planned';
	$post_id = $existing ? $existing->ID : wp_insert_post( array( 'post_type' => 'post', 'post_status' => brounhall_locale_seed_status( $item ), 'post_title' => sanitize_text_field( $item['title'] ?? $data['title'] ?? $slug ), 'post_excerpt' => sanitize_textarea_field( $data['excerpt'] ?? '' ), 'post_name' => $slug, 'post_content' => sanitize_textarea_field( $data['excerpt'] ?? '' ) ) );
	if ( is_wp_error( $post_id ) ) return 'failed';
	wp_update_post( array( 'ID' => $post_id, 'post_status' => brounhall_locale_seed_status( $item ), 'post_title' => sanitize_text_field( $item['title'] ?? $data['title'] ?? $slug ), 'post_excerpt' => sanitize_textarea_field( $data['excerpt'] ?? '' ), 'post_name' => $slug ) );
	update_post_meta( $post_id, '_brounhall_blog_data', wp_slash( wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
	return $existing ? 'updated' : 'created';
}

function brounhall_locale_migrate_command( $args, $assoc_args ) {
	$data = brounhall_locale_seed_file( $assoc_args['file'] ?? '' );
	$execute = ! empty( $assoc_args['execute'] );
	$counts = array( 'pages' => array(), 'treatments' => array(), 'doctors' => array(), 'faqs' => array(), 'blogs' => array() );
	if ( ! empty( $assoc_args['replace'] ) && ! empty( $data['blogs'] ) ) $counts['blogs']['cleared'] = brounhall_locale_clear_blogs( $execute );
	foreach ( (array) ( $data['pages'] ?? array() ) as $item ) { $result = brounhall_locale_migrate_page( $item, $execute ); $counts['pages'][ $result ] = ( $counts['pages'][ $result ] ?? 0 ) + 1; }
	foreach ( (array) ( $data['treatments'] ?? array() ) as $item ) { $result = brounhall_locale_migrate_treatment( $item, $execute ); $counts['treatments'][ $result ] = ( $counts['treatments'][ $result ] ?? 0 ) + 1; }
	foreach ( (array) ( $data['doctors'] ?? array() ) as $item ) { $result = brounhall_locale_migrate_doctor( $item, $execute ); $counts['doctors'][ $result ] = ( $counts['doctors'][ $result ] ?? 0 ) + 1; }
	foreach ( (array) ( $data['faqs'] ?? array() ) as $item ) { $result = brounhall_locale_migrate_faq( $item, $execute ); $counts['faqs'][ $result ] = ( $counts['faqs'][ $result ] ?? 0 ) + 1; }
	foreach ( (array) ( $data['blogs'] ?? array() ) as $item ) { $result = brounhall_locale_migrate_blog( $item, $execute ); $counts['blogs'][ $result ] = ( $counts['blogs'][ $result ] ?? 0 ) + 1; }
	WP_CLI::success( ( $execute ? 'Arabic migration completed. ' : 'Dry run only. Nothing was written. ' ) . wp_json_encode( $counts ) );
}
