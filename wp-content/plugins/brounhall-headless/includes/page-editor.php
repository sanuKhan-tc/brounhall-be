<?php
/**
 * JSON-backed editor for native marketing Pages.
 *
 * The legacy YAML post content is intentionally retained as a fallback until
 * the frontend consumes this meta value.
 */

defined( 'ABSPATH' ) || exit;

const BROUNHALL_PAGE_DATA_META = '_brounhall_page_data';

add_action( 'add_meta_boxes_page', 'brounhall_add_page_editor' );
add_action( 'add_meta_boxes_page', 'brounhall_hide_legacy_page_editor', 20 );
add_action( 'save_post_page', 'brounhall_save_page_editor', 10, 2 );
add_action( 'admin_enqueue_scripts', 'brounhall_enqueue_page_editor_assets' );
add_action( 'plugins_loaded', 'brounhall_register_page_migration_command' );

function brounhall_add_page_editor() {
	add_meta_box(
		'brounhall-page-editor',
		__( 'Bourn Hall Page Content', 'brounhall-headless' ),
		'brounhall_render_page_editor',
		'page',
		'normal',
		'high'
	);
}

function brounhall_hide_legacy_page_editor() {
	remove_meta_box( 'postdivrich', 'page', 'normal' );
}

function brounhall_enqueue_page_editor_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'page' !== get_current_screen()->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_style( 'dashicons', '.brounhall-section{border:1px solid #c3c4c7;margin:0 0 16px;background:#fff}.brounhall-section>h3{margin:0;padding:12px 14px;background:#f0f6fc;border-bottom:1px solid #c3c4c7}.brounhall-field{padding:10px 14px}.brounhall-field label{display:block;font-weight:600;margin-bottom:5px}.brounhall-field input[type=text],.brounhall-field input[type=url],.brounhall-field textarea{width:100%;max-width:760px}.brounhall-field textarea{min-height:90px}.brounhall-media{display:flex;gap:8px;align-items:center}.brounhall-media input{max-width:140px}.brounhall-nested{margin:8px 0 0 14px;padding-left:14px;border-left:3px solid #dcdcde}.brounhall-legacy{padding:10px 12px;background:#fff8e5;border-left:4px solid #dba617}');
	$script = <<<'JS'
jQuery(function($){$('.brounhall-media-button').on('click',function(e){e.preventDefault();var input=$('#'+$(this).data('target'));var frame=wp.media({title:'Select image',button:{text:'Use image'},library:{type:'image'},multiple:false});frame.on('select',function(){input.val(frame.state().get('selection').first().id);});frame.open();});});
JS;
	wp_add_inline_script( 'jquery-core', $script );
}

function brounhall_render_page_editor( $post ) {
	$data = get_post_meta( $post->ID, BROUNHALL_PAGE_DATA_META, true );
	$data = is_string( $data ) ? json_decode( $data, true ) : $data;

	wp_nonce_field( 'brounhall_page_editor', 'brounhall_page_editor_nonce' );

	if ( ! is_array( $data ) || empty( $data['sections'] ) ) {
		echo '<p class="brounhall-legacy">This page still uses the legacy YAML content. Run <code>wp brounhall pages migrate</code> once to populate the new editor.</p>';
		return;
	}

	echo '<p>Each highlighted panel is one page section. Text, descriptions, links, and Media Library IDs are stored as validated JSON.</p>';
	foreach ( $data['sections'] as $index => $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$title = isset( $section['id'] ) ? (string) $section['id'] : 'Section ' . ( $index + 1 );
		echo '<div class="brounhall-section"><h3>' . esc_html( $title ) . '</h3><div class="brounhall-nested">';
		foreach ( $section as $key => $value ) {
			brounhall_render_page_editor_value( $value, array( 'sections', $index, $key ), $key );
		}
		echo '</div></div>';
	}
}

function brounhall_render_page_editor_value( $value, $path, $label ) {
	if ( is_array( $value ) ) {
		if ( array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
			foreach ( $value as $index => $item ) {
				echo '<fieldset class="brounhall-nested"><legend>' . esc_html( ucwords( str_replace( '_', ' ', $label ) ) . ' ' . ( $index + 1 ) ) . '</legend>';
				brounhall_render_page_editor_value( $item, array_merge( $path, array( $index ) ), $label );
				echo '</fieldset>';
			}
			return;
		}

		echo '<div class="brounhall-nested"><strong>' . esc_html( ucwords( str_replace( '_', ' ', $label ) ) ) . '</strong>';
		foreach ( $value as $key => $item ) {
			brounhall_render_page_editor_value( $item, array_merge( $path, array( $key ) ), $key );
		}
		echo '</div>';
		return;
	}

	$name = 'brounhall_page_data';
	foreach ( $path as $part ) {
		$name .= '[' . $part . ']';
	}
	$input_id = 'brounhall-' . md5( $name );
	$label_text = ucwords( str_replace( '_', ' ', $label ) );
	$type = brounhall_page_editor_field_type( $label, $value );

	echo '<div class="brounhall-field"><label for="' . esc_attr( $input_id ) . '">' . esc_html( $label_text ) . '</label>';
	if ( 'image' === $type ) {
		echo '<div class="brounhall-media"><input id="' . esc_attr( $input_id ) . '" type="number" min="0" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"><button type="button" class="button brounhall-media-button" data-target="' . esc_attr( $input_id ) . '">Choose image</button></div>';
	} elseif ( 'textarea' === $type ) {
		echo '<textarea id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
	} elseif ( 'boolean' === $type ) {
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0"><label><input id="' . esc_attr( $input_id ) . '" type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( $value, true, false ) . '> Enabled</label>';
	} else {
		echo '<input id="' . esc_attr( $input_id ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
	}
	echo '</div>';
}

function brounhall_page_editor_field_type( $key, $value ) {
	$key = strtolower( (string) $key );
	if ( preg_match( '/(^|_)(image|image_id|poster_image_id|logo_image_id)$/', $key ) || preg_match( '/(^|_)image_id$/', $key ) ) {
		return 'image';
	}
	if ( is_bool( $value ) ) {
		return 'boolean';
	}
	if ( preg_match( '/description|body|paragraph|note|introduction|content|text|answer|caption/', $key ) ) {
		return 'textarea';
	}
	if ( preg_match( '/href|url|link/', $key ) ) {
		return 'url';
	}
	return 'text';
}

function brounhall_save_page_editor( $post_id, $post ) {
	if ( ! isset( $_POST['brounhall_page_editor_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_page_editor_nonce'] ) ), 'brounhall_page_editor' ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['brounhall_page_data'] ) ) {
		return;
	}

	$data = brounhall_sanitize_page_editor_value( wp_unslash( $_POST['brounhall_page_data'] ) );
	if ( is_array( $data ) && isset( $data['sections'] ) && is_array( $data['sections'] ) ) {
		$data['version'] = 1;
		update_post_meta( $post_id, BROUNHALL_PAGE_DATA_META, wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
}

function brounhall_sanitize_page_editor_value( $value, $key = '' ) {
	if ( is_array( $value ) ) {
		$clean = array();
		foreach ( $value as $child_key => $child_value ) {
			$clean[ sanitize_key( (string) $child_key ) === (string) $child_key ? $child_key : sanitize_key( (string) $child_key ) ] = brounhall_sanitize_page_editor_value( $child_value, $child_key );
		}
		return $clean;
	}
	if ( preg_match( '/(^|_)(image_id|poster_image_id|logo_image_id)$/i', (string) $key ) ) {
		$id = absint( $value );
		return $id && wp_attachment_is_image( $id ) ? $id : 0;
	}
	if ( preg_match( '/href|url|link/i', (string) $key ) ) {
		return esc_url_raw( (string) $value );
	}
	if ( preg_match( '/description|body|paragraph|note|introduction|content|text|answer|caption/i', (string) $key ) ) {
		return sanitize_textarea_field( (string) $value );
	}
	if ( 'enabled' === strtolower( (string) $key ) ) {
		return (bool) $value;
	}
	return sanitize_text_field( (string) $value );
}

/**
 * Small YAML reader for the existing page contract. It intentionally handles
 * only the map/list/scalar/block-string subset already used by these Pages.
 */
function brounhall_page_yaml_to_array( $source ) {
	$source = html_entity_decode( preg_replace( '/<[^>]+>/', '', (string) $source ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$source = preg_replace( '/^\s*<!--.*?-->\s*$/m', '', $source );
	$lines = preg_split( '/\r?\n/', $source );
	$tokens = array();
	foreach ( $lines as $line ) {
		if ( '' === trim( $line ) || preg_match( '/^\s*#/', $line ) ) {
			continue;
		}
		preg_match( '/^(\s*)(.*)$/', $line, $match );
		$tokens[] = array( strlen( $match[1] ), $match[2] );
	}
	$index = 0;
	return brounhall_page_yaml_parse_node( $tokens, $index, $tokens[0][0] ?? 0 );
}

function brounhall_page_yaml_parse_node( $tokens, &$index, $indent ) {
	$is_list = isset( $tokens[ $index ] ) && $tokens[ $index ][0] === $indent && 0 === strpos( $tokens[ $index ][1], '- ' );
	$result = array();
	while ( isset( $tokens[ $index ] ) && $tokens[ $index ][0] === $indent ) {
		$text = $tokens[ $index ][1];
		if ( $is_list ) {
			if ( 0 !== strpos( $text, '- ' ) ) break;
			$rest = trim( substr( $text, 2 ) );
			$index++;
			if ( preg_match( '/^([^:]+):\s*(.*)$/', $rest, $match ) ) {
				$item = array( trim( $match[1] ) => brounhall_page_yaml_scalar( $match[2] ) );
				while ( isset( $tokens[ $index ] ) && $tokens[ $index ][0] > $indent ) {
					$child = brounhall_page_yaml_parse_node( $tokens, $index, $tokens[ $index ][0] );
					$item = array_merge( $item, is_array( $child ) ? $child : array() );
				}
				$result[] = $item;
			} else {
				$result[] = brounhall_page_yaml_scalar( $rest );
			}
			continue;
		}
		if ( ! preg_match( '/^([^:]+):\s*(.*)$/', $text, $match ) ) { $index++; continue; }
		$key = trim( $match[1] );
		$value = trim( $match[2] );
		$index++;
		if ( '>' === $value || '|' === $value ) {
			$parts = array();
			while ( isset( $tokens[ $index ] ) && $tokens[ $index ][0] > $indent ) $parts[] = trim( $tokens[ $index++ ][1] );
			$result[ $key ] = '>' === $value ? implode( ' ', $parts ) : implode( "\n", $parts );
		} elseif ( isset( $tokens[ $index ] ) && $tokens[ $index ][0] > $indent ) {
			$result[ $key ] = brounhall_page_yaml_parse_node( $tokens, $index, $tokens[ $index ][0] );
		} else {
			$result[ $key ] = brounhall_page_yaml_scalar( $value );
		}
	}
	return $result;
}

function brounhall_page_yaml_scalar( $value ) {
	$value = trim( $value );
	if ( 'true' === strtolower( $value ) ) return true;
	if ( 'false' === strtolower( $value ) ) return false;
	if ( 'null' === strtolower( $value ) || '~' === $value ) return null;
	if ( is_numeric( $value ) ) return false !== strpos( $value, '.' ) ? (float) $value : (int) $value;
	if ( strlen( $value ) >= 2 && ( ( '"' === $value[0] && '"' === substr( $value, -1 ) ) || ( "'" === $value[0] && "'" === substr( $value, -1 ) ) ) ) $value = substr( $value, 1, -1 );
	return str_replace( array( '\\"', '\\n' ), array( '"', "\n" ), $value );
}

function brounhall_register_page_migration_command() {
	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_command( 'brounhall pages migrate', 'brounhall_migrate_pages_command' );
	}
}

function brounhall_migrate_pages_command( $args, $assoc_args ) {
	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1 ) );
	$count = 0;
	foreach ( $pages as $page ) {
		if ( ! empty( get_post_meta( $page->ID, BROUNHALL_PAGE_DATA_META, true ) ) && empty( $assoc_args['force'] ) ) continue;
		$data = brounhall_page_yaml_to_array( $page->post_content );
		if ( ! is_array( $data ) || empty( $data['sections'] ) || ! is_array( $data['sections'] ) ) continue;
		$data['version'] = 1;
		update_post_meta( $page->ID, BROUNHALL_PAGE_DATA_META, wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		$count++;
	}
	WP_CLI::success( sprintf( 'Migrated %d pages to %s.', $count, BROUNHALL_PAGE_DATA_META ) );
}
