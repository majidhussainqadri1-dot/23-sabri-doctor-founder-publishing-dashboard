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
- File 23 owns federated operations, bounded cross-module organizational metadata, dashboard preferences, saved views, explicit guarded routing, bounded aggregate caches, adapter health, and dashboard audit evidence.
- File 25 owns final public profile timelines and visual presentation under the amended numbering.

File 23 does not own publication bodies, native drafts, profile identities, native knowledge bodies, review decisions, reviewer assignments, native schedules, cron state, source records, media binaries, comments, corrections, retractions, clinical records, patient identifiers, raw analytics events, or notification delivery records.

## Current Phase

**Phase 23F — Corrected Collections, Campaigns, and Knowledge Links Runtime**

Development version `0.6.1` provides:

- exactly three File 23-owned metadata tables under Schema Version `3`;
- verification of required tables, columns, and indexes before repository readiness;
- metadata-only collections, Founder campaigns, collection-item references, and knowledge links;
- approved current-account, capability, scope, Founder, and object-level visibility authority;
- actor-scoped idempotency hashes and request fingerprints;
- exact replay and same-key/different-payload conflict handling;
- bounded audit-reason and observed native-version persistence;
- no persisted native destinations or parallel content/results/reporting backend;
- a concrete WordPress repository for verified server-side reads and idempotent creates;
- validated repository envelopes and fail-closed cross-user record rejection;
- a versioned native-reference resolver contract and strict native reference validation;
- separate read, collection-write, and knowledge-write readiness;
- local/development/staging-only explicit write configuration;
- production writes, mutation REST, update, reorder, and archive execution disabled;
- policy, runtime-authority, schema, replay, payload-conflict, and repository tests;
- retained Phase 23A–23E regression and architecture gates.

## Implemented Views

1. **Overview** — workspace, account state, provider readiness, and safety notices.
2. **Founder/Doctor Workspace** — role policy, native cards, gated destinations, profile, knowledge, and activity projections.
3. **Content Inventory** — validated native projections and read-only inspector.
4. **Universal Review Inbox** — bounded native queue with current authorization metadata.
5. **Federated Publishing Calendar** — bounded native schedules, timezones, conflicts, failures, and current authorization metadata.
6. **Saved Views** — bounded non-clinical personal filters.
7. **System Status** — capability-protected, non-sensitive diagnostics.

The Phase 23F Collections UI and REST routes are not yet exposed. The concrete repository currently supplies internal verified reads; knowledge-link creation remains unavailable until a reviewed native resolver is injected.

## Repository Workflow

- Default branch: `main`
- Phase 23A Draft PR #1: unmerged
- Phase 23B Draft PR #2: unmerged
- Phase 23C Draft PR #3: reviewed, corrected, QA-green, unmerged
- Phase 23D Draft PR #4: source-reviewed, corrected, QA-green, unmerged
- Phase 23E stacked branch and PRs: source-reviewed, corrected, unmerged
- Phase 23F Draft PR #7: second corrective review in progress, unmerged
- Active stacked branch: `phase/23f-collections-knowledge`
- **No merge before completed review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance.**

## Versions

- Plugin: `0.6.1`
- Metadata Schema: `3`
- Adapter Contract: `2.0.0`
- WordPress: 6.5+
- PHP: 8.0+
- Sabri Membership Core: 1.0.1 or formally accepted compatible 1.x

## Phase 23F Evidence

- `docs/AUDIT-PHASE-23F-2026-07-30.md`
- `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`
- `docs/COLLECTIONS-KNOWLEDGE.md`
- `docs/PHASE-23F-REVIEW-GATE.md`
- `docs/PHASE-23F-STATUS.md`
- `tests/phase23f-policy-tests.php`
- `tests/phase23f-runtime-tests.php`
- `tests/phase23f-repository-tests.php`
- `tests/architecture-guard.php`

## Status

The first Phase 23F review found eighteen foundation defects. A second independent review of the runtime and persistence slice found another eighteen defects. Both defect sets have corrective source changes. The second exact-head PHP 8.0–8.3 automated gate, WordPress Schema Version 3 migration, real File 00 accounts, real native providers, Hostinger staging, privacy/accessibility/cache/backup/restore/rollback testing, Founder review, and explicit merge authorization remain mandatory. All pull requests remain Draft and unmerged.
