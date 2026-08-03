# Phase 23F Status

## Current State

- Phase: 23F — Collections, Campaigns, Knowledge Links, Read API, and Read-Only UI
- Branch: `phase/23f-collections-knowledge`
- Parent head: corrected Phase 23E commit `196b2b58e2b5b1da734fd8b19797058891151756`
- Plugin development version: `0.6.2`
- Metadata schema version: `3`
- Nature: stacked Draft candidate
- Production readiness: not ready
- Staging readiness: not ready
- Merge readiness: blocked
- Exact-head automated status: only GitHub checks attached to the current documentation-inclusive commit are authoritative

## Reviews and Corrections

- [x] Initial eighteen foundation defects recorded and corrected
- [x] Second eighteen runtime/persistence defects recorded and corrected
- [x] Third sixteen persistence/item/read-API defects recorded and corrected
- [x] Overflow, invalid UTF-8, optional-filter, malformed timestamp, and health-cache edge defects corrected
- [x] Sixteen principal Collections UI defects recorded and corrected
- [x] Five UI corrective re-review findings recorded and corrected
- [x] Protected Collections route, approved navigation, view model, dashboard integration, conditional CSS, and System Status implemented
- [x] Founder-only institution scope presentation and server-side Founder authority aligned
- [x] Malformed array-valued view selector fails closed
- [x] Scope-preserving, RTL-safe back navigation
- [x] Bounded filters, pagination, captions, definition lists, empty/error states, responsive tables, visible focus, reduced motion, and forced colors
- [x] Dedicated UI, corrective-static, malformed-route, IDOR, privacy, and no-mutation tests
- [x] Dedicated PHP 8.0–8.3 exact-head UI workflow
- [ ] Both final documentation-inclusive matrices must pass and retain artifacts

## Corrected Foundation, Runtime, REST, and UI

- [x] Exactly three File 23-owned metadata tables
- [x] Schema Version 3 verifies required tables, columns, and indexes
- [x] Request-cached repository health
- [x] No persisted native destination, content body, report, clinical data, media, or raw analytics
- [x] Actor-scoped idempotency, canonical request fingerprints, exact replay, and deterministic conflicts
- [x] Strict repository inputs, output projections, lifecycle semantics, pagination, and continuation
- [x] Overflowing integers, invalid UTF-8, malformed timestamps, unknown fields, and cross-user records rejected
- [x] Approved-account, capability, scope, Founder, parent-collection, and object-level visibility gates
- [x] Parent collection authorization before every item query
- [x] Archived items excluded consistently from default reads
- [x] Six explicit GET-only REST routes
- [x] Strict REST query allowlists, truthful pagination headers, and private/no-store behavior
- [x] Protected read-only Collections and Knowledge dashboard view
- [x] Collection list/detail, active item list/detail, and knowledge-link list/detail
- [x] Founder-only institution scope option
- [x] Phase 23F readiness in System Status
- [x] No mutation REST route or mutation UI
- [x] Production writes disabled
- [x] Update, reorder, and archive operations disabled

## Still Not Implemented or Accepted

- [ ] Concrete native-provider reference resolver
- [ ] Real-provider fresh authorization, outage, deleted/private object, and permission-loss behavior
- [ ] Collection-item create execution
- [ ] Collection and campaign update/archive execution
- [ ] Collection-item reorder/update/archive execution
- [ ] Knowledge-link update/archive execution
- [ ] Optimistic-concurrency execution for future mutations
- [ ] Canonical shared audit-service integration
- [ ] Mutation REST routes and nonce callbacks
- [ ] Schema Version 2-to-3 WordPress staging migration evidence
- [ ] Real File 00 Founder, Doctor, contributor, pending, and suspended account evidence
- [ ] Cross-doctor IDOR and enumeration staging evidence
- [ ] LiteSpeed/cache privacy and full manual accessibility/responsive/RTL acceptance
- [ ] Backup, restore, deactivation/reactivation, schema rollback, and application rollback
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Explicit merge authorization recorded

## Governing Restriction

No production mutation is enabled. The concrete repository and read-only UI are present, but the native-reference resolver is deliberately absent. `SPDB_PHASE23F_WRITES_ENABLED` remains false unless explicitly defined in an approved non-production test. No create, update, reorder, archive, mutation REST, production write, phase merge, or Pull Request merge is permitted before complete review, correction, corrective re-review, final exact-head QA, staging evidence, Founder acceptance, and explicit merge authorization.
