# Phase 23F Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before completed review.**

Phase 23F remains a stacked Draft candidate. Schema, contracts, policy code, tests, documentation, and later runtime work must be independently reviewed, corrected, re-reviewed, and tested on the exact corrected head.

## Corrective Source Review Result

The initial foundation was independently reviewed. Eighteen defects were recorded in `docs/AUDIT-PHASE-23F-2026-07-30.md`, corrected, and re-reviewed at source level. The corrected foundation now removes persisted native destinations and parallel result fields, restores all prior architecture guards, adds current-account and capability authority, scopes institution knowledge to Founder governance, verifies schema installation, and executes Phase 23F tests in CI.

**Corrective source re-review completed** for the corrected foundation and the newly added fail-closed runtime service/native-reference contract. This statement does not approve the future repository implementation, REST mutations, UI, staging, or production use.

## Current Candidate

- Branch: `phase/23f-collections-knowledge`
- Parent: corrected Phase 23E head
- Scope: Collections, Founder-governed campaigns, and cross-module knowledge links
- Plugin development version: `0.6.0`
- Metadata schema version: `2`
- Native ownership: preserved
- Production mutation: disabled
- Phase 23F REST mutation routes: absent
- Staging readiness: not ready
- Merge readiness: blocked

## Source Review Gate

- [x] Initial Phase 23F production files independently reviewed
- [x] Eighteen initial defects documented
- [x] Every initial source-level defect corrected
- [x] Corrective foundation source re-review completed
- [x] Metadata-only ownership boundary reviewed
- [x] No native publication, profile, knowledge, clinical, media, destination, report, or analytics duplication in the schema
- [x] Collection scope and current-account authority reviewed
- [x] Campaign Founder authority and capability reviewed
- [x] Campaign phrase screening documented as defense in depth, not complete moderation
- [x] Knowledge relation allowlist and self-link rejection reviewed
- [x] Canonical provider/object reference contract reviewed
- [x] Native-reference resolver contract added
- [x] Schema uniqueness, actor-scoped idempotency keys, indexing, and post-install verification reviewed
- [x] Sensitive-text and Unicode-aware length validation reviewed
- [x] Prior Phase 23A–23E architecture guards restored and extended
- [ ] Concrete WordPress repository implementation reviewed
- [ ] Real native-reference resolver implementation reviewed
- [ ] Object-level authorization against real providers reviewed
- [ ] Cross-user and cross-doctor IDOR evidence completed
- [ ] Optimistic concurrency persistence reviewed
- [ ] Idempotent write persistence and replay behavior reviewed
- [ ] Canonical audit-service persistence reviewed
- [ ] Phase 23F REST nonce and strict mutation payload allowlists reviewed
- [ ] Phase 23F accessible responsive UI and RTL source review completed

## Exact-Head Automated Gate

- [ ] PHP 8.0 successful on final corrected head
- [ ] PHP 8.1 successful on final corrected head
- [ ] PHP 8.2 successful on final corrected head
- [ ] PHP 8.3 successful on final corrected head
- [ ] PHP syntax successful
- [ ] JavaScript syntax successful
- [ ] Phase 23A–23E regressions successful
- [ ] Corrective Phase 23F tests successful
- [ ] Restored and extended architecture guard successful
- [ ] Forbidden native ownership and persisted-destination checks successful
- [ ] Privacy tests successful
- [ ] Version and required-file checks successful
- [ ] QA artifacts and exact-head checksums retained

## WordPress Staging Gate

- [ ] Fresh activation without fatal error
- [ ] Upgrade from corrected Phase 23E without data loss
- [ ] Schema creation and verification through WordPress migration APIs
- [ ] Founder institution collection
- [ ] Doctor own-scope collection
- [ ] Founder campaign creation
- [ ] Non-Founder campaign rejection
- [ ] Ethical campaign validation and human moderation
- [ ] Cross-module object resolution against real providers
- [ ] Missing, deleted, private, suspended, or permission-lost native object handling
- [ ] Cross-doctor IDOR resistance
- [ ] Concurrent metadata update conflicts
- [ ] Duplicate and replay resistance
- [ ] Archive without native deletion
- [ ] LiteSpeed and hosting-cache privacy
- [ ] Desktop, tablet, mobile, keyboard, screen reader, zoom, contrast, reduced motion, and RTL
- [ ] Backup, restore, deactivation/reactivation, and rollback

## Final Acceptance Gate

- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request moved from Draft only after all preceding gates pass
- [ ] Merge explicitly authorized

Any source change after exact-head QA invalidates that evidence and requires the complete automated gate to run again.
