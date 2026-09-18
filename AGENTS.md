# AGENTS.md — WordPress Backend Submodule

## Scope

This repository contains project-owned WordPress code and portable configuration for the headless CMS integration.

It should contain application-owned artifacts such as:

```text
backend/
├── plugin/
│   └── barun-hall-headless/
├── acf-json/
├── config/
└── README.md
```

Do not commit WordPress core, XAMPP, mutable uploads, runtime caches, local databases, production dumps, secrets, or machine-specific absolute paths.

## Backend Responsibilities

WordPress owns:

- editorial content and publish state;
- native Pages for general marketing routes;
- structured page sections;
- reusable domain entities and relationships;
- global settings;
- navigation hierarchy and mega-menu configuration;
- SEO input fields;
- media metadata;
- CMS-side preview/revalidation integration;
- project-specific GraphQL exposure and validation.

## Project Plugin Rule

Project-specific behavior belongs in a project-owned plugin, not WordPress core and not a theme `functions.php` unless the behavior is genuinely theme-specific.

Keep plugin modules separated by concern, for example:

```text
includes/
├── content-types.php
├── taxonomies.php
├── graphql.php
├── settings.php
├── navigation.php
├── seo.php
├── preview.php
├── revalidation.php
└── security.php
```

## Content Modeling

Use native WordPress Pages for normal marketing routes.

Use controlled structured sections (for example ACF Flexible Content or equivalent) mapped to known frontend components.

Typical approved section families include:

- Hero;
- Rich Text;
- Text + Image / Image + Text;
- Card Grid;
- Service / Doctor / Department grids;
- Statistics;
- Logo Grid;
- Testimonial;
- FAQ;
- Related Content;
- CTA;
- Contact/Form configuration.

Use dedicated models only for reusable domain entities where the project requires them, such as people/doctors, services, departments/specialties, articles/news and locations/facilities.

Do not give editors arbitrary JavaScript, inline event handlers, custom CSS, unrestricted iframe/embed execution or unsafe HTML capabilities.

## GraphQL Rules

1. WPGraphQL is the content API.
2. Public content is read-only.
3. Production anonymous access must be constrained to approved/persisted operations according to the project security plan.
4. Do not create public content mutations for browser forms.
5. Keep pagination bounded.
6. Avoid exposing fields not required by the frontend contract.
7. Production debug output must be disabled.
8. Production public introspection/schema exposure must follow the documented security policy.
9. Draft/private content must not be anonymously accessible.
10. New or changed fields require contract compatibility review with the frontend.

## ACF and Schema Stability

Treat field names/keys and GraphQL names as API contracts.

- Prefer additive schema changes.
- Do not casually rename/remove fields consumed by the deployed frontend.
- Provide defaults/migration logic where existing content would otherwise break.
- Version-control portable ACF definitions when the project uses ACF JSON/exported definitions.
- Keep content migrations idempotent and reversible where practical.

## Navigation and Mega Menu

WordPress owns hierarchy and content configuration; Next.js owns presentation.

Mega-menu fields must be controlled/typed. Do not store arbitrary markup as the layout contract.

Validate and sanitize menu data and URLs. Unsupported layouts/configuration must have a predictable frontend fallback.

## SEO Model

Every routable content type should expose the agreed SEO contract, including where applicable:

- meta title;
- meta description;
- canonical override;
- robots index/follow;
- Open Graph fields;
- Twitter fields;
- breadcrumb title.

Do not store infrastructure secrets or executable scripts in SEO/global settings fields.

## Revalidation

CMS publish/update/unpublish/delete events should emit a strict event payload for Next.js.

The webhook must be signed server-side and include replay-resistant timestamp information.

Do not send an unrestricted purge path that the frontend blindly executes.

## Preview

Use a dedicated, least-privilege identity/mechanism for server-to-server preview access.

Draft/revision access must remain authorized. Preview credentials are secrets and must never be committed or exposed via GraphQL/public settings.

## WordPress Security

Custom code must:

- perform capability checks for privileged actions;
- verify nonces for relevant admin actions;
- sanitize input;
- escape output;
- prefer WordPress APIs over raw SQL;
- use prepared queries when SQL is unavoidable;
- avoid logging secrets/auth headers/private payloads;
- keep file editing/debug exposure disabled in production configuration;
- work with the hosting WAF/security controls rather than bypass them.

## Local XAMPP Rules

XAMPP is development-only and not the production security boundary.

The backend source should be linked/synced into local `wp-content` using a repeatable developer-local method. Do not commit absolute XAMPP paths.

## Backend Validation Before Completion

Use the repository's actual test/tooling commands. Relevant checks may include:

- PHP syntax/lint;
- WordPress/plugin unit tests;
- content-type/taxonomy registration tests;
- GraphQL schema/contract checks;
- sanitization/capability tests;
- revalidation signature/payload tests;
- security checks for anonymous draft/private/mutation access.

Do not claim a WordPress configuration or GraphQL contract is working unless it has been verified in the appropriate environment.
