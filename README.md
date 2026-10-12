# إيلاف — Elaf newspaper website

Laravel 13 + MySQL 8 + Redis, Clean Architecture modules under `src/` (namespace `Elaf\`).
Requirements: [PRD.md](PRD.md). Rules for contributors and AI agents: [CLAUDE.md](CLAUDE.md).

## Quick start (Docker)

```bash
make up      # creates .env, builds images, installs deps, migrates
make check   # Pint + Larastan (max) + Pest on MySQL
```

Site: http://localhost:8080 · Admin: http://localhost:8080/admin · Writers: http://localhost:8080/writer · Health: http://localhost:8080/up · Mail: http://localhost:8025

Other targets: `make help`.

## Data
- `make seed` (or `make fresh`) adds roles/permissions (PRD §2) and the paper's
  sections. Both seeders are safe to re-run, also in production.
- In the `local` environment it also adds demo content (generated Arabic text)
  and one user per role: `writer@elaf.test`, `contributor@elaf.test`,
  `editor@elaf.test`, `editor-in-chief@elaf.test`, `admin@elaf.test`, password
  `password`.
- `contributor@elaf.test` is an approved contributor for the writers' portal
  (`/writer`); new contributors register at `/writers/register` (emails land in
  Mailpit).
- Contributor switches live in `.env` (`ELAF_CONTRIBUTORS_*`); Turnstile keys
  (`TURNSTILE_*`) are required in production.
- The scheduler publishes scheduled articles every minute
  (`php artisan articles:publish-scheduled`).

## Print issues
- Upload in the admin (Issues, Editor-in-Chief and Admin). The `queue` service
  renders the pages (poppler + Imagick); a failure shows its reason with a
  retry button. Demo data includes six generated issues.
- Back-fill an archive folder:
  `docker compose exec app php artisan issues:import /path/to/pdfs --first-number=1 --dry-run`
  (numbers counted up in date order from names like `إيلاف_8_10_2026.pdf`), or
  `--csv=map.csv` with the columns `file,number,date,title`. Drop `--dry-run`
  to import; re-running skips numbers that already exist.

## Running the site (admin)
- **Settings** (Admin): name, logo, contacts, social links, masthead,
  analytics, contributor switches. The values in `.env` are only defaults.
- **Homepage**, **Menus**, **Ads**, **Static pages** (Editor-in-Chief and
  Admin): arrange homepage blocks by drag and drop, edit the header,
  sections and footer menus, schedule ads in five slots, write the about /
  contact / privacy pages.
- **Reader letters** (editors): letters sent from `/letters` wait for
  moderation; an approved letter can become a draft article.

## Frontend
`make up` starts Vite with hot reload. To see production assets (e.g. for
Lighthouse), run `docker compose exec vite npm run build`, stop the `vite`
service and delete `public/hot`. Give headless Chromium containers
`--shm-size=1g`: with Docker's default 64 MB the tab crashes on long pages.

## Without Docker
PHP 8.3 + Composer: `composer install && composer check` (tests use SQLite in memory).

## Layout
- `src/<Module>/{Domain,Application,Infrastructure,Presentation}` — see PRD §5.
- `tests/{Unit,Application,Contract,Feature,Architecture}`; fakes in `tests/Support/Fakes`.
- `docker/` images and config, `docs/adr` decisions, `docs/plans` phase plans.
