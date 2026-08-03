# Phase 23E Independent Corrective Audit — 2026-07-30

## Verdict

The initial `0.5.0` Universal Review Inbox and Federated Publishing Calendar candidate was **not acceptable for merge**. Independent review found twenty-four authorization, privacy, integrity, pagination, confirmation, and accessibility defects. Every source-level finding listed below has been corrected in development version `0.5.1`, then subjected to corrective regression testing.

**DO NOT MERGE.** Source correction and automated QA do not replace real File 21/File 22 adapters, Hostinger staging, real-account and IDOR evidence, accessibility acceptance, cache verification, rollback evidence, or Founder acceptance.

## Findings and Corrections

1. **Malformed filters silently broadened queries.** Invalid providers, states, dates, timezones, and nested values were discarded. They now fail closed and no provider is queried.
2. **Unknown projection surfaces defaulted to calendar semantics.** Only exact `review` and `calendar` surfaces are accepted.
3. **Invalid pagination silently defaulted or clamped.** Page and per-page values now require canonical positive integers within fixed bounds.
4. **Reversed date ranges were accepted.** Review and calendar ranges now reject `from > to`.
5. **Provider pagination was federated incorrectly.** The requested page was sent independently to each provider. File 23 now retrieves a bounded first-page window, globally validates and sorts it, then paginates centrally.
6. **Totals were ambiguous.** Current-page count, accessible validated window, native reported total, and page count are now separate values.
7. **Aggregate total overflow was possible.** Per-provider totals are bounded and federated totals use saturating aggregation with a truthful truncation state.
8. **Malformed continuation flags were silently treated as false.** `has_more` must now be a strict boolean.
9. **Associative arrays could masquerade as item, operation, or flag lists.** Projection lists now require canonical zero-based list shape.
10. **Object identifiers were inconsistent with inventory and REST routes.** Review/calendar projections now use the canonical projection object-ID validator and 128-character REST route boundary.
11. **Provider markup could be silently stripped into trusted text.** Required and optional projected text must already be plain, bounded, control-free, and privacy-safe.
12. **Duplicate flags and operation keys were silently deduplicated.** Duplicates now invalidate the provider projection.
13. **Separation-of-duties metadata could be missing or malformed.** The flag is now explicit and boolean.
14. **Self-approval and self-rejection relied on provider metadata and projected UI.** Both operations are blocked for the author regardless of provider flags and are rechecked on direct REST execution.
15. **Direct mutation routes lacked fresh object-level authorization.** Every operation now re-fetches and revalidates the current native projection before the broker executes.
16. **Review assignment and calendar ownership could be bypassed by a direct route.** Current reviewer assignment, own/Founder scope, provider eligibility, operation state, and native version are now rechecked server-side.
17. **Providers could downgrade canonical operation capability semantics.** Every operation has a File 23-owned contract; provider definitions must exactly match its capability and surface.
18. **Reviewer assignment was not Founder-only and target eligibility was unchecked.** `assign_reviewer` now requires current Founder authority, an approved target account, and target review capability.
19. **Mutation routes lacked explicit request-nonce proof.** A valid WordPress REST nonce is now required both in route permission and execution defense-in-depth.
20. **Unknown or authority-bearing payload fields were ignored.** Mutation payloads now use a strict operation-specific allowlist and reject non-scalar shapes.
21. **Operation-specific data was optional.** Request-changes/rejection require structured reason and meaningful privacy-safe note; assignment requires reviewer ID; scheduling requires canonical UTC time and IANA timezone.
22. **Timestamp regex accepted impossible dates.** UTC schedule timestamps are parsed and required to round-trip to canonical RFC 3339 UTC.
23. **Audit and review text privacy validation was incomplete.** URLs, email, Pakistani mobile, CNIC-like data, control characters, and excessive lengths are rejected.
24. **A broker re-fetch could confirm the wrong native reference.** File 23 now requires the confirmed object type and ID to match the requested native object before reporting success.

## Additional Corrective Controls

- Provider exceptions remain isolated and non-sensitive.
- Provider acceptance remains File 23-controlled; production writes fail closed without accepted adapters.
- Mutation templates remain absent; the review and calendar pages expose only safe native destinations and authorization metadata.
- Review and calendar tables retain all columns on mobile through keyboard-focusable horizontal regions.
- Pagination links preserve only normalized filters and expose visible focus.
- Reduced-motion preferences are respected.
- No review row, decision, assignment, schedule, cron state, publication body, source, media, comment, correction, retraction, or raw analytics record is owned by File 23.

## Corrective Re-review Scope

The corrective review covers:

- `includes/class-spdb-review-calendar-validator.php`
- `includes/class-spdb-review-calendar-service.php`
- `includes/class-spdb-review-calendar-rest-controller.php`
- `includes/class-spdb-dashboard-page.php`
- `includes/class-spdb-workspace-resolver.php`
- `includes/class-spdb-plugin.php`
- `templates/review.php`
- `templates/calendar.php`
- `assets/css/review-calendar.css`
- Phase 23E fixtures, tests, architecture guard, workflow, versioning, and documentation.

## Remaining Acceptance Gates

- Real File 21 Newsroom review adapter.
- Real File 22 Composer and schedule adapter.
- Hostinger WordPress staging activation.
- Real Founder, reviewer, Doctor, pending, suspended, and reviewer-target accounts.
- Cross-doctor and cross-reviewer IDOR tests against real records.
- Timezone, DST, conflict, failure, retry, cron reconciliation, and notification evidence.
- LiteSpeed and hosting cache privacy verification.
- Desktop, tablet, mobile, keyboard, screen-reader, zoom, contrast, reduced-motion, and RTL acceptance.
- Upgrade, deactivation/reactivation, backup/restore, and rollback evidence.
- Founder review and explicit merge authorization.

## Merge Gate

The Pull Request remains Draft and unmerged. Automated success is evidence of source-level consistency only; it is not staging or production acceptance.
