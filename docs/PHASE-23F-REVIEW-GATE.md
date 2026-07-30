# Phase 23F Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before completed review.**

Phase 23F remains a stacked Draft candidate. Schema, contracts, policy code, repository code, runtime services, tests, documentation, REST work, and UI work must each be independently reviewed, corrected, re-reviewed, and tested on the exact corrected head.

## Review History

The initial foundation was independently reviewed. Eighteen defects were recorded in `docs/AUDIT-PHASE-23F-2026-07-30.md`, corrected, re-reviewed, and tested.

The subsequent runtime and persistence slice was independently reviewed again. A second set of eighteen defects was recorded in `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`. Corrective source work includes Schema Version 3, table/column/index verification, a concrete WordPress repository, repository-envelope validation, approved-account read authority, fail-closed institution scope, request fingerprints, audit-reason persistence, observed native versions, and separate read/collection-write/knowledge-write readiness.

The mandatory corrective re-review then identified output-field allowlisting, strict projection normalization, and public-request schema-verification overhead defects. Those findings were corrected, executable regression coverage was added, and the complete PHP 8.0–8.3 source gate passed again.

**Corrective source re-review completed** refers only to the current Phase 23F source slice. It does not approve WordPress staging, a real native resolver, REST mutation, UI, production use, Founder acceptance, or merge.

## Current Candidate

- Branch: `phase/23f-collections-knowledge`
- Parent: corrected Phase 23E head
- Scope: Collections, Founder-governed campaigns, and cross-module knowledge links
- Plugin development version: `0.6.1`
- Metadata schema version: `3`
- Native ownership: preserved
- Concrete File 23 repository: implemented for verified reads and idempotent creates
- Strict allowlisted output projections: implemented
- Native-reference resolver injection: absent
- Production mutation: disabled
- Phase 23F REST mutation routes: absent
- Update, reorder, and archive execution: disabled
- Source readiness: corrective source gate completed
- Staging readiness: not ready
- Merge readiness: blocked

## Source Review Gate

- [x] Initial Phase 23F production files independently reviewed
- [x] Eighteen initial defects documented and corrected
- [x] Initial corrective foundation source re-review completed
- [x] Runtime and persistence slice independently reviewed
- [x] Second eighteen defects documented and corrected
- [x] Corrective re-review findings documented and corrected
- [x] Metadata-only ownership boundary preserved
- [x] No native publication, profile, knowledge, clinical, media, destination, report, or analytics duplication in the schema
- [x] Collection scope and approved current-account authority reviewed
- [x] Campaign Founder authority and capability reviewed
- [x] Campaign phrase screening documented as defense in depth, not complete moderation
- [x] Knowledge relation allowlist and self-link rejection reviewed
- [x] Native-reference contract and exact scope validation reviewed
- [x] Schema tables, columns, indexes, uniqueness, and lifecycle verification reviewed
- [x] Schema verification removed from every public request
- [x] Actor-scoped idempotency and request-fingerprint conflict behavior reviewed
- [x] Bounded audit-reason and native-version persistence reviewed
- [x] Repository envelope and projected-row IDOR validation reviewed
- [x] Strict output-field allowlists and reconstructed projections reviewed
- [x] Unknown fields, malformed numeric values, text, lists, enums, and timestamps fail closed
- [x] Concrete WordPress repository reads and creates reviewed
- [x] Unsupported update, reorder, and archive operations fail closed
- [x] Prior Phase 23A–23E architecture guards retained
- [x] Corrective exact-head automated source evidence completed
- [ ] Real native-reference resolver implementation reviewed
- [ ] Object-level authorization against real providers reviewed
- [ ] Cross-user and cross-doctor IDOR staging evidence completed
- [ ] Optimistic concurrency update execution reviewed
- [ ] Canonical shared audit-service integration reviewed
- [ ] Phase 23F REST nonce and strict mutation payload allowlists reviewed
- [ ] Phase 23F accessible responsive UI and RTL source review completed

## Exact-Head Automated Gate

- [x] PHP 8.0 successful on final corrected source head
- [x] PHP 8.1 successful on final corrected source head
- [x] PHP 8.2 successful on final corrected source head
- [x] PHP 8.3 successful on final corrected source head
- [x] PHP syntax successful
- [x] JavaScript syntax successful
- [x] Phase 23A–23E regressions successful
- [x] Phase 23F policy tests successful
- [x] Phase 23F runtime-authority tests successful
- [x] Phase 23F concrete-repository tests successful
- [x] Schema Version 3 verification tests successful
- [x] Strict projection and unknown-field rejection tests successful
- [x] Replay and same-key/different-payload conflict tests successful
- [x] Restored architecture guard successful
- [x] Forbidden native ownership and persisted-destination checks successful
- [x] Privacy tests successful
- [x] Version and required-file checks successful
- [x] QA artifacts and exact-head checksums retained

## WordPress Staging Gate

- [ ] Fresh activation without fatal error
- [ ] Upgrade from corrected Phase 23E and Schema Version 2 without data loss
- [ ] Schema Version 3 tables, columns, and indexes verified through WordPress migration APIs
- [ ] Founder institution collection
- [ ] Doctor own-scope collection
- [ ] Founder campaign creation
- [ ] Non-Founder campaign rejection
- [ ] Ethical campaign validation and human moderation
- [ ] Cross-module object resolution against real providers
- [ ] Missing, deleted, private, suspended, or permission-lost native object handling
- [ ] Cross-doctor IDOR resistance
- [ ] Exact idempotent replay and payload-conflict behavior
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
