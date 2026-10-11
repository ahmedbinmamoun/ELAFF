# ADR-0007 — Contributors (Phase 2b)

- Status: accepted
- Date: 2026-10-12

## Decisions

1. **`Contributor` is a small entity**, although PRD §5.1 lists only four core
   aggregates. Its status workflow (pending_email → pending_approval → active
   ⇄ suspended, or rejected) and the display-name approval are real behaviour,
   unit-tested like the article workflow. Approving and reinstating both lead to
   *active*, so each also checks its starting state.

2. **Accounts and roles are explicit ports.** `ContributorAccounts` creates the
   users row; `RoleAssignments` (Identity) gives or takes the `contributor` role.
   Writing permission comes from that role: it is granted on approval and
   removed on suspension, so a suspended session can no longer write either.
   `users.type` (staff/contributor) and `users.status` decide panel access:
   staff → `/admin` only; contributors → `/writer` only, while pending approval
   or active.

3. **No account enumeration.** Registering with a known email returns exactly
   like a new registration; the address owner gets an "already registered"
   email. Registration never signs the user in; the signed verification link
   works without a session.

4. **Byline.** A contributor's article is always signed with their own author
   profile (created on approval, unique slug); `ContributorByline` enforces it in
   the use cases, so a tampered form cannot change it. Editors set bylines freely.

5. **Limits (FR-CON-10)**: at most 5 articles in review per contributor, body up
   to 40 000 characters (staff: 200 000), registration rate-limited (Filament,
   3/min) plus honeypot and Turnstile.

6. **Settings before the Settings module.** `contributors.require_account_approval`,
   `contributors.editor_can_publish` and the guest byline label are read through
   ports (`ContributorSettings`, `EditorialSettings`) from `config/elaf.php`
   (env-driven). Phase 5 swaps the adapter for database settings.

7. **Turnstile behind `CaptchaVerifier`**, failing closed (no secret, network
   error → refused). Without keys, local and testing environments use a disabled
   verifier; production never does.

8. **Emails are event-driven**: listeners in Contributors\Infrastructure send one
   queued, RTL Arabic mailable after the transaction commits. Links to the admin
   and portal are plain URLs (Infrastructure must not depend on Presentation).

9. **Bulk unpublish** is available to whoever moderates contributors (Editor and
   higher), only for that contributor's articles, each through the normal
   unpublish (events, audit log).
