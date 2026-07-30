# Phase 23F Status

## Current State

- Phase: 23F — Collections, Campaigns, and Knowledge Links
- Branch: `phase/23f-collections-knowledge`
- Parent head: corrected Phase 23E commit `196b2b58e2b5b1da734fd8b19797058891151756`
- Plugin development version: `0.6.0`
- Metadata schema version: `2`
- Nature: stacked Draft candidate
- Production readiness: not ready
- Staging readiness: not ready
- Merge readiness: blocked

## Corrective Review Completed

- [x] Independent review of the initial Phase 23F foundation
- [x] Eighteen defects recorded in `docs/AUDIT-PHASE-23F-2026-07-30.md`
- [x] Initial source defects corrected
- [x] Corrective foundation source re-review completed
- [x] Prior Phase 23A–23E architecture guards restored
- [x] Phase 23F-specific architecture checks added
- [x] Phase 23F executable tests wired into the PHP matrix

## Corrected Foundation

- [x] Expanded metadata repository contract
- [x] Exactly three File 23-owned metadata tables
- [x] Post-install table verification before schema-version acceptance
- [x] Removed `progress`, results, report URL, and every persisted native destination
- [x] Actor-scoped idempotency uniqueness
- [x] Owner/scope-scoped canonical relation hashes
- [x] Approved-account and capability gates
- [x] Founder-only institution collections, campaigns, and institution knowledge links
- [x] Campaign-only fields isolated from ordinary collections
- [x] Defense-in-depth anti-dark-pattern phrase screen accurately documented
- [x] Unicode-aware bounded text and sensitive-data rejection
- [x] Contributor shape validation separated from runtime eligibility validation
- [x] Native-reference resolver interface

## Next Coding Started

- [x] Fail-closed `SPDB_Collections_Service`
- [x] Truthful repository/resolver health projection
- [x] Bounded read-query normalization
- [x] Current-user record visibility boundary
- [x] Contributor eligibility recheck contract
- [x] Native reference existence, visibility, permission, version, and safe-destination re-resolution boundary
- [x] Development/staging-only write constant with production denial
- [x] No Phase 23F mutation REST route
- [x] Schema lifecycle wired to activation and upgrade

## Still Not Implemented or Accepted

- [ ] Concrete WordPress repository implementation
- [ ] Concrete native-provider reference resolver
- [ ] Collection-item create, reorder, update, and archive service execution
- [ ] Knowledge-link persistence execution
- [ ] Optimistic concurrency and replay persistence tests
- [ ] Canonical audit-service integration
- [ ] Explicit Phase 23F read REST routes
- [ ] Explicit Phase 23F mutation REST routes and nonce callbacks
- [ ] Dashboard Collections projection and accessible UI
- [ ] Real-provider outage and stale-reference handling
- [ ] Migration rollback implementation and drill
- [ ] WordPress staging and real-provider acceptance
- [ ] Founder review completed
- [ ] Founder acceptance recorded

## Governing Restriction

No production mutation is enabled. The repository and resolver are deliberately not injected into the default runtime, and `SPDB_PHASE23F_WRITES_ENABLED` is false unless explicitly defined in an approved development or staging test. No phase or pull request may be merged before the complete gate in `docs/PHASE-23F-REVIEW-GATE.md` passes and merge is explicitly authorized.
