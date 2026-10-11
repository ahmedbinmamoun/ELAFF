# Phase 2 — Admin panel (plan)

Goal (PRD §17): Filament resources for the Content module with workflow actions
(submit, approve, return, publish, schedule, unpublish), rich editor with
tables and sanitized output, revision history/restore, role-aware UI.

**Accept:** each role can do exactly what the PRD §2 matrix allows (Feature
tests per role); no Filament class contains business rules.

## Panel
- Filament 5 (Livewire 4), panel `admin` at `/admin`, Arabic/RTL (app locale),
  brand "إيلاف", teal/navy primary colour.
- `AdminPanelProvider` lives in `Shared\Presentation\Filament`; resources live in
  each module's `Presentation\Filament`.
- Access: staff roles only (writer, editor, editor-in-chief, admin). Contributors
  get their own panel in Phase 2b.
- Filament's published assets are generated (`filament:upgrade` in Composer's
  post-autoload-dump), not committed.

## Writes go through handlers
- Create/Edit pages override `handleRecordCreation`/`handleRecordUpdate` and call
  the Phase 1 handlers. `InvalidInput` becomes a form error on its field;
  `NotAuthorized` → 403; other domain errors → danger notification.
- Workflow actions (`ArticleWorkflowActions`) call one handler each. Their
  visibility asks the permission (Gate) and the domain transition table
  (`ArticleStatus::canTransitionTo`) — no rule is re-implemented in Filament.
- Policies (Presentation) delegate to Application: `ArticleAccess` gets pure
  `canEdit/canSubmit` questions so the policy and the handler share one rule.

## Resources
- **Articles**: list (writers see only their own), create, edit, view (read-only
  for locked articles). Rich editor: h2-h4, lists, quote, links, tables, and a
  "figure" block (image + alt + caption) converted to `<figure>` on save
  (`FigureBlockConverter`, presentation-only mapping). Featured image upload,
  authors (byline order), tags with autocomplete, series + part, SEO fields.
  Relation managers: Revisions (restore) and History (audit log, read-only).
- **Categories, Tags, Series, Authors**: CRUD via handlers. New use cases:
  `DeleteCategory` (refused while it has articles or subcategories),
  `RenameTag`, `DeleteTag`, `DeleteSeries`, `DeleteAuthor` (refused while bylined).
- **Users** (admin only): staff users and their role via Identity handlers
  `CreateStaffUser`, `UpdateStaffUser` (an admin cannot remove their own admin
  role). Contributor accounts are Phase 2b.

## Tests
- Feature, per role: panel access; article list scoping; create/edit/view;
  every workflow action visible and working exactly per the matrix; taxonomy
  and users resources per role; validation errors on the right field; figure
  caption round-trip; revision restore.
- Application tests for the new handlers; contract tests for the new repository
  methods; architecture test: Presentation never uses repositories (write ports).

## Out of scope
Contributor panel (2b), homepage/menus/ads/settings resources (Phase 5), issues
(Phase 4), image optimisation to WebP (Phase 6), 2FA (Phase 7).
