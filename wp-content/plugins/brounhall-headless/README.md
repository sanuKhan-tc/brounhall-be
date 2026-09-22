# BrounHall Headless

The `bh_treatment` CPT is the canonical reusable model for treatment-detail content. Its WordPress slug is the public identifier; the CPT is not exposed through `wp/v2`.

Endpoints:

- `GET /wp-json/brounhall/v1/treatments` — published, bounded `{ items: [{ slug, title }] }`.
- `GET /wp-json/brounhall/v1/treatments/{slug}` — published normalized treatment detail.

Treatment data is stored as validated structured JSON meta and edited through the Treatment content box. Supported section types are `rich_text`, `content_groups`, `eligibility_content`, `treatment_process`, `assessment_panel`, `feature_panel`, `genetic_testing`, and `related_treatments`. Related-card variants are `icon` and `image`; action styles are `primary` and `secondary`.

Activate the plugin to idempotently seed the six initial treatments. Add another treatment by creating a Treatment, using a slug-safe slug, and entering the same schema. Drafts and private records never appear in the anonymous API.

## Doctors

The `bh_doctor` CPT is the reusable doctor model. Public reads use the project API only:

- `GET /wp-json/brounhall/v1/doctors` — published lightweight directory records.
- `GET /wp-json/brounhall/v1/doctors/{slug}` — published doctor detail.

Doctor fields are controlled structured data: role, headline, specialty, clinic, image ID/alt text, nationality, languages, areas of interest, education, and bio. Clinic values are limited to `Dubai`, `Abu Dhabi`, and `Al Ain`. The plugin seeds the doctors from the frontend project data idempotently on activation; drafts and private doctors are never public.

Doctor records support the allow-listed `type` values `doctor` and `embryologist`. Use `GET /wp-json/brounhall/v1/doctors?type=embryologist` for the embryology directory.

## Clinics

The `bh_clinic` CPT is the reusable clinic/location model. Public reads use:

- `GET /wp-json/brounhall/v1/clinics` — published lightweight clinic list.
- `GET /wp-json/brounhall/v1/clinics/{slug}` — published clinic detail.

Clinic content is validated structured JSON containing address, phone, hours, description, and image metadata. The plugin seeds Dubai, Abu Dhabi, Al Ain, and Virtual consultation records; drafts and private clinics are never public.

## Structured Pages

The plugin seeds the published `success-rates` WordPress Page using the same versioned YAML-in-code-block contract used by Home and About. The frontend consumes it through its server-only page loader and keeps static content as a safe fallback.

## Cost Pages

The cost module seeds the published `finance`, `insurance`, and `packages` Pages with the current structured content. The frontend reads these through the existing WordPress Page REST contract and maps the `financing_options`, `insurance_providers`, and `packages` sections into the existing components. Static content remains a fallback during migration.
