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
- `seo.php` — future SEO integration
- `preview.php` — future preview integration
- `revalidation.php` — future revalidation integration
- `security.php` — future security integration

No module currently implements business behavior.

## General marketing pages

Native WordPress Pages (`page`) are the canonical CMS model for general marketing routes such as Home, About, Contact, Careers, campaigns, and other informational pages. Editors use the standard WordPress workflow: **Pages → Add New/Edit Page**.

Page identity remains native to WordPress: title, slug/permalink, publication status, author, and parent hierarchy. WPGraphQL's existing Page support exposes published Pages to the headless frontend. This plugin does not register a duplicate Page post type or add page-builder, layout, hero, SEO, section, or presentation fields.

## Global settings

`Settings → BrounHall Settings` stores the four BH-018 editorial scalar values in the `brounhall_global_settings` option: public phone, public email, public address, and footer copyright text. The native WordPress site title (`blogname`) remains the canonical site-name setting.

Media and reusable link fields are deferred to BH-019. SEO fields are deferred to BH-020. CMS settings must not contain secrets or infrastructure configuration.

## Intentionally not implemented

This release does not include custom post types, taxonomies, ACF field groups, GraphQL fields or persisted queries, navigation logic, SEO settings, preview, revalidation, forms, redirects, or frontend integration.

## Local development

- WordPress: http://brounhall-wp.local/
- GraphQL: http://brounhall-wp.local/graphql
