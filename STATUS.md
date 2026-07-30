# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23F — Collections, Campaigns, and Knowledge Links
- Branch: `phase/23f-collections-knowledge`
- Parent branch: `phase/23e-review-calendar`
- Phase 23A PR #1: Draft, unmerged
- Phase 23B PR #2: Draft, twice source-reviewed, unmerged
- Phase 23C PR #3: Draft, source-reviewed, corrected, QA-green, unmerged
- Phase 23D PR #4: Draft, source-reviewed, corrected, QA-green, unmerged
- Phase 23E stacked work: source-reviewed, corrected, unmerged
- Phase 23F PR #7: Draft, open, second corrective review in progress, unmerged
- Plugin version: `0.6.1`
- Metadata schema: `3`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked by second corrected exact-head QA, WordPress staging, real native providers, real-account/privacy/accessibility/rollback evidence, Founder acceptance, and explicit merge authorization
- Technical status: two independent Phase 23F reviews recorded eighteen findings each; corrective source changes implemented; second exact-head workflow pending

## Corrected Phase 23F Scope

- [x] Exactly three File 23-owned organizational metadata tables
- [x] Schema Version 3 table, column, and index verification
- [x] No native content, destination, clinical data, media, result, report, or raw analytics duplication
- [x] Current approved File 00 account, capability, scope, and Founder authority
- [x] Actor-scoped idempotency with canonical request fingerprints
- [x] Exact replay and same-key/different-payload conflict handling
- [x] Bounded audit-reason and observed native-version persistence
- [x] Concrete WordPress repository for verified reads and idempotent creates
- [x] Repository-envelope and projected-row IDOR validation
- [x] Fail-closed institution scope without silent downgrade
- [x] Record-type-specific status filters
- [x] Native reference exact ID, visibility, permission, owner, version, and safe-destination validation
- [x] Read, collection-write, and knowledge-write readiness separated
- [x] Production writes disabled
- [x] Update, reorder, archive, and Phase 23F mutation REST routes disabled
- [x] Initial audit recorded in `docs/AUDIT-PHASE-23F-2026-07-30.md`
- [x] Runtime second audit recorded in `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`

## Phase 23F Review and Acceptance Gates

- [x] Stacked branch created from corrected Phase 23E head
- [x] Initial Phase 23F implementation candidate completed
- [x] Draft stacked PR #7 opened
- [x] Initial independent source review completed
- [x] Initial eighteen defects documented and corrected
- [x] Initial corrective source re-review completed
- [x] Runtime and persistence slice independently reviewed
- [x] Second eighteen defects documented
- [x] Second corrective source changes implemented
- [ ] PHP 8.0 final exact-head workflow successful
- [ ] PHP 8.1 final exact-head workflow successful
- [ ] PHP 8.2 final exact-head workflow successful
- [ ] PHP 8.3 final exact-head workflow successful
- [ ] Phase 23A–23E regressions green
- [ ] Phase 23F policy, runtime, and repository tests green
- [ ] Schema Version 3, replay, payload-conflict, and architecture gates green
- [ ] QA artifacts and source checksums retained
- [ ] Schema Version 2 to Version 3 upgrade accepted on WordPress staging
- [ ] Real File 00 Founder, Doctor, contributor, pending, and suspended accounts accepted
- [ ] Real native-reference resolver accepted
- [ ] Real-provider stale, deleted, private, permission-lost, and outage behavior accepted
- [ ] Cross-doctor IDOR and enumeration resistance accepted
- [ ] LiteSpeed and hosting-cache privacy verified
- [ ] Desktop, tablet, mobile, keyboard, screen-reader, zoom, contrast, reduced-motion, and RTL accepted
- [ ] Upgrade, deactivation/reactivation, backup/restore, and rollback accepted
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull Request ready for merge

## Non-Negotiable Restrictions

1. No duplicate publication, Composer, profile, native knowledge, Newsroom, review, schedule, source, media, clinical, comment, correction, retraction, report, or analytics backend.
2. No native destination persisted in File 23 metadata.
3. No provider self-acceptance for staging or production.
4. No caller-supplied user, role, Founder flag, author, scope, capability, account state, environment, native state, owner, version, or permission is authoritative.
5. No generic unrestricted action endpoint.
6. No metadata read for a non-approved current File 00 account.
7. No institution scope silently downgraded to own scope.
8. No write without verified schema/repository health, current authority, idempotency key, request fingerprint, audit reason, and operation-specific evidence.
9. No knowledge-link create without fresh native reference resolution.
10. No update, reorder, archive, or mutation REST execution before its separate review and acceptance gate.
11. No malformed repository response, foreign owner record, or optimistic state is treated as success.
12. No patient-identifying content, contact information, secret, signed URL, or native destination enters persistent metadata, logs, errors, or routes.
13. No merge before review, correction, re-review, exact-head QA, staging, rollback, Founder acceptance, and explicit authorization.

## Next Technical Step

Run the complete PHP 8.0–8.3 workflow on the second documentation-inclusive corrected head, inspect every job and retained artifact, correct every failure immediately, then perform a final source re-review. PR #7 and all predecessor PRs remain Draft and unmerged.
