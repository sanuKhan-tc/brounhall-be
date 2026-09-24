<?php
/**
 * JSON-backed editor for native marketing Pages.
 *
 * The legacy YAML post content is intentionally retained as a fallback until
 * the frontend consumes this meta value.
 */

defined( 'ABSPATH' ) || exit;

const BROUNHALL_PAGE_DATA_META = '_brounhall_page_data';
const BROUNHALL_ENTITY_DATA_META = '_brounhall_entity_data';

add_action( 'add_meta_boxes_page', 'brounhall_add_page_editor' );
add_action( 'add_meta_boxes_page', 'brounhall_hide_legacy_page_editor', 20 );
add_action( 'save_post_page', 'brounhall_save_page_editor', 10, 2 );
add_action( 'admin_enqueue_scripts', 'brounhall_enqueue_page_editor_assets' );
add_action( 'plugins_loaded', 'brounhall_register_page_migration_command' );
add_action( 'rest_api_init', 'brounhall_register_page_data_rest_field' );
add_action( 'add_meta_boxes', 'brounhall_add_entity_editor' );
add_action( 'save_post', 'brounhall_save_entity_editor', 10, 2 );
add_action( 'admin_menu', 'brounhall_hide_empty_canonical_entity_menus', 999 );

function brounhall_register_page_data_rest_field() {
	register_rest_field(
		'page',
		'brounhallPageData',
		array(
			'get_callback' => function ( $page ) {
				$data = json_decode( (string) get_post_meta( $page['id'], BROUNHALL_PAGE_DATA_META, true ), true );
				return is_array( $data ) ? $data : null;
			},
			'schema'       => array(
				'description' => 'Validated Bourn Hall page section data.',
				'type'        => 'object',
				'context'     => array( 'view' ),
			),
		)
	);
}

function brounhall_hide_empty_canonical_entity_menus() {
	$aliases = array(
		'doctor'   => 'bh_doctor',
		'service'  => 'bh_treatment',
		'location' => 'bh_clinic',
		'faq'      => 'bh_faq',
	);
	foreach ( $aliases as $canonical => $legacy ) {
		if ( post_type_exists( $legacy ) && ! get_posts( array( 'post_type' => $canonical, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) && get_posts( array( 'post_type' => $legacy, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
			remove_menu_page( 'edit.php?post_type=' . $canonical );
		}
	}
}

function brounhall_entity_post_types() {
	return array( 'doctor', 'service', 'location', 'faq', 'bh_doctor', 'bh_treatment', 'bh_clinic', 'bh_faq' );
}

function brounhall_entity_source_meta_key( $post_type ) {
	return array(
		'doctor'       => '_brounhall_legacy_data',
		'service'      => '_brounhall_legacy_data',
		'location'     => '_brounhall_legacy_data',
		'faq'          => '_brounhall_legacy_data',
		'bh_doctor'    => '_brounhall_doctor_data',
		'bh_treatment' => '_brounhall_treatment_data',
		'bh_clinic'   => '_brounhall_clinic_data',
		'bh_faq'      => '_brounhall_faq_data',
	)[ $post_type ] ?? '';
}

function brounhall_add_entity_editor( $post_type ) {
	if ( ! in_array( $post_type, brounhall_entity_post_types(), true ) ) {
		return;
	}

	add_meta_box( 'brounhall-entity-editor', __( 'Bourn Hall Structured Content', 'brounhall-headless' ), 'brounhall_render_entity_editor', $post_type, 'normal', 'high' );
	remove_meta_box( 'postdivrich', $post_type, 'normal' );
}

function brounhall_render_entity_editor( $post ) {
	$data = json_decode( (string) get_post_meta( $post->ID, BROUNHALL_ENTITY_DATA_META, true ), true );
	if ( ! is_array( $data ) ) {
		$data = json_decode( (string) get_post_meta( $post->ID, brounhall_entity_source_meta_key( $post->post_type ), true ), true );
	}

	wp_nonce_field( 'brounhall_entity_editor', 'brounhall_entity_editor_nonce' );
	if ( ! is_array( $data ) ) {
		echo '<p class="brounhall-legacy">No structured JSON data is available yet. Run <code>wp brounhall entities migrate</code>.</p>';
		return;
	}

	echo '<p>Fields are generated from this record’s JSON shape. Long text uses textareas and image IDs use the WordPress Media Library.</p><div class="brounhall-editor-toolbar"><input class="brounhall-editor-filter" type="search" placeholder="Search this treatment..."><button type="button" class="button brounhall-expand-all">Expand all</button><button type="button" class="button brounhall-collapse-all">Collapse all</button></div>';
	foreach ( $data as $key => $value ) {
		echo '<div class="brounhall-section"><h3><button type="button" class="brounhall-section-toggle"><span>' . esc_html( brounhall_editor_label( $key ) ) . '</span></button></h3><div class="brounhall-nested">';
		brounhall_render_page_editor_value( $value, array( $key ), $key, 'brounhall_entity_data' );
		echo '</div></div>';
	}
}

function brounhall_save_entity_editor( $post_id, $post ) {
	if ( ! in_array( $post->post_type, brounhall_entity_post_types(), true ) || ! isset( $_POST['brounhall_entity_editor_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brounhall_entity_editor_nonce'] ) ), 'brounhall_entity_editor' ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['brounhall_entity_data'] ) ) {
		return;
	}

	$data = brounhall_sanitize_page_editor_value( wp_unslash( $_POST['brounhall_entity_data'] ) );
	if ( is_array( $data ) ) {
		update_post_meta( $post_id, BROUNHALL_ENTITY_DATA_META, wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
}

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
	$screen = get_current_screen();
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || ( 'page' !== $screen->post_type && ! in_array( $screen->post_type, brounhall_entity_post_types(), true ) ) ) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_style( 'dashicons', '.brounhall-editor-toolbar{display:flex;gap:8px;align-items:center;margin:12px 0}.brounhall-editor-filter{flex:1;max-width:420px}.brounhall-section{border:1px solid #c3c4c7;margin:0 0 16px;background:#fff;box-shadow:0 1px 1px #0000000d}.brounhall-section>h3{display:flex;align-items:center;justify-content:space-between;margin:0;padding:0;background:#f0f6fc;border-bottom:1px solid #c3c4c7}.brounhall-section-toggle{display:flex;flex:1;align-items:center;justify-content:space-between;padding:14px;background:transparent;border:0;font-size:14px;font-weight:600;text-align:left;cursor:pointer}.brounhall-section-toggle:after{content:"\\f140";font:normal 20px/1 dashicons}.brounhall-section.is-collapsed .brounhall-section-toggle:after{content:"\\f142"}.brounhall-section.is-collapsed>.brounhall-nested{display:none}.brounhall-field{padding:10px 14px}.brounhall-field label{display:block;font-weight:600;margin-bottom:5px}.brounhall-field input[type=text],.brounhall-field input[type=url],.brounhall-field textarea{width:100%;max-width:760px}.brounhall-field textarea{min-height:90px}.brounhall-media{display:flex;gap:8px;align-items:center}.brounhall-media input{max-width:140px}.brounhall-nested{margin:8px 0 0 14px;padding-left:14px;border-left:3px solid #dcdcde}.brounhall-repeater{margin:10px 0}.brounhall-repeat-item{position:relative;padding:8px 32px 8px 10px;margin:8px 0;background:#f6f7f7;border:1px solid #dcdcde}.brounhall-remove-item{position:absolute;top:8px;right:8px;color:#b32d2e;border:0;background:transparent;cursor:pointer}.brounhall-add-item{margin:8px 0}.brounhall-legacy{padding:10px 12px;background:#fff8e5;border-left:4px solid #dba617}');
	$script = <<<'JS'
jQuery(function($){var root=$('#brounhall-entity-editor,#brounhall-page-editor');root.on('click','.brounhall-section-toggle',function(){ $(this).closest('.brounhall-section').toggleClass('is-collapsed'); });root.on('click','.brounhall-remove-item',function(){var list=$(this).closest('.brounhall-repeater');if(list.find('.brounhall-repeat-item').length>1)$(this).closest('.brounhall-repeat-item').remove();});root.on('click','.brounhall-add-item',function(){var list=$(this).closest('.brounhall-repeater'),items=list.find('.brounhall-repeat-item'),template=items.last().clone(),index=Number(list.attr('data-next-index')||items.length);template.find('[name]').each(function(){this.name=this.name.replace(/\[\d+\](?=\[|$)/g,'['+index+']');});template.find('[id]').each(function(){this.id=this.id+'-'+index;});template.find('input:not([type=hidden]),textarea').each(function(){if(this.type==='checkbox')this.checked=false;else this.value='';});template.insertBefore($(this));list.attr('data-next-index',index+1);});root.on('input','.brounhall-editor-filter',function(){var query=this.value.toLowerCase();root.find('.brounhall-section').each(function(){ $(this).toggle(!query||$(this).text().toLowerCase().indexOf(query)!==-1); });});root.on('click','.brounhall-expand-all',function(){root.find('.brounhall-section').removeClass('is-collapsed');});root.on('click','.brounhall-collapse-all',function(){root.find('.brounhall-section').addClass('is-collapsed');});root.on('click','.brounhall-media-button',function(e){e.preventDefault();var input=$('#'+$(this).data('target'));var frame=wp.media({title:'Select image',button:{text:'Use image'},library:{type:'image'},multiple:false});frame.on('select',function(){input.val(frame.state().get('selection').first().id);});frame.open();});});
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

	echo '<p>Each highlighted panel is one page section. Text, descriptions, links, and Media Library IDs are stored as validated JSON.</p><div class="brounhall-editor-toolbar"><input class="brounhall-editor-filter" type="search" placeholder="Search page sections..."><button type="button" class="button brounhall-expand-all">Expand all</button><button type="button" class="button brounhall-collapse-all">Collapse all</button></div>';
	foreach ( $data['sections'] as $index => $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$title = isset( $section['id'] ) ? (string) $section['id'] : 'Section ' . ( $index + 1 );
		echo '<div class="brounhall-section"><h3><button type="button" class="brounhall-section-toggle"><span>' . esc_html( brounhall_editor_label( $title ) ) . '</span></button></h3><div class="brounhall-nested">';
		foreach ( $section as $key => $value ) {
			brounhall_render_page_editor_value( $value, array( 'sections', $index, $key ), $key );
		}
		echo '</div></div>';
	}
}

function brounhall_editor_label( $value ) {
	$label = preg_replace( '/([a-z])([A-Z])/', '$1 $2', (string) $value );
	return ucwords( str_replace( array( '_', '-' ), ' ', $label ) );
}

function brounhall_render_page_editor_value( $value, $path, $label, $root = 'brounhall_page_data' ) {
	if ( is_array( $value ) ) {
		if ( array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
			echo '<div class="brounhall-repeater" data-next-index="' . esc_attr( count( $value ) ) . '">';
			foreach ( $value as $index => $item ) {
				echo '<fieldset class="brounhall-nested brounhall-repeat-item"><legend>' . esc_html( brounhall_editor_label( $label ) . ' ' . ( $index + 1 ) ) . '</legend><button type="button" class="brounhall-remove-item" aria-label="Remove item">Remove</button>';
				brounhall_render_page_editor_value( $item, array_merge( $path, array( $index ) ), $label, $root );
				echo '</fieldset>';
			}
			echo '<button type="button" class="button brounhall-add-item">Add ' . esc_html( brounhall_editor_label( $label ) ) . '</button></div>';
			return;
		}

		echo '<div class="brounhall-nested"><strong>' . esc_html( brounhall_editor_label( $label ) ) . '</strong>';
		foreach ( $value as $key => $item ) {
			brounhall_render_page_editor_value( $item, array_merge( $path, array( $key ) ), $key, $root );
		}
		echo '</div>';
		return;
	}

	$name = $root;
	foreach ( $path as $part ) {
		$name .= '[' . $part . ']';
	}
	$input_id = 'brounhall-' . md5( $name );
	$label_text = brounhall_editor_label( $label );
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
	if ( preg_match( '/(^|_)(image|imageid|image_id|poster_image_id|logo_image_id)$/', $key ) || preg_match( '/(^|_)image_id$/', $key ) ) {
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
			$key = preg_match( '/^[A-Za-z][A-Za-z0-9_]*$/', (string) $child_key ) ? (string) $child_key : sanitize_key( (string) $child_key );
			$clean[ $key ] = brounhall_sanitize_page_editor_value( $child_value, $child_key );
		}
		return $clean;
	}
	if ( preg_match( '/(^|_)(imageid|image_id|poster_image_id|logo_image_id)$/i', (string) $key ) ) {
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
			$is_quoted_scalar = strlen( $rest ) > 1 && in_array( $rest[0], array( '"', "'" ), true ) && $rest[0] === substr( $rest, -1 );
			if ( $is_quoted_scalar ) {
				$result[] = brounhall_page_yaml_scalar( $rest );
			} elseif ( preg_match( '/^([^:]+):\s*(.*)$/', $rest, $match ) ) {
				$item_key = trim( $match[1] );
				$item_key = strlen( $item_key ) > 1 && in_array( $item_key[0], array( '"', "'" ), true ) && $item_key[0] === substr( $item_key, -1 ) ? substr( $item_key, 1, -1 ) : $item_key;
				$item = array( $item_key => brounhall_page_yaml_scalar( $match[2] ) );
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
		$key = strlen( $key ) > 1 && in_array( $key[0], array( '"', "'" ), true ) && $key[0] === substr( $key, -1 ) ? substr( $key, 1, -1 ) : $key;
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
	if ( strlen( $value ) > 1 && in_array( $value[0], array( '"', "'" ), true ) && $value[0] !== substr( $value, -1 ) ) $value = substr( $value, 1 );
	if ( strlen( $value ) > 1 && in_array( substr( $value, -1 ), array( '"', "'" ), true ) && $value[0] !== substr( $value, -1 ) ) $value = substr( $value, 0, -1 );
	return str_replace( array( '\\"', '\\n' ), array( '"', "\n" ), $value );
}

function brounhall_register_page_migration_command() {
	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_command( 'brounhall pages migrate', 'brounhall_migrate_pages_command' );
		WP_CLI::add_command( 'brounhall entities migrate', 'brounhall_migrate_entities_command' );
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

function brounhall_migrate_entities_command( $args, $assoc_args ) {
	$query = new WP_Query( array( 'post_type' => brounhall_entity_post_types(), 'post_status' => 'any', 'posts_per_page' => -1, 'no_found_rows' => true ) );
	$count = 0;
	foreach ( $query->posts as $post ) {
		if ( ! empty( get_post_meta( $post->ID, BROUNHALL_ENTITY_DATA_META, true ) ) && empty( $assoc_args['force'] ) ) continue;
		$data = json_decode( (string) get_post_meta( $post->ID, brounhall_entity_source_meta_key( $post->post_type ), true ), true );
		if ( ! is_array( $data ) ) continue;
		update_post_meta( $post->ID, BROUNHALL_ENTITY_DATA_META, wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		$count++;
	}
	WP_CLI::success( sprintf( 'Migrated %d entities to %s.', $count, BROUNHALL_ENTITY_DATA_META ) );
}
