# Phase 23C — Review and Acceptance Gate

## Status

Phase 23C is an implementation candidate for the read-only federated content inventory. It is not reviewed, staging-accepted, merge-ready, or production-ready merely because source files or automated checks exist.

## Mandatory Rule

> **DO NOT MERGE before review is complete, every discovered defect is corrected, corrective re-review is complete, tests are rerun on the exact corrected head, staging acceptance is complete, and Founder acceptance is recorded.**

## Source Review Gate

- [ ] Complete independent review of every Phase 23C changed file
- [ ] Verify native data ownership remains unchanged
- [ ] Verify no duplicate content, draft, review, schedule, source, media, comment, correction, retraction, or analytics store exists
- [ ] Verify inventory reads are current-user and native-policy scoped
- [ ] Verify Founder institution scope cannot be forged
- [ ] Verify provider exceptions and malformed responses remain isolated
- [ ] Verify four-dimensional state mappings never guess unknown native states
- [ ] Verify safe destination handling and secret-bearing URL rejection
- [ ] Verify no mutation endpoint, action form, or direct adapter mutation exists
- [ ] Document every finding
- [ ] Correct every finding
- [ ] Complete corrective re-review

## Exact-Head Automated Gate

- [ ] PHP 8.0 successful
- [ ] PHP 8.1 successful
- [ ] PHP 8.2 successful
- [ ] PHP 8.3 successful
- [ ] PHP syntax successful
- [ ] JavaScript syntax successful
- [ ] Phase 23A contract tests successful
- [ ] Phase 23B dashboard-core regressions successful
- [ ] REST privacy tests successful
- [ ] Capability installer tests successful
- [ ] Provider registration isolation tests successful
- [ ] Phase 23C inventory tests successful
- [ ] Architecture boundary guard successful
- [ ] Exact PR-head SHA verified
- [ ] QA artifacts and source checksums retained

## WordPress Staging Gate

- [ ] Fresh activation without fatal error
- [ ] Upgrade from reviewed Phase 23B candidate
- [ ] `/publishing-dashboard/?view=inventory` protected and private
- [ ] Inventory REST endpoints protected and private
- [ ] LiteSpeed and hosting cache layers do not cache dashboard or REST data
- [ ] Real File 21 adapter returns accurate own-content projections
- [ ] Real File 22/native Composer destinations are accurate and safe
- [ ] Founder institution scope matches native records
- [ ] Verified doctor sees only authorized owned content
- [ ] Non-verified, pending, and suspended accounts remain read-only and scoped
- [ ] Another doctor's restricted/private item is never exposed
- [ ] Invalid, sensitive, mismatched, or unknown projections behave safely
- [ ] One provider failure leaves healthy provider results usable
- [ ] No patient-identifying data appears in inventory, logs, errors, or URLs
- [ ] No duplicate Composer, Newsroom, content database, or native workflow exists

## UX and Accessibility Gate

- [ ] Desktop
- [ ] Tablet
- [ ] Mobile
- [ ] Keyboard-only navigation
- [ ] Screen-reader landmarks, labels, and table semantics
- [ ] Visible focus
- [ ] 200% and 400% zoom
- [ ] Contrast
- [ ] RTL
- [ ] Long titles and identifiers
- [ ] Empty, partial-error, loading-equivalent, and zero-provider states
- [ ] Weak connection behavior

## Reliability and Rollback Gate

- [ ] Large provider result set within bounded window
- [ ] Provider timeout/exception/malformed response
- [ ] Adapter unavailable/incompatible/revoked/suspended states
- [ ] Deactivation/reactivation
- [ ] Backup and restore
- [ ] Rollback to reviewed Phase 23B candidate
- [ ] No native data changed by rollback

## Founder Acceptance Gate

- [ ] Founder source review completed
- [ ] Founder inventory workflow accepted
- [ ] Founder visual experience accepted
- [ ] Founder privacy and ownership boundaries accepted
- [ ] Founder acceptance recorded on the exact reviewed head
- [ ] Pull request explicitly marked ready only after all preceding gates are complete

## Current Decision

**DO NOT MERGE.** Phase 23C remains a stacked Draft change until every applicable checkbox above is supported by evidence.
