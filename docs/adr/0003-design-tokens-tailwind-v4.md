# ADR-0003 — Design tokens with Tailwind CSS v4

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
