# Phase 2b — Contributors portal (plan)

Goal (PRD §17): registration, email verification, account approval, contributor
panel, locked-on-submit behaviour, editor notes on return, limits, notification
emails, suspend action.

**Accept:** FR-CON-01..13; Feature tests prove a contributor **cannot** publish,
schedule, mark breaking, see others' articles, or reach `/admin`; an Editor can
approve/return; only the Editor-in-Chief (or Editors when
`contributors.editor_can_publish` is on) can publish a contributor article.

## Data
- `users`: `type` (staff/contributor), `status` (pending_email,
  pending_approval, active, rejected, suspended), `last_login_at`.
- `contributor_profiles` (PRD §7): user, linked author, phone, bio, photo, terms
  accepted at, approved by/at, rejection reason, requested display name.

## Contributors module
- Domain: `Contributor` with `ContributorStatus` (state machine). It has real
  behaviour (verify, approve, reject, suspend, reinstate, rename request), so it
  is a small entity rather than a DTO; events for notifications.
- Use cases: `RegisterContributor`, `VerifyContributorEmail`,
  `ApproveContributor` (assigns the contributor role and creates + links an
  Author, unique slug), `RejectContributor`, `SuspendContributor`,
  `ReinstateContributor`, `UpdateContributorProfile` (a name change waits for
  approval), `ApproveDisplayNameChange`, `UnpublishAllByContributor`.
- Ports: `ContributorRepository`, `AuthorProfiles` (creates/renames the linked
  author through Content), `ContributorSettings`, `CaptchaVerifier` (Shared),
  `PasswordHasher` (Identity, reused).
- Registration never reveals whether an email exists: a known email gets an
  "you already have an account" mail and the same on-screen answer.

## Content changes
- Contributor articles: the byline is always the contributor's own author;
  body limit 40 000 characters; at most 5 articles in review at once
  (`ArticleCounts` port).
- `contributors.editor_can_publish`: editors may publish/schedule contributor
  articles when on (`EditorialSettings` port, config-backed until the Settings
  module exists in Phase 5).

## Presentation
- Second Filament panel `writer` at `/writer`: login, "My Articles" (always
  `created_by = me`), "New Article", "My Profile", status dashboard. Only
  contributors with status pending_approval or active can sign in; pending
  ones see their status only.
- Registration at `/writers/register` (honeypot, Filament rate limit, Turnstile
  through `CaptchaVerifier`, terms checkbox). Verification link is a signed
  URL handled without login.
- Admin: Contributors resource (editors+): approve, reject with reason, suspend,
  reinstate, unpublish all, approve name change.
- Emails (queued, Arabic, RTL): verification; editors on new registration and
  contributor submission; contributor on account approved/rejected and article
  approved/returned/published.

## Tests
- Domain unit (status machine), Application with fakes for every use case,
  contract tests for the new ports, Feature tests for the acceptance list above
  plus registration (honeypot, captcha, enumeration), verification, emails.

## Out of scope
Public byline rendering of "كاتب ضيف" (setting exists; rendering in Phase 3),
Settings UI (Phase 5), Turnstile CSP rules (Phase 7).
