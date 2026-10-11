# ADR-0008 — Public site (Phase 3)

- Status: accepted
- Date: 2026-10-12

## Decisions

1. **Read side = query builder + read models.** `ArticleQueries` and
   `TaxonomyQueries` (Content\Application\Query) return DTOs. The Eloquent
   implementations use the query builder with joins, never models or lazy
   relations. Bylines for any list are fetched in one extra query
   (`ArticleCardHydrator`).

2. **Window functions** (`ROW_NUMBER() OVER (PARTITION BY …)`) give the homepage
   category sections and the columnists strip in one query each. MySQL 8 and
   SQLite ≥ 3.25 (tests) both support them.

3. **Constant query counts are tested**: each key page is rendered, the content
   doubled, and the number of queries must not change (and stay under a small
   ceiling). A planted per-row byline query fails all of them.

4. **Only published articles are public.** Drafts, scheduled and unpublished
   articles answer 404; ids are never reused, so an article that comes back
   keeps its URL. `/article/{id}/{slug?}` redirects (301) to the canonical slug.

5. **Homepage** is a fixed composition (breaking ticker, hero by featured flag and
   priority, latest, columnists, the first six visible categories) until the
   block manager of Phase 5 makes it configurable.

6. **Frontend budget**: no JS framework on the public site — one module of a
   few hundred bytes (copy link, Web Share). Tailwind v4 output ~6.5 KB gzipped.
   System Arabic fonts until the self-hosted subsets of Phase 6. Images reserve
   a 16:9 box (no layout shift); the first image loads eagerly with high
   priority, the rest lazily. Responsive `srcset` and WebP come with the image
   pipeline (Phase 6).

7. **Routes belong to the module**: `Content\Presentation\Http\routes.php`,
   loaded by `ContentPresentationServiceProvider`; `routes/web.php` stays empty.

8. **Dates**: `ArabicDate` writes Gregorian dates with Arabic month names and
   Western digits in the site timezone (PRD §1); not left to Carbon's locale.

## Measured (2026-10-12, seeded demo data, production assets)
Lighthouse 12.8 mobile (simulated slow 4G, Moto G profile), against the dev
stack:

| Page | Performance | Accessibility | SEO | LCP | CLS | TBT | Transfer |
|---|---|---|---|---|---|---|---|
| Home | 98 | 100 | 100 | 1.7 s | 0 | 0 ms | 14 KiB |
| Article | 100 | 100 | 100 | 1.3 s | 0 | 0 ms | 14 KiB |

Best practices scores 79 only for "no HTTPS" (the dev stack is plain HTTP;
production terminates TLS at Cloudflare/Caddy). Demo articles have no images,
so image weight is not measured yet; the image pipeline (Phase 6) keeps
article pages under the 600 KB budget.

To repeat: build assets, stop the `vite` service, delete `public/hot`, and run
Lighthouse in a container on the `elaf_default` network against `http://nginx/`.
