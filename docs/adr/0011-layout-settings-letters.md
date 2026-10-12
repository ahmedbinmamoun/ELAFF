# ADR-0011 — Layout, ads, letters, settings, pages (Phase 5)

- Status: accepted
- Date: 2026-10-14

## Decisions

1. **Settings are stored key by key over `.env` defaults.** `config/elaf.php`
   is the identity of a fresh installation; every value saved in the admin
   (`settings` table, JSON values) wins, and an empty saved value clears a
   default. The whole set is cached (`elaf.settings.v1`) and dropped on
   `SettingsChanged` (FR-SET-02). The contributor switches moved from config
   to settings; `StoredEditorialSettings` implements the Content and
   Contributors ports. Settings are Admin only (PRD §2).

2. **Who may do what** (PRD §2 leaves pages and letters open):
   `pages.manage` and `menus.manage`, `homepage.manage`, `ads.manage` for the
   Editor-in-Chief and Admin; `letters.moderate` for editors and above;
   `settings.manage` and `ads.raw-html` (unsanitized ad HTML, FR-ADS-02) for
   the Admin only.

3. **Menus are saved whole** (`SaveMenu`): order, one level of sub-items and
   visibility in one validated command, errors naming the item by position.
   `menu_items.target_id` has no foreign key (category or page); the public
   query drops items whose target is hidden or gone. Link types (category,
   page, issues, URL) become URLs through a registry. All menus load in one
   query; until the header/sections menus are saved, visible categories
   stand in.

4. **Homepage blocks** (`homepage_blocks`, with a `region` column the PRD
   does not list) replace `config/homepage.php`, which remains the
   composition of a fresh installation (used while the table is empty). A
   block type may also implement `ConfigurableBlock` (admin label + options
   form), so a new type is still one class + one registration (FR-HOME-03;
   tested with an anonymous class). The `BlockTypeCatalog` port the use
   cases check types against is implemented by the presentation registry.
   Drag-and-drop goes through `ReorderHomepageBlocks`, which refuses an
   order that does not name every block exactly once (stale screens).

5. **The breaking ticker is a homepage block** (`breaking_ticker`): other
   pages keep it under the header; on the homepage the Editor-in-Chief
   places or hides it. It reuses the request's header data (no query).

6. **Ads** (`Ad` aggregate): slots are an enum with recommended sizes; the
   page reserves the slot's shape, so nothing shifts. Image ads need a
   target URL; HTML ads may carry their own links and are sanitized
   (staff profile) unless the actor holds `ads.raw-html`. Active ads load in
   one query per request (scoped read model) and each slot picks by weight
   through a `Randomness` port. `/ads/{id}/go` counts with one atomic UPDATE
   and redirects; impressions are not tracked (FR-ADS-03). Ads are soft
   deleted (PRD §7). Inline article ads go after the third paragraph.

7. **Reader letters** (`ReaderLetter` aggregate): plain text, never rendered
   as HTML; the IP is stored only as an HMAC with the app key. Spam guards:
   honeypot (bots get the thank-you page and nothing is stored), 5 posts a
   minute per IP, the `CaptchaVerifier` port. The PRD suggests Livewire for
   this form; a plain server-rendered form needs no JavaScript and keeps the
   JS budget. Conversion goes through Content's `CreateArticleDraft` (its
   checks apply) in the "بريد القراء" section and byline, created on first
   use; only the sender's name crosses to Content (FR-LET-03).

8. **Static pages** live in the Settings module (PRD §5.3) with a sanitized
   body (staff profile) and `/page/{slug}`. Demo pages are local only; in
   production the newspaper writes them.

## Consequences
- Every public page pays two fixed queries more than in Phase 4 (settings
  until cached, active ads); the query-count tests were raised by those.
- Analytics loads Google's tag when an id is set; it is outside the JS
  budget and needs CSP rules (Phase 7).
