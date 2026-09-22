<?php
defined( 'ABSPATH' ) || exit;

function brounhall_cost_page_content( $slug ) {
	$documents = array(
		'finance' => <<<'YAML'
version: 1
page:
  template: cost-finance
sections:
  - id: hero
    type: hero
    enabled: true
    content:
      title_lead: "Financing"
      title_accent: "Options"
      description: >
        Your dream of starting or growing your family shouldn't wait on your budget. Explore flexible ways to pay for your treatment, tailored to how you bank and shop.
    media:
      image_key: couple-planning
      alt: "Couple planning fertility treatment"
  - id: financing-options
    type: financing_options
    enabled: true
    content:
      note: "We're committed to providing you with the best possible support and care. To learn more about our financing options and find the best plan for you, please contact us directly."
    partners:
      - key: emirates-nbd
        name: "Emirates NBD Cardholders"
        logo_key: emirates-nbd
        logo_alt: "Emirates NBD"
        logo_width: 216
        logo_height: 56
        points:
          - "0% Interest Instalment Plan"
          - "Eligible for purchases of AED 5000 or more"
          - "Choose a 6 or 12-month option"
      - key: tabby
        name: "Tabby Users"
        logo_key: tabby
        logo_alt: "Tabby"
        logo_width: 148
        logo_height: 52
        summary: "Split payments into 3 instalments"
        points:
          - "Down payment: 25%"
          - "New Users: Up to AED 15K"
          - "Current Users: Up to AED 20K"
      - key: esaad
        name: "Esaad Cardholders"
        logo_key: esaad
        logo_alt: "Esaad"
        logo_width: 72
        logo_height: 72
        summary: "Enjoy a 20% discount on treatments"
      - key: fazaa
        name: "Fazaa Cardholders"
        logo_key: fazaa
        logo_alt: "Fazaa"
        logo_width: 72
        logo_height: 72
        summary: "Get up to 20% discount on treatments"
YAML,
		'insurance' => <<<'YAML'
version: 1
page:
  template: cost-insurance
sections:
  - id: hero
    type: hero
    enabled: true
    content:
      title_lead: "Insurance for"
      title_accent: "Fertility Treatments"
      description: >
        At Bourn Hall Fertility Clinic UAE, we understand that navigating insurance coverage for fertility treatment can be complex. Our goal is to support you through every step, helping you understand what services may be covered by your insurance provider.
    media:
      image_key: insight-consultation
      alt: "Fertility consultation at Bourn Hall"
  - id: accepted-insurance-providers
    type: insurance_providers
    enabled: true
    content:
      title_lead: "Accepted Insurance"
      title_accent: "Providers"
      clinics:
        - key: dubai
          name: "Bourn Hall Dubai"
          providers:
            - key: neuron
              name: "Neuron"
              logo_key: neuron
              logo_alt: "Neuron"
              logo_width: 105
              logo_height: 42
            - key: daman
              name: "Daman GHQ only"
              logo_key: daman
              logo_alt: "Daman"
              logo_width: 78
              logo_height: 69
            - key: nextcare-dubai
              name: "Next Care"
              logo_key: nextcare
              logo_alt: "Nextcare"
              logo_width: 110
              logo_height: 20
        - key: abu-dhabi
          name: "Bourn Hall Abu Dhabi"
          providers:
            - key: thiqa-abu-dhabi
              name: "Thiqa"
              logo_key: thiqa
              logo_alt: "Thiqa"
              logo_width: 97
              logo_height: 65
            - key: nextcare-abu-dhabi
              name: "Next Care"
              logo_key: nextcare
              logo_alt: "Nextcare"
              logo_width: 110
              logo_height: 20
        - key: al-ain
          name: "Bourn Hall Al Ain"
          providers:
            - key: thiqa-al-ain
              name: "Thiqa"
              logo_key: thiqa
              logo_alt: "Thiqa"
              logo_width: 97
              logo_height: 65
      notes:
        - "Prior to treatment, please check with your insurance provider what will and will not be covered by your insurance."
        - "We offer direct billing with select providers for eligible services, subject to pre-approval and policy terms."
        - "If your insurance provider is not listed or if you discover that you are not covered, please don't hesitate to contact us. We will gladly provide you with the most competitive packages for treatment costs."
  - id: coverage
    type: coverage
    enabled: true
    content:
      title: "What May Be Covered?"
      intro: "Coverage for fertility-related services varies between insurance providers and plans. In general, the following services may be covered depending on your policy:"
      items:
        - "In vitro consultations with fertility specialists"
        - "Ultrasound scans"
        - "Certain diagnostic or fertility investigations"
        - "Laboratory tests and blood work"
        - "Hormonal assessments"
      note: "It's important to confirm with your insurance provider whether fertility treatments are included in your specific plan, and whether prior approval is required."
  - id: pre-approval
    type: steps
    enabled: true
    content:
      title: "Pre-Approval and Authorisation Support"
      intro: "Our dedicated insurance coordination team can help:"
      steps:
        - key: coverage-limits
          title: "Confirm your insurance coverage limits"
        - key: pre-authorisation
          title: "Assist with pre-authorisation requests"
        - key: documents
          title: "Prepare supporting documents for claim submissions"
        - key: queries
          title: "Answer any queries related to billing or covered services"
  - id: contact
    type: contact_panel
    enabled: true
    content:
      title: "Contact Our Insurance Team"
      body: "If you have questions about your insurance coverage or would like help with the pre-approval process, please don't hesitate to get in touch. Reach out confidently and we'll get you going."
      phone: "800483"
      phone_href: "tel:800483"
    media:
      image_key: insurance-team
      alt: "Bourn Hall insurance team"
YAML,
		'packages' => <<<'YAML'
version: 1
page:
  template: cost-packages
sections:
  - id: hero
    type: hero
    enabled: true
    content:
      title_lead: "Fertility"
      title_accent: "Treatment Offers"
      description: >
        Explore our latest fertility treatment offers, thoughtfully designed to make expert care more accessible. From IVF to egg freezing, find the right option with personalised guidance at every step.
    media:
      image_key: couple-planning
      alt: "Couple planning fertility treatment"
  - id: packages
    type: packages
    enabled: true
    content:
      intro:
        - >
          Starting fertility treatment, or even just thinking about it, can feel like a big step, and it helps to know that you have supportive options in place. At Bourn Hall Fertility Clinic in Dubai, Abu Dhabi, and Al Ain, our newest offers are created to make care more accessible while keeping the focus on your treatment plan, your timeline, and the right clinical guidance. Whether you're looking to support ovarian reserve planning ahead, you'll find clear pathways and a team that's with you through treatment in a calm, reassuring way.
        - >
          If you're ready to move toward IVF treatment, our IVF offers help you take your next step with confidence, backed by experienced fertility specialists and personalised care. And if you're thinking more about the future, our egg freezing offer is a practical, empowering option that lets you preserve your fertility and keep your choices open. From your first consultation through treatment planning, we're here to make the process feel less overwhelming, more straightforward and fully tailored to you.
      offers:
        - key: egg-freezing
          title: "Egg Freezing"
          featured: false
          items:
            - "Free Consultation"
            - "Egg collection"
            - "Egg freezing"
            - "Storage up to 1 year"
            - "Ultrasounds"
            - "Treatment Profile"
            - "*Medication Excluded"
          price: "AED 12,000"
        - key: ivf-fresh-embryo-transfer
          title: "IVF + Fresh Embryo Transfer"
          featured: true
          items:
            - "Consultation"
            - "Egg collection"
            - "Sperm preparation and ICSI"
            - "Fertilisation"
            - "1 year storage"
            - "Ultrasounds Hormonal Profile"
            - "Pregnancy tests (2)"
            - "*Medication Excluded"
          price: "AED 20,000"
        - key: ivf-pgt-a-fet
          title: "IVF + PGT-A + FET"
          featured: false
          items:
            - "Consultation"
            - "Egg collection"
            - "Sperm preparation and ICSI"
            - "Fertilisation"
            - "PGT-A for 24 chromosomes, biopsy, freezing and 1 year storage"
            - "Ultrasound + Treatment profile"
            - "Embryo transfer"
            - "Pregnancy tests (2)"
            - "*Medication Excluded"
          price: "AED 37,000"
  - id: contact
    type: contact_panel
    enabled: true
    content:
      title_lead: "You're Not Alone."
      title_accent: "Let's Begin Together."
      paragraphs:
        - >
          We have made every step of your journey with us clear, supportive and easy to navigate. From your first point of contact to your final follow-up, our goal is to ease the pressure off, so you can focus on what matters most.
        - >
          You can book an appointment online or request a call back from our patient advisors. They're here to guide you, answer your questions, and help you feel informed and confident at every step.
      highlight: "Your first consultation is complimentary"
      note: "There's no commitment — just a space to talk, learn, and be heard."
      phone: "800483"
      phone_href: "tel:800483"
    media:
      image_key: insurance-team
      alt: "Bourn Hall patient support team"
YAML,
	);
	return $documents[ $slug ] ?? '';
}

function brounhall_seed_cost_page( $slug, $title ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $page ) { return (int) $page->ID; }
	$content = '<pre class="wp-block-code"><code>' . esc_html( brounhall_cost_page_content( $slug ) ) . '</code></pre>';
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content ), true );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

function brounhall_seed_cost_pages() {
	brounhall_seed_cost_page( 'finance', 'Financing Options' );
	brounhall_seed_cost_page( 'insurance', 'Insurance for Fertility Treatments' );
	brounhall_seed_cost_page( 'packages', 'Fertility Treatment Offers' );
}

add_action( 'init', function () {
	if ( '1' !== get_option( 'brounhall_cost_pages_seeded' ) ) {
		brounhall_seed_cost_pages();
		update_option( 'brounhall_cost_pages_seeded', '1', false );
	}
}, 20 );
