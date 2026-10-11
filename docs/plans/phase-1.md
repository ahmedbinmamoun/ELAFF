# Phase 1 — Content core (plan)

Goal (PRD §17): Domain + Application + Infrastructure for Article, Category, Tag,
Series, Author; migrations; seeders (categories, roles, demo data); Spatie
roles/permissions; audit log.

**Accept:** FR-ART-01..04, 06, 08, FR-TAX-*, FR-AUT-*; state machine fully
unit-tested; contract tests green; use cases covered with in-memory fakes.

## Domain (`Elaf\Content\Domain`)
- `Article` aggregate (full entity, PRD §6.1) with `ArticleId`, `ArticleStatus`
  (enum owning the transition table), `ArticleOrigin` (staff/contributor),
  `ArticleContent` (the editable fields as one value object, also the revision
  snapshot), `Title`, `ArticleBody`, `ArticleSource`, `FeaturedImage`,
  `SeriesPlacement`, `IssuePlacement`, `Breaking`.
- Behaviour: `draft`, `revise`, `submitForReview`, `withdraw`, `approve`,
  `returnToWriter`, `publish`, `schedule`, `unpublish`, `markBreaking`,
  `unmarkBreaking`, `promote` (featured + homepage priority), `assignToIssue`.
  Each method takes the acting user and the `Clock`, enforces invariants and
  records a domain event carrying the actor (for the audit log).
- Invariants: illegal transitions throw `IllegalArticleTransition`; nobody
  approves their own article; publishable = title + (body or featured image) +
  category + ≥1 author; `published_at` ≥ `created_at`; schedule time must be in
  the future; breaking expiry must be in the future.
- Typed ids for the lightweight models (`CategoryId`, `TagId`, `SeriesId`,
  `AuthorId`) so the aggregate can reference them.
- Shared: `Slug` (Unicode/Arabic aware) moves into `Shared\Domain\Text` because
  categories, authors and (later) pages use it too; `UserId` lives in
  `Identity\Domain`; `IssueId` in `Publishing\Domain` (the Issue aggregate
  comes in Phase 4, the id is needed now for FR-ART-01's issue link).

## Application (`Elaf\Content\Application`)
- Article handlers: `CreateArticleDraft`, `UpdateArticle`,
  `SubmitArticleForReview`, `WithdrawSubmission`, `ApproveArticle`,
  `ReturnArticleToWriter`, `PublishArticle`, `ScheduleArticle`,
  `UnpublishArticle`, `PublishDueScheduledArticles`, `MarkArticleBreaking`,
  `UnmarkArticleBreaking`, `PromoteArticle`, `RestoreArticleRevision`.
- Lightweight models (PRD §6.5): `Create/Update` handlers for Category, Series,
  Author; `EnsureTagsExist` for free-form tags (FR-TAX-02). Each has a DTO and a
  repository interface; validation lives in the handlers.
- Ports: `ArticleRepository` (write: `nextId`, `get`, `save`),
  `DueScheduledArticles`, `ArticleRevisions`, `HtmlSanitizer` (FR-ART-03),
  `TagQueries::suggest` (autocomplete, FR-TAX-02).
- Authorization through the `Authorizer` port with permissions from
  `ContentPermission`. Owner-only editing and the post-submit lock (writers and
  contributors can edit only their own draft/returned articles) are enforced
  in `UpdateArticleHandler`.

## Infrastructure
- Migrations per PRD §7: `authors`, `categories` (+ `is_open_to_contributors`
  for FR-CON-06), `tags`, `series`, `articles`, `article_author`, `article_tag`,
  `article_revisions`, `article_id_sequence`.
- Eloquent models, mappers and repositories; `HtmlPurifierSanitizer` with a
  staff and a stricter contributor profile.
- Audit log: a listener writes every Article domain event to Spatie
  Activitylog (actor, time, diff); revisions are stored as JSON snapshots.
- `ContentServiceProvider`; `IdentityServiceProvider` (User model gets Spatie
  `HasRoles`).

## Seeders (idempotent)
- `RolesAndPermissionsSeeder` (PRD §2 matrix), `CategorySeeder` (FR-TAX-01),
  `DemoContentSeeder` (local only; generated Arabic text, demo staff users).

## Tests
- Unit: every state transition (legal and illegal), invariants, value objects.
- Application: every handler with in-memory fakes.
- Contract: one suite per repository/port, run on the in-memory and the
  Eloquent implementation.
- Feature: audit log written on publish, seeders idempotent, roles matrix.

## Out of scope (later phases)
- Filament resources and policies (Phase 2), contributor accounts (Phase 2b),
  public pages incl. author/series/tag pages and series prev/next (Phase 3),
  view counting (FR-ART-09, Phase 3), `search_text` + FULLTEXT index (Phase 6).
