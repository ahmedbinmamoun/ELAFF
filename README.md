# إيلاف — Elaf newspaper website

Laravel 13 + MySQL 8 + Redis, Clean Architecture modules under `src/` (namespace `Elaf\`).
Requirements: [PRD.md](PRD.md). Rules for contributors and AI agents: [CLAUDE.md](CLAUDE.md).

## Quick start (Docker)

```bash
make up      # creates .env, builds images, installs deps, migrates
make check   # Pint + Larastan (max) + Pest on MySQL
```

Site: http://localhost:8080 · Health: http://localhost:8080/up · Mail: http://localhost:8025

Other targets: `make help`.

## Without Docker
PHP 8.3 + Composer: `composer install && composer check` (tests use SQLite in memory).

## Layout
- `src/<Module>/{Domain,Application,Infrastructure,Presentation}` — see PRD §5.
- `tests/{Unit,Application,Contract,Feature,Architecture}`; fakes in `tests/Support/Fakes`.
- `docker/` images and config, `docs/adr` decisions, `docs/plans` phase plans.
