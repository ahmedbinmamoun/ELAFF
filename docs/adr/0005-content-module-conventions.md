# ADR-0005 — Content module conventions (Phase 1)

- Status: accepted
- Date: 2026-10-11

Decisions taken while building the Content core that the PRD leaves open.

## 1. Article ids from a sequence table
ADR-0002 says the Eloquent repository reserves an id by inserting the row.
An article row cannot exist before it has a category and an author (non-null
foreign keys), so ids come from a one-column `article_id_sequence` table
instead. Ids stay auto-increment integers; gaps are harmless.

## 2. Ids shared across modules
`UserId` lives in `Identity\Domain`, `IssueId` in `Publishing\Domain`; the
Content domain references them (an article has an author user and may sit
on an issue page). A domain may depend on another module's **domain** ids,
never on its Application/Infrastructure. `Slug` and `OptionalText` are
shared value objects in `Shared\Domain\Text`.

## 3. Who acts
Commands carry `actorId` (the signed-in user, filled in by Presentation).
It is recorded on every domain event and in the audit log, and it drives
"own article" checks. Permission checks go through the `Authorizer` port,
which asks Laravel's Gate about the same signed-in user. Article handlers
enforce the PRD §2 matrix themselves (`ArticleAccess`), so Filament and the
contributor portal cannot bypass it.

`PublishDueScheduledArticles` has no user: the publication is attributed to
the last user who acted on the article (the one who scheduled it).

## 4. Audit log and revisions (FR-ART-08)
- Every article event (created, updated with a field diff, submitted, …)
  is written to Spatie Activitylog (`log_name = articles`) by
  `RecordArticleActivity`, after the transaction commits.
- Each content save also stores a JSON snapshot in `article_revisions`;
  restoring one is itself a new revision.

## 5. Article workflow details
- `returned → in_review` on resubmit (the PRD diagram draws the arrow to
  `draft`; resubmitting directly is the same thing for the writer).
- A writer can withdraw an `in_review` article back to `draft` (FR-CON-06).
- Publishing a scheduled article early is allowed; it is published "now".
- Republishing an unpublished article keeps its original `published_at`.
- Tags are a set: stored sorted by id so equal content compares equal.

## 6. Spatie migrations
The published migration stubs read `config()` at runtime and fail Larastan
level max. They are replaced by equivalent typed migrations with literal
table names (no teams); `config/activitylog.php` uses the literal table name
to match.

## 7. Contributor categories
Until PRD §19 Q9 is answered, the seeded opinion sections (رأي اقتصادي,
رأي سياسي) are open to contributors. Editors can change this per category.
