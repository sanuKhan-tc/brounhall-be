<?php
/**
 * Import local frontend raster assets into the WordPress Media Library.
 * Run from the repository root: php backend/tools/import-frontend-images.php
 */

defined( 'ABSPATH' ) || require_once dirname( __DIR__, 2 ) . '/backend/wp-load.php';

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$source_root = realpath( dirname( __DIR__, 2 ) . '/frontend/public/images' );
if ( ! $source_root || ! is_dir( $source_root ) ) {
	fwrite( STDERR, "Frontend image directory not found.\n" );
	exit( 1 );
}

$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' );
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source_root, FilesystemIterator::SKIP_DOTS ) );
$imported = array();

foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || ! in_array( strtolower( $file->getExtension() ), $allowed, true ) ) {
		continue;
	}
	$path = $file->getRealPath();
	$relative = ltrim( str_replace( '\\', '/', substr( $path, strlen( $source_root ) ) ), '/' );
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_brounhall_source_path',
		'meta_value'     => $relative,
	) );
	if ( $existing ) {
		$imported[ $relative ] = (int) $existing[0];
		continue;
	}

	$filetype = wp_check_filetype( $file->getFilename() );
	if ( empty( $filetype['type'] ) ) {
		continue;
	}
	$upload = wp_upload_bits( $file->getFilename(), null, file_get_contents( $path ) );
	if ( ! empty( $upload['error'] ) ) {
		fwrite( STDERR, "Upload failed for {$relative}: {$upload['error']}\n" );
		continue;
	}
	$attachment_id = wp_insert_attachment( array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_text_field( pathinfo( $file->getFilename(), PATHINFO_FILENAME ) ),
		'post_status'    => 'inherit',
	), $upload['file'] );
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		fwrite( STDERR, "Attachment creation failed for {$relative}.\n" );
		continue;
	}
	$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
	wp_update_attachment_metadata( $attachment_id, $metadata );
	update_post_meta( $attachment_id, '_brounhall_source_path', $relative );
	$imported[ $relative ] = (int) $attachment_id;
}

$images_source = file_get_contents( dirname( __DIR__, 2 ) . '/frontend/src/lib/images.ts' );
preg_match_all( '/^\s{2}([A-Za-z0-9]+):\s*\{\s*\r?\n\s*src:\s*"(https:\/\/images\.unsplash\.com\/[^"\r\n]+)"/m', $images_source, $external_images, PREG_SET_ORDER );
foreach ( $external_images as $external ) {
	$key      = $external[1];
	$url      = $external[2];
	$extension = pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'jpg';
	$relative = 'external/' . sanitize_file_name( $key . '.' . $extension );
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_brounhall_source_path',
		'meta_value'     => $relative,
	) );
	if ( $existing ) {
		$imported[ $relative ] = (int) $existing[0];
		continue;
	}
	$temp = download_url( $url, 30 );
	if ( is_wp_error( $temp ) ) {
		fwrite( STDERR, "Download failed for {$key}: {$temp->get_error_message()}\n" );
		continue;
	}
	$filename = sanitize_file_name( $key . '.' . $extension );
	$upload   = wp_upload_bits( $filename, null, file_get_contents( $temp ) );
	wp_delete_file( $temp );
	if ( ! empty( $upload['error'] ) ) {
		fwrite( STDERR, "Upload failed for {$key}: {$upload['error']}\n" );
		continue;
	}
	$filetype = wp_check_filetype( $filename );
	$attachment_id = wp_insert_attachment( array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_text_field( $key ),
		'post_status'    => 'inherit',
	), $upload['file'] );
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		fwrite( STDERR, "Attachment creation failed for {$key}.\n" );
		continue;
	}
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $attachment_id, '_brounhall_source_path', $relative );
	$imported[ $relative ] = (int) $attachment_id;
}

$legacy_paths = array(
	1002 => 'why-choose-video.png',
	1101 => 'insights/sperm-health.png',
	1201 => 'external/embryo.jpg',
	1301 => 'external/ivfCare.jpg',
	1302 => 'treatment-icsi-b.png',
	1303 => 'external/lab.jpg',
	1304 => 'treatment-egg-freezing.png',
	1305 => 'external/geneticTesting.jpg',
	1401 => 'treatment-icsi-b.png',
	1501 => 'external/lab.jpg',
	1601 => 'external/geneticTesting.jpg',
	1701 => 'insights/ivf-consultation.png',
	1702 => 'insights/female-fertility-age.png',
	1703 => 'insights/sperm-health.png',
	1704 => 'external/ivfCare.jpg',
	1705 => 'treatment-egg-freezing.png',
	1706 => 'external/consultation.jpg',
	1707 => 'treatment-icsi-b.png',
	1708 => 'external/geneticTesting.jpg',
	301  => 'about/about-hero-team.png',
	302  => 'about/about-legacy-building.png',
	304  => 'about/about-different-baby.png',
	305  => 'about/about-history-building.png',
	306  => 'accreditation/jci.png',
	307  => 'accreditation/cap.png',
);
$aliases = array();
foreach ( $legacy_paths as $legacy_id => $relative ) {
	if ( isset( $imported[ $relative ] ) ) {
		$aliases[ $legacy_id ] = $imported[ $relative ];
	}
}
update_option( 'brounhall_media_aliases', $aliases, false );

// Attach imported portraits to the existing directory records without replacing
// editorial data. Re-running this script is safe and updates only imageId.
$directory_images = array(
	'bh_doctor' => array(
		'dr-ghada-hussein' => 'doctors/dr-ghada-hussein.png',
		'dr-sajida-detho' => 'doctors/dr-sajida-detho.png',
		'dr-shazia-magray' => 'doctors/dr-shazia-magray.png',
		'dr-larissa-schindler' => 'doctors/dr-larissa-schindler.png',
		'dr-limia-ibrahim' => 'doctors/dr-limia-ibrahim.png',
		'dr-majeed-aloum' => 'doctors/dr-majeed-aloum.png',
		'dr-munira-furniturewala' => 'doctors/dr-munira-furniturewala.png',
		'dr-heba-hashem' => 'doctors/dr-heba-hashem.png',
		'dr-ali-thwaini' => 'doctors/dr-ali-thwaini.png',
		'dr-gautam-allahbadia' => 'doctors/dr-gautam-allahbadia.png',
	),
	'bh_clinic' => array(
		'dubai' => 'clinics/dubai.png',
		'abu-dhabi' => 'clinics/abu-dhabi.png',
		'al-ain' => 'clinics/al-ain.png',
		'virtual' => 'clinics/virtual.png',
	),
);
foreach ( $directory_images as $post_type => $records ) {
	foreach ( $records as $slug => $relative ) {
		if ( empty( $imported[ $relative ] ) ) { continue; }
		$post = get_page_by_path( $slug, OBJECT, $post_type );
		if ( ! $post ) { continue; }
		$meta_key = 'bh_doctor' === $post_type ? '_brounhall_doctor_data' : '_brounhall_clinic_data';
		$data = json_decode( get_post_meta( $post->ID, $meta_key, true ), true );
		if ( ! is_array( $data ) ) { continue; }
		$data['imageId'] = (int) $imported[ $relative ];
		update_post_meta( $post->ID, $meta_key, wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}
}

echo wp_json_encode( $imported, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
