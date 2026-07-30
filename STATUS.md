# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23C — Federated Content Inventory
- Branch: `phase/23c-federated-inventory`
- Parent branch: `phase/23b-dashboard-core`
- Phase 23A PR #1: Draft, unmerged
- Phase 23B PR #2: Draft, twice source-reviewed, unmerged
- Phase 23C PR #3: Draft, open, unmerged
- Plugin version: `0.3.1`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked by corrected exact-head QA, WordPress staging, real-account/privacy/accessibility/rollback testing, and Founder acceptance
- Technical status: Phase 23C independent source review completed; fourteen findings corrected; exact-current-head evidence must be recorded after the final documentation head

## Corrective Phase 23C Scope

- [x] Read-only federated inventory service
- [x] 200-item maximum accessible federated window
- [x] Exact-canonical provider/type/state/language/topic/sort/scope validation
- [x] Server-injected current user ID
- [x] File 23 defense-in-depth own-content enforcement
- [x] Founder-only institution scope
- [x] Cross-doctor list and inspector denial
- [x] Provider filter revalidation
- [x] Duplicate canonical-reference suppression
- [x] Strict provider-total validation
- [x] Reported, accessible, and validated-window totals separated
- [x] Four-dimensional state projection and unknown mapping warnings
- [x] Strict RFC 3339 timestamps
- [x] Same-origin, fragment-free, non-secret destination and thumbnail validation
- [x] Generic provider failure diagnostics
- [x] Read-only inspector and REST endpoints
- [x] Operation metadata inspection with execution disabled
- [x] Active filters preserved in inspector and pagination links
- [x] Mobile inventory retains material status columns
- [x] Expanded executable IDOR, filter, pagination, URL, timestamp, duplicate, total, and failure tests
- [x] Independent corrective audit recorded

## Review and Acceptance Gates

- [x] Draft stacked PR opened
- [x] Independent review of Phase 23C changed source completed
- [x] Fourteen defects documented
- [x] All identified source defects corrected
- [x] Corrective source re-review completed
- [ ] Exact-current-head PHP 8.0 successful
- [ ] Exact-current-head PHP 8.1 successful
- [ ] Exact-current-head PHP 8.2 successful
- [ ] Exact-current-head PHP 8.3 successful
- [ ] Contract and Dashboard Core regressions green
- [ ] REST privacy, capability-installer, and provider-isolation tests green
- [ ] Corrective inventory tests green
- [ ] Architecture boundary guard green
- [ ] Exact-head artifacts and source checksums retained
- [ ] Real File 21 adapter accepted on staging
- [ ] Real File 22/Composer destinations accepted on staging
- [ ] Founder institution inventory verified with a real account
- [ ] Doctor own-content inventory verified with real accounts
- [ ] Pending and suspended read-only inventory verified
- [ ] Cross-doctor IDOR/privacy accepted against native records
- [ ] LiteSpeed and hosting cache privacy verified
- [ ] Desktop, tablet, mobile, keyboard, screen-reader, zoom, contrast, and RTL accepted
- [ ] Upgrade, deactivation/reactivation, backup/restore, and rollback accepted
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request ready for merge

## Review Record

`docs/AUDIT-PHASE-23C-2026-07-30.md` records the complete finding and correction set. Any later source or evidence-affecting commit invalidates prior exact-head evidence and requires a complete rerun.

## Non-Negotiable Restrictions

1. No duplicate publication, Composer, Newsroom, review, schedule, source, media, comment, correction, retraction, or analytics backend.
2. No provider self-acceptance for staging or production.
3. No production write action in Phase 23C.
4. No client-supplied user, role, owner, authority, capability, status, or environment is trusted.
5. Own scope requires a validated native owner match at the File 23 boundary.
6. Unknown native states remain unknown.
7. Inventory endpoints, forms, and buttons remain read-only.
8. No patient-identifying data in File 23 storage, logs, errors, caches, URLs, or exports.
9. No material status column is removed from mobile access.
10. No merge before review, correction, re-review, exact-head QA, staging, and Founder acceptance.

## Next Technical Step

Complete the exact-current-head PHP 8.0–8.3 workflow, inspect every job and artifact, correct any failure immediately, update Draft PR #3 with the final evidence, and keep PRs #1–#3 unmerged.
