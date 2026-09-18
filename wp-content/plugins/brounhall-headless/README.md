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

## Intentionally not implemented

This release does not include custom post types, taxonomies, ACF field groups, GraphQL fields or persisted queries, navigation logic, SEO settings, preview, revalidation, forms, redirects, or frontend integration.

## Local development

- WordPress: http://brounhall-wp.local/
- GraphQL: http://brounhall-wp.local/graphql
