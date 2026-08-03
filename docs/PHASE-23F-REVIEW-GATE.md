# Phase 23F Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before completed review.**

Phase 23F remains a stacked Draft candidate. Schema, contracts, policy, repository, runtime services, REST, UI, tests, documentation, migration, and every later mutation must each be independently reviewed, corrected, re-reviewed, and tested on the exact corrected head.

## Review History

1. The foundation review recorded eighteen defects in `docs/AUDIT-PHASE-23F-2026-07-30.md`.
2. The runtime and persistence review recorded eighteen further defects in `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`.
3. The persistence, item-authority, lifecycle, and read-API review recorded sixteen principal defects in `docs/AUDIT-PHASE-23F-THIRD-REVIEW-2026-07-31.md`, followed by overflow, UTF-8, optional-filter, and timestamp edge corrections.
4. The Collections UI review recorded sixteen principal defects and five corrective re-review findings in `docs/AUDIT-PHASE-23F-COLLECTIONS-UI-2026-07-31.md`.

**Corrective source re-review completed** means only that the recorded source defects have corrective code and tests. It does not approve WordPress staging, a real native resolver, real provider authorization, production use, Founder acceptance, or merge. The authoritative automated result is the check suite attached to the current documentation-inclusive commit.

## Current Candidate

- Branch: `phase/23f-collections-knowledge`
- Parent: corrected Phase 23E head
- Plugin development version: `0.6.2`
- Metadata schema version: `3`
- Native ownership: preserved
- Concrete repository: verified reads and idempotent creates
- Read API: six explicit GET-only routes
- Dashboard UI: protected read-only Collections and Knowledge view
- Native-reference resolver injection: absent
- Production mutation: disabled
- Mutation REST and mutation UI: absent
- Update, reorder, and archive execution: disabled
- Staging readiness: not ready
- Merge readiness: blocked

## Source Review Gate

- [x] Initial eighteen foundation defects documented and corrected
- [x] Second eighteen runtime/persistence defects documented and corrected
- [x] Third sixteen persistence/item/read-API defects documented and corrected
- [x] Overflow, invalid UTF-8, malformed timestamp, optional-filter, and health-cache edge defects corrected
- [x] Sixteen Collections UI defects documented and corrected
- [x] Five UI corrective re-review findings documented and corrected
- [x] Metadata-only ownership boundary preserved
- [x] No native content, destination, report, clinical, media, or analytics duplication
- [x] Approved-account, capability, scope, Founder, parent, and object authority reviewed
- [x] Repository, lifecycle, continuation, item, and output projections reviewed
- [x] Parent collection authorization precedes item reads
- [x] Six GET-only REST routes and private/no-store behavior reviewed
- [x] Protected Collections route, navigation, view model, and dashboard integration reviewed
- [x] Founder-only institution scope presentation reviewed
- [x] Malformed view selectors fail closed
- [x] Read-only template has no mutation form, mutation button, native write, or persisted destination
- [x] Accessible captions, landmarks, definition lists, current-page semantics, pagination, focus, responsive layout, RTL, reduced motion, and forced colors reviewed
- [x] System Status projects non-sensitive Phase 23F readiness
- [x] Dedicated UI and malformed-route regression tests added
- [x] Dedicated exact-head PHP 8.0–8.3 UI workflow added
- [ ] Both exact-head matrices must be green on the final documentation-inclusive commit
- [ ] Final-head artifacts and checksums retained
- [ ] Real native-reference resolver implementation reviewed
- [ ] Object-level authorization against real providers reviewed
- [ ] Cross-user and cross-doctor IDOR staging evidence completed
- [ ] Optimistic-concurrency update execution reviewed before any update route
- [ ] Canonical shared audit-service integration reviewed

## Exact-Head Automated Gate

A prior green run is invalid after any source or documentation change. The same final commit must have:

- [ ] Baseline Integrity: PHP 8.0 successful
- [ ] Baseline Integrity: PHP 8.1 successful
- [ ] Baseline Integrity: PHP 8.2 successful
- [ ] Baseline Integrity: PHP 8.3 successful
- [ ] Collections UI: PHP 8.0 successful
- [ ] Collections UI: PHP 8.1 successful
- [ ] Collections UI: PHP 8.2 successful
- [ ] Collections UI: PHP 8.3 successful
- [ ] PHP and JavaScript syntax successful
- [ ] Phase 23A–23E regressions successful
- [ ] Phase 23F policy, runtime, repository, item-IDOR, read-REST, privacy, and architecture suites successful
- [ ] UI boundary, corrective-static, malformed-route, accessibility-marker, and no-mutation suites successful
- [ ] Required-file, version, merge-gate, checksum, and artifact steps successful

The check suite and artifacts for the current commit are authoritative; these static boxes remain conservative.

## WordPress Staging Gate

- [ ] Fresh activation without fatal error
- [ ] Upgrade from corrected Phase 23E and Schema Version 2 without data loss
- [ ] Schema Version 3 tables, columns, and indexes verified through WordPress migration APIs
- [ ] Read-only REST routes verified under real WordPress authentication
- [ ] Founder institution and Doctor own-scope collection reads
- [ ] Parent-authorized collection-item list and detail
- [ ] Pending/suspended-account denial
- [ ] Foreign collection/item enumeration resistance
- [ ] Real-provider missing, private, deleted, permission-lost, and outage behavior
- [ ] LiteSpeed and hosting-cache privacy
- [ ] Desktop, tablet, mobile, keyboard, screen reader, zoom, contrast, reduced motion, forced colors, and RTL
- [ ] Backup, restore, deactivation/reactivation, schema rollback, and application rollback

## Final Acceptance Gate

- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull Request moved from Draft only after every preceding gate passes
- [ ] Merge explicitly authorized

Any later source or documentation change requires both exact-head matrices to run again. PR #7 remains Draft and unmerged.
