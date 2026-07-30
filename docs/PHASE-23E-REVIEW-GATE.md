# Phase 23E Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before review is complete.**

No Phase 23E branch, pull request, package, or release may be merged until every changed production file has completed independent review, every discovered defect has been corrected, the corrected source has been re-reviewed, and the complete workflow is green on the exact corrected head. WordPress staging, real native providers, real accounts, accessibility, privacy, rollback, and Founder acceptance remain separate mandatory gates.

## Initial Candidate

- Branch: `phase/23e-review-calendar`
- Parent: corrected Phase 23D head
- Plugin version: `0.5.0`
- Adapter Contract: `2.0.0`
- Nature: stacked Draft candidate

## Source Review Gate

- [ ] Independent review of every Phase 23E changed production file completed
- [ ] Universal Review Inbox ownership boundary reviewed
- [ ] Review assignment and separation-of-duties rules reviewed
- [ ] Founder and reviewer scope reviewed
- [ ] Calendar ownership, timezone, conflict, and failure projections reviewed
- [ ] Explicit REST operation routes reviewed
- [ ] CSRF, capability, account-state, IDOR, object-version, and idempotency controls reviewed
- [ ] Native re-fetch and false-success prevention reviewed
- [ ] Patient privacy and destination safety reviewed
- [ ] Provider failure isolation and global bounds reviewed
- [ ] Accessibility, responsive, and RTL source review completed
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
- [ ] Phase 23A contract regressions successful
- [ ] Phase 23B Dashboard Core regressions successful
- [ ] Phase 23C inventory regressions successful
- [ ] Phase 23D role-workspace regressions successful
- [ ] Phase 23E review/calendar tests successful
- [ ] REST privacy tests successful
- [ ] Capability installer and provider-isolation tests successful
- [ ] Architecture boundary guard successful
- [ ] Version and required-file checks successful
- [ ] QA artifacts and source checksums retained

## WordPress Staging Gate

- [ ] Activation and upgrade without fatal error
- [ ] Real File 21 Newsroom queue projection
- [ ] Real File 21 review decision execution and re-fetch
- [ ] Real File 22 Composer/schedule routes
- [ ] Native schedule create, reschedule, unschedule, and failure reconciliation
- [ ] Founder institution review/calendar scope
- [ ] Assigned reviewer scope
- [ ] Separation of duties for author-reviewer conflict
- [ ] Pending and suspended accounts denied mutation
- [ ] Cross-doctor IDOR and object-enumeration resistance
- [ ] Native timezone and daylight-saving behavior
- [ ] Author suspension, permission loss, privacy hold, and copyright hold revalidation
- [ ] Provider outage and partial-provider failure
- [ ] No duplicate Newsroom or schedule database
- [ ] LiteSpeed and hosting-cache privacy
- [ ] Desktop, tablet, mobile, keyboard, screen reader, focus, zoom, contrast, reduced motion, and RTL
- [ ] Fresh install, upgrade, deactivation/reactivation, backup/restore, and rollback

## Final Acceptance Gate

- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Pull request moved from Draft only after all preceding gates pass
- [ ] Merge explicitly authorized

Any source commit after exact-head QA invalidates that automated evidence and requires a complete rerun on the new head.
