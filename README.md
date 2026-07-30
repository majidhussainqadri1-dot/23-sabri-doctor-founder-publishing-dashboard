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
- File 23 owns federated operations, bounded cross-module organizational metadata, dashboard preferences, saved views, guarded routing, bounded aggregate caches, adapter health, and dashboard audit evidence.
- File 25 owns final public profile timelines and visual presentation under the amended numbering.

File 23 does not own publication bodies, native drafts, profile identities, native knowledge bodies, review decisions, reviewer assignments, native schedules, cron state, source records, media binaries, comments, corrections, retractions, clinical records, patient identifiers, raw analytics events, or notification delivery records.

## Current Phase

**Phase 23F — Corrected Collections, Campaigns, Knowledge Links, Read API, and Read-Only Dashboard UI**

Development version `0.6.2` provides:

- exactly three File 23-owned metadata tables under Schema Version `3`;
- verified and request-cached repository health;
- metadata-only collections, Founder campaigns, collection-item references, and knowledge links;
- approved current-account, capability, scope, Founder, parent-collection, and object-level authority;
- actor-scoped idempotency, request fingerprints, exact replay, payload conflicts, and duplicate-relation conflicts;
- bounded audit reasons and observed native versions without persisted native destinations;
- strict repository queries, persistence records, envelopes, lifecycle ordering, and cross-user rejection;
- six explicit GET-only REST projections with private/no-store behavior and truthful pagination headers;
- a protected read-only Collections and Knowledge dashboard view;
- collection list/detail, parent-authorized active item list/detail, knowledge list/detail, bounded filters, and pagination;
- Founder-only institution scope presentation and server-side institution authorization;
- accessible captions, landmarks, definition lists, empty/error states, focusable overflow regions, visible focus, RTL, reduced-motion, and forced-color treatment;
- non-sensitive collection readiness in System Status;
- separate read, collection-write, knowledge-write, and any-write readiness;
- production writes, mutation REST, update, reorder, and archive execution disabled;
- PHP 8.0–8.3 policy, runtime, repository, item-IDOR, read-REST, UI, malformed-route, privacy, and architecture tests.

## Implemented Views and Read Surfaces

1. **Overview** — workspace, account state, provider readiness, and safety notices.
2. **Founder/Doctor Workspace** — role policy, native cards, gated destinations, profile, knowledge, and activity projections.
3. **Content Inventory** — validated native projections and read-only inspector.
4. **Collections & Knowledge** — server-rendered read-only collection, item, campaign, and knowledge-link metadata.
5. **Universal Review Inbox** — bounded native queue with current authorization metadata.
6. **Federated Publishing Calendar** — bounded native schedules, timezones, conflicts, failures, and current authorization metadata.
7. **Saved Views** — bounded non-clinical personal filters.
8. **System Status** — capability-protected, non-sensitive diagnostics including Phase 23F readiness.
9. **Phase 23F read API** — explicit GET projections for collections, collection details, collection items, item details, knowledge links, and link details.

Knowledge-link creation remains unavailable in the default runtime until a separately reviewed native resolver is injected. No Phase 23F mutation REST route or mutation UI exists.

## Repository Workflow

- Default branch: `main`
- Phase 23A Draft PR #1: unmerged
- Phase 23B Draft PR #2: unmerged
- Phase 23C Draft PR #3: reviewed, corrected, QA-green, unmerged
- Phase 23D Draft PR #4: source-reviewed, corrected, QA-green, unmerged
- Phase 23E stacked branch and PRs: source-reviewed, corrected, unmerged
- Phase 23F Draft PR #7: four source-review cycles recorded, corrected, still Draft and unmerged
- Active stacked branch: `phase/23f-collections-knowledge`
- **No merge before completed review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, Founder acceptance, and explicit authorization.**

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
- `docs/AUDIT-PHASE-23F-COLLECTIONS-UI-2026-07-31.md`
- `docs/COLLECTIONS-KNOWLEDGE.md`
- `docs/PHASE-23F-REVIEW-GATE.md`
- `docs/PHASE-23F-STATUS.md`
- `tests/phase23f-policy-tests.php`
- `tests/phase23f-runtime-tests.php`
- `tests/phase23f-repository-tests.php`
- `tests/phase23f-read-rest-tests.php`
- `tests/phase23f-collections-ui-tests.php`
- `tests/phase23f-collections-ui-corrective-tests.php`
- `tests/dashboard-router-malformed-view-tests.php`
- `tests/architecture-guard.php`

## Status

Four Phase 23F review cycles have recorded eighteen foundation defects, eighteen runtime/persistence defects, sixteen persistence/item/read-API defects, and sixteen principal UI defects plus five corrective UI findings. Corrective source changes are present. The documentation-inclusive final head must pass both exact-head PHP 8.0–8.3 matrices. WordPress Schema Version 3 migration, real File 00 accounts, real native providers, Hostinger staging, privacy/accessibility/cache/backup/restore/rollback testing, Founder review, and explicit merge authorization remain mandatory. All pull requests remain Draft and unmerged.
