# Phase 23D Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before review is complete.**

No Phase 23D branch, pull request, package, or release may be merged until every discovered defect is corrected, the affected source is re-reviewed, the complete workflow is green on the exact corrected head, and all applicable staging and Founder acceptance evidence is recorded.

## Initial Candidate

- Branch: `phase/23d-role-workspaces`
- Parent: reviewed Phase 23C head
- Plugin version: `0.4.0`
- Adapter Contract: `2.0.0`
- Nature: stacked Draft candidate

## Source Review Gate

- [ ] Independent review of every Phase 23D changed production file completed
- [ ] Authorization and cross-user scope review completed
- [ ] Founder-only official publishing review completed
- [ ] Pending/suspended read-only review completed
- [ ] Adapter acceptance and environment-gate review completed
- [ ] Destination and privacy review completed
- [ ] Projection-shape and truthful-data review completed
- [ ] Failure-isolation review completed
- [ ] Accessibility and responsive source review completed
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
- [ ] Phase 23A contract tests successful
- [ ] Phase 23B Dashboard Core regressions successful
- [ ] Phase 23C inventory regressions successful
- [ ] Phase 23D role-workspace tests successful
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
- [ ] No fabricated counts or dead actions
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

Any source commit after exact-head QA invalidates the automated evidence and requires a complete new run on the new head.
