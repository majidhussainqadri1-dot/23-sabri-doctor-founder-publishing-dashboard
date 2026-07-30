# Phase 23F Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before completed review.**

Phase 23F remains a stacked Draft candidate. Schema, contracts, policy code, tests, documentation, and later runtime work must be independently reviewed, corrected, re-reviewed, and tested on the exact corrected head.

## Initial Foundation

- Branch: `phase/23f-collections-knowledge`
- Parent: corrected Phase 23E head
- Scope: Collections, Founder-governed campaigns, and cross-module knowledge links
- Native ownership: preserved
- Production mutation: disabled
- Staging readiness: not ready
- Merge readiness: blocked

## Source Review Gate

- [ ] Every Phase 23F production file independently reviewed
- [ ] Metadata-only ownership boundary reviewed
- [ ] No native publication, profile, knowledge, clinical, media, or analytics duplication
- [ ] Collection scope and ownership reviewed
- [ ] Campaign Founder authority reviewed
- [ ] Campaign ethics and anti-dark-pattern rules reviewed
- [ ] Knowledge relation allowlist reviewed
- [ ] Canonical provider/object references reviewed
- [ ] Object-level authorization and native re-resolution reviewed
- [ ] IDOR resistance reviewed
- [ ] Schema uniqueness, indexing, migration, and rollback reviewed
- [ ] Optimistic concurrency and version conflicts reviewed
- [ ] Idempotency and audit persistence reviewed
- [ ] REST nonce and strict payload allowlists reviewed
- [ ] Privacy and sensitive-text rejection reviewed
- [ ] Accessibility, responsive, and RTL source review completed
- [ ] Every defect documented
- [ ] Every defect corrected
- [ ] Corrective source re-review completed

## Exact-Head Automated Gate

- [ ] PHP 8.0 successful
- [ ] PHP 8.1 successful
- [ ] PHP 8.2 successful
- [ ] PHP 8.3 successful
- [ ] PHP syntax successful
- [ ] JavaScript syntax successful
- [ ] Phase 23A–23E regressions successful
- [ ] Phase 23F policy tests successful
- [ ] Schema architecture guard successful
- [ ] Forbidden native ownership checks successful
- [ ] Privacy tests successful
- [ ] Authorization and IDOR tests successful
- [ ] Version and required-file checks successful
- [ ] QA artifacts and exact-head checksums retained

## WordPress Staging Gate

- [ ] Fresh activation without fatal error
- [ ] Upgrade from corrected Phase 23E without data loss
- [ ] Schema creation through WordPress migration APIs
- [ ] Founder institution collection
- [ ] Doctor own-scope collection
- [ ] Founder campaign creation
- [ ] Non-Founder campaign rejection
- [ ] Ethical campaign validation
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
