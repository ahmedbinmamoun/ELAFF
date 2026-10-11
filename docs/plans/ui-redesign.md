# Public UI redesign (before Phase 4)

Reference: `docs/design/home.jpeg` (desktop). No mobile mockup exists in the
repo; the mobile layout follows the written brief (mobile first, then 768 px
and 1024 px+). Verified at 360, 390, 768 and 1280 px.

## Visual language
- Navy masthead bar and footer, red accent for active nav item, kicker badges
  and "عاجل"; light grey page background with white, softly rounded cards.
- Tokens (colors, fonts, radii, spacing) in `tailwind.config.js`, loaded by
  Tailwind v4 through `@config` (supersedes the CSS `@theme` block of ADR-0003).
- RTL logical utilities only; touch targets ≥ 44 px.

## Layout
- **Mobile header**: hamburger (drawer with all sections), logo + tagline,
  search toggle. Section nav scrolls horizontally; extra sections under
  "المزيد". Breaking ticker with "عاجل" badge and prev/next (Alpine).
- **Desktop header**: date + social links row, logo + masthead row, navy nav bar
  with search field.
- **Bottom tab bar** (mobile only, hidden ≥ 768 px, safe-area aware): الرئيسية,
  الأعداد (/issues), المفضلة (/favorites), حسابي (/writer/login).
- **Homepage = blocks**. `config/homepage.php` lists blocks (type, region,
  options); each type is a renderer class registered in a registry
  (`HomepageBlockRenderer`, Open/Closed, FR-HOME-03). Types: `hero`,
  `latest_articles`, `most_read`, `category_section`, `current_issue`. Phase 5
  moves the list from config to the `homepage_blocks` table and the admin.
  Regions: `top`, `main`, `aside` (desktop three columns with the sections
  list), `bottom`.
- **Cards**: hero (image, dark gradient, kicker badge, title, excerpt, date —
  white on ≥ 75 % black for contrast ≥ 4.5:1), medium card, list card (120×90
  thumbnail), numbered "most read" list.
- **Favorites**: client-side only (Alpine + localStorage). `/favorites` renders
  saved ids through `GET /favorites/cards?ids=…` (HTML fragment).
- Same language on article, category, author, tag, series, columnists pages and
  placeholders for issues (Phase 4) and search (Phase 6).

## Additions outside the presentation layer (deliberate, additive)
- `ArticleQueries::mostRead()` and `::cards()` (read side only). Most read
  orders by `views_count`, then recency, until view counting (FR-ART-09) fills
  the counter.
- `categories.icon` (optional key from a fixed icon set) and
  `articles.print_section` (optional, the printed paper's section). Saved
  through the existing use cases, so their input DTOs and the article content
  value object gain one optional field each.
- Web categories seeded (أخبار السودان، الاقتصاد، النفط والطاقة، السياسة، العالم،
  تقارير وتحقيقات، آراء وكتابات، مقالات، منوعات) with icons; the twelve print
  sections become the `print_section` options (config).
- Site identity (tagline, masthead names, contact email, social links, footer
  links) comes from `config/elaf.php` until the Settings module (Phase 5).

## Not covered yet
- `srcset`/WebP: the list-card markup emits `srcset` for WebP derivatives when
  they exist; the image pipeline that creates them is Phase 6.
- `current_issue` renders nothing until issues exist (Phase 4).

## Result (2026-10-12)

### Checked at 360, 390, 768 and 1280 px
Screenshots of home, article and category in headless Chromium with Arabic
fonts, against production assets and seeded demo data (generated text and
abstract gradient pictures):
- 360/390: header (hamburger, logo + tagline, search), scrolling section bar
  with "المزيد", عاجل ticker with prev/next, hero, list cards, latest strip,
  numbered most-read list, bottom tab bar.
- 768: same stack, wider cards, tab bar hidden; category sections two-up.
- 1280: matches `home.jpeg`: date/social row, logo + motto + masthead, navy
  nav bar with search, hero with side list, sections list with icons, two
  cards + latest strip, most read, columnists, category sections.

### Lighthouse 12.8 mobile (simulated slow 4G)
| Page | Performance | Accessibility | SEO | LCP | CLS | TBT |
|---|---|---|---|---|---|---|
| Home | 97 | 100 | 100 | 1.8 s | 0 | 140 ms |
| Article | 94 | 100 | 100 | 1.7 s | 0 | 240 ms |

Best practices 79 = plain HTTP in development only. Budgets: JS 19.7 KB gz
(Alpine 3 included; limit 40 KB), CSS 10.3 KB gz, home 240 KiB total with
demo images.

Alpine's start-up walk over the whole DOM first pushed the article to 81
(TBT 750 ms). Fixes: `x-ignore` on the article body (nothing interactive
there) and `content-visibility: auto` on below-the-fold regions (asides,
related, footer).

### Found and fixed along the way
- Uploaded images were never served in development: the dev nginx container
  mounted only `public/`, so the `public/storage` symlink pointed nowhere.
  Production had the same gap (the web image copied `public/` without the
  symlink, created only at runtime in the app container). Fixed in
  `docker-compose.yml` (uploads mounted read-only) and the Dockerfile (relative
  symlink baked into the image).
- Filament's Blade Icons package owns `<x-icon>`; ours is `<x-svg-icon>`.
- Header data (sections, ticker) is computed once per request
  (`PublicChrome`, scoped) and shared with the layout and page views.

### Notes for later phases
- Alpine's standard build evaluates expressions with `new Function`; the
  strict CSP of Phase 7 needs the `@alpinejs/csp` build (components are
  already written as `Alpine.data`, the inline expressions are short).
- The homepage block list moves from `config/homepage.php` to the database and
  the admin in Phase 5 (FR-HOME-01); the registry and renderers stay.
- `current_issue` renders once Phase 4 provides issues; `/issues` and `/search`
  are styled placeholders until Phases 4 and 6.
