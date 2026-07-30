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
- Phase 23E PR #5: Draft, open, unmerged
- Plugin version: `0.5.0`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked by Phase 23E independent review, defect correction, corrective re-review, exact-head QA, WordPress staging, real-account/privacy/accessibility/rollback testing, and Founder acceptance
- Technical status: Initial Phase 23E implementation candidate and Draft PR completed; initial exact-head workflow and independent review remain pending

## Implemented Phase 23E Candidate Scope

- [x] Optional native review/calendar projection interface
- [x] Server-derived current user, account, Founder, reviewer, scope, capability, and environment context
- [x] Universal Review Inbox as a bounded native projection
- [x] Native review state, reviewer assignment, due date, flags, version, freshness, and destination validation
- [x] Separation-of-duties removal of final self-approval and self-rejection
- [x] Federated Publishing Calendar as a bounded native projection
- [x] Native UTC schedule, IANA timezone, conflicts, failures, version, freshness, and destination validation
- [x] Explicit approve, request-changes, reject, and assign-reviewer routes
- [x] Explicit schedule, reschedule, and unschedule routes
- [x] Guarded operation-broker execution
- [x] Object-version, idempotency-key, and audit-reason requirements
- [x] Native object re-fetch before confirmed success
- [x] Provider-declared operation and native allowed-operation intersection
- [x] File 23-controlled environment acceptance gate
- [x] Strict totals, text, flags, timestamps, timezones, references, and same-origin URLs
- [x] Provider failure isolation and global provider/item limits
- [x] Responsive Review Inbox and Calendar templates
- [x] Executable Phase 23E tests
- [x] Phase 23E architecture and no-merge documentation

## Phase 23E Review and Acceptance Gates

- [x] Stacked branch created from the exact corrected Phase 23D head
- [x] Initial implementation candidate completed
- [x] Draft stacked PR #5 opened
- [ ] Independent source review completed
- [ ] Every defect documented
- [ ] Every defect corrected
- [ ] Corrective source re-review completed
- [ ] PHP 8.0 exact-head workflow successful
- [ ] PHP 8.1 exact-head workflow successful
- [ ] PHP 8.2 exact-head workflow successful
- [ ] PHP 8.3 exact-head workflow successful
- [ ] Contract, Dashboard Core, inventory, and role-workspace regressions green
- [ ] Phase 23E review/calendar tests green
- [ ] REST privacy, capability-installer, and provider-isolation tests green
- [ ] Architecture boundary guard green
- [ ] QA artifacts and source checksums retained
- [ ] Real File 21 Newsroom queue and decisions accepted on staging
- [ ] Real File 22 Composer/schedule routes accepted on staging
- [ ] Founder institution review/calendar scope verified
- [ ] Assigned reviewer scope and separation of duties verified
- [ ] Pending and suspended accounts denied review/schedule mutations
- [ ] Native timezone, conflicts, failures, and cron reconciliation verified
- [ ] Author suspension, permission loss, privacy hold, and copyright hold revalidated
- [ ] Cross-doctor IDOR and enumeration resistance accepted
- [ ] LiteSpeed and hosting-cache privacy verified
- [ ] Desktop, tablet, mobile, keyboard, screen-reader, zoom, contrast, reduced-motion, and RTL accepted
- [ ] Upgrade, deactivation/reactivation, backup/restore, and rollback accepted
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request ready for merge

## Non-Negotiable Restrictions

1. No duplicate publication, Composer, profile, knowledge, Newsroom, review, reviewer-assignment, schedule, cron, source, media, comment, correction, retraction, or analytics backend.
2. No provider self-acceptance for staging or production.
3. No caller-supplied user, role, Founder flag, reviewer, author, scope, capability, account state, or environment is trusted.
4. No generic unrestricted action endpoint.
5. No review or schedule mutation outside the guarded operation broker.
6. No mutation without current capability, approved File 00 account state, provider declaration, native authorization, environment acceptance, object version, idempotency key, and audit reason.
7. No final self-approval or self-rejection where separation of duties is required.
8. No visual or optimistic state is treated as success before native execution and re-fetch.
9. No non-IANA timezone, relative timestamp, malformed total, unsafe destination, or unbounded projection is accepted.
10. No patient-identifying content, contact information, secret, or signed URL enters review/calendar projections, logs, errors, or routes.
11. File 23 creates no native review or schedule table.
12. No merge before review, correction, re-review, exact-head QA, staging, rollback, and Founder acceptance.

## Next Technical Step

Run the complete PHP 8.0–8.3 exact-head workflow for Draft PR #5, inspect every job and artifact, correct any failure immediately, and then begin an independent Phase 23E source review. PRs #1–#5 must remain Draft and unmerged.
