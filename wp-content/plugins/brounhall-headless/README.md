# BrounHall Headless

The `bh_treatment` CPT is the canonical reusable model for treatment-detail content. Its WordPress slug is the public identifier; the CPT is not exposed through `wp/v2`.

Endpoints:

- `GET /wp-json/brounhall/v1/treatments` — published, bounded `{ items: [{ slug, title }] }`.
- `GET /wp-json/brounhall/v1/treatments/{slug}` — published normalized treatment detail.

Treatment data is stored as validated structured JSON meta and edited through the Treatment content box. Supported section types are `rich_text`, `content_groups`, `eligibility_content`, `treatment_process`, `assessment_panel`, `feature_panel`, `genetic_testing`, and `related_treatments`. Related-card variants are `icon` and `image`; action styles are `primary` and `secondary`.

Activate the plugin to idempotently seed the six initial treatments. Add another treatment by creating a Treatment, using a slug-safe slug, and entering the same schema. Drafts and private records never appear in the anonymous API.
