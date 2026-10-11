# ADR-0001 — Stack and versions

- Status: accepted
- Date: 2026-10-11

## Context
PRD §5.4 asks for the latest stable Laravel and a fixed, recorded toolchain.

## Decision
Versions resolved by Composer/npm at project creation:

| Component | Version |
|---|---|
| PHP | 8.3 (Docker `php:8.3-fpm-bookworm`) |
| Laravel framework | 13.35.0 |
| Pest | 4.7.8 (+ pest-plugin-laravel 4.1, pest-plugin-arch 4.0) |
| Larastan / PHPStan | 3.13.0 / 2.3.1, level `max` |
| Laravel Pint | 1.32.1 (Laravel preset + `declare_strict_types`) |
| MySQL | 8.4 LTS |
| Redis | 7 |
| Node (assets) | 24 |
| Vite / Tailwind CSS | 8 / 4 |
| nginx | 1.27 |
| Spatie Laravel Permission | 8.3.0 (Phase 1) |
| Spatie Laravel Activitylog | 4.12.3 (Phase 1) |
| HTMLPurifier (`ezyang/htmlpurifier`) | 4.19.1 (Phase 1, see ADR-0004) |

Build-time tooling (not runtime dependencies): `mlocati/php-extension-installer`
to compile PHP extensions in the image, and `axllent/mailpit` (dev only).

Filament and Spatie Backup/Media Library are installed in the phase that
first uses them, pinned then and added to this table.

## Consequences
- Pest test files are excluded from PHPStan (their fluent DSL is not
  statically typeable); test support code is analysed at level max.
- Pest 4 silently passes `arch()->expect([ns1, ns2])` with several targets,
  so architecture rules are generated one module at a time.
