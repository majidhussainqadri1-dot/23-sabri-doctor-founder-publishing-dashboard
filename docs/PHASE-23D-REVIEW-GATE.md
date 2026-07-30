# Phase 23D Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before review is complete.**

No Phase 23D branch, pull request, package, or release may be merged until every discovered defect is corrected, the affected source is re-reviewed, the complete workflow is green on the exact corrected head, and all applicable staging and Founder acceptance evidence is recorded.

## Corrective Candidate

- Branch: `phase/23d-role-workspaces`
- Parent: reviewed Phase 23C head
- Pull request: Draft PR #4, open and unmerged
- Plugin version: `0.4.1`
- Adapter Contract: `2.0.0`
- Nature: stacked corrective Draft candidate
- Audit: `docs/AUDIT-PHASE-23D-2026-07-30.md`

## Source Review Gate

- [x] Independent review completed
- [x] Every Phase 23D changed production file reviewed
- [x] Authorization and cross-user scope review completed
- [x] Founder-only official publishing review completed
- [x] Pending/suspended read-only review completed
- [x] Adapter acceptance and environment-gate review completed
- [x] Destination and privacy review completed
- [x] Projection-shape and truthful-data review completed
- [x] Failure-isolation and global-bound review completed
- [x] Accessibility and responsive source review completed
- [x] Sixteen defects documented
- [x] Every identified source defect corrected
- [x] Corrective source re-review completed

## Exact-Head Automated Gate

- [ ] PHP 8.0 successful on final corrected head
- [ ] PHP 8.1 successful on final corrected head
- [ ] PHP 8.2 successful on final corrected head
- [ ] PHP 8.3 successful on final corrected head
- [ ] PHP syntax successful
- [ ] JavaScript syntax successful
- [ ] Phase 23A contract tests successful
- [ ] Phase 23B Dashboard Core regressions successful
- [ ] Phase 23C inventory regressions successful
- [ ] Corrective Phase 23D role-workspace tests successful
- [ ] REST privacy tests successful
- [ ] Capability-installer and provider-isolation tests successful
- [ ] Architecture boundary guard successful
- [ ] Version and required-file checks successful
- [ ] QA artifacts and source checksums retained

## WordPress Staging Gate

- [ ] Activation without fatal error
- [ ] Founder workspace with a real Founder account
- [ ] Verified Doctor workspace with a real Doctor account
- [ ] Trusted Doctor category-policy behavior
- [ ] Pending account restricted read-only behavior
- [ ] Suspended account restricted read-only behavior
- [ ] Cross-doctor scope and destination privacy
- [ ] Real File 21 official/professional projections
- [ ] Real File 22 Composer destinations
- [ ] Real File 03 profile completion, verification, edit, and public destinations
- [ ] Native knowledge portfolio projections
- [ ] No fabricated counts, duplicate actions, or dead actions
- [ ] LiteSpeed and hosting-cache privacy
- [ ] Desktop, tablet, and mobile
- [ ] Keyboard, screen reader, focus, zoom, contrast, reduced motion, and RTL
- [ ] Fresh install and upgrade
- [ ] Deactivation/reactivation
- [ ] Backup and restore
- [ ] Rollback

## Final Acceptance Gate

- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request moved from Draft only after all preceding gates pass
- [ ] Merge explicitly authorized

Any source or evidence-affecting commit after exact-head QA invalidates the automated evidence and requires a complete new run on the new head.
