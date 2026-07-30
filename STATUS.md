# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23B — Dashboard Core
- Branch: `phase/23b-dashboard-core`
- Parent branch: `phase/23a-governance-contracts`
- Parent pull request: Draft PR #1, reviewed technically, unmerged
- Phase 23B pull request: Draft PR #2, open and unmerged
- Plugin version: `0.2.0`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked pending exact-head QA, WordPress staging acceptance, accessibility acceptance, and Founder acceptance
- Technical status: Source review, defect correction, and corrective re-review completed; final exact-head automated evidence pending

## Why Phase 23B Is Stacked

The Founder authorized continued construction while preserving the permanent rule that no work is merged before its review is complete. Phase 23B therefore starts from the reviewed Phase 23A head as a stacked branch. PR #1 and PR #2 remain Draft and unmerged.

## Implemented Phase 23B Scope

- [x] Protected `/publishing-dashboard/` route
- [x] Upgrade-safe rewrite registration
- [x] Authentication and File 00 capability gate
- [x] Founder, trusted-doctor, doctor, restricted, denied, and dependency-failure workspace resolution
- [x] Current-user binding for workspace authority and status privacy
- [x] Private no-store/noindex route and shortcode response headers
- [x] Private no-store policy for `/spdb/v1` REST responses
- [x] Responsive accessible dashboard shell
- [x] Truthful overview without fabricated publishing counts
- [x] Provider-readiness projection
- [x] Non-sensitive system-state projection
- [x] Personal bounded saved views
- [x] Separate saved-view read and approved-account write permissions
- [x] Typed write-time and read-time saved-view validation
- [x] Patient-sensitive key, URL, email, phone-pattern, and invalid-date exclusion
- [x] JavaScript first-view, empty-state, create, and delete behavior
- [x] Phase 23B executable tests added
- [x] CI requirements extended to PHP 8.0–8.3, JavaScript syntax, REST privacy, and architecture checks

## Phase 23B Review and Acceptance Gates

- [x] Independent source review completed
- [x] Defects documented
- [x] All discovered source defects corrected
- [x] Corrective source re-review completed
- [ ] Exact-current-head PHP 8.0–8.3 checks green
- [ ] Dashboard-core and REST privacy tests green on exact current head
- [ ] Architecture boundary guard green on exact current head
- [ ] Protected route tested on WordPress staging
- [ ] Private cache and indexing headers verified through the staging cache stack
- [ ] Founder workspace verified with a real Founder account
- [ ] Verified-doctor workspace verified with a real doctor account
- [ ] Pending and suspended read-only workspaces verified
- [ ] Mobile and accessibility acceptance completed
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request ready for merge

## Corrective Review Record

The Phase 23B source audit is recorded in:

`docs/AUDIT-PHASE-23B-2026-07-30.md`

The audit records route-header timing, shortcode privacy and asset timing, restricted mutation, read-time validation, deletion persistence, first-view JavaScript, cross-user status privacy, typed filter validation, date handling, REST cache policy, rewrite upgrades, asset registration, and regression-test defects and corrections.

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

Run the complete workflow on the exact current head. After it is green, record the exact SHA and workflow run in Draft PR #2. Then perform the outstanding Hostinger WordPress staging, role, cache-header, responsive, accessibility, and Founder acceptance tests. Both PRs remain unmerged.
