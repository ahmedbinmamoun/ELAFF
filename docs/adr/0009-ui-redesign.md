# ADR-0009 — Public UI redesign decisions

- Status: accepted
- Date: 2026-10-12

Context: the client's design (`docs/design/home.jpeg`) and brief arrived
before Phase 4. Details in `docs/plans/ui-redesign.md`.

## Decisions

1. **Web sections vs. print sections.** The site's categories are now the web
   sections of the design (أخبار السودان، الاقتصاد، النفط والطاقة، السياسة، العالم،
   تقارير وتحقيقات، آراء وكتابات، مقالات، منوعات), each with an icon. The twelve
   sections of the printed paper (PRD FR-TAX-01's seed list) become the options
   of a new optional `articles.print_section`. Seeding stays non-destructive:
   existing databases keep their categories.

2. **Additive changes outside Presentation**, accepted although the brief asked
   for presentation-only work, because the alternative was writing around the
   use cases (CLAUDE.md: writes go through handlers):
   - `ArticleQueries::mostRead()` and `::cards()` (read side);
   - `categories.icon` and `articles.print_section`, through the existing input
     DTOs, `ArticleContent` (and its revision snapshots; old snapshots read as
     null) and repositories.

3. **Homepage blocks before Phase 5.** A registry of `HomepageBlockRenderer`
   classes (Layout\Presentation) renders the ordered list in
   `config/homepage.php`. FR-HOME-03 holds today (a test registers a new type);
   Phase 5 only moves the list into `homepage_blocks` and the admin. A
   `columnists` block was added: PRD FR-HOME-02/FR-AUT-02 require it even
   though the brief's list omitted it.

4. **Alpine.js** (PRD-approved) for the drawer, "المزيد", ticker and favourites;
   ~15 KB gz. Favourites are browser-only (localStorage), rendered through
   `GET /favorites/cards?ids=…`; nothing is stored on the server.

5. **Site identity in config** (`config/elaf.php` → env) until the Settings
   module: motto, description, masthead names, contact email, social links,
   footer links. Empty values are not rendered.

6. **Responsive images**: the markup emits `srcset` for WebP width derivatives
   when they exist; the pipeline that creates them is Phase 6. Until then the
   original is served with fixed dimensions (no layout shift).
