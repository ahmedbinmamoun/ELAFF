# Phase 4 — Issues archive (plan)

Goal (PRD §17): issue upload, queued rasterization, viewer, index, PDF
download, `issues:import`, link articles to issue/page.

**Accept:** FR-ISS-01..06; a PDF uploaded in the admin is rendered to WebP
pages by the queue; failures show in the admin with a retry; `/issues` and
`/issues/{number}` work without JS; the homepage "current issue" block and the
article's print link use the latest ready issue; `make check` green.

## Publishing domain (core aggregate, PRD §6.2)
- `Issue`: `IssueId`, `IssueNumber` (positive int, unique), `PublicationDate`,
  title, `IssueFile` (path, size, checksum), `ProcessingStatus`
  (pending → processing → ready | failed; failed → pending on retry), page
  count, cover, pages (`IssuePage`: number, image, thumb, width, height),
  processing error.
- Behaviour: `upload()`, `revise()` (number, date, title), `replaceFile()`
  (back to pending), `startProcessing()`, `markReady()`, `markFailed()`,
  `retry()`. Events `IssueUploaded`, `IssueProcessed`,
  `IssueProcessingFailed`, `IssueProcessingRetried`, `IssueRevised`.
- Invariants: a ready issue has as many page images as its page count (≥ 1);
  only a pending issue starts processing (makes the job idempotent).

## Application
- `IssuePermission::ManageIssues` (Editor-in-Chief, Admin; PRD §2 matrix).
- Use cases: `UploadIssue`, `UpdateIssue` (optional PDF replacement),
  `DeleteIssue` (refused while articles are linked), `ProcessIssuePdf` (queued),
  `RetryIssueProcessing`, `FailIssueProcessing` (a crashed/timed-out job),
  `ImportIssuesFromDirectory`, `RecordIssueDownload`.
- `AssignArticleToIssue` lives in **Content** (it changes the Article
  aggregate); it checks the issue through a Content port `PrintIssues`
  implemented by Publishing (ADR-0010).
- Ports (PRD §5.7): `IssueRepository`, `IssueFiles` (FileStorage for issues:
  sniff, store, delete), `PdfRasterizer`, `ImageOptimizer`, `IssueDownloads`.
- `IssueStorageLayout`: where files go, from number + checksum
  (`issues/{number}/{checksum10}/…`). A replaced PDF gets a new folder, so
  every file URL can be cached for a long time.
- `IssueFileName`: parses `إيلاف_{day}_{month}_{year}.pdf` (numeric or Arabic
  month names, Arabic-Indic digits).
- Read side: `IssueQueries` → `IssueSummary`, `IssueDetail` (pages + table of
  contents of published articles grouped by page), `latest()`, `paginate(year)`,
  `years()`. Only ready issues are public. `Paginated` moves to Shared.

## Infrastructure
- Migration: `issues`, `issue_pages` (PRD §7, plus width/height for the viewer
  and `downloads_count`, `processed_at`); `articles.issue_id` gets its foreign
  key.
- `PopplerPdfRasterizer`: `pdftoppm -r 110 -png` through Symfony Process with an
  argument array (no shell) and a timeout, into a temporary folder.
- `ImagickImageOptimizer`: WebP full (≤ 1100 px wide) + thumb (320 px), social
  card 1200×630 JPEG from the cover.
- `PublicDiskIssueFiles`: public disk; PDF sniffed by magic bytes and finfo.
- Listener queues `ProcessIssuePdfJob` on upload/retry; the job's `failed()`
  calls `FailIssueProcessing`.

## Presentation
- Admin `IssueResource`: list (cover, number, date, pages, status badge,
  downloads; filters status/year; polling while processing), create/edit with
  PDF upload (≤ 150 MB, Livewire temporary upload limit raised), view with
  error and table of contents, retry and delete actions through use cases.
- Article admin: "link to print issue" action (issue + page) for editors.
- Public: `/issues` (year filter, pagination, latest issue first),
  `/issues/{number}` (cover, date/number, PDF button, table of contents, page
  viewer), `/issues/{number}/pdf` (counts, then redirects to the file).
- Viewer: a plain list of lazy page images without JS; Alpine adds a
  full-screen viewer with swipe, keyboard arrows, zoom, page thumbnails.
- Homepage `current_issue` block renders the latest ready issue; the article
  page links to "العدد N، صفحة P".
- `php artisan issues:import {directory} {--csv=} {--first-number=} {--dry-run}`.

## Tests
- Domain unit (status machine, invariants, value objects), Application with
  in-memory fakes for every use case, contract tests for `IssueRepository`
  (in-memory + Eloquent) and `IssueQueries`, file-name parser unit tests.
- Integration: real `pdftoppm` + Imagick on a generated PDF (skipped when
  poppler is missing).
- Feature: admin upload/retry/delete/permissions, public pages (no-JS
  viewer, 404 for non-ready issues, year filter, pagination), download
  counter + redirect, import command (pattern, CSV, dry run), homepage block,
  article link, query counts.

## Out of scope
OCR/text search of issues (Phase 8), caching (Phase 6), JSON-LD for issues
(Phase 6), S3 storage.

## Result (2026-10-12)

- **Pipeline**: upload in the admin → `IssueUploaded` → queued
  `ProcessIssuePdfJob` → `pdftoppm` 110 DPI → WebP 1100 px + 320 px thumbs +
  1200×630 social JPEG → ready. Checked end to end on the Redis queue and in a
  test with the real tools (generated PDFs, `DemoPdf`).
- **Admin**: Issues resource (EiC/Admin) with cover, status badge, polling,
  failure reason, retry, delete (refused while articles are linked), PDF
  replacement; "ربط بالعدد المطبوع" action on articles (editors+), with the
  page checked against the page count.
- **Public**: `/issues` (latest featured, year chips, 24 per page),
  `/issues/{n}` (cover, PDF button with size, contents by page, previous/next
  issue, page strip, full page list, full-screen viewer), `/issues/{n}/pdf`,
  homepage `current_issue` block, "نُشر في النسخة المطبوعة" link on articles.
- **Import**: `issues:import` with file-name dates (numeric or Arabic months,
  Arabic-Indic digits), `--first-number`, `--csv`, `--dry-run`; re-runnable.
- **Demo data**: six weekly issues (Thursdays) with four generated pages; the
  latest has eight demo articles linked.

### Checks
- `make check`: 645 tests (MySQL), also green on SQLite; Larastan max clean.
- Viewer driven in headless Chromium at 390 px: open, ArrowLeft/ArrowRight,
  right swipe, double-tap zoom, Escape; focus on close, scroll locked, no JS
  errors.
- No horizontal overflow at 360/412 px on home, article, `/issues`, issue.
- Lighthouse 12.8 mobile, production assets:

| Page | Performance | Accessibility | SEO | LCP | CLS | TBT |
|---|---|---|---|---|---|---|
| `/issues` | 100 | 100 | 100 | 1.3 s | 0 | 10 ms |
| `/issues/120` | 100 | 100 | 100 | 1.3 s | 0 | 30 ms |
| Home | 100 | 100 | 100 | 1.7 s | 0 | 20 ms |

  Best practices 79: plain HTTP in development. JS 19.9 KB gz, CSS 10.4 KB gz.

### Found and fixed
- The new homepage card made the single-column mobile grid wider than the
  screen (grid items default to `min-width: auto`): CLS 0.62 at 360 px. The
  aside now has `min-w-0` and the card uses stacked buttons.
- Headless Chromium crashed on long pages: Docker's 64 MB `/dev/shm`; run it
  with `--shm-size=1g` (README).
- Blade's `@json` cannot parse a nested closure; the viewer data is built in
  a `@php` line.
