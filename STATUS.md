# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23C — Federated Content Inventory
- Branch: `phase/23c-federated-inventory`
- Parent branch: `phase/23b-dashboard-core`
- Phase 23A pull request: Draft PR #1, unmerged
- Phase 23B pull request: Draft PR #2, twice reviewed technically, unmerged
- Phase 23C pull request: Not yet opened
- Plugin version: `0.3.0`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked pending Phase 23C independent review, correction, exact-head QA, WordPress staging, accessibility, rollback, and Founder acceptance
- Technical status: Initial Phase 23C implementation in progress; review and exact-current-head QA pending

## Why Phase 23C Is Stacked

The Founder authorized continued construction while preserving the permanent rule that no work is merged before review completion. Phase 23C therefore starts from the exact Phase 23B reviewed head. PR #1 and PR #2 remain Draft and unmerged; the Phase 23C candidate will also remain Draft and unmerged.

## Implemented Phase 23C Candidate Scope

- [x] Read-only federated inventory service
- [x] Bounded query normalizer and 200-item maximum federated window
- [x] Provider, object-type, state, language, topic, date, sort, direction, and scope filters
- [x] Server-injected current user ID
- [x] Doctor own-content scope
- [x] Founder-only institution scope
- [x] Four-dimensional lifecycle/review/visibility/operational projection
- [x] Unknown native state mapping warning
- [x] Canonical object reference and native version validation
- [x] Privacy-class validation against provider declaration
- [x] Same-origin, non-secret edit/preview/public destination validation
- [x] Provider exception isolation and partial-result diagnostics
- [x] Read-only item inspector
- [x] Native operation metadata projection with execution disabled
- [x] Read-only REST list and inspector endpoints
- [x] Responsive accessible inventory template
- [x] Phase 23C executable inventory tests
- [x] Architecture guard extended for read-only inventory boundaries
- [x] Phase 23C contract and review-gate documentation

## Phase 23C Review and Acceptance Gates

- [ ] Draft stacked pull request opened
- [ ] Independent source review completed
- [ ] Every defect documented
- [ ] Every defect corrected
- [ ] Corrective source re-review completed
- [ ] Exact-current-head PHP 8.0–8.3 checks green
- [ ] Contract and Dashboard Core regression tests green
- [ ] REST privacy, capability-installer, and provider-isolation tests green
- [ ] Inventory tests green
- [ ] Architecture boundary guard green
- [ ] Exact-head QA artifacts and source checksums retained
- [ ] Real File 21 adapter accepted on WordPress staging
- [ ] Real File 22/native Composer destinations accepted on staging
- [ ] Founder institution inventory verified with a real account
- [ ] Verified doctor own-content inventory verified with a real account
- [ ] Pending and suspended read-only inventory verified
- [ ] Cross-doctor IDOR/privacy tests accepted
- [ ] LiteSpeed and hosting cache privacy verified
- [ ] Mobile, keyboard, screen-reader, zoom, contrast, RTL, and responsive acceptance completed
- [ ] Upgrade, deactivation/reactivation, backup/restore, and rollback accepted
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request ready for merge

## Completed Parent Baselines

### Phase 23A

Technically reviewed governance, native ownership, Adapter Contract 2.0.0, File 00 fail-closed authorization, provider acceptance separation, server-controlled environment, guarded operation broker, object-version/idempotency controls, tests, and architecture boundaries. PR #1 remains Draft and unmerged.

### Phase 23B

Twice-reviewed private route, role-aware workspaces, real File 00 role capability provisioning, private route/REST cache policy, responsive shell, truthful overview, saved views, provider callback isolation, conflict protection, and exact-head QA artifacts. PR #2 remains Draft and unmerged pending staging and Founder acceptance.

## Non-Negotiable Restrictions

1. No duplicate publication backend.
2. No duplicate Composer.
3. No duplicate Newsroom or review ledger.
4. No duplicate native schedule, correction, retraction, source, media, comment, or raw analytics store.
5. No provider may self-declare staging or production acceptance.
6. No production write action is enabled in Phase 23C.
7. No client-supplied role, user ID, author, provider authority, status, capability, or environment is trusted.
8. No patient-identifying content may be stored in File 23-owned data, logs, tasks, caches, notifications, or exports.
9. Unknown native states remain unknown until an adapter mapping is reviewed.
10. No inventory endpoint, form, or button may execute a native mutation.
11. No navigation item or button may point to an unimplemented action.
12. No merge occurs before review completion, defect correction, corrective re-review, exact-head QA, staging acceptance, and Founder acceptance.

## Next Technical Step

Open a stacked Draft PR for Phase 23C, run the complete exact-head PHP matrix, inspect every failure, correct all defects, and keep all PRs unmerged. After automated QA is green, perform the mandatory independent source review and immediate corrective cycle before any staging or merge decision.
