# PRD — Elaf (إيلاف) Newspaper Website

> Product Requirements Document written for **Claude Code**.
> Stack: Laravel + MySQL + Blade/Tailwind (RTL) + Filament admin, running in Docker.
> Architecture: **Clean Architecture** + **SOLID**, enforced by automated architecture tests.

---

## 0. How Claude Code must use this document

1. Read the whole document before writing any code.
2. First action: create `CLAUDE.md` in the repo root from **Appendix A** so the architecture rules persist across sessions.
3. Work **phase by phase** (Section 17). One phase = a small series of commits. Do not start a phase before the previous one passes its acceptance criteria.
4. At the start of each phase, write a short plan in `docs/plans/phase-N.md`, then implement.
5. Before finishing any task, run the quality gates (Section 15): `make check` must pass (Pint, PHPStan/Larastan, Pest incl. architecture tests).
6. Do not add a dependency outside the **Approved Stack** (Section 5.4) without writing an ADR in `docs/adr/NNNN-title.md` that explains why.
7. If something is ambiguous, pick the simplest option that respects the architecture rules, record the decision in an ADR, and continue. Ask the human only for items listed in Section 19 (Open Questions).
8. Never copy real articles from the newspaper into seeds or tests. Use generated demo content.

---

## 1. Product context and goals

**Client:** a Sudanese weekly newspaper (Arabic, economic and political focus). It publishes one print issue per week (Thursday), numbered sequentially, plus online news between issues. The print edition is distributed as a PDF.

**Problem:** the paper currently circulates only as PDF. Content is not searchable, not indexed by Google, not shareable per article, and has no archive.

**Goals**
- G1. Publish articles online daily, in addition to the weekly print issue.
- G2. Keep a browsable, downloadable **archive of print issues** (cover, pages, PDF).
- G3. Make every article a shareable, SEO-friendly page (Google News ready).
- G4. Let the editorial team manage **everything** (sections, menus, homepage layout, ads, settings) without a developer.
- G5. Work well on **weak mobile connections** (primary audience: Sudan and diaspora).
- G6. Be simple to hand over to the client (Docker, documentation, backups).
- G7. Let external writers register and submit articles that go live **only after editorial approval**.

**Non-goals for v1:** subscriptions/paywall, user comments, native mobile apps, video hosting, English UI, e-commerce, newsletters (design the ports, build in Phase 2 if time allows).

**Locale facts**
- Language: Arabic only, **RTL** everywhere. UI strings in `lang/ar`.
- Timezone: `Africa/Khartoum`. Dates are Gregorian, shown with Arabic month names (أكتوبر, نوفمبر…). Western digits (0-9).
- Currency/numbers appear only inside article text; no currency logic.

---

## 2. Users, roles and permissions

| Role | Description |
|---|---|
| Reader (anonymous) | Reads articles, browses issues, searches, submits reader letters |
| Writer | Newsroom staff writer: writes articles, edits only own drafts, submits for review |
| Contributor | External writer who **self-registers**, writes only in the contributor portal; **every article requires editorial approval** (see 2.1 and FR-CON) |
| Editor | Edits any article, approves or sends back, moderates reader letters |
| Editor-in-Chief | Publishes/schedules/unpublishes, controls homepage, breaking news, ads, issues |
| Admin | Everything, plus users, roles, settings, backups |

Permission matrix (✔ allowed, ○ own items only, ✖ denied):

| Capability | Writer | Editor | Editor-in-Chief | Admin |
|---|---|---|---|---|
| Create article / edit draft | ○ | ✔ | ✔ | ✔ |
| Submit for review | ○ | ✔ | ✔ | ✔ |
| Approve / return to writer | ✖ | ✔ | ✔ | ✔ |
| Publish / schedule / unpublish | ✖ | ✖ | ✔ | ✔ |
| Mark breaking / featured | ✖ | ✖ | ✔ | ✔ |
| Manage categories, tags, series | ✖ | ✔ | ✔ | ✔ |
| Manage issues (upload PDF) | ✖ | ✖ | ✔ | ✔ |
| Moderate reader letters | ✖ | ✔ | ✔ | ✔ |
| Edit homepage blocks, menus | ✖ | ✖ | ✔ | ✔ |
| Manage ads | ✖ | ✖ | ✔ | ✔ |
| Site settings, users, roles | ✖ | ✖ | ✖ | ✔ |

Authorization is implemented with Laravel Policies backed by role/permission checks (Spatie Permission). Policies live in the Presentation layer and call a small `Authorizer` port when Application code needs a permission check.

### 2.1 Contributors (external writers)

Contributors are not staff. They register themselves, are approved once by the editorial team, and can then submit articles. **A contributor can never publish.**

| Capability | Contributor |
|---|---|
| Register, verify email, edit own profile | ✔ (display name change needs Editor approval) |
| Create / edit own **draft** or **returned** article | ○ |
| Submit for review / withdraw a submission | ○ |
| Edit an article while it is in review or after approval | ✖ (locked) |
| Publish / schedule / mark breaking or featured | ✖ |
| Choose category | ✔ only among categories enabled for contributors |
| See other people's articles, admin panel, analytics | ✖ |

Flow: `register → verify email → account approved by Editor → write → submit → Editor approves (or returns with a note) → Editor-in-Chief publishes`.

---

## 3. Functional requirements

IDs are stable; reference them in commits and tests (e.g. `FR-ART-04`).

### 3.1 Articles (FR-ART)
- FR-ART-01 An article has: `kicker` (small headline above the title, optional), `title`, `subtitle` (optional), `dateline` (e.g. "الخرطوم", optional), `body` (rich HTML), `excerpt`, featured image with caption and credit, one or more authors, `source` (e.g. "إيلاف", "رويترز", "نقلاً عن …"), category, tags, optional series membership (`series_id`, `series_part`), optional print issue link (`issue_id`, `issue_page`).
- FR-ART-02 Rich text editor supports headings (h2-h4), lists, blockquote, links, **tables**, inline images with captions, embeds disabled by default.
- FR-ART-03 Body HTML is sanitized on save (allow-list). Scripts, iframes and inline event handlers are always stripped.
- FR-ART-04 Status workflow (state machine in the domain):
  `draft → in_review → approved → published`, plus `scheduled` (approved with a future `published_at`), `unpublished`, and `returned` (back to writer with a note). Illegal transitions throw a domain exception.
- FR-ART-05 Scheduled articles go live automatically (scheduler runs every minute).
- FR-ART-06 `is_breaking` articles appear in the breaking ticker; breaking status can carry an expiry time.
- FR-ART-07 `is_featured` and `homepage_priority` influence the hero block.
- FR-ART-08 Every state change and edit is recorded in an audit log with actor, time, and diff. Editors can restore a previous revision.
- FR-ART-09 Reading time and view count are tracked (view count updated asynchronously, bots ignored).
- FR-ART-10 Unicode (Arabic) slugs. Public URL: `/article/{id}/{slug}`; wrong slug → 301 to canonical.

### 3.2 Categories, tags, series (FR-TAX)
- FR-TAX-01 Categories are fully editable (name, slug, description, order, visibility, optional parent). Seed with the paper's current sections: أخبار، أخبار الأسبوع، أسواق، تقارير، ما وراء الأخبار، تحقيق، حوار، رأي اقتصادي، رأي سياسي، من صحافة العالم، بريد القراء، أخيرة.
- FR-TAX-02 Tags are free-form with autocomplete.
- FR-TAX-03 A **series** groups multi-part articles (e.g. "كيف نفهم الحرب والسلام (3)"): ordered parts, series page, previous/next links inside each article.

### 3.3 Authors and columnists (FR-AUT)
- FR-AUT-01 Author profile: name, slug, photo, short bio, role/title, optional social links, optional linked staff user.
- FR-AUT-02 Author page lists their articles. Columnists (flag `is_columnist`) appear in a homepage block with photo and latest column title.

### 3.4 Print issues archive (FR-ISS)
- FR-ISS-01 An issue has: number (unique), publication date, optional title/note, PDF file, cover image, page images.
- FR-ISS-02 Uploading a PDF triggers a queued job that renders the **cover and every page to WebP** (full + thumbnail) and stores page count.
- FR-ISS-03 Issue page: cover, date/number, table of contents (articles linked to this issue, grouped by page order), page viewer (swipe/zoom), PDF download button.
- FR-ISS-04 Issues index with year filter and pagination. "Latest issue" is available to the homepage.
- FR-ISS-05 Artisan command `issues:import {directory}` bulk-imports existing PDFs (number/date parsed from the filename pattern `إيلاف_{day}_{month}_{year}.pdf` or a CSV map).
- FR-ISS-06 Processing failures are visible in the admin (status: pending, processing, ready, failed) with a retry action.

### 3.4b Pages (FR-PAG)
- FR-PAG-01 Static pages (about, contact, advertise, privacy) editable in admin, with slug and SEO fields.

### 3.5 Homepage layout (FR-HOME)
- FR-HOME-01 The homepage is an **ordered list of blocks** stored in the database, editable by drag-and-drop in admin: activate/deactivate, reorder, configure.
- FR-HOME-02 Block types (v1): `hero` (lead story + secondary stories), `breaking_ticker`, `latest_articles`, `category_section` (pick category, count), `columnists`, `current_issue`, `ad_slot`, `most_read`.
- FR-HOME-03 New block types must be addable by creating one class and registering it (Open/Closed). No `switch` on block type anywhere.

### 3.6 Navigation (FR-NAV)
- FR-NAV-01 Header menu, footer menu and a "sections" menu are managed in admin: items link to a category, page, issue index, or custom URL; nested one level; ordered; show/hide.

### 3.7 Ads (FR-ADS)
- FR-ADS-01 Named **slots** (`header_banner`, `home_between_sections`, `article_sidebar`, `article_inline`, `footer`) are defined in code; ads are managed in admin.
- FR-ADS-02 Ad: title, image or HTML snippet (sanitized unless admin), target URL, slot, start/end date, active flag, weight (rotation).
- FR-ADS-03 Click tracking via redirect route `/ads/{id}/go` (counts clicks). Impressions are not tracked in v1.
- FR-ADS-04 Official notices (full-page images such as central bank announcements) can be published as an article with a large image and no body; no special feature needed.

### 3.8 Reader letters — "بريد القراء" (FR-LET)
- FR-LET-01 Public form: name, email (private), optional phone, title, message. Spam protection: honeypot, rate limit, and Cloudflare Turnstile (behind a port).
- FR-LET-02 Moderation queue: `pending → approved | rejected`. An approved letter can be converted into an article in the "بريد القراء" category.
- FR-LET-03 Reader emails and phones are never shown publicly.

### 3.9 Search (FR-SRC)
- FR-SRC-01 Search across title, subtitle, excerpt, body, author names, tags. Arabic-aware normalization (see 12.2).
- FR-SRC-02 Filters: category, date range, author. Results are paginated and highlight the query in excerpts.

### 3.10 SEO and syndication (FR-SEO)
- FR-SEO-01 Per-page title/description, canonical, Open Graph and Twitter cards. Overrides per article/page in admin.
- FR-SEO-02 JSON-LD `NewsArticle` on article pages, `BreadcrumbList` on categories, `Organization` site-wide.
- FR-SEO-03 `sitemap.xml` (index), `news-sitemap.xml` (articles from the last 48 hours), `robots.txt`, RSS feed for latest and per category.

### 3.11 Settings (FR-SET)
- FR-SET-01 Key-value settings editable in admin: site name, tagline, logo, favicon, contact email, phone, address, WhatsApp channel URL, Facebook URL, other social links, footer text, masthead (editor-in-chief, publisher), analytics ID, default OG image.
- FR-SET-02 Settings are cached and invalidated on change.

### 3.12 Sharing (FR-SHR)
- FR-SHR-01 Share buttons: WhatsApp, Facebook, X, copy link, native share (Web Share API when available). WhatsApp is first (primary channel for this audience).

### 3.13 Contributors — self-registration and moderated submissions (FR-CON)
- FR-CON-01 Public registration at `/writers/register`: name, email, password, short bio, optional phone, optional photo, and **mandatory acceptance of the contributor terms** (originality, responsibility for content, right of the paper to edit). Protected by honeypot, rate limit and Cloudflare Turnstile (behind the `CaptchaVerifier` port). Responses must not reveal whether an email is already registered.
- FR-CON-02 Email verification is required before anything else (`pending_email → pending_approval`).
- FR-CON-03 **Account approval** by an Editor or higher (`pending_approval → active | rejected`). Controlled by setting `contributors.require_account_approval` (default **true**). Pending users can sign in only to see their status.
- FR-CON-04 On approval, an `Author` profile is created and linked automatically (slug from the name) so bylines and author pages work. Editors can edit it.
- FR-CON-05 **Contributor portal** `/writer`: a second Filament panel with its own login and a minimal navigation: My Articles, New Article, My Profile. It only ever queries articles where `created_by = current user`.
- FR-CON-06 In the portal a contributor can: write with the same rich editor (tables, images with captions), upload a featured image, choose a category (only those flagged "open to contributors"), add tags, **submit for review**, **withdraw** a submission back to draft, and read editor notes on returned articles.
- FR-CON-07 Submitting locks the article for the contributor. During review, Editors may edit the text (every change goes to the revision history) and then **approve** or **return with a mandatory note**.
- FR-CON-08 After approval the article waits in `approved` until the Editor-in-Chief publishes or schedules it (default). Setting `contributors.editor_can_publish` (default **false**) lets Editors publish contributor articles directly.
- FR-CON-09 Once published, a contributor cannot edit the article (corrections go through the editors).
- FR-CON-10 Hard limits: at most 5 articles in `in_review` per contributor at once, maximum body length, throttled submissions.
- FR-CON-11 Emails (queued through the `Mailer` port): editors are notified of new registrations and submissions; contributors are notified when their account is approved/rejected and when an article is approved, returned (with the note), or published.
- FR-CON-12 Editors can **suspend** a contributor: login blocked, existing published articles stay online unless explicitly unpublished (bulk "unpublish all by this contributor" action available).
- FR-CON-13 Public byline may show a "كاتب ضيف" label for contributors (toggle in settings).

---

## 4. Non-functional requirements

| Area | Requirement |
|---|---|
| Performance | Mobile Lighthouse Performance ≥ 90 on home, category, article. LCP ≤ 2.5 s on simulated Slow 4G. Article page weight ≤ 600 KB (no ads), HTML ≤ 80 KB gzipped. |
| JS budget | ≤ 40 KB gzipped total on public pages (Alpine + small modules). No SPA framework on the public site. |
| Images | WebP, responsive `srcset`, `width`/`height` always set, lazy-loaded below the fold. |
| Fonts | Self-hosted, subsetted Arabic WOFF2 (Cairo for UI, Noto Naskh Arabic for body), `font-display: swap`. No third-party font CDNs. |
| Caching | Anonymous HTML cacheable; cache invalidated by domain events on publish/unpublish/edit. Cloudflare caches anonymous pages with short TTL + stale-while-revalidate. |
| SEO | Server-rendered HTML for all public pages. Valid structured data. |
| Accessibility | WCAG 2.1 AA for the public site: contrast, focus states, alt text, semantic headings, RTL-correct reading order. |
| Security | OWASP Top 10 aware. CSRF, rate limiting, strict CSP, security headers, validated uploads, hashed passwords, optional 2FA for Editor-in-Chief/Admin. |
| Reliability | Daily automated backups (DB + uploaded files) to storage the client owns. Restore procedure documented and tested once. |
| Observability | Structured logs, failed-jobs table, health endpoint `/up`, error tracking port (Sentry optional). |
| Maintainability | PHPStan level max, strict types, architecture tests, ≥ 80% coverage on Application, ≥ 90% on Domain. |
| Portability | Everything runs from `docker compose up`. No host-specific paths. |

---

## 5. Architecture

### 5.1 Principles
- **Clean Architecture** (dependency rule): source code dependencies point **inward only**.
  `Presentation → Application → Domain` and `Infrastructure → Application/Domain`.
  Nothing in an inner layer knows about an outer layer.
- **Modular by bounded context**, each module has the same four layers.
- **Pragmatism rule (anti over-engineering):** full domain modelling (entities, value objects, mappers) is required only for **core aggregates**: `Article`, `Issue`, `ReaderLetter`, `Ad`. Simple configuration data (`Menu`, `Page`, `Setting`, `Tag`, `HomepageBlock`) uses lightweight Application services plus repository interfaces and may return plain DTOs; do not invent entity classes with no behaviour.
- Framework is a detail: Laravel lives in Infrastructure and Presentation; Domain and Application are plain PHP.

### 5.2 Layers and what they contain

| Layer | Contains | May depend on | Must NOT use |
|---|---|---|---|
| **Domain** | Entities, value objects, enums, domain services, domain events, repository **interfaces**, domain exceptions | Nothing (PHP std only) | `Illuminate\*`, Eloquent, Facades, `config()`, `now()` |
| **Application** | Use cases (**Actions**), command/query DTOs, read models, **ports** (interfaces for outside world), application events/listeners contracts | Domain | `Illuminate\Http`, Eloquent, Facades, Filament, Blade |
| **Infrastructure** | Eloquent models, repository implementations, mappers, adapters for ports (storage, PDF, search, sanitizer, clock, mail), migrations, queue jobs (thin) | Application, Domain, frameworks | Business rules |
| **Presentation** | HTTP controllers, FormRequests, view models, Blade views, Filament resources/pages, Artisan commands, Policies | Application (use cases), Domain (enums/VOs for display) | Direct Eloquent writes, business rules |

**Allowed boundary exception (documented):** Filament resources and read-only list/detail queries in Presentation may import Infrastructure **Eloquent models** for tables and forms. **All state-changing operations must still go through Application Actions** (Filament `handleRecordCreation`, `handleRecordUpdate`, custom Actions call the use case). Public site reads go through read-model query interfaces (see 5.6).

### 5.3 Directory layout

```
app/                       # Laravel skeleton only (Providers, Http/Kernel glue)
src/                       # PSR-4 namespace: Elaf\
  Shared/
    Domain/                # Identifier VOs, DomainEvent base, Clock interface, exceptions
    Application/           # Ports shared by modules (EventDispatcher, Transaction, Authorizer)
    Infrastructure/        # SystemClock, LaravelEventDispatcher, DbTransaction
  Content/                 # Articles, categories, tags, series, authors
    Domain/ Application/ Infrastructure/ Presentation/
  Publishing/              # Print issues, PDF processing, issue viewer
    Domain/ Application/ Infrastructure/ Presentation/
  Layout/                  # Homepage blocks, menus, ad slots + ads
    Domain/ Application/ Infrastructure/ Presentation/
  Engagement/              # Reader letters, (later) newsletter
    Domain/ Application/ Infrastructure/ Presentation/
  Identity/                # Staff users, roles, permissions
  Contributors/            # Self-registration, approval, contributor portal (second Filament panel)
    Domain/ Application/ Infrastructure/ Presentation/
  Settings/                # Site settings, static pages
  Search/                  # Search port + adapters, Arabic normalizer
  Seo/                     # Meta builders, sitemap, RSS, JSON-LD
resources/views/           # Blade (public) organised by module
resources/css, resources/js
database/migrations, database/seeders, database/factories
tests/{Unit,Application,Feature,Architecture}
docker/                    # Dockerfiles, nginx, php.ini, entrypoints
docs/{adr,plans,runbooks}
```

Inside a module, example (`Content`):

```
Content/Domain/Article/Article.php               # aggregate root
Content/Domain/Article/ArticleId.php             # value object
Content/Domain/Article/ArticleStatus.php         # enum + transition rules
Content/Domain/Article/Slug.php                  # value object (unicode slug)
Content/Domain/Article/ArticleRepository.php     # interface (write side)
Content/Domain/Article/Events/ArticlePublished.php
Content/Application/Article/PublishArticle/PublishArticleCommand.php
Content/Application/Article/PublishArticle/PublishArticleHandler.php   # use case
Content/Application/Article/Query/ArticleQueries.php                   # read interface
Content/Application/Article/Query/ArticleListItem.php                  # read model DTO
Content/Infrastructure/Persistence/EloquentArticleRepository.php
Content/Infrastructure/Persistence/Models/ArticleModel.php
Content/Infrastructure/Persistence/ArticleMapper.php
Content/Presentation/Http/ArticleController.php
Content/Presentation/Filament/ArticleResource.php
```

### 5.4 Approved stack
PHP 8.3+, latest stable Laravel (verify with Composer; record version in ADR-0001), MySQL 8, Redis 7, Filament (latest stable compatible with Laravel), Tailwind CSS (with RTL support), Alpine.js, Livewire (only where listed), Spatie Permission, Spatie Media Library (or plain Storage if simpler; decide in ADR), Spatie Activitylog, Spatie Backup, HTMLPurifier (via a Laravel wrapper), Imagick + poppler-utils (`pdftoppm`), Pest, Larastan, Laravel Pint. Anything else needs an ADR.

### 5.5 SOLID, applied concretely

- **S — Single Responsibility.** One use case = one class (`PublishArticleHandler`). Controllers only translate HTTP ⇄ commands/queries. Mappers only map. A class that needs "and" in its description gets split.
- **O — Open/Closed.** Behaviour that varies is a **strategy behind an interface, resolved from a registry**, never a `switch`/`if` chain on a type string: homepage block renderers (`HomepageBlockRenderer`), ad slot policies, search engines (`ArticleSearch`), PDF rasterizers. Adding a new block type = add a class + register in a service provider; no edits to existing classes.
- **L — Liskov Substitution.** Every implementation must honour its interface contract exactly (same exceptions, same null semantics). Write **contract tests** that run against every implementation of a port (e.g. the same suite runs on `InMemoryArticleRepository` and `EloquentArticleRepository`).
- **I — Interface Segregation.** Small, role-based interfaces: `ArticleRepository` (write: `get`, `save`, `nextId`) is separate from `ArticleQueries` (read: `latest`, `byCategory`, `search`…). Clients never depend on methods they don't call. No "god" repository.
- **D — Dependency Inversion.** Application and Domain depend on interfaces they own. Bindings live in module service providers (`ContentServiceProvider` etc.). Constructor injection only; **no Facades, `app()`, `config()`, `now()`, `auth()` inside Domain or Application**. Time comes from the `Clock` port; randomness/IDs from injected generators.

### 5.6 Write side vs read side (CQRS-lite)
- **Write:** Controller/Filament → Command DTO → Handler (use case) → loads aggregate via `*Repository` → domain method → `save` → dispatches domain events.
- **Read:** Controller → `*Queries` interface → returns read-model DTOs optimised for views (no entities, no lazy loading, no N+1). Implementations in Infrastructure may use Eloquent/Query Builder with eager loading and caching decorators (`CachedArticleQueries` wraps `EloquentArticleQueries` — Decorator pattern).

### 5.7 Ports (interfaces owned by Application/Domain)
`Clock`, `EventDispatcher`, `Transaction`, `Authorizer`, `FileStorage`, `ImageOptimizer`, `PdfRasterizer`, `HtmlSanitizer`, `ArticleSearch`, `SlugGenerator`, `CaptchaVerifier`, `Mailer`, `CacheInvalidator`, `ErrorReporter`.
Each has at least a production adapter and an in-memory/fake adapter for tests.

### 5.8 Domain events and listeners
`ArticlePublished`, `ArticleUnpublished`, `ArticleUpdated`, `IssueUploaded`, `IssueProcessed`, `IssueProcessingFailed`, `ReaderLetterReceived`, `ReaderLetterApproved`, `SettingsChanged`, `HomepageLayoutChanged`.
Listeners (Infrastructure) handle cache invalidation, search indexing, sitemap refresh, audit log, notifications. Slow work is queued.

### 5.9 Enforcing the rules (mandatory)
Add architecture tests (Pest `arch()` or Deptrac) that fail the build when violated:

```php
arch('domain is framework-free')
    ->expect('Elaf\*\Domain')->not->toUse(['Illuminate', 'Filament', 'Livewire']);

arch('application does not know infrastructure or presentation')
    ->expect('Elaf\*\Application')
    ->not->toUse(['Elaf\*\Infrastructure', 'Elaf\*\Presentation', 'Illuminate\Http', 'Illuminate\Database']);

arch('no facades in domain/application')
    ->expect(['Elaf\*\Domain', 'Elaf\*\Application'])->not->toUse('Illuminate\Support\Facades');

arch('handlers are final and readonly-friendly')
    ->expect('Elaf\*\Application')->classes->toBeFinal();
```

Also: `declare(strict_types=1)` in every file, classes `final` by default, value objects `readonly`, enums for closed sets, no static state.

---

## 6. Domain model (core aggregates)

### 6.1 Article (aggregate root)
- Identity: `ArticleId`. Value objects: `Slug`, `Title`, `ArticleBody` (sanitized HTML), `Dateline`, `ArticleSource`, `PublishedAt`.
- Behaviour (methods on the aggregate, each enforces invariants):
  `submitForReview()`, `approve(EditorId)`, `returnToWriter(EditorId, Note)`, `publish(Clock)`, `schedule(DateTimeImmutable)`, `unpublish()`, `markBreaking(?DateTimeImmutable $until)`, `unmarkBreaking()`, `revise(ArticleContent)`, `assignToIssue(IssueId, PageNumber)`.
- Invariants: a published article must have title, body (or featured image for notice-type), category, at least one author; `published_at` cannot be before `created_at`; only `approved` or `scheduled` can be published; unpublished articles keep their URL; an article created by a Contributor can never skip `in_review → approved`, approval requires an Editor-or-higher actor (checked through the `Authorizer` port in the handler), and nobody can approve their own article.
- State machine:

```
draft ──submit──▶ in_review ──approve──▶ approved ──publish──▶ published
  ▲                  │                      │  └─schedule──▶ scheduled ──(time)──▶ published
  └──── returned ◀───┘                      └──────────────────────────▶ (unpublish) unpublished ──publish──▶ published
```

### 6.2 Issue (aggregate root)
- `IssueId`, `IssueNumber` (VO, positive int, unique), `PublicationDate`, `IssueFile` (path, size, checksum), `ProcessingStatus` (pending/processing/ready/failed), `PageCount`, cover path, page image paths.
- Behaviour: `startProcessing()`, `markReady(PageCount, paths)`, `markFailed(reason)`, `retry()`.

### 6.3 ReaderLetter (aggregate root)
- `ReaderLetterId`, sender (name, private email/phone), title, body, `LetterStatus` (pending/approved/rejected), moderator note.
- Behaviour: `approve()`, `reject(reason)`, `convertToArticle(...)` (returns a command for the Content module through an application service, not a direct dependency).

### 6.4 Ad (aggregate root)
- `AdId`, slot (enum `AdSlot`), creative (image path or sanitized HTML), target URL, schedule window (`DateRange`), weight, `active`.
- Behaviour: `isRunningAt(Clock)`, `activate()`, `deactivate()`, `reschedule(DateRange)`, `registerClick()` (counter in read model, not on the aggregate).

### 6.5 Lightweight models (no rich entities needed)
`Category`, `Tag`, `Series`, `Author`, `Menu`/`MenuItem`, `Page`, `Setting`, `HomepageBlock` — repository interface + DTO, validation in Application layer.

---

## 7. Database schema (MySQL 8, utf8mb4_unicode_ci, InnoDB)

Use ULIDs or auto-increment ids (decide in ADR-0002; the public URL uses a numeric id, so **auto-increment bigint** is the default). All tables have `created_at`, `updated_at`; soft deletes on `articles`, `authors`, `ads`.

| Table | Key columns |
|---|---|
| `users` | id, name, email (unique), email_verified_at, password, type (staff/contributor), status (pending_email/pending_approval/active/rejected/suspended), is_active, last_login_at |
| `contributor_profiles` | id, user_id FK (unique), author_id FK null, phone null, bio, terms_accepted_at, approved_by FK null, approved_at null, rejection_reason null |
| roles/permissions | Spatie Permission tables |
| `authors` | id, name, slug (unique), photo_path, title, bio, is_columnist, user_id (nullable FK) |
| `categories` | id, parent_id (nullable), name, slug (unique), description, sort_order, is_visible, seo_title, seo_description |
| `tags` | id, name, slug (unique) |
| `series` | id, title, slug (unique), description |
| `issues` | id, number (unique), published_on (date, indexed), title, pdf_path, pdf_size, pdf_checksum, cover_path, page_count, processing_status, processing_error |
| `issue_pages` | id, issue_id FK, page_number, image_path, thumb_path, unique(issue_id, page_number) |
| `articles` | id, category_id FK, series_id FK null, series_part null, issue_id FK null, issue_page null, kicker, title, subtitle, slug, dateline, excerpt, body (longtext), search_text (longtext, normalized), featured_image_path, featured_image_caption, featured_image_credit, source_name, source_url null, status (indexed), published_at (indexed), scheduled_for null, is_breaking, breaking_until null, is_featured, homepage_priority, views_count, reading_minutes, seo_title, seo_description, canonical_url null, created_by FK, updated_by FK, reviewed_by FK null, submitted_at null, return_note null, deleted_at. Indexes: (status, published_at), (category_id, status, published_at), FULLTEXT(title, subtitle, excerpt, search_text). |
| `article_author` | article_id, author_id, position |
| `article_tag` | article_id, tag_id |
| `article_revisions` | id, article_id, user_id, snapshot (json), created_at |
| `ads` | id, title, slot, image_path null, html null, target_url, starts_at null, ends_at null, weight, is_active, clicks_count |
| `homepage_blocks` | id, type (string key), config (json), position, is_active |
| `menus` / `menu_items` | menu: id, key (header/footer/sections); item: id, menu_id, parent_id null, label, type (category/page/issues/url), target_id null, url null, position, is_visible |
| `pages` | id, slug (unique), title, body, is_published, seo_title, seo_description |
| `reader_letters` | id, name, email, phone null, title, body, status, moderator_note null, article_id null FK, ip_hash |
| `settings` | key (PK), value (json) |
| `activity_log` | Spatie Activitylog |
| `jobs`, `failed_jobs`, `cache`, `sessions` | Laravel defaults (Redis for cache/queue/session in Docker) |

---

## 8. Use cases (Application layer, one handler each)

**Content:** `CreateArticleDraft`, `UpdateArticle`, `SubmitArticleForReview`, `ApproveArticle`, `ReturnArticleToWriter`, `PublishArticle`, `ScheduleArticle`, `UnpublishArticle`, `PublishDueScheduledArticles` (scheduler), `MarkArticleBreaking`, `RestoreArticleRevision`, `RecordArticleView`, `ManageCategory`, `ManageTag`, `ManageSeries`, `ManageAuthor`.
**Publishing:** `UploadIssue`, `ProcessIssuePdf` (queued), `RetryIssueProcessing`, `AssignArticleToIssue`, `ImportIssuesFromDirectory`.
**Layout:** `ReorderHomepageBlocks`, `ConfigureHomepageBlock`, `ManageMenu`, `CreateAd`, `UpdateAd`, `RecordAdClick`, `SelectAdForSlot`.
**Engagement:** `SubmitReaderLetter`, `ApproveReaderLetter`, `RejectReaderLetter`, `ConvertLetterToArticle`.
**Contributors:** `RegisterContributor`, `VerifyContributorEmail`, `ApproveContributor`, `RejectContributor`, `SuspendContributor`, `ReinstateContributor`, `WithdrawSubmission`, `UnpublishAllByContributor` (article submission reuses `SubmitArticleForReview`; limits enforced in the handler).
**Settings:** `UpdateSettings`, `UpdatePage`.
**Queries (read side):** `LatestArticles`, `ArticlesByCategory`, `ArticlesByAuthor`, `ArticlesBySeries`, `ArticlesByTag`, `ArticleDetail` (with related, series nav), `MostRead(period)`, `BreakingNow`, `HomepageBlocks`, `IssueList`, `IssueDetail`, `SearchArticles`, `SitemapEntries`.

Each handler: validates its command (or receives an already-validated command), uses `Transaction` when it touches more than one aggregate, emits domain events after commit.

---

## 9. Public routes (server-rendered)

| Route | Purpose |
|---|---|
| `/` | Homepage (blocks) |
| `/category/{slug}` | Category listing (paginated) |
| `/article/{id}/{slug?}` | Article page (canonical redirect) |
| `/author/{slug}`, `/authors` | Author page / columnists index |
| `/series/{slug}` | Series page |
| `/tag/{slug}` | Tag listing |
| `/issues`, `/issues/{number}` | Archive index / issue page with viewer |
| `/issues/{number}/pdf` | PDF download (counts downloads, streams or redirects to signed URL) |
| `/search?q=` | Search |
| `/letters`, `POST /letters` | Reader letters form + submit (throttled) |
| `/page/{slug}` | Static pages |
| `/ads/{id}/go` | Ad click redirect |
| `/feed`, `/feed/{category}` | RSS |
| `/sitemap.xml`, `/news-sitemap.xml`, `/robots.txt`, `/up` | SEO and health |

Contributor portal: `/writers/register` (public), `/writer` (second Filament panel, own login, own articles only).

Admin panel: `/admin` (Filament). Resources: Articles, Contributors (approve/reject/suspend), Categories, Tags, Series, Authors, Issues, Reader Letters, Ads, Homepage (custom page with sortable blocks), Menus, Pages, Users/Roles, Settings, Activity Log, Backups.

---

## 10. Frontend requirements

- Blade + Tailwind (logical properties `ms-*`, `me-*`, `ps-*`, `pe-*`; `dir="rtl"` on `<html>`). Alpine for small interactions (menus, ticker, tabs, viewer controls). Livewire only for: search suggestions (optional) and the reader-letter form.
- Layouts: `layouts/app.blade.php`, partials for header, footer, breaking ticker, ad slot, share bar, article card (3 variants: lead, standard, compact).
- Article page: kicker, title, subtitle, byline (authors with photos), dateline + date + reading time, featured image with caption/credit, body with typographic styles for tables/quotes/captions, share bar, tags, series navigation, related articles, ad slots, link to the print issue and page.
- Homepage visual language: classic newspaper — strong typographic hierarchy, hero lead story, dense lists, columnist strip, "current issue" card with cover.
- No layout shift: reserve space for ads and images.
- Dark mode is optional and **out of scope** for v1.
- All user-visible strings go through `__()` with keys in `lang/ar`.
- Provide a single design-token file (colors, spacing, font sizes) in `tailwind.config.js` so the client can re-brand quickly; initial palette from the masthead (deep teal/navy + red accent) — adjust once the logo is provided.

---

## 11. Print issue pipeline (Publishing module)

1. Admin uploads PDF (max 150 MB, `application/pdf` verified by content sniffing, not by extension).
2. `UploadIssue` stores the file via `FileStorage`, creates `Issue(status=pending)`, dispatches `IssueUploaded`.
3. Listener queues `ProcessIssuePdf` job → handler calls `PdfRasterizer` port.
4. Adapter `PopplerPdfRasterizer` (shell out to `pdftoppm` with timeouts and escaped args, never string-concatenated user input) renders each page at ~110 DPI → `ImageOptimizer` converts to WebP (full ≈ 1100 px wide, thumb ≈ 320 px). Cover = page 1 (+ social image 1200×630).
5. On success: `markReady(pageCount, paths)`, event `IssueProcessed` (invalidates caches). On failure: `markFailed(reason)` and admin shows a retry button.
6. Page viewer: lazy-loads page images, swipe, pinch-zoom, keyboard arrows, "download PDF". Works without JS as a plain list of images.
7. Optional (Phase 2): OCR/text layer for archive search. Do **not** attempt to auto-import articles from PDF text; extracted Arabic text from these PDFs is unreliable (letters are dropped or reordered).

---

## 12. Search, SEO, caching

### 12.1 Search port
`ArticleSearch::search(SearchQuery): SearchResultPage`. Default adapter `MysqlFulltextArticleSearch`. A `MeilisearchArticleSearch` adapter may be added later without touching callers (Open/Closed).

### 12.2 Arabic normalization (`ArabicTextNormalizer`, pure domain/shared service, unit-tested)
Applied to indexed text **and** queries: remove tashkeel and tatweel; unify alef forms (أ إ آ → ا); `ى → ي`; `ة → ه`; normalize Arabic-Indic digits to Western; collapse whitespace; strip HTML. Store the result in `articles.search_text`, refreshed by a listener on `ArticleUpdated`.

### 12.3 SEO
`SeoMetaBuilder` (Application service) returns title, description, canonical, OG, Twitter and JSON-LD for each page type through a `SeoContext` DTO. Sitemaps are generated by a scheduled command and cached. `news-sitemap.xml` only lists articles published in the last 48 hours.

### 12.4 Caching
- `CachedArticleQueries` decorator (Redis) for home blocks, category lists, most-read, menus, settings.
- Invalidation via listeners on domain events (`CacheInvalidator` port) using cache tags.
- Responses for anonymous GET requests send `Cache-Control: public, max-age=60, stale-while-revalidate=300`; admin and any response with a session cookie are never public-cacheable.

---

## 13. Docker and infrastructure

### 13.1 Services (`docker-compose.yml` for development)
| Service | Image / notes |
|---|---|
| `app` | PHP 8.3-FPM, extensions: pdo_mysql, redis, gd, imagick, intl, zip, bcmath, opcache; `poppler-utils`; non-root user |
| `nginx` | Serves `public/`, gzip (and brotli if available), long cache headers for hashed assets, security headers |
| `mysql` | MySQL 8, named volume `mysql_data`, utf8mb4 |
| `redis` | Redis 7 (cache, queue, sessions) |
| `queue` | Same image as `app`, runs `php artisan queue:work --tries=3 --timeout=300` |
| `scheduler` | Same image, runs `php artisan schedule:work` |
| `mailpit` | Dev only (SMTP catcher) |
| `vite` | Dev only (HMR) |

### 13.2 Production (`docker-compose.prod.yml`)
- Image built in CI, `restart: unless-stopped`, healthchecks, no source mounts, no mailpit/vite.
- Config via environment file **outside the image**; `.env.example` documents every variable.
- Named volumes: `mysql_data`, `storage_data` (uploads, issue pages). Backup these.
- TLS: Cloudflare in front (Full/Strict with origin certificate) or Caddy/Traefik with Let's Encrypt (document both in `docs/runbooks/deploy.md`).
- Target: a single small VPS (≥ 2 GB RAM, 4 GB recommended). Must run under `docker compose up -d` with no manual steps besides filling `.env`.

### 13.3 Developer ergonomics
`Makefile` targets: `make up`, `make down`, `make install`, `make migrate`, `make seed`, `make test`, `make check` (pint + phpstan + pest), `make shell`, `make fresh` (migrate:fresh --seed).

### 13.4 Backups
Spatie Backup nightly: DB dump + `storage` to a disk configured by env (local path, S3-compatible, or SFTP). Retention 7 daily / 4 weekly. Runbook explains restore (`docs/runbooks/restore.md`) and the **handover** procedure to a new server.

### 13.5 CI (GitHub Actions)
On every push/PR: `composer validate`, Pint (check), Larastan, Pest (unit, application, feature, architecture) with MySQL service, `npm run build`, Docker image build. Main branch builds and tags the production image.

---

## 14. Testing strategy

| Level | Scope | Rules |
|---|---|---|
| Unit (Domain) | Aggregates, value objects, state machine, `ArabicTextNormalizer`, `Slug` | Pure PHP, no framework boot, fast |
| Application | Handlers with **in-memory fakes** of ports and repositories | Proves DIP: no DB needed |
| Contract | Same test suite against every implementation of a port | e.g. `ArticleRepositoryContract` run on in-memory + Eloquent |
| Feature | HTTP routes, Filament resources (Livewire testing), policies, scheduler | Uses real DB (MySQL in CI) |
| Architecture | Layer rules (5.9) | Must run in CI |
| Non-functional | Lighthouse CI on home/article (budget in Section 4) | Warn in CI, fail on regression > 5 points |

Test data comes from factories with **fake Arabic text** (a small Arabic lorem generator), never from real articles.

---

## 15. Quality gates and conventions

- `make check` = Pint (Laravel preset) + Larastan (level max) + Pest. Must be green before every commit that ends a task.
- Naming: handlers `VerbNounHandler`, commands `VerbNounCommand`, read models `NounListItem`/`NounDetail`, interfaces without `I` prefix or `Interface` suffix unless ambiguous (`ArticleRepository`, `PdfRasterizer`).
- No business logic in controllers, Blade, Filament resources, Eloquent models, migrations, or listeners that are not thin delegators.
- No `static` helpers with state; no service locator; no god classes (> 200 lines needs justification).
- Git: trunk-based with short branches `phase-N/short-name`, **Conventional Commits** (`feat(content): publish article use case`), reference FR ids.
- Every architectural decision that deviates from this document gets an ADR.

---

## 16. Security checklist (verify in Phase 7)

- [ ] All inputs validated (FormRequests / command validation); output escaped in Blade; rich text sanitized with an allow-list.
- [ ] CSRF on all state-changing routes; `SameSite=Lax` cookies; secure + httpOnly in production.
- [ ] Rate limits: login, reader letters, search, ad click, view counter.
- [ ] Strict CSP (no inline scripts except nonce'd), `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS behind HTTPS.
- [ ] Upload checks: MIME sniffing, size limits, randomized filenames, no execution in upload dirs.
- [ ] Shell calls (`pdftoppm`) use argument arrays, timeouts, and a temp directory.
- [ ] Admin: strong password policy, optional 2FA for Editor-in-Chief and Admin, login throttling, session timeout.
- [ ] Reader PII (email/phone) never exposed publicly; IPs stored hashed.
- [ ] Contributor registration: Turnstile + honeypot + throttling, email verification, no account-enumeration leaks, password policy, terms acceptance stored with timestamp.
- [ ] Panel isolation: contributor sessions cannot reach `/admin`; contributor queries are always scoped to `created_by` (tested); policies deny every transition except submit/withdraw.
- [ ] Contributor rich text is sanitized with the **strict** profile (no embeds, no raw HTML blocks), stricter than staff.
- [ ] Secrets only in environment; none committed; dependency audit (`composer audit`, `npm audit`) clean.
- [ ] Backups encrypted at rest on the target disk where supported.

---

## 17. Delivery phases and acceptance criteria

### Phase 0 — Foundation
- Repo, Docker (dev + prod), Makefile, CI, Laravel install, `src/` PSR-4 autoload, module skeletons, base ports and in-memory fakes, architecture tests, `CLAUDE.md`, ADR-0001 (stack versions).
- **Accept:** `make up && make check` green on a clean clone; architecture tests exist and pass; `/up` returns 200.

### Phase 1 — Content core
- Domain + Application + Infrastructure for Article, Category, Tag, Series, Author; migrations, seeders (categories, roles, demo data); Spatie roles/permissions; audit log.
- **Accept:** FR-ART-01..04, 06, 08, FR-TAX-*, FR-AUT-*; state machine fully unit-tested; contract tests green; use cases covered with in-memory fakes.

### Phase 2 — Admin panel (Filament)
- Resources for the above with workflow actions (submit, approve, return, publish, schedule, unpublish), rich editor with tables and sanitized output, revision history/restore, role-aware UI.
- **Accept:** each role can do exactly what the matrix in Section 2 allows (Feature tests per role); no Filament class contains business rules.

### Phase 2b — Contributors portal
- Registration, email verification, account approval, contributor panel, locked-on-submit behaviour, editor notes on return, limits, notification emails, suspend action.
- **Accept:** FR-CON-01..13; Feature tests prove a contributor **cannot** publish, schedule, mark breaking, see others' articles, or reach `/admin`; an Editor can approve/return; only Editor-in-Chief (or Editors when `contributors.editor_can_publish` is on) can publish a contributor article.

### Phase 3 — Public site
- Layout, homepage (hero, latest, category sections, ticker), category, article, author, series, tag pages; share bar; RSS.
- **Accept:** Blade pages are RTL-correct; Lighthouse mobile ≥ 90 on home/article using seeded data; slug redirect works; no N+1 queries (query-count assertions on key pages).

### Phase 4 — Issues archive
- Issue upload, queued rasterization, viewer, index, PDF download, `issues:import`, link articles to issue/page.
- **Accept:** a 20+ page sample PDF processes in the queue; viewer works with and without JS; failed processing shows retry.

### Phase 5 — Layout, ads, letters, settings, pages
- Homepage block manager (drag-and-drop), menus, ad slots and click tracking, reader letters with moderation, settings, static pages.
- **Accept:** FR-HOME-*, FR-NAV-*, FR-ADS-*, FR-LET-*, FR-SET-*, FR-PAG-*; adding a new homepage block type requires only a new class + registration (demonstrate with a test).

### Phase 6 — Search, SEO, performance
- Arabic-aware search, sitemaps, JSON-LD, caching decorators with event-based invalidation, image/asset pipeline, font subsetting.
- **Accept:** FR-SRC-*, FR-SEO-*; Section 4 budgets met; structured data passes validation; cache invalidates within 5 s of publish.

### Phase 7 — Hardening and handover
- Security checklist, backups + restore drill, runbooks (deploy, restore, add editor, rotate secrets), README, user guide for editors (Arabic, short, with screenshots), final ADR review.
- **Accept:** fresh VPS deploy from scratch in < 30 minutes following the runbook; restore drill documented; all checklists ticked.

### Phase 8 (optional) — Newsletter, OCR archive search, Meilisearch
Only after sign-off of Phase 7.

---

## 18. Definition of Done (every task)

1. Code follows the layer rules; architecture tests pass.
2. Tests written at the right level (Section 14); coverage targets respected.
3. `make check` is green; no new PHPStan baseline entries.
4. Strings localized; RTL verified at 360 px width.
5. Migrations are reversible; seeders are idempotent.
6. Docs updated (README/runbook/ADR) when behaviour or setup changes.
7. Commit messages reference the FR id(s).

---

## 19. Assumptions and open questions

Assumptions (proceed with these unless told otherwise):
- Public language is Arabic only; Gregorian dates.
- Logo, brand colors and WhatsApp/Facebook URLs are not available yet → use settings placeholders and a neutral palette.
- Articles are entered by editors in the admin (no automatic import from PDF).
- Client will own the hosting account, domain, repository and backup storage at handover.

Open questions (ask the human, then record the answer in an ADR):
1. Does the client have the source files (Word/InDesign) of articles to speed up data entry?
2. Should previous issues be back-filled (how many, from which year)?
3. Final domain name and hosting provider/plan?
4. Is Cloudflare Turnstile acceptable for spam protection, or should a different captcha be used?
5. Are ads sold directly only, or is an ad network (e.g., AdSense) wanted later?
6. Is a newsletter desired in the first release?
7. Should contributor **accounts** need editor approval (default: yes) or only email verification?
8. After an Editor approves a contributor article, should it wait for the Editor-in-Chief (default) or publish immediately?
9. Which categories are open to contributors, and should contributors be paid or credited in any special way (affects the byline label)?

---

## Appendix A — `CLAUDE.md` (create this file in the repo root)

```markdown
# Elaf Newspaper — Project rules for Claude Code

Source of truth: `PRD.md`. Language of code, comments, commits: English. UI text: Arabic (`lang/ar`).

## Architecture (non-negotiable)
- Clean Architecture, modules under `src/` (namespace `Elaf\`): Shared, Content, Publishing, Layout, Engagement, Identity, Contributors, Settings, Search, Seo.
- Each module has Domain / Application / Infrastructure / Presentation.
- Dependency rule: Presentation → Application → Domain; Infrastructure → Application/Domain. Never the reverse.
- Domain and Application: plain PHP. No Illuminate, Eloquent, Facades, `config()`, `now()`, `auth()`.
- Writes go through Application handlers (one use case per class). Reads go through `*Queries` interfaces returning DTOs.
- Filament/Controllers contain no business rules. Filament may read Eloquent models; it must write via handlers.
- Variation points use interfaces + registries (no `switch` on type strings). Inject dependencies via constructors.
- Pragmatism: full entities only for Article, Issue, ReaderLetter, Ad. Others are lightweight DTO + repository.

## Quality
- `declare(strict_types=1)`, `final` classes by default, `readonly` value objects, enums for closed sets.
- Run `make check` (Pint + Larastan max + Pest incl. architecture tests) before finishing any task.
- Tests: Domain unit, Application with in-memory fakes, contract tests per port, Feature tests for HTTP/Filament.
- Never use real article content in seeds/tests; use generated Arabic demo text.

## Workflow
- Work phase by phase (PRD §17). Plan in `docs/plans/phase-N.md`. Conventional Commits referencing FR ids.
- New dependency or deviation → write an ADR in `docs/adr/`.
- Performance and RTL are acceptance criteria, not polish.
```

## Appendix B — Kickoff prompt to paste into Claude Code

```text
Read PRD.md fully. Create CLAUDE.md from Appendix A, then execute Phase 0 only:
Docker (dev + prod), Makefile, CI, Laravel install with the src/ PSR-4 module skeleton,
base ports with in-memory fakes, architecture tests, ADR-0001 with the exact stack versions.
Write the plan in docs/plans/phase-0.md first. Stop when `make up && make check` is green and
summarise what you did and what Phase 1 will start with.
```
