<?php
defined( 'ABSPATH' ) || exit;

function brounhall_seed_faqs() {
	$data = array(
		'help' => array(
			'body' => "Have a question not listed here? Our team is happy to help.\nContact us directly or book a free initial call.",
			'cta'  => array( 'href' => '/contact', 'label' => 'Contact Us' ),
		),
		'categories' => array(
			array( 'title' => 'Treatments & Services', 'body' => 'Find answers to common questions about fertility treatments, appointments, costs and care.' ),
			array( 'title' => 'Appointments & Consultations', 'body' => 'Learn how to book a visit, what to bring to your first consultation, and how follow-up care works.' ),
			array( 'title' => 'Success & Safety', 'body' => 'Understand how we measure outcomes, laboratory standards, and the safeguards around every treatment.' ),
			array( 'title' => 'Cost & Financing', 'body' => 'Explore treatment pricing, what is included in packages, and available payment plan options.' ),
			array( 'title' => 'General Questions', 'body' => 'Answers about clinic locations, languages spoken, international patients, and getting started.' ),
		),
		'items' => array(
			array( 'question' => 'When should I see a fertility specialist?', 'answer' => 'If you are under 35 and have been trying to conceive for 12 months, or over 35 and have been trying for 6 months, a consultation can help. You should also seek advice sooner if you have irregular cycles, known gynaecological or male-factor issues, or a history of miscarriage.' ),
			array( 'question' => 'What happens at the first consultation?', 'answer' => 'Your specialist reviews your medical history, discusses previous tests or treatments, and recommends diagnostics where needed. You leave with a clear, personalised plan — not a one-size-fits-all protocol.' ),
			array( 'question' => 'How long does an IVF cycle take?', 'answer' => 'Most IVF cycles take around six to nine weeks from consultation to pregnancy test, depending on your protocol, whether embryos are frozen, and whether genetic testing is included.' ),
			array( 'question' => 'Are Bourn Hall clinics accredited?', 'answer' => 'Yes. Bourn Hall Dubai was the first stand-alone fertility centre in the Middle East to receive JCI accreditation. Our UAE clinics also hold laboratory quality accreditations and follow international clinical standards.' ),
			array( 'question' => 'Do you treat male infertility?', 'answer' => 'Yes. Male-factor infertility is assessed and treated with andrology, urology support, ICSI and surgical sperm retrieval when required. Both partners are included in the care plan.' ),
			array( 'question' => 'Can international patients be treated at Bourn Hall UAE?', 'answer' => 'Yes. We support patients travelling to the UAE with virtual consultations, coordinated diagnostics and a dedicated patient pathway across Dubai, Abu Dhabi and Al Ain.' ),
		),
	);
	$post = get_page_by_path( 'faq', OBJECT, 'bh_faq' );
	$id   = $post ? $post->ID : wp_insert_post( array( 'post_type' => 'bh_faq', 'post_status' => 'publish', 'post_title' => 'Frequently Asked Questions', 'post_name' => 'faq', 'menu_order' => 1 ) );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	if ( ! $post || ! get_post_meta( $id, '_brounhall_faq_data', true ) ) {
		update_post_meta( $id, '_brounhall_faq_data', wp_json_encode( brounhall_normalize_faq_data( $data ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}
	return (int) $id;
}

add_action( 'init', function () {
	if ( '1' !== get_option( 'brounhall_faqs_seeded' ) ) {
		brounhall_seed_faqs();
		update_option( 'brounhall_faqs_seeded', '1', false );
	}
}, 20 );
