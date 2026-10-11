# Phase 0 — Foundation (plan)

Goal (PRD §17): `make up && make check` green on a clean clone; architecture tests exist and pass; `/up` returns 200.

## Steps
1. Laravel skeleton (latest stable) with Pest, Larastan, Pint. `src/` mapped to `Elaf\` via PSR-4.
2. `CLAUDE.md` from PRD Appendix A.
3. Module skeletons under `src/` (Shared, Content, Publishing, Layout, Engagement, Identity, Contributors, Settings, Search, Seo), each with Domain / Application / Infrastructure / Presentation.
4. Shared kernel:
   - Domain: `Clock`, `DomainEvent`, `DomainException`, integer identifier base.
   - Application ports: `EventDispatcher`, `Transaction`, `Authorizer`.
   - Infrastructure adapters: `SystemClock`, `LaravelEventDispatcher`, `DbTransaction`, `GateAuthorizer`.
   - In-memory fakes (in `tests/Support/Fakes`): `FrozenClock`, `RecordingEventDispatcher`, `ImmediateTransaction`, `InMemoryAuthorizer`.
   - `SharedServiceProvider` binds ports to adapters; one service provider per module (empty for now).
   - Ports listed in PRD §5.7 that belong to later phases (FileStorage, PdfRasterizer, …) are created with the module that first needs them, so they are not speculative.
5. Architecture tests (PRD §5.9) + strict types + final classes; contract tests for `Clock` and `Transaction`.
6. Docker: multi-stage `docker/php/Dockerfile` (PHP 8.3-FPM, pdo_mysql, redis, gd, imagick, intl, zip, bcmath, opcache, poppler-utils, non-root), nginx, MySQL 8, Redis 7, queue, scheduler, mailpit, vite (dev); `docker-compose.prod.yml`.
7. `Makefile` targets from PRD §13.3; `composer check` script as a host fallback.
8. GitHub Actions CI (PRD §13.5).
9. ADR-0001 (stack versions), ADR-0002 (identifiers), ADR-0003 (Tailwind v4 design tokens).
10. Health check feature test for `/up`.

## Out of scope
Any business feature (starts in Phase 1), Filament, Spatie packages (installed in the phase that uses them).
