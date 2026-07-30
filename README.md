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

**Phase 23F — Third-Corrected Collections, Campaigns, Knowledge Links, and Read API**

Development version `0.6.2` provides:

- exactly three File 23-owned metadata tables under Schema Version `3`;
- verification of required tables, columns, and indexes before repository readiness;
- request-cached repository health to avoid repeated schema inspection inside one request;
- metadata-only collections, Founder campaigns, collection-item references, and knowledge links;
- approved current-account, capability, scope, Founder, parent-collection, and object-level visibility authority;
- actor-scoped idempotency hashes and request fingerprints;
- exact replay, same-key/different-payload conflict, and duplicate canonical-relation conflict handling;
- bounded audit-reason and observed native-version persistence;
- no persisted native destinations or parallel content/results/reporting backend;
- a concrete WordPress repository with strict direct-query and persistence-record validation;
- strict repository envelopes, lifecycle ordering, collection-item projections, and fail-closed cross-user rejection;
- six explicit read-only REST projections with private/no-store behavior and truthful pagination headers;
- a versioned native-reference resolver contract and strict native-reference validation;
- separate read, collection-write, knowledge-write, and any-write readiness;
- local/development/staging-only explicit write configuration;
- production writes, mutation REST, update, reorder, and archive execution disabled;
- policy, runtime-authority, schema, replay, payload-conflict, repository, item-IDOR, and read-REST tests;
- retained Phase 23A–23E regression and architecture gates.

## Implemented Views and Read Surfaces

1. **Overview** — workspace, account state, provider readiness, and safety notices.
2. **Founder/Doctor Workspace** — role policy, native cards, gated destinations, profile, knowledge, and activity projections.
3. **Content Inventory** — validated native projections and read-only inspector.
4. **Universal Review Inbox** — bounded native queue with current authorization metadata.
5. **Federated Publishing Calendar** — bounded native schedules, timezones, conflicts, failures, and current authorization metadata.
6. **Saved Views** — bounded non-clinical personal filters.
7. **System Status** — capability-protected, non-sensitive diagnostics.
8. **Phase 23F read API** — explicit GET projections for collections, collection details, collection items, item details, knowledge links, and link details.

The Phase 23F visual Collections UI is not yet implemented. Knowledge-link creation remains unavailable in the default runtime until a separately reviewed native resolver is injected. No Phase 23F mutation REST route exists.

## Repository Workflow

- Default branch: `main`
- Phase 23A Draft PR #1: unmerged
- Phase 23B Draft PR #2: unmerged
- Phase 23C Draft PR #3: reviewed, corrected, QA-green, unmerged
- Phase 23D Draft PR #4: source-reviewed, corrected, QA-green, unmerged
- Phase 23E stacked branch and PRs: source-reviewed, corrected, unmerged
- Phase 23F Draft PR #7: third corrective cycle in progress, unmerged
- Active stacked branch: `phase/23f-collections-knowledge`
- **No merge before completed review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance.**

## Versions

- Plugin: `0.6.2`
- Metadata Schema: `3`
- Adapter Contract: `2.0.0`
- WordPress: 6.5+
- PHP: 8.0+
- Sabri Membership Core: 1.0.1 or formally accepted compatible 1.x

## Phase 23F Evidence

- `docs/AUDIT-PHASE-23F-2026-07-30.md`
- `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`
- `docs/AUDIT-PHASE-23F-THIRD-REVIEW-2026-07-31.md`
- `docs/COLLECTIONS-KNOWLEDGE.md`
- `docs/PHASE-23F-REVIEW-GATE.md`
- `docs/PHASE-23F-STATUS.md`
- `tests/phase23f-policy-tests.php`
- `tests/phase23f-runtime-tests.php`
- `tests/phase23f-repository-tests.php`
- `tests/phase23f-read-rest-tests.php`
- `tests/architecture-guard.php`

## Status

Three independent Phase 23F review cycles have now recorded eighteen foundation defects, eighteen runtime/persistence defects, and sixteen third-review persistence/item/REST defects. Corrective source changes for all recorded findings are present. The documentation-inclusive PHP 8.0–8.3 exact-head gate must pass again after these changes. WordPress Schema Version 3 migration, real File 00 accounts, real native providers, Hostinger staging, privacy/accessibility/cache/backup/restore/rollback testing, Founder review, and explicit merge authorization remain mandatory. All pull requests remain Draft and unmerged.
