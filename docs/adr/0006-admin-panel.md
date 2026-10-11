# ADR-0006 — Admin panel (Phase 2)

- Status: accepted
- Date: 2026-10-11

## Decisions

1. **Filament 5.10 on Livewire 4** (latest stable for Laravel 13). The panel
   provider lives in `Shared\Presentation\Filament` and lists resources from
   each module's `Presentation\Filament`. Arabic/RTL comes from the app locale
   and Filament's bundled Arabic translations.

2. **Writes only through use cases.** Create/Edit pages override
   `handleRecordCreation`/`handleRecordUpdate`; workflow buttons and delete
   buttons call one handler each. An architecture test forbids Presentation from
   using any write port (repositories, `ArticleUnitOfWork`) or Eloquent
   repository. Filament reads Eloquent models (PRD §5.2 exception).

3. **One rule, two callers.** Policies don't re-implement access rules:
   `ArticlePolicy` asks `ArticleAccess::canEdit/canSubmit` (Application) through
   a per-user `Authorizer`. Workflow buttons are shown when the user has the
   permission and `ArticleStatus::canTransitionTo` allows the move; the use case
   still decides. The approve button is hidden on one's own article (display
   only; the domain refuses it anyway).

4. **Errors in Arabic.** `InvalidInput` and `ArticleWorkflowViolation` carry a
   reason code; `UseCaseErrors` maps codes to `lang/ar/errors.php`, shown as
   form errors (on the field named by the use case) or notifications.

5. **Captioned images** (FR-ART-02) are a rich-editor custom block. The stored
   body is plain `<figure><img><figcaption>` HTML; `FigureBlockConverter`
   (Presentation) converts to and from the editor's block markup. The sanitizer
   and the public site never see editor-specific markup.

6. **Uploads** (featured images, inline figures, author photos) go to the
   `public` disk through Filament's upload field; the domain stores the path.
   The `FileStorage`/`ImageOptimizer` ports arrive with the first use case that
   handles files itself (issue PDFs, Phase 4) and WebP conversion (Phase 6).

7. **Deletes are guarded use cases**: a category, series or author still used
   by an article cannot be deleted (articles would change without a revision).
   Deleting a tag removes it from articles; restoring an old revision skips tags,
   authors and series deleted since. Articles are never deleted, only unpublished.

8. **Staff users** are managed by admins (`users.manage`) through Identity use
   cases. An admin cannot remove their own admin role. Contributors are not
   listed (Phase 2b).

9. **Filament's published assets are build output**: `filament:upgrade` runs in
   Composer's post-autoload-dump (also in the production image build) and
   `public/{css,js,fonts}/filament` are git-ignored.

10. Livewire's `max_nesting_depth` is raised to 32 so tables in the rich editor
    sync (Filament's documented requirement).
