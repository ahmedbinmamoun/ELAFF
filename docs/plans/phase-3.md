# Phase 3 — Public site (plan)

Goal (PRD §17): layout, homepage (hero, latest, category sections, ticker),
category, article, author, series, tag pages; share bar; RSS.

**Accept:** Blade pages are RTL-correct; Lighthouse mobile ≥ 90 on home/article
using seeded data; slug redirect works; no N+1 queries (query-count assertions
on key pages).

## Read side (PRD §5.6)
- `Content\Application\Article\Query`: `ArticleQueries` and `TaxonomyQueries`
  interfaces returning read-model DTOs (`ArticleCard`, `ArticleDetail`,
  `CategorySection`, `ColumnistItem`, `Paginated`, …). No entities, no lazy loading.
- `EloquentArticleQueries` / `EloquentTaxonomyQueries` (Infrastructure): query
  builder with joins; bylines for a whole list in one extra query; homepage
  category sections in one query (`ROW_NUMBER() OVER (PARTITION BY category)`).
  The number of queries per page is constant, whatever the amount of content.
- Only published articles are public; an unpublished article's URL answers 404
  (its id is never reused).
- Caching decorators come in Phase 6.

## Pages and routes (PRD §9)
- `/` homepage: breaking ticker, hero (featured by homepage priority, then
  latest), latest list, category sections, columnists strip. Fixed composition
  for now; Phase 5 turns it into configurable blocks.
- `/article/{id}/{slug?}`: 301 to the canonical slug; kicker, title, subtitle,
  bylines with photos, dateline, date, reading time, featured image, body, share
  bar (WhatsApp first, FR-SHR-01), tags, series navigation (FR-TAX-03), related.
- `/category/{slug}`, `/author/{slug}`, `/authors` (columnists), `/series/{slug}`,
  `/tag/{slug}` with pagination.
- `/feed`, `/feed/{category}` RSS 2.0 (latest 30).
- Basic `<title>`, description and canonical now; full SEO (JSON-LD, OG,
  sitemaps) in Phase 6.

## Frontend
- Blade + Tailwind v4 tokens (ADR-0003), logical properties for RTL, system
  Arabic font stack (self-hosted subsets in Phase 6). Mobile first.
- No framework JS: one small module for the menu toggle, copy-link and Web
  Share. Images reserve their space (aspect-ratio) to avoid layout shift.
- Dates: Gregorian with Arabic month names and Western digits (PRD §1).
- Contributor bylines get "كاتب ضيف" when the setting is on (FR-CON-13).

## Tests
- Feature tests per page (content, RTL, 404s, pagination, slug 301, unpublished
  hidden), RSS validity, query-count assertions that stay flat when content
  doubles, contract-style tests of the query implementations.
- Lighthouse (mobile) on home and article against a production build.

## Out of scope
Homepage block manager, menus, ads (Phase 5); issues (Phase 4); search, SEO
metadata, sitemaps, caching, image pipeline, fonts (Phase 6).
