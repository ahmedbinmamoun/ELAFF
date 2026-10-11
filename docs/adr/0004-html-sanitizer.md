# ADR-0004 — HTML sanitizer: HTMLPurifier used directly

- Status: accepted
- Date: 2026-10-11

## Context
FR-ART-03 requires allow-list sanitizing of article bodies. PRD §5.4 approves
"HTMLPurifier (via a Laravel wrapper)" such as `mews/purifier`.

## Decision
Depend on `ezyang/htmlpurifier` (4.19) directly, behind the `HtmlSanitizer`
port (`Shared\Application\Html`). The adapter `HtmlPurifierSanitizer`
(`Shared\Infrastructure\Html`) configures two profiles in code:

- **Staff**: h2-h4, paragraphs, lists, blockquote, links (`target=_blank`
  allowed, `rel=noopener` added), tables with `class`, `figure`/`figcaption`
  and images.
- **Contributor** (PRD §16): the same structure without `class`/`target`;
  links get `rel=nofollow`.

Both strip scripts, iframes/objects/embeds, inline styles, event handlers and
non-http(s)/mailto URLs. The definition cache lives in
`storage/framework/cache/htmlpurifier`.

## Why not the wrapper
The wrapper only adds a facade and a config file. The port already hides the
library, the config is clearer as typed code next to the profiles, and the
adapter stays plain PHP, so Application tests run it without booting Laravel.
One dependency less.

## Consequences
- No separate fake sanitizer: Application tests use the real adapter (fast,
  framework-free). The `HtmlSanitizerContractTest` pins the behaviour.
- `h1`/`h5`/`h6` are reduced to their text; the editor (Phase 2) offers h2-h4 only.
