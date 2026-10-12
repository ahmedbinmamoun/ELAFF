# Phase 5 — Layout, ads, letters, settings, pages (plan)

Goal (PRD §17): homepage block manager (drag-and-drop), menus, ad slots and
click tracking, reader letters with moderation, settings, static pages.

**Accept:** FR-HOME-*, FR-NAV-*, FR-ADS-*, FR-LET-*, FR-SET-*, FR-PAG-*; adding
a homepage block type needs only a new class + registration (test).

## Who may do what (PRD §2)
| Ability | Editor | Editor-in-Chief | Admin |
|---|---|---|---|
| `letters.moderate` reader letters | ✔ | ✔ | ✔ |
| `homepage.manage` blocks, `menus.manage` menus | | ✔ | ✔ |
| `ads.manage` ads | | ✔ | ✔ |
| `pages.manage` static pages | | ✔ | ✔ |
| `settings.manage` site settings | | | ✔ |
| `ads.raw-html` unsanitized ad HTML (FR-ADS-02) | | | ✔ |

## Slices (one commit each, tests with each)
1. **Settings + pages** (Settings module). `settings` key → JSON value; a typed
   `SiteSettings` DTO with defaults from `config/elaf.php`; `UpdateSettings`
   (validated, `SettingsChanged`); cached read with invalidation on change
   (FR-SET-02). Admin settings page (Admin only): identity, logo, favicon,
   contact, social links, masthead, footer text, analytics id, default OG
   image, contributor switches (replacing the config-backed ports). `pages`
   with `Create/Update/DeletePage`, sanitized body, `/page/{slug}`, seeded
   about/contact/advertise/privacy with generated text.
2. **Menus** (Layout). `menus` (header, sections, footer) and `menu_items`
   (category / page / issues / url, one level of nesting, order, visibility,
   optional icon). `SaveMenu` replaces a menu's items in one validated
   command. Admin: one screen per menu with a nested, reorderable repeater.
   Public: header, drawer, sections list and footer read the menus; link
   types turn into URLs through a registry. Seeded from the sections and pages.
3. **Homepage blocks** (Layout). `homepage_blocks` (type, region, options,
   position, active) replaces `config/homepage.php` (kept as seed defaults).
   Use cases: add, configure, reorder, remove. Each block type class also
   declares its admin label and options form, so a new type is still one
   class + one registration. Admin: drag-and-drop table (reorder goes through
   the use case). New types: `breaking_ticker`, `ad_slot`.
4. **Ads** (Layout, core aggregate `Ad`). Slots in code (`AdSlot` enum with
   reserved aspect ratios), image or HTML creative, target URL, schedule,
   weight, active. `CreateAd`, `UpdateAd`, `DeleteAd`, `SelectAdForSlot`
   (weighted random through a port), `RecordAdClick`; `/ads/{id}/go` counts
   and redirects. Slots rendered in header, homepage (`ad_slot` block),
   article sidebar, inside the article body, footer; nothing rendered when no
   ad runs.
5. **Reader letters** (Engagement, core aggregate `ReaderLetter`). `/letters`
   form (honeypot, rate limit, Turnstile through `CaptchaVerifier`), plain
   text, IP stored only as a keyed hash. Moderation: approve, reject with
   reason, convert an approved letter into a draft article in "بريد القراء"
   (through a Content port). Emails and phones never leave the admin.

## Tests
Domain units (Ad schedule/weights, ReaderLetter states), Application with
fakes for every use case, contract tests for new repositories, Feature tests
for admin screens, permissions, public pages, the click redirect, the letter
form (spam guards), menus/blocks driving the public pages, query counts.

## Staging during this phase
The customer tests a frozen production build (`~/Downloads/Elaf-staging`)
sharing the dev database: Phase 5 migrations are additive only and the dev
database is never refreshed while staging runs.

## Out of scope
Search, SEO metadata/sitemaps, caching decorators for articles (Phase 6);
CSP for ad HTML and analytics (Phase 7); newsletter (Phase 8).
