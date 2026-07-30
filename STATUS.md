# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23D — Founder and Doctor Role Workspaces
- Branch: `phase/23d-role-workspaces`
- Parent branch: `phase/23c-federated-inventory`
- Phase 23A PR #1: Draft, unmerged
- Phase 23B PR #2: Draft, twice source-reviewed, unmerged
- Phase 23C PR #3: Draft, source-reviewed, corrected, exact-head QA-green, unmerged
- Phase 23D PR #4: Draft, open, source-reviewed, corrected, automated-QA-green, unmerged
- Plugin version: `0.4.1`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked by WordPress staging, real-account/privacy/accessibility/rollback testing, and Founder acceptance
- Technical status: Phase 23D independent source review completed; sixteen findings corrected; complete PHP 8.0–8.3 regression matrix passed; one final exact-head evidence run is required after this documentation update

## Corrected Phase 23D Scope

- [x] Optional native role-workspace adapter interface
- [x] File 00 re-derived Founder, trusted-doctor, Doctor, restricted, and read-only authority
- [x] Truthful bounded measured cards with mandatory source timestamps
- [x] Founder official-publishing and Doctor reviewed-publishing policies
- [x] Canonical action-type mutability, capability, and Founder-only contract
- [x] Provider-declared capability enforcement
- [x] Current-user owner scope and Founder-only institution scope
- [x] Exact-origin HTTP(S), matching-port, fragment-free, non-secret native destinations
- [x] Raw-query duplicate/array/encoding rejection and recursive nested-target rejection
- [x] Adapter acceptance and environment gate for mutating launch destinations
- [x] Profile and knowledge destinations routed through centralized action gates
- [x] Native profile completion, verification, eligibility, and mandatory source timestamps
- [x] Native knowledge portfolio and successful-case aggregates with mandatory source timestamps
- [x] Global provider, card, action, activity, alert, profile, and knowledge bounds
- [x] Duplicate launch-action suppression and explicit truncation notice
- [x] Provider exception and invalid-projection isolation
- [x] Truthful no-provider and unavailable states
- [x] Responsive and accessible role-workspace template
- [x] Localized native-management disclosure
- [x] Expanded corrective workspace tests and architecture guards
- [x] Independent corrective audit recorded

## Phase 23D Review and Acceptance Gates

- [x] Stacked branch created from the exact reviewed Phase 23C head
- [x] Initial implementation completed
- [x] Draft stacked PR #4 opened
- [x] Independent source review completed
- [x] Sixteen defects documented
- [x] Every identified source defect corrected
- [x] Corrective source re-review completed
- [x] PHP 8.0 corrective matrix successful
- [x] PHP 8.1 corrective matrix successful
- [x] PHP 8.2 corrective matrix successful
- [x] PHP 8.3 corrective matrix successful
- [x] Contract, Dashboard Core, and inventory regressions green
- [x] Corrective workspace tests green
- [x] REST privacy, capability-installer, and provider-isolation tests green
- [x] Architecture boundary guard green
- [x] QA artifacts and source checksums retained for corrected source
- [ ] Final exact-current-documentation-head evidence retained
- [ ] Real File 21 official/professional projections accepted on staging
- [ ] Real File 22 Composer destinations accepted on staging
- [ ] Real File 03 profile projections accepted on staging
- [ ] Native knowledge portfolio projections accepted on staging
- [ ] Founder workspace verified with a real Founder account
- [ ] Doctor and trusted-doctor workspaces verified with real accounts
- [ ] Pending and suspended restricted workspaces verified
- [ ] Cross-doctor scope and destination privacy accepted
- [ ] LiteSpeed and hosting cache privacy verified
- [ ] Desktop, tablet, mobile, keyboard, screen-reader, zoom, contrast, reduced-motion, and RTL accepted
- [ ] Upgrade, deactivation/reactivation, backup/restore, and rollback accepted
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request ready for merge

## Non-Negotiable Restrictions

1. No duplicate publication, Composer, profile, knowledge, Newsroom, review, schedule, source, media, comment, correction, retraction, or analytics backend.
2. No provider self-acceptance for staging or production.
3. No caller-supplied user, role, Founder flag, owner, scope, read-only state, capability, status, or environment is trusted.
4. No provider may redefine action mutability, required capability, or Founder-only semantics.
5. No Doctor receives Founder-only official publishing.
6. No restricted account receives mutating native launch actions.
7. No profile or knowledge destination bypasses centralized action gates.
8. No measured card appears without a bounded numeric native value and absolute source timestamp.
9. No unsafe, cross-origin, signed, secret-bearing, expiring, credentialed, fragment, duplicate-query, or nested-target destination is rendered.
10. No patient-identifying content enters workspace cards, actions, activity, alerts, profile, knowledge, logs, errors, or URLs.
11. The workspace view does not execute native mutations.
12. No merge before review, correction, re-review, exact-head QA, staging, rollback, and Founder acceptance.

## Next Technical Step

Complete the final workflow against this documentation-inclusive head, retain its artifacts and checksums, update Draft PR #4 without changing repository source, and keep PRs #1–#4 unmerged.
