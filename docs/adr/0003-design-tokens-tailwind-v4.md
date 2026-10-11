# ADR-0003 — Design tokens with Tailwind CSS v4

> Amended 2026-10-12 (UI redesign): tokens moved to `tailwind.config.js`,
> loaded with `@config`, as PRD §10 asks. Component CSS reads them with
> `theme()`; Tailwind v4 does not emit CSS variables for JS-config tokens.

- Status: accepted
- Date: 2026-10-11

## Context
PRD §10 asks for a single design-token file in `tailwind.config.js`. Tailwind v4
(current stable) moved configuration into CSS.

## Decision
Tokens (colors, fonts) live in the `@theme` block of `resources/css/app.css`.
It is the only place to edit for re-branding. Fonts are self-hosted; the
skeleton's Bunny Fonts CDN was removed (PRD §4: no third-party font CDNs).
Logical utilities (`ms-*`, `pe-*`) are used for RTL as required.
