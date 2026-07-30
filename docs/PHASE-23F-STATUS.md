# Phase 23F Status

## Current State

- Phase: 23F — Collections, Campaigns, Knowledge Links, and Read-Only Projections
- Branch: `phase/23f-collections-knowledge`
- Parent head: corrected Phase 23E commit `196b2b58e2b5b1da734fd8b19797058891151756`
- Plugin development version: `0.6.2`
- Metadata schema version: `3`
- Nature: stacked Draft candidate
- Production readiness: not ready
- Staging readiness: not ready
- Merge readiness: blocked
- Exact-head automated status: determined only by GitHub checks attached to the current commit; prior green runs do not authorize a changed head

## Reviews, Corrections, and Source QA

- [x] Initial Phase 23F foundation independently reviewed
- [x] Initial eighteen defects recorded and corrected
- [x] Initial corrective source re-review completed
- [x] Phase 23F runtime and persistence slice independently reviewed again
- [x] Second eighteen defects recorded and corrected
- [x] Second corrective source re-review completed
- [x] Third independent review completed
- [x] Third sixteen principal defects recorded in `docs/AUDIT-PHASE-23F-THIRD-REVIEW-2026-07-31.md`
- [x] Third sixteen principal defects corrected
- [x] Corrective re-review exposed and corrected optional-filter, overflow, invalid UTF-8, and malformed optional-timestamp defects
- [x] Code-inclusive PHP 8.0, 8.1, 8.2, and 8.3 matrix completed successfully after edge corrections
- [x] Phase 23A–23E regressions completed successfully on the corrected code slice
- [x] Phase 23F policy, runtime, repository, schema, replay, item-IDOR, read-REST, privacy, and architecture controls completed successfully on the corrected code slice
- [ ] The current documentation-inclusive commit must itself have a green exact-head matrix and retained artifacts before it is treated as source-QA complete

## Corrected Foundation, Runtime, and Read API

- [x] Exactly three File 23-owned metadata tables
- [x] Schema Version 3 verifies required tables, columns, and indexes
- [x] Schema verification restricted to activation, administrative lifecycle, and request-cached repository-health paths
- [x] No persisted native destination, content body, result, report, clinical data, media, or raw analytics
- [x] Persisted observed native versions for collection items and knowledge links
- [x] Persisted bounded created/last audit reasons
- [x] Actor-scoped idempotency and canonical request fingerprints
- [x] Exact replay distinguished from same-key/different-payload conflict
- [x] Duplicate canonical knowledge relations return deterministic conflict
- [x] Approved-account, capability, scope, Founder, parent-collection, and object-level visibility gates
- [x] Unauthorized institution reads fail closed and are never rewritten to own scope
- [x] Record-type-specific status validation in service and direct repository queries
- [x] Strict persistence and output-field allowlists
- [x] Strict IDs, versions, positions, totals, pages, text, lists, enums, timestamps, and lifecycle semantics
- [x] Exact integer round-trip prevents overflow saturation
- [x] Valid UTF-8 required for projected and persisted text/native versions
- [x] Malformed optional database timestamps remain invalid and are rejected
- [x] Mathematically consistent pagination and continuation validation
- [x] Fail-closed native reference existence, visibility, permission, owner, version, and destination validation
- [x] Read, collection-write, knowledge-write, and any-write readiness separated
- [x] Concrete `SPDB_WP_Collections_Repository`
- [x] Verified collection, collection-item, and knowledge-link reads
- [x] Parent collection authorization before every item query
- [x] Archived items excluded consistently from default item reads
- [x] Idempotent collection and knowledge-link create persistence
- [x] Six explicit read-only Phase 23F REST routes
- [x] Strict REST query allowlists and truthful pagination headers
- [x] Global private/no-store REST privacy applies to Phase 23F routes
- [x] No Phase 23F mutation REST route
- [x] Production writes disabled
- [x] Update, reorder, and archive repository operations explicitly disabled

## Still Not Implemented or Accepted

- [ ] Concrete native-provider reference resolver
- [ ] Collection-item create execution
- [ ] Collection and campaign update/archive execution
- [ ] Collection-item reorder/update/archive execution
- [ ] Knowledge-link update/archive execution
- [ ] Full optimistic-concurrency execution tests for future mutations
- [ ] Canonical shared audit-service integration
- [ ] Phase 23F mutation REST routes and nonce callbacks
- [ ] Dashboard Collections projection and accessible UI
- [ ] Real-provider outage and stale-reference handling
- [ ] Schema Version 2-to-3 staging migration evidence
- [ ] Migration rollback implementation and drill
- [ ] WordPress staging and real-provider acceptance
- [ ] Real File 00 Founder, Doctor, contributor, pending, and suspended account evidence
- [ ] Cross-doctor IDOR, cache privacy, accessibility, responsive, and RTL evidence
- [ ] Backup, restore, deactivation/reactivation, and rollback evidence
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Explicit merge authorization recorded

## Governing Restriction

No production mutation is enabled. The concrete repository is injected for verified reads, but the native-reference resolver is deliberately absent. `SPDB_PHASE23F_WRITES_ENABLED` remains false unless explicitly defined in an approved local, development, or staging test. No create/update/reorder/archive REST route, production write, phase merge, or Pull Request merge is permitted before complete review, correction, corrective re-review, current-head QA, staging evidence, Founder acceptance, and explicit merge authorization.