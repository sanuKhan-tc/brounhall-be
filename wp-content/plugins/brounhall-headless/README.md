# BrounHall Headless

Version 0.1.0 — foundation scaffold.

This project-owned plugin is the WordPress-side foundation for BrounHall's headless architecture. It provides a deterministic bootstrap for future CMS integration while keeping content, presentation, and frontend concerns separate.

## Requirements

- WPGraphQL
- Advanced Custom Fields FREE
- WPGraphQL for ACF
- WPGraphQL Smart Cache

These plugins are expected to provide the underlying integrations; this foundation does not bundle or enforce them.

## Modules

- `content-types.php` — future content type registrations
- `taxonomies.php` — future taxonomy registrations
- `graphql.php` — future GraphQL integration
- `settings.php` — future project settings integration
- `navigation.php` — future navigation integration
- `seo.php` — reusable SEO field group for routable content types
- `preview.php` — future preview integration
- `revalidation.php` — future revalidation integration
- `security.php` — future security integration
- `fields.php` — reusable ACF Free media/link field definitions

No module currently implements business behavior.

## General marketing pages

Native WordPress Pages (`page`) are the canonical CMS model for general marketing routes such as Home, About, Contact, Careers, campaigns, and other informational pages. Editors use the standard WordPress workflow: **Pages → Add New/Edit Page**.

Page identity remains native to WordPress: title, slug/permalink, publication status, author, and parent hierarchy. WPGraphQL's existing Page support exposes published Pages to the headless frontend. This plugin does not register a duplicate Page post type or add page-builder, layout, hero, SEO, section, or presentation fields.

## Global settings

`Settings → BrounHall Settings` stores the BH-018 editorial scalar values and the BH-029 organization metrics in the `brounhall_global_settings` option: public phone, public email, public address, footer copyright text, success rate, live births, and years of trusted care. The native WordPress site title (`blogname`) remains the canonical site-name setting. Metric values remain empty until approved production figures are supplied.

Media and reusable link fields are deferred to BH-019. SEO fields are deferred to BH-020. CMS settings must not contain secrets or infrastructure configuration.

## Global Statistics

BH-029 is a global-backed Statistics contract: the existing `brounhall_global_settings` option owns the semantic `success_rate`, `live_births`, and `years_of_trusted_care` display values, exposed through the public `brounhallGlobalSettings` GraphQL object. Labels remain frontend-owned (`Success Rates`, `Live Births`, and `Years of Trusted Care`), as do StatsRow count-up animation and presentation. No Page-level metric fields or repeated metric structure is created. The homepage and About static values currently conflict; final production figures require a content-owner decision. BH-059 will own the typed `GetGlobalSettings` operation, and the existing frontend remains unchanged.

## Global Primary CTA

BH-034 resolves the shared primary CTA as Global Settings content. The existing `brounhall_global_settings` option stores `primary_cta_title`, `primary_cta_description`, and a controlled `primary_cta_link` shape (`title`, `url`, `target`), exposed through `brounhallGlobalSettings.primaryCtaTitle`, `primaryCtaDescription`, and `primaryCta`. URLs accept safe relative, HTTP(S), mailto, and tel links; targets are limited to supported values. No Page CTA field group or page-level override exists. HomeCta and BrandCta presentation remains frontend-owned; formal frontend integration is deferred to BH-079/BH-089.

## Location/Clinic entity

BH-043 uses the canonical technical post type `location` with editor-facing labels **Clinic** / **Clinics** and GraphQL names `Location` / `Locations`. The frontend has reusable clinic records with stable `/clinics/[slug]` routes, location-specific addresses, phones, hours, images, and summaries. Native title, excerpt, featured image, slug, and revisions provide the core entity; the `locationDetails` group adds only the source-proven short display name, clinic phone, and plain-text opening hours. Coordinates, map embeds, directions URLs, email, and relationships to doctors/services/departments are not currently source-proven or are deferred. Routable Location records receive the shared `seo` group from BH-047 and frontend content remains static.

## FAQ content model

BH-032 uses reusable `faq` items because the frontend exposes an ordered collection of plain Question + Answer content. The FAQ item title is the question and `faq_answer` is a plain-text textarea; native Pages select and order FAQ Items through the `faqSection` field group. FAQ Items are GraphQL-visible supporting content without standalone public routing, and the page relationship is bounded by the selected items. Section title, description, help text, and the optional BH-019 contact link are stored only when configured on the Page; accordion behavior and FAQ schema remain frontend-owned.

## Article/Insight content model

BH-042 deliberately uses native WordPress Posts as the Article/Insight model. The frontend presents the same editorial shape as “Fertility Insights & Resources” and `/blogs` content: title, slug, excerpt, body, featured image, category, and author, with native publication and modified dates available through WPGraphQL. No duplicate Article CPT or ACF fields are required. Native Posts are exposed as `Post`/`Posts`, and Next.js owns the public `/blogs` and `/blogs/[slug]` routes while WordPress retains its native post URI. Author values are not re-modeled because the current static content includes organization/display names rather than a proven WordPress-user contract; read time remains derived/presentation data. Routable Posts receive the shared `seo` group from BH-047; related content remains owned by BH-033, and committed Article operations remain deferred to BH-066.

## Related Content

BH-033 provides a Page-owned curated Related Content section for bounded Post selections. The `BrounHall Related Content` group is exposed as `relatedContent`; it stores only source-proven section title, description, optional link, and an ordered `related_content_posts` Relationship restricted to native `post` entities. Post title, slug, excerpt, featured image, and category remain native Post data and are not duplicated on Pages. The homepage’s current first-three static ordering maps to this curated section; blog-detail `getRelatedBlogs()` remains contextual/static-order behavior for later Article operations and mapping. Card presentation remains frontend-owned.

## Common field definitions

BH-019 provides project-owned PHP factories in `includes/fields.php` because ACF Free does not provide Clone fields. Image definitions use WordPress Media Library attachment IDs; link definitions use the ACF Link field's array shape (`title`, `url`, `target`). ACF Clone is intentionally not used.

Social collections are deferred. Logo/media attachment, SEO image/link fields, page-section consumers, and GraphQL media/link fragments remain deferred to their later tasks.

## SEO fields

BH-047 attaches one reusable `BrounHall SEO` ACF Free field group to all currently routable entity types: native Pages, Doctors, Treatments/Services, Clinics/Locations, and native Posts used by the `/blogs/[slug]` frontend route. It is exposed through WPGraphQL for ACF as the consistent `seo` field. The contract provides optional meta title/description, canonical URL override, robots index/follow, Open Graph title/description/image, Twitter title/description/image, and breadcrumb title fields. Text and image overrides are optional; robots defaults are enabled; empty overrides allow later frontend fallback logic.

Canonical overrides are restricted to absolute `http`/`https` URLs and unsafe schemes are rejected/sanitized. Image fields reuse the BH-019 Media Library attachment-ID contract. FAQs remain non-routable support content and Global Settings remains global configuration, so neither receives per-record SEO fields. Department/Specialty remains absent. The frontend `SeoFields` fragment and Next.js metadata rendering remain deferred to later frontend SEO tasks. No Yoast or Rank Math dependency is used.

## Hero fields

The `BrounHall Hero` ACF Free field group is attached to native Pages and exposed through WPGraphQL for ACF as `hero`. It provides optional eyebrow, title, subtitle, description, Hero image, primary CTA, and secondary CTA fields. Images reuse the BH-019 Media Library attachment-ID contract and links reuse the BH-019 Link contract. The current frontend Hero uses additional presentation-specific composition and repeated trust avatars; those are not modeled here because WordPress does not own presentation and ACF Free has no repeater. Frontend consumption and the `HeroFields` fragment are deferred to later frontend/page-section tasks.

## Rich Text fields

The `BrounHall Rich Text` ACF Free field group is attached to native Pages and exposed through WPGraphQL for ACF as `richText`. It provides an optional plain-text title and a plain-text body textarea; blank lines separate paragraphs. Stored HTML, scripts, events, styles, embeds, and arbitrary markup are not accepted because the current frontend renders content as plain text. Frontend consumption and the page-section GraphQL fragment are deferred to BH-058; other section types remain deferred to BH-023+.

## Text + Image fields

The `BrounHall Text + Image` ACF Free field group is attached to native Pages and exposed through WPGraphQL for ACF as `textImage`. It provides optional eyebrow, title, and plain-text body fields plus an optional image using the BH-019 WordPress Media Library attachment-ID contract. Image + Text intentionally reuses this same CMS contract: the frontend `MediaSplit` component controls normal versus reversed visual ordering. Orientation, stacking, spacing, and other presentation choices remain frontend-owned; no `imageText` field or orientation setting is stored in WordPress. Frontend consumption and the page-section GraphQL fragment are deferred to BH-058.

## Image + Text decision

BH-024 is intentionally merged into BH-023. Frontend reverse layouts use the same `MediaSplit` props and editorial content shape, so a duplicate CMS field group is not required.

## Service/Treatment entity

The reusable backend entity uses the stable technical post type key `service` and GraphQL names `Service`/`Services`, while the WordPress editor uses the source-proven user-facing labels **Treatment** and **Treatments**. Native title, slug, editor content, excerpt, and featured image provide the core entity fields. BH-045 reviewed the frontend and found no source-proven Service-to-Doctor, Service-to-Location, Service-to-Department/Specialty, or other Service relationship: treatment records contain static treatment data and category labels only, while the appointment form independently selects a clinic and treatment. No relationship fields or reciprocal links are implemented. Routable Service records receive the shared `seo` group from BH-047; Service GraphQL operations remain deferred to BH-065, and frontend integration remains separate.

## Service Grid

The `BrounHall Service Grid` ACF Free field group is attached to native Pages and exposed as `serviceGrid`. Editors select and order canonical Treatments through the `service_grid_services` Relationship field, restricted to the `service` post type; Service title, slug, summary, and featured image remain on the Service entity and are not duplicated on the Page. The section also provides source-proven plain-text title/description fields and an optional BH-019 link field. The editorial selection is bounded and preserves selection order. All-Service operations remain deferred to BH-065; taxonomy filtering to future Service taxonomy work; Service relationships to BH-045; entity SEO to BH-047. Frontend pages remain static and unmodified.

## Doctor entity

The reusable people represented by the current frontend are doctors and fertility specialists, so BH-039 uses the stable `doctor` post type with **Doctor** / **Doctors** editor labels and GraphQL names. Native title, editor content, revisions, and featured image provide the name, biography, and portrait; the `doctor_role` and nullable `doctor_location` fields are exposed through `doctorProfile`. The clinic field is a single ACF Post Object restricted to the canonical `location` post type and is exposed as `doctorLocation`. Qualifications, languages, specialty, Doctor-to-Service relationships, Doctor-to-Department/Specialty relationships, and booking behavior remain deferred to their owning tasks; routable Doctor records receive the shared `seo` group from BH-047. The Doctor Grid remains deferred to BH-027 and frontend pages remain static.

## Doctor relationships

BH-044 adds the source-proven Doctor-to-Location relationship. Each current frontend Doctor belongs to one Clinic, and the Doctors directory filters by that Clinic. Editors assign one canonical Clinic to a Doctor in the `doctorProfile` group; the field is nullable for legacy or incomplete records. Doctor-to-Service and Doctor-to-Department/Specialty relationships are not source-proven and remain unimplemented.

## Doctor Grid

The `BrounHall Doctor Grid` ACF Free field group is attached to native Pages and exposed as `doctorGrid`. Editors select and order canonical Doctors through the `doctor_grid_doctors` Relationship field, restricted to the `doctor` post type; Doctor name, role, portrait, slug, and biography remain on the Doctor entity and are not duplicated on the Page. The section also provides source-proven plain-text title/description fields and an optional BH-019 link field. The selection is bounded and preserves editor order. Specialty/department relationships remain unimplemented because BH-040 found no independent entity; Doctor-to-Location is provided by BH-044, entity SEO remains deferred to BH-047, and the Doctor Grid frontend remains static and unmodified.

## Department relationships

BH-046 is intentionally N/A. No canonical Department/Specialty entity exists in the frontend or backend: doctor specialty values are display classifications, and treatment categories are static grouping labels. Consequently there is no Department-to-Doctor, Department-to-Service, Department-to-Location, or other Department relationship, CPT, taxonomy, ACF field, or GraphQL type. Introducing one requires a new explicit architecture decision based on source-proven canonical identity and editorial ownership.

## Intentionally not implemented

This release does not include additional future content types, taxonomies, ACF field groups for the Service entity, persisted queries, navigation logic, SEO settings, preview, revalidation, forms, redirects, or frontend integration.

## Local development

- WordPress: http://brounhall-wp.local/
- GraphQL: http://brounhall-wp.local/graphql
