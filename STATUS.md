# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23B — Dashboard Core
- Branch: `phase/23b-dashboard-core`
- Parent branch: `phase/23a-governance-contracts`
- Parent pull request: Draft PR #1, reviewed technically, unmerged
- Phase 23B pull request: Not yet opened
- Plugin version: `0.2.0`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked pending Phase 23B review, correction, staging acceptance, and Founder acceptance
- Technical status: Dashboard-core implementation in progress; current-head QA pending

## Why Phase 23B Is Stacked

The Founder authorized continued construction while preserving the permanent rule that no work is merged before its review is complete. Phase 23B therefore starts from the reviewed Phase 23A head as a stacked branch. PR #1 remains Draft and unmerged.

## Implemented Phase 23B Scope

- [x] Protected `/publishing-dashboard/` route
- [x] Upgrade-safe rewrite registration
- [x] Authentication and File 00 capability gate
- [x] Founder, trusted-doctor, doctor, restricted, denied, and dependency-failure workspace resolution
- [x] Current-user binding for workspace authority
- [x] Private no-store/noindex response headers
- [x] Responsive accessible dashboard shell
- [x] Truthful overview without fabricated publishing counts
- [x] Provider-readiness projection
- [x] Non-sensitive system-state projection
- [x] Personal bounded saved views
- [x] Saved-view REST permission checks
- [x] Write-time and read-time saved-view validation
- [x] Patient-sensitive filter exclusion
- [x] JavaScript keyboard-operable create/delete behavior
- [x] Phase 23B executable tests added
- [x] CI requirements extended to PHP 8.0–8.3 and JavaScript syntax

## Phase 23B Review and Acceptance Gates

- [ ] Independent source review completed
- [ ] Defects documented
- [ ] All discovered defects corrected
- [ ] Corrective re-review completed
- [ ] Exact-current-head PHP 8.0–8.3 checks green
- [ ] Dashboard-core executable tests green
- [ ] Architecture boundary guard green
- [ ] Protected route tested on WordPress staging
- [ ] Private cache and indexing headers verified on staging
- [ ] Founder workspace verified with a real Founder account
- [ ] Verified-doctor workspace verified with a real doctor account
- [ ] Pending and suspended read-only workspaces verified
- [ ] Mobile and accessibility acceptance completed
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request ready for merge

## Phase 23A Baseline

Phase 23A established and technically reviewed:

- native data ownership;
- Adapter Contract 2.0.0;
- File 00 fail-closed authorization;
- provider acceptance separated from technical capability;
- server-controlled environment resolution;
- guarded native operation broker;
- object-version and idempotency controls;
- executable contract tests;
- architecture boundary guards;
- PHP 8.0–8.3 CI.

Its PR remains Draft and unmerged until the applicable acceptance is explicitly recorded.

## Non-Negotiable Restrictions

1. No duplicate publication backend.
2. No duplicate Composer.
3. No duplicate Newsroom or review ledger.
4. No duplicate native schedule, correction, retraction, source, media, comment, or raw analytics store.
5. No provider may self-declare staging or production acceptance.
6. No production write action is enabled in Phase 23B.
7. No client-supplied role, author, provider, status, capability, or environment is trusted.
8. No patient-identifying content may be stored in File 23-owned data, logs, tasks, caches, notifications, or exports.
9. No navigation item or button may point to an unimplemented action.
10. No merge occurs before review completion, defect correction, corrective re-review, exact-head QA, staging acceptance, and Founder acceptance.

## Next Technical Step

Open a stacked Draft PR for Phase 23B, run the complete exact-head workflow, conduct an independent source review, correct every discovered defect, rerun QA, and keep both PRs unmerged until their respective acceptance gates are complete.
