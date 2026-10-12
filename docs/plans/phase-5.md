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

## Result (2026-10-14)

All five slices built and tested (755 tests, MySQL; Larastan max clean):

1. **Settings + pages**: admin settings page (identity, logo, favicon, OG
   image, contacts, social links, masthead, footer text, analytics id,
   contributor switches), cached with invalidation on change; static pages
   with `/page/{slug}`.
2. **Menus**: header, sections, footer; nested reorderable editor; public
   header (with sub-item dropdowns), drawer, sections list and footer read
   them.
3. **Homepage blocks**: drag-and-drop list, show/hide, per-type options
   forms; new types `breaking_ticker` and `ad_slot`; FR-HOME-03 shown with a
   test that registers an anonymous block class.
4. **Ads**: five slots, image or HTML, schedule, weight, click redirect,
   placements in header, footer, article sidebar, inside the article and
   between homepage sections.
5. **Reader letters**: `/letters` form with honeypot, rate limit and
   captcha; moderation queue with a pending badge; approve, reject with
   reason, convert to a draft in "بريد القراء".

### Checks
- No horizontal overflow at 360/412 px on home, article, letters, page.
- Accessibility 100 and SEO 100 on home, article, letters. A "most read"
  title link was below the 24 px target size; fixed.
- Performance could not be measured cleanly: the machine was loaded
  (Lighthouse CPU benchmark 550–890, normally well above 1000) and the
  same page swung between 69 and 98. Same-moment medians: Phase 4 build 93,
  Phase 5 build 88 on the homepage; article 90, letters 90. The JS bundle
  is byte-identical to Phase 4 and the DOM size is unchanged (772 vs 778
  nodes). **To re-measure on a quiet machine in Phase 6**, together with
  the caching work.
- Phase 4 acceptance re-checked: a 24-page PDF rendered in 38 s.
