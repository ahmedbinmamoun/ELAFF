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

## Commands
- `make up` / `make down` — start/stop the Docker dev stack.
- `make check` — Pint (test mode) + Larastan + Pest, run inside the `app` container.
- Without Docker: `composer check` runs the same gates on the host (tests use SQLite in-memory).
