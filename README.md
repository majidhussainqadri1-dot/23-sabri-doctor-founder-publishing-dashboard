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
- File 23 owns operational projections, dashboard preferences, saved views, explicit guarded routing, later cross-module metadata, bounded aggregate caches, adapter health, and dashboard audit events.
- File 25 owns final public profile timelines and visual presentation under the amended numbering.

File 23 does not own publication bodies, native drafts, profile identities, knowledge records, review decisions, reviewer assignments, native schedules, cron state, source records, media binaries, comments, corrections, retractions, raw analytics events, or notification delivery records.

## Current Phase

**Phase 23E — Universal Review Inbox and Federated Publishing Calendar**

Development version `0.5.0` adds an initial stacked candidate for:

- optional native review/calendar projections over Adapter Contract `2.0.0`;
- a Universal Review Inbox that preserves native review ownership;
- a Federated Publishing Calendar that preserves native schedule ownership;
- server-derived reviewer, Founder, account-state, capability, scope, and environment context;
- validated review state, assignment, due date, safety/privacy/source/copyright flags, native version, and freshness;
- validated UTC schedule time, IANA native timezone, conflicts, failures, native version, and freshness;
- explicit approve, request-changes, reject, assign-reviewer, schedule, reschedule, and unschedule REST routes;
- guarded operation-broker execution with object version, idempotency key, audit reason, provider declaration, capability, account state, and File 23-controlled acceptance;
- native object re-fetch before success is reported;
- separation-of-duties protection against final self-approval and self-rejection;
- exact-origin destinations, strict totals, bounded flags and records, failure isolation, and truthful empty/truncated states;
- responsive Review Inbox and Publishing Calendar views;
- executable Phase 23E tests and architecture guards.

No native review or schedule mutation is performed by the projection service or templates. Mutation routes use only explicit registered operations through the operation broker, and production writes remain fail-closed until adapter acceptance is supplied by a later reviewed governance layer.

## Implemented Views

1. **Overview** — workspace, account state, provider readiness, and safety notices.
2. **Founder/Doctor Workspace** — role policy, native cards, gated actions, profile, knowledge, and activity projections.
3. **Content Inventory** — validated native projections and read-only inspector.
4. **Universal Review Inbox** — bounded native review queue and safe native review destinations.
5. **Federated Publishing Calendar** — bounded native schedules, timezones, conflicts, failures, and safe native destinations.
6. **Saved Views** — bounded non-clinical personal filters.
7. **System Status** — capability-protected, non-sensitive diagnostics.

## Repository Workflow

- Default branch: `main`
- Phase 23A Draft PR #1: unmerged
- Phase 23B Draft PR #2: unmerged
- Phase 23C Draft PR #3: reviewed, corrected, QA-green, unmerged
- Phase 23D Draft PR #4: source-reviewed, corrected, exact-head QA-green, unmerged
- Phase 23E Draft PR #5: open, Draft, unmerged
- Active stacked branch: `phase/23e-review-calendar`
- **No merge before completed review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance.**

## Versions

- Plugin: `0.5.0`
- Adapter Contract: `2.0.0`
- WordPress: 6.5+
- PHP: 8.0+
- Sabri Membership Core: 1.0.1 or formally accepted compatible 1.x

## Phase 23E Documentation

- `docs/REVIEW-CALENDAR.md`
- `docs/PHASE-23E-REVIEW-GATE.md`
- `tests/review-calendar-tests.php`

## Previous Review Records

- `docs/AUDIT-PHASE-23D-2026-07-30.md`
- `docs/ROLE-WORKSPACES.md`
- `docs/PHASE-23D-REVIEW-GATE.md`
- `docs/AUDIT-PHASE-23C-2026-07-30.md`
- `docs/FEDERATED-INVENTORY.md`
- `docs/PHASE-23C-REVIEW-GATE.md`
- `docs/AUDIT-PHASE-23B-SECOND-REVIEW-2026-07-30.md`
- `docs/ADAPTER-CONTRACT.md`
- `docs/DATA-OWNERSHIP.md`
- `docs/CAPABILITIES.md`
- `docs/STATE-PROJECTION.md`
- `SECURITY.md`

## Status

Phase 23E initial coding is complete as a Draft candidate. Independent source review, defect correction, corrective re-review, exact-head QA, real File 21/File 22 adapters, Hostinger staging, real reviewer/Founder/doctor accounts, privacy/accessibility/rollback testing, and Founder acceptance remain mandatory. All pull requests remain Draft and unmerged.
