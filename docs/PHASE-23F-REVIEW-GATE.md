# Phase 23F Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before completed review.**

Phase 23F remains a stacked Draft candidate. Schema, contracts, policy code, repository code, runtime services, tests, documentation, REST work, UI work, migration work, and every later mutation must each be independently reviewed, corrected, re-reviewed, and tested on the exact corrected head.

## Review History

The initial foundation was independently reviewed. Eighteen defects were recorded in `docs/AUDIT-PHASE-23F-2026-07-30.md`, corrected, re-reviewed, and tested.

The subsequent runtime and persistence slice was independently reviewed again. A second set of eighteen defects was recorded in `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`, corrected, re-reviewed, and tested.

The collection-item and read-API boundary was then independently reviewed a third time. Sixteen principal defects were recorded in `docs/AUDIT-PHASE-23F-THIRD-REVIEW-2026-07-31.md`. Corrections added strict direct repository validation, request-cached health, truthful continuation state, lifecycle semantics, parent collection item authorization, deterministic relation conflicts, and six explicit GET-only REST projections.

The mandatory third corrective re-review then found an optional-filter regression, overflowing decimal saturation, invalid UTF-8 acceptance, and malformed optional timestamp erasure. All four edge defects were corrected and executable tests were added.

**Corrective source re-review completed** applies only after the GitHub checks attached to the current documentation-inclusive commit are green. It does not approve WordPress staging, a real native resolver, REST mutation, accessible UI, production use, Founder acceptance, or merge.

## Current Candidate

- Branch: `phase/23f-collections-knowledge`
- Parent: corrected Phase 23E head
- Scope: Collections, Founder-governed campaigns, collection-item projections, and cross-module knowledge links
- Plugin development version: `0.6.2`
- Metadata schema version: `3`
- Native ownership: preserved
- Concrete File 23 repository: implemented for verified reads and idempotent creates
- Strict persistence and output projections: implemented
- Collection-item parent authorization: implemented
- Phase 23F read-only REST routes: six explicit GET projections
- Native-reference resolver injection: absent
- Production mutation: disabled
- Phase 23F REST mutation routes: absent
- Update, reorder, and archive execution: disabled
- Exact-head automated state: determined by current GitHub checks, never by a prior run number in this static document
- Staging readiness: not ready
- Merge readiness: blocked

## Source Review Gate

- [x] Initial Phase 23F production files independently reviewed
- [x] Eighteen initial defects documented and corrected
- [x] Initial corrective foundation source re-review completed
- [x] Runtime and persistence slice independently reviewed
- [x] Second eighteen defects documented and corrected
- [x] Second corrective source re-review completed
- [x] Collection-item, lifecycle, repository, and read-API slice independently reviewed
- [x] Third sixteen principal defects documented and corrected
- [x] Third corrective re-review edge findings documented and corrected
- [x] Metadata-only ownership boundary preserved
- [x] No native publication, profile, knowledge, clinical, media, destination, report, or analytics duplication in the schema
- [x] Collection scope and approved current-account authority reviewed
- [x] Campaign Founder authority and capability reviewed
- [x] Campaign phrase screening documented as defense in depth, not complete moderation
- [x] Knowledge relation allowlist, self-link rejection, replay, and duplicate-relation conflict reviewed
- [x] Native-reference contract and exact scope validation reviewed
- [x] Schema tables, columns, indexes, uniqueness, and lifecycle verification reviewed
- [x] Schema verification removed from every public request and cached per repository request
- [x] Actor-scoped idempotency and request-fingerprint conflict behavior reviewed
- [x] Bounded audit-reason and native-version persistence reviewed
- [x] Repository envelope, continuation state, projected-row IDOR, and lifecycle validation reviewed
- [x] Strict persistence and output-field allowlists reviewed
- [x] Overflowing integers, invalid UTF-8, malformed timestamps, unknown fields, text, lists, enums, and versions fail closed
- [x] Parent collection authorization precedes every collection-item query
- [x] Archived collection items are excluded consistently from default reads
- [x] Concrete WordPress repository reads and creates reviewed
- [x] Six explicit Phase 23F GET-only REST routes reviewed
- [x] Strict REST query allowlists, approved-account permission, pagination headers, and private/no-store coverage reviewed
- [x] Unsupported create/update/reorder/archive REST and repository operations fail closed
- [x] Prior Phase 23A–23E architecture controls retained
- [ ] The current documentation-inclusive commit must have a green exact-head PHP 8.0–8.3 matrix and retained artifacts
- [ ] Real native-reference resolver implementation reviewed
- [ ] Object-level authorization against real providers reviewed
- [ ] Cross-user and cross-doctor IDOR staging evidence completed
- [ ] Optimistic concurrency update execution reviewed
- [ ] Canonical shared audit-service integration reviewed
- [ ] Phase 23F REST nonce and strict mutation payload allowlists reviewed before any future mutation route
- [ ] Phase 23F accessible responsive UI and RTL source review completed

## Exact-Head Automated Gate

These controls must all be successful on the **same current commit**. A prior green run is invalid after any source or documentation change.

- [ ] PHP 8.0 successful on current documentation-inclusive head
- [ ] PHP 8.1 successful on current documentation-inclusive head
- [ ] PHP 8.2 successful on current documentation-inclusive head
- [ ] PHP 8.3 successful on current documentation-inclusive head
- [ ] PHP syntax successful
- [ ] JavaScript syntax successful
- [ ] Phase 23A–23E regressions successful
- [ ] Phase 23F policy tests successful
- [ ] Phase 23F runtime-authority tests successful
- [ ] Phase 23F concrete-repository tests successful
- [ ] Phase 23F collection-item and read-REST tests successful
- [ ] Schema Version 3 verification tests successful
- [ ] Strict persistence, UTF-8, overflow, optional-timestamp, and lifecycle tests successful
- [ ] Replay, payload-conflict, and duplicate-relation tests successful
- [ ] Architecture and GET-only REST controls successful
- [ ] Forbidden native ownership and persisted-destination checks successful
- [ ] Privacy tests successful
- [ ] Version and required-file checks successful
- [ ] QA artifacts and exact-head checksums retained

The authoritative state of these boxes is the GitHub check suite and artifacts for the current commit, not this static pre-run checklist.

## WordPress Staging Gate

- [ ] Fresh activation without fatal error
- [ ] Upgrade from corrected Phase 23E and Schema Version 2 without data loss
- [ ] Schema Version 3 tables, columns, and indexes verified through WordPress migration APIs
- [ ] Verified read-only REST routes under real WordPress routing and authentication
- [ ] Founder institution collection read
- [ ] Doctor own-scope collection read
- [ ] Parent-authorized collection-item list and detail
- [ ] Foreign collection/item enumeration resistance
- [ ] Founder campaign creation in explicitly enabled staging only
- [ ] Non-Founder campaign rejection
- [ ] Ethical campaign validation and human moderation
- [ ] Cross-module object resolution against real providers
- [ ] Missing, deleted, private, suspended, or permission-lost native object handling
- [ ] Cross-doctor IDOR resistance
- [ ] Exact idempotent replay, payload conflict, and duplicate-relation conflict behavior
- [ ] Concurrent metadata update conflicts after update operations are separately approved
- [ ] Archive without native deletion after archive operations are separately approved
- [ ] LiteSpeed and hosting-cache privacy
- [ ] Desktop, tablet, mobile, keyboard, screen reader, zoom, contrast, reduced motion, and RTL
- [ ] Backup, restore, deactivation/reactivation, schema rollback, and application rollback

## Final Acceptance Gate

- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull Request moved from Draft only after all preceding gates pass
- [ ] Merge explicitly authorized

Any source change after exact-head QA invalidates that evidence and requires the complete automated gate to run again.