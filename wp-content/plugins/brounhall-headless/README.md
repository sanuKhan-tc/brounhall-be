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
- `seo.php` — reusable SEO field group for native Pages
- `preview.php` — future preview integration
- `revalidation.php` — future revalidation integration
- `security.php` — future security integration
- `fields.php` — reusable ACF Free media/link field definitions

No module currently implements business behavior.

## General marketing pages

Native WordPress Pages (`page`) are the canonical CMS model for general marketing routes such as Home, About, Contact, Careers, campaigns, and other informational pages. Editors use the standard WordPress workflow: **Pages → Add New/Edit Page**.

Page identity remains native to WordPress: title, slug/permalink, publication status, author, and parent hierarchy. WPGraphQL's existing Page support exposes published Pages to the headless frontend. This plugin does not register a duplicate Page post type or add page-builder, layout, hero, SEO, section, or presentation fields.

## Global settings

`Settings → BrounHall Settings` stores the four BH-018 editorial scalar values in the `brounhall_global_settings` option: public phone, public email, public address, and footer copyright text. The native WordPress site title (`blogname`) remains the canonical site-name setting.

Media and reusable link fields are deferred to BH-019. SEO fields are deferred to BH-020. CMS settings must not contain secrets or infrastructure configuration.

## Common field definitions

BH-019 provides project-owned PHP factories in `includes/fields.php` because ACF Free does not provide Clone fields. Image definitions use WordPress Media Library attachment IDs; link definitions use the ACF Link field's array shape (`title`, `url`, `target`). ACF Clone is intentionally not used.

Social collections are deferred. Logo/media attachment, SEO image/link fields, page-section consumers, and GraphQL media/link fragments remain deferred to their later tasks.

## SEO fields

The `BrounHall SEO` ACF Free field group is attached to native WordPress Pages and exposed through WPGraphQL for ACF as `seo`. It provides optional meta title/description, canonical URL override, robots index/follow, Open Graph title/description/image, Twitter title/description/image, and breadcrumb title fields. Empty values intentionally allow frontend fallbacks.

The image fields reuse the BH-019 Media Library attachment-ID contract. Future CPT attachment is deferred to BH-047, the frontend `SeoFields` fragment to BH-056, and Next.js SEO rendering to BH-102+. No Yoast or Rank Math dependency is used.

## Hero fields

The `BrounHall Hero` ACF Free field group is attached to native Pages and exposed through WPGraphQL for ACF as `hero`. It provides optional eyebrow, title, subtitle, description, Hero image, primary CTA, and secondary CTA fields. Images reuse the BH-019 Media Library attachment-ID contract and links reuse the BH-019 Link contract. The current frontend Hero uses additional presentation-specific composition and repeated trust avatars; those are not modeled here because WordPress does not own presentation and ACF Free has no repeater. Frontend consumption and the `HeroFields` fragment are deferred to later frontend/page-section tasks.

## Rich Text fields

The `BrounHall Rich Text` ACF Free field group is attached to native Pages and exposed through WPGraphQL for ACF as `richText`. It provides an optional plain-text title and a plain-text body textarea; blank lines separate paragraphs. Stored HTML, scripts, events, styles, embeds, and arbitrary markup are not accepted because the current frontend renders content as plain text. Frontend consumption and the page-section GraphQL fragment are deferred to BH-058; other section types remain deferred to BH-023+.

## Text + Image fields

The `BrounHall Text + Image` ACF Free field group is attached to native Pages and exposed through WPGraphQL for ACF as `textImage`. It provides optional eyebrow, title, and plain-text body fields plus an optional image using the BH-019 WordPress Media Library attachment-ID contract. Image + Text intentionally reuses this same CMS contract: the frontend `MediaSplit` component controls normal versus reversed visual ordering. Orientation, stacking, spacing, and other presentation choices remain frontend-owned; no `imageText` field or orientation setting is stored in WordPress. Frontend consumption and the page-section GraphQL fragment are deferred to BH-058.

## Image + Text decision

BH-024 is intentionally merged into BH-023. Frontend reverse layouts use the same `MediaSplit` props and editorial content shape, so a duplicate CMS field group is not required.

## Service/Treatment entity

The reusable backend entity uses the stable technical post type key `service` and GraphQL names `Service`/`Services`, while the WordPress editor uses the source-proven user-facing labels **Treatment** and **Treatments**. Native title, slug, editor content, excerpt, and featured image provide the core entity fields. Service relationships are deferred to BH-045, SEO attachment to BH-047, Service GraphQL operations to BH-065, and frontend integration remains separate.

## Service Grid

The `BrounHall Service Grid` ACF Free field group is attached to native Pages and exposed as `serviceGrid`. Editors select and order canonical Treatments through the `service_grid_services` Relationship field, restricted to the `service` post type; Service title, slug, summary, and featured image remain on the Service entity and are not duplicated on the Page. The section also provides source-proven plain-text title/description fields and an optional BH-019 link field. The editorial selection is bounded and preserves selection order. All-Service operations remain deferred to BH-065; taxonomy filtering to future Service taxonomy work; Service relationships to BH-045; entity SEO to BH-047. Frontend pages remain static and unmodified.

## Doctor entity

The reusable people represented by the current frontend are doctors and fertility specialists, so BH-039 uses the stable `doctor` post type with **Doctor** / **Doctors** editor labels and GraphQL names. Native title, editor content, revisions, and featured image provide the name, biography, and portrait; the only custom field is the plain-text `doctor_role` field exposed through `doctorProfile`. Qualifications, languages, clinic/location, specialty, Doctor relationships, booking behavior, and entity SEO remain deferred to their owning tasks. The Doctor Grid remains deferred to BH-027 and frontend pages remain static.

## Intentionally not implemented

This release does not include additional future content types, taxonomies, ACF field groups for the Service entity, persisted queries, navigation logic, SEO settings, preview, revalidation, forms, redirects, or frontend integration.

## Local development

- WordPress: http://brounhall-wp.local/
- GraphQL: http://brounhall-wp.local/graphql
