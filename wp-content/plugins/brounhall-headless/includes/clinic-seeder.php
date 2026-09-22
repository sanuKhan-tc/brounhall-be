<?php
defined( 'ABSPATH' ) || exit;

function brounhall_seed_clinic( $clinic ) {
	$clinic['title'] = $clinic['title'] ?? 'Bourn Hall Clinic ' . $clinic['name'];
	$post = get_page_by_path( $clinic['slug'], OBJECT, 'bh_clinic' );
	$id = $post ? $post->ID : wp_insert_post( array( 'post_type' => 'bh_clinic', 'post_status' => 'publish', 'post_title' => $clinic['name'], 'post_name' => $clinic['slug'], 'menu_order' => $clinic['order'] ) );
	if ( is_wp_error( $id ) || ! $id ) { return 0; }
	if ( ! $post ) {
		update_post_meta( $id, '_brounhall_clinic_data', wp_json_encode( brounhall_normalize_clinic_data( $clinic ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	} else {
		$current = brounhall_normalize_clinic_data( json_decode( get_post_meta( $id, '_brounhall_clinic_data', true ), true ) );
		if ( ! $current['title'] ) {
			$current['title'] = $clinic['title'];
			update_post_meta( $id, '_brounhall_clinic_data', wp_json_encode( $current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}
	}
	return (int) $id;
}

function brounhall_seed_clinics() {
	$clinics = array(
		array( 'order' => 1, 'slug' => 'dubai', 'name' => 'Dubai', 'address' => 'Dubai Healthcare City, Building 64, Block B', 'phone' => '+971 4 429 8400', 'hours' => 'Mon–Sat: 8:00 AM – 6:00 PM', 'description' => 'Our 22,000 sq ft Dubai clinic includes a controlled-access clean-room laboratory and a full team of fertility specialists, embryologists and nurses.', 'imageAlt' => 'Bourn Hall Clinic Dubai' ),
		array( 'order' => 2, 'slug' => 'abu-dhabi', 'name' => 'Abu Dhabi', 'address' => 'Khalidiyah, Al Nahyan Camp Area, Abu Dhabi', 'phone' => '+971 2 222 1218', 'hours' => 'Mon–Sat: 8:00 AM – 6:00 PM', 'description' => 'Located in Khalidiyah, our Abu Dhabi clinic brings pioneering IVF science and JCI-accredited care to the capital.', 'imageAlt' => 'Bourn Hall Clinic Abu Dhabi' ),
		array( 'order' => 3, 'slug' => 'al-ain', 'name' => 'Al Ain', 'address' => 'Al Jimi Area, Al Ain, Abu Dhabi Emirate', 'phone' => '800-IVF (483)', 'hours' => 'Mon–Sat: 8:00 AM – 6:00 PM', 'description' => 'Our Al Ain clinic offers comprehensive fertility care with andrology, embryology and cryopreservation laboratories.', 'imageAlt' => 'Bourn Hall Clinic Al Ain' ),
		array( 'order' => 4, 'slug' => 'virtual', 'name' => 'Virtual', 'address' => 'Available across the UAE and internationally', 'phone' => '800-IVF (483)', 'hours' => 'By appointment', 'description' => 'Start your journey from home with a confidential virtual consultation, then continue care at the clinic that suits you.', 'imageAlt' => 'Virtual consultation' ),
	);
	foreach ( $clinics as $clinic ) { brounhall_seed_clinic( $clinic ); }
}

add_action( 'init', function () {
	if ( '2' !== get_option( 'brounhall_clinics_seeded' ) ) {
		brounhall_seed_clinics();
		update_option( 'brounhall_clinics_seeded', '2', false );
	}
}, 20 );
