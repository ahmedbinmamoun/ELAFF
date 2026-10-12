# ADR-0010 — Print issues archive (Phase 4)

- Status: accepted
- Date: 2026-10-12

## Decisions

1. **Issue is a full aggregate** (PRD §5.1): `Issue` with `IssueNumber`,
   `PublicationDate`, `IssueFile`, `IssueRendering` (pages + social image) and
   the `ProcessingStatus` machine pending → processing → ready | failed,
   failed → pending on retry; a replaced PDF sends any state back to pending.
   Ids come from an `issue_id_sequence` table, like articles (ADR-0005).

2. **Linking an article to an issue page is a Content use case**
   (`AssignArticleToIssue`), not a Publishing one as PRD §8 lists it: it
   changes the Article aggregate, whose writes belong to Content. Content asks
   about issues through its own port `PrintIssues`, implemented in
   `Publishing\Infrastructure`. Editors and above may link (it is article
   metadata); managing issues is the Editor-in-Chief's and Admin's
   (`issues.manage`, PRD §2).

3. **Storage layout** `issues/{number}/{first 12 hex of SHA-256}/…` on the
   public disk (`elaf.issues.disk`): the PDF, `pages/NNN.webp` (≤ 1100 px),
   `pages/NNN-thumb.webp` (320 px) and `social.jpg` (1200×630). A replaced PDF
   gets a new folder, so every URL always serves the same bytes; the old
   folder is removed after the change is saved. PDFs are public: the paper
   distributes them freely (PRD §1).

4. **Download** `/issues/{n}/pdf` increments `downloads_count` with one atomic
   UPDATE, then redirects (302) to the file. nginx serves it; no PHP worker is
   held for a 150 MB stream. Throttled to 30/min per client.

5. **Ports** (PRD §5.7): `IssueFiles` (the FileStorage port narrowed to what
   issues need: sniff, store, delete), `PdfRasterizer` (poppler `pdftoppm`,
   110 DPI, argument array, 240 s limit), `ImageOptimizer` (Imagick WebP),
   `IssueDownloads`, `IssueArticleLinks`. Upload type is decided by content
   (`%PDF-` magic and libmagic), never by the file name.

6. **Failure handling** (FR-ISS-06): rendering errors become a reason key on
   the issue (`pdf_unreadable`, `timeout`, `tool_missing`, `image_failed`,
   `no_pages`, `job_crashed`) translated in the admin; details go to the log.
   The job has one attempt; its `failed()` hook records `job_crashed` so an
   issue never stays "processing". Only a pending issue is processed, so a
   duplicate job is harmless. Retrying is the editor's decision.

7. **Bulk import** (`issues:import`, FR-ISS-05) runs from the server's shell,
   which is trusted: it uploads through the same `IssueUploader` as the admin
   but without the permission check, and its events carry no actor. Numbers
   are never guessed: they come from a CSV map or from `--first-number`
   counted up in date order; `--dry-run` shows the plan first.

8. **Public pages show ready issues only.** The viewer is a plain list of
   lazy page images (works without JS) plus an Alpine full-screen viewer:
   swipe, arrow keys (RTL: ArrowLeft and a right swipe go forward), zoom,
   prefetch of neighbours. Livewire's temporary upload limit is raised to
   150 MB / 30 minutes for the admin upload.

9. **Shared `Paginated`** moved from Content to `Shared\Application\Query`,
   since both modules page their read models.

## Consequences
- Rendering takes about 1–2 s per page in the dev container; a 24-page issue
  finishes well inside the job's 290 s limit.
- S3 storage would need the rasterizer to download the PDF first; out of
  scope (single VPS, PRD §13.2).
- Issue caching and JSON-LD come with Phase 6.
