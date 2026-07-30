# Sabri Doctor and Founder Publishing Dashboard

File 23 of the **Sabri Social Homeopathy Platform**.

## Governing Principle

> One Federated Publishing Command Center — Role-Specific Workspaces — Native Ownership Preserved — Every Action Executed through an Authorized Versioned Adapter.

## Architectural Boundaries

- File 00 owns identity, verification, roles, capabilities, and account state.
- File 03 owns Founder and Doctor profile records.
- File 20 owns the global application shell and global Safe Mode.
- File 21 owns social/news publications, Newsroom review, sources, comments, corrections, retractions, schedules, cron reconciliation, and public feed behavior.
- File 22 owns creation, drafts, autosave, editing, preview, validation, submission, and native Composer/schedule orchestration.
- File 23 owns operational projections, dashboard preferences, saved views, explicit guarded routing, bounded aggregate caches, adapter health, and dashboard audit events.
- File 25 owns final public profile timelines and visual presentation under the amended numbering.

File 23 does not own publication bodies, native drafts, profile identities, knowledge records, review decisions, reviewer assignments, native schedules, cron state, source records, media binaries, comments, corrections, retractions, raw analytics events, or notification delivery records.

## Current Phase

**Phase 23E — Corrected Universal Review Inbox and Federated Publishing Calendar**

Development version `0.5.1` provides:

- native review and schedule projections over Adapter Contract `2.0.0`;
- current File 00 reviewer, Founder, account-state, capability, scope, and environment authority;
- fail-closed filters, bounded first-window provider queries, global sorting, and central pagination;
- separate native-reported, accessible-window, current-page, and page-count values;
- explicit File 23 operation contracts for capability, Founder-only policy, state, assignment, ownership, and separation of duties;
- fresh native projection authorization before every review or schedule mutation;
- independent self-approval and self-rejection denial;
- Founder-only reviewer assignment with approved capable target validation;
- REST nonce, strict payload allowlist, object version, idempotency key, audit reason, and required operation fields;
- canonical UTC schedule timestamp and IANA timezone validation;
- exact-origin destinations, privacy-safe projected and submitted text, strict flags, operations, totals, and continuation state;
- broker execution followed by a matching native object re-fetch;
- provider failure isolation, bounded totals, responsive tables, keyboard focus, accessible pagination, and reduced-motion support;
- corrective tests and architecture gates for the twenty-four audit findings.

No review or schedule mutation occurs in projection services or templates. Mutation routes are explicit, freshly authorized, broker-only, and fail closed without File 23-controlled provider acceptance.

## Implemented Views

1. **Overview** — workspace, account state, provider readiness, and safety notices.
2. **Founder/Doctor Workspace** — role policy, native cards, gated destinations, profile, knowledge, and activity projections.
3. **Content Inventory** — validated native projections and read-only inspector.
4. **Universal Review Inbox** — bounded native queue with current authorization metadata.
5. **Federated Publishing Calendar** — bounded native schedules, timezones, conflicts, failures, and current authorization metadata.
6. **Saved Views** — bounded non-clinical personal filters.
7. **System Status** — capability-protected, non-sensitive diagnostics.

## Repository Workflow

- Default branch: `main`
- Phase 23A Draft PR #1: unmerged
- Phase 23B Draft PR #2: unmerged
- Phase 23C Draft PR #3: reviewed, corrected, QA-green, unmerged
- Phase 23D Draft PR #4: source-reviewed, corrected, exact-head QA-green, unmerged
- Phase 23E Draft PR #5: source-reviewed, corrected, corrective exact-head QA pending, unmerged
- Active stacked branch: `phase/23e-review-calendar`
- **No merge before completed review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance.**

## Versions

- Plugin: `0.5.1`
- Adapter Contract: `2.0.0`
- WordPress: 6.5+
- PHP: 8.0+
- Sabri Membership Core: 1.0.1 or formally accepted compatible 1.x

## Phase 23E Evidence

- `docs/AUDIT-PHASE-23E-2026-07-30.md`
- `docs/REVIEW-CALENDAR.md`
- `docs/PHASE-23E-REVIEW-GATE.md`
- `tests/review-calendar-tests.php`
- `tests/architecture-guard.php`

## Status

Phase 23E independent source review found twenty-four defects. All documented source defects were corrected and corrective source re-review completed. Final documentation-inclusive PHP 8.0–8.3 exact-head evidence, real File 21/File 22 adapters, Hostinger staging, real-account/privacy/accessibility/cache/rollback testing, and Founder acceptance remain mandatory. All pull requests remain Draft and unmerged.
