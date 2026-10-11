# ADR-0002 — Identifiers

- Status: accepted
- Date: 2026-10-11

## Decision
Auto-increment `BIGINT UNSIGNED` primary keys. Public article URLs use the numeric
id (`/article/{id}/{slug}`), issues are addressed by their issue number.

In the domain, each aggregate has its own id value object extending
`Elaf\Shared\Domain\Identity\IntegerId` (`ArticleId`, `IssueId`, …), so ids of
different aggregates cannot be mixed up. Repositories expose `nextId()`; the
Eloquent implementation reserves the id by inserting into a sequence table
(see ADR-0005 §1).

## Alternatives
ULIDs: no ordering/size benefit worth the uglier public URLs for this site.
