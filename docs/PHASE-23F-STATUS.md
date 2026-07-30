# Phase 23F Status

## Current State

- Phase: 23F — Collections, Campaigns, and Knowledge Links
- Branch: `phase/23f-collections-knowledge`
- Parent head: corrected Phase 23E commit `196b2b58e2b5b1da734fd8b19797058891151756`
- Plugin development version: `0.6.1`
- Metadata schema version: `3`
- Nature: stacked Draft candidate
- Production readiness: not ready
- Staging readiness: not ready
- Merge readiness: blocked

## Reviews, Corrections, and Source QA

- [x] Initial Phase 23F foundation independently reviewed
- [x] Initial eighteen defects recorded and corrected
- [x] Initial corrective source re-review completed
- [x] Phase 23F runtime and persistence slice independently reviewed again
- [x] Second eighteen defects recorded in `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`
- [x] Second corrective source changes implemented
- [x] Corrective re-review found and corrected strict-projection and frontend schema-verification overhead defects
- [x] Unknown projection fields and malformed numeric projections covered by executable tests
- [x] Corrective source re-review completed
- [x] Exact-head PHP 8.0, 8.1, 8.2, and 8.3 QA completed successfully
- [x] Phase 23A–23E regression suites completed successfully
- [x] Phase 23F policy, runtime, repository, schema, replay, payload-conflict, and architecture gates completed successfully
- [x] Exact-head source checksums and PHP-matrix QA artifacts retained

## Corrected Foundation and Runtime

- [x] Exactly three File 23-owned metadata tables
- [x] Schema Version 3 verifies required tables, columns, and indexes
- [x] Schema verification restricted to activation, administrative lifecycle, and repository-health paths rather than every public request
- [x] No persisted native destination, content body, result, report, clinical data, media, or raw analytics
- [x] Persisted observed native versions for collection items and knowledge links
- [x] Persisted bounded created/last audit reasons
- [x] Actor-scoped idempotency and canonical request fingerprints
- [x] Exact replay distinguished from same-key/different-payload conflict
- [x] Approved-account, capability, scope, Founder, and object-level visibility gates
- [x] Unauthorized institution reads fail closed and are never rewritten to own scope
- [x] Record-type-specific status validation
- [x] Strict output-field allowlists and reconstructed safe projections
- [x] Strict positive identifiers and versions; validated text, lists, enums, timestamps, and campaign semantics
- [x] Unicode-aware bounded text and sensitive-data rejection
- [x] Fail-closed native reference existence, visibility, permission, owner, version, and destination validation
- [x] Read readiness separated from collection-write and knowledge-write readiness
- [x] Concrete `SPDB_WP_Collections_Repository`
- [x] Verified collection, collection-item, and knowledge-link repository reads
- [x] Idempotent collection and knowledge-link create persistence
- [x] Concrete repository injected for server-side collection reads
- [x] No Phase 23F mutation REST route
- [x] Production writes disabled
- [x] Update, reorder, and archive repository operations explicitly disabled

## Still Not Implemented or Accepted

- [ ] Concrete native-provider reference resolver
- [ ] Collection-item create execution
- [ ] Collection and campaign update/archive execution
- [ ] Collection-item reorder/update/archive execution
- [ ] Knowledge-link update/archive execution
- [ ] Full optimistic-concurrency execution tests
- [ ] Canonical shared audit-service integration
- [ ] Explicit Phase 23F read REST routes
- [ ] Explicit Phase 23F mutation REST routes and nonce callbacks
- [ ] Dashboard Collections projection and accessible UI
- [ ] Real-provider outage and stale-reference handling
- [ ] Migration rollback implementation and drill
- [ ] WordPress staging and real-provider acceptance
- [ ] Founder review completed
- [ ] Founder acceptance recorded

## Governing Restriction

No production mutation is enabled. The concrete repository is injected for verified reads, but the native-reference resolver is deliberately absent. `SPDB_PHASE23F_WRITES_ENABLED` remains false unless explicitly defined in an approved local, development, or staging test. No update, reorder, archive, mutation REST, phase merge, or Pull Request merge is permitted before its complete review, correction, corrective re-review, exact-head QA, staging evidence, Founder acceptance, and explicit merge authorization.
