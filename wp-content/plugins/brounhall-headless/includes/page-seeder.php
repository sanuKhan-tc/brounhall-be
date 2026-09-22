<?php
defined( 'ABSPATH' ) || exit;

function brounhall_success_rates_page_document() {
	return <<<'YAML'
version: 1

page:
  template: success-rates

sections:

  - id: hero
    type: hero
    enabled: true
    content:
      title: "Success Rates at Bourn Hall UAE"
      title_lead: "Success Rates at"
      title_accent: "Bourn Hall UAE"
      description: >
        At Bourn Hall Fertility Clinic UAE, we are proud of our consistently strong success rates, reflecting our commitment to clinical excellence, advanced technology and compassionate care. Our team of internationally trained fertility specialists uses evidence-based protocols and personalised treatment plans to give every patient the best possible chance of success.
    media:
      image_id: 1701
      alt: "Bourn Hall fertility clinic"

  - id: why-success-rates
    type: feature_steps
    enabled: true
    content:
      title_lead: "Why Success Rates Matter and"
      title_accent: "How to Understand Them"
      introduction: >
        Success rates are an important consideration when choosing a fertility clinic, but it is equally important to understand what these numbers mean.
      factors:
        - title: "Age of the patient"
        - title: "Type of fertility treatment (e.g. IVF, ICSI, IUI)"
        - title: "Cause of infertility"
        - title: "Embryo quality and stage of transfer"
        - title: "Medical history and underlying conditions"
      note: "At Bourn Hall UAE, we provide transparent and realistic guidance based on your individual case."

  - id: lab-success-rates
    type: text_image
    enabled: true
    content:
      title_lead: "Our Lab"
      title_accent: "Success Rates"
      paragraphs:
        - >
          Our success rates are comparable with the world’s leading fertility centres, and we continuously audit and monitor our outcomes to maintain the highest standards.
        - >
          While we cannot guarantee a result, our focus is always on maximising your chances of pregnancy in a healthy, safe and ethically responsible way.
        - >
          Detailed clinical success rate information available on request during consultation with our specialists.
    media:
      image_id: 1702
      alt: "Bourn Hall fertility laboratory"
    overlay:
      title: "Click here to Check Fertility Health"
      action:
        label: "Learn more"
        href: "/#fertility-health"

  - id: assessment
    type: contact_panel
    enabled: true
    content:
      title: "Start with a Personalised Assessment"
      body: "We recommend booking an initial FREE consultation to better understand your chances based on your unique circumstances. Your success is our shared goal."
      phone: "800483"
      phone_href: "tel:800483"
    media:
      image_id: 1703
      alt: "Fertility consultation at Bourn Hall"
YAML;
}

function brounhall_seed_success_rates_page() {
	$page = get_page_by_path( 'success-rates', OBJECT, 'page' );
	if ( $page ) { return (int) $page->ID; }

	$content = '<pre class="wp-block-code"><code>' . esc_html( brounhall_success_rates_page_document() ) . '</code></pre>';
	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Success Rates',
		'post_name'    => 'success-rates',
		'post_content' => $content,
	), true );

	return is_wp_error( $id ) ? 0 : (int) $id;
}

add_action( 'init', function () {
	if ( '1' !== get_option( 'brounhall_success_rates_page_seeded' ) ) {
		brounhall_seed_success_rates_page();
		update_option( 'brounhall_success_rates_page_seeded', '1', false );
	}
}, 20 );
