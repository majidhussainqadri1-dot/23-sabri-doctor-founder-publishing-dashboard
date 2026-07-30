# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23E — Universal Review Inbox and Federated Publishing Calendar
- Branch: `phase/23e-review-calendar`
- Parent branch: `phase/23d-role-workspaces`
- Phase 23A PR #1: Draft, unmerged
- Phase 23B PR #2: Draft, twice source-reviewed, unmerged
- Phase 23C PR #3: Draft, source-reviewed, corrected, exact-head QA-green, unmerged
- Phase 23D PR #4: Draft, source-reviewed, corrected, exact-head QA-green, unmerged
- Phase 23E PR #5: Draft, open, source-reviewed, corrected, unmerged
- Plugin version: `0.5.1`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked by corrected exact-head QA, WordPress staging, real native adapters, real-account/privacy/accessibility/rollback evidence, and Founder acceptance
- Technical status: Phase 23E independent source review completed; twenty-four defects documented and corrected; corrective source re-review completed; final exact-head workflow pending

## Corrected Phase 23E Scope

- [x] Optional native review/calendar projection interface
- [x] Current File 00 user, account, Founder, reviewer, scope, capability, and environment re-derived server-side
- [x] Universal Review Inbox with bounded native ownership-preserving projections
- [x] Federated Publishing Calendar with bounded native ownership-preserving projections
- [x] Fail-closed normalized filters and reversed-range rejection
- [x] Central bounded pagination and truthful reported/accessed/current-page totals
- [x] Canonical operation contracts for surface, capability, Founder policy, eligible state, ownership, assignment, and separation
- [x] Fresh object-level authorization before every native mutation
- [x] Self-approval and self-rejection denial independent of provider flags
- [x] Founder-only reviewer assignment and reviewer-target eligibility
- [x] Explicit REST nonce, strict payload allowlists, required operation fields, object version, idempotency, and audit reason
- [x] Canonical UTC schedule timestamps and IANA native timezone checks
- [x] Provider capability-downgrade rejection
- [x] Matching native confirmation reference before success
- [x] Provider failure isolation and bounded aggregate totals
- [x] Accessible responsive views, visible focus, central pagination, and reduced motion
- [x] Expanded corrective tests and architecture controls
- [x] Independent audit recorded in `docs/AUDIT-PHASE-23E-2026-07-30.md`

## Phase 23E Review and Acceptance Gates

- [x] Stacked branch created from the exact corrected Phase 23D head
- [x] Initial implementation candidate completed
- [x] Draft stacked PR #5 opened
- [x] Independent source review completed
- [x] Twenty-four defects documented
- [x] Every documented source defect corrected
- [x] Corrective source re-review completed
- [ ] PHP 8.0 final exact-head workflow successful
- [ ] PHP 8.1 final exact-head workflow successful
- [ ] PHP 8.2 final exact-head workflow successful
- [ ] PHP 8.3 final exact-head workflow successful
- [ ] Contract, Dashboard Core, inventory, and role-workspace regressions green
- [ ] Corrective Phase 23E review/calendar tests green
- [ ] REST privacy, capability-installer, and provider-isolation tests green
- [ ] Architecture boundary guard green
- [ ] QA artifacts and source checksums retained
- [ ] Real File 21 Newsroom queue and decisions accepted on staging
- [ ] Real File 22 Composer/schedule routes accepted on staging
- [ ] Founder institution review/calendar scope verified
- [ ] Assigned reviewer scope, Founder-only assignment, and separation of duties verified
- [ ] Pending and suspended accounts denied review/schedule mutations
- [ ] Native timezone, DST, conflicts, failures, and cron reconciliation verified
- [ ] Author suspension, permission loss, privacy hold, and copyright hold revalidated
- [ ] Cross-doctor and cross-reviewer IDOR and enumeration resistance accepted
- [ ] LiteSpeed and hosting-cache privacy verified
- [ ] Desktop, tablet, mobile, keyboard, screen-reader, zoom, contrast, reduced-motion, and RTL accepted
- [ ] Upgrade, deactivation/reactivation, backup/restore, and rollback accepted
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request ready for merge

## Non-Negotiable Restrictions

1. No duplicate publication, Composer, profile, knowledge, Newsroom, review, reviewer-assignment, schedule, cron, source, media, comment, correction, retraction, or analytics backend.
2. No provider self-acceptance for staging or production.
3. No caller-supplied user, role, Founder flag, reviewer, author, scope, capability, account state, environment, native state, or separation flag is authoritative.
4. No generic unrestricted action endpoint.
5. No review or schedule mutation outside the guarded operation broker.
6. No mutation without current capability, approved File 00 state, fresh item authorization, provider declaration, native authorization, environment acceptance, object version, idempotency key, audit reason, and required operation data.
7. No final self-approval or self-rejection.
8. No Reviewer assignment without current Founder authority and an eligible target.
9. No optimistic state or mismatched native re-fetch is treated as success.
10. No malformed query silently broadens scope.
11. No patient-identifying content, contact information, secret, or signed URL enters projections, payloads, logs, errors, or routes.
12. No merge before review, correction, re-review, exact-head QA, staging, rollback, and Founder acceptance.

## Next Technical Step

Run the complete PHP 8.0–8.3 workflow on the final documentation-inclusive corrected head, inspect every job and retained artifact, and correct any failure immediately. PRs #1–#5 remain Draft and unmerged.
