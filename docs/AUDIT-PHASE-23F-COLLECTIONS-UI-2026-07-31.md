# Phase 23F Collections UI Independent Review — 2026-07-31

## Verdict

The corrected Phase 23F repository, service, and read-only REST slice is suitable as a source foundation for an accessible dashboard projection, but the existing dashboard UI is **not acceptable as a Collections interface**. Independent review found sixteen UI-integration defects that must be corrected before the view can be considered source-complete.

**DO NOT MERGE.** This audit authorizes an accessible read-only dashboard slice only. It does not authorize item creation, campaign mutation, updates, reorder, archive, a native resolver, production writes, staging acceptance, or merge.

## Findings

1. `SPDB_Dashboard_Router::normalize_view()` does not accept a `collections` view, so direct navigation is silently redirected to Overview.
2. Dashboard navigation has no Collections and Knowledge entry despite the implemented Phase 23F read service.
3. The dashboard header still displays “Phase 23E — Review and Calendar,” which is factually stale.
4. `SPDB_Dashboard_Page` injects the collections service but never resolves or passes any collection, item, campaign, or knowledge projection to the template.
5. There is no dedicated UI query contract or strict allowlist for collection, item, knowledge, scope, filter, and pagination parameters.
6. No server-side view model distinguishes collection list, collection detail, collection-item detail, knowledge list, and knowledge detail states.
7. No dashboard render path exercises the parent-collection authorization boundary before showing collection items.
8. No collection or knowledge pagination UI exists, and no source proves that pagination links preserve only validated filters.
9. The dashboard does not show repository/read readiness or explain why writes, native resolution, updates, reorder, and archive are unavailable.
10. There are no truthful empty, not-found, forbidden, malformed-query, repository-unavailable, or partial-state UI presentations for Phase 23F.
11. No accessible table captions, result summaries, current-page semantics, landmark labels, or detail definition lists exist for Collections.
12. No responsive card fallback or mobile-safe treatment exists for collection and knowledge tables.
13. No Collections-specific visible-focus, reduced-motion, high-contrast, long-text, or RTL styling exists.
14. The dashboard System Status view does not project Phase 23F repository/read/write readiness.
15. No executable tests cover router acceptance, strict UI query rejection, parent item IDOR prevention, escaped output, pagination, empty/error states, or mutation-control absence.
16. No static architecture control prevents a future Collections template from introducing direct mutation forms, mutation buttons, native write calls, or unsafe destinations.

## Required Corrections

- Add `collections` as an explicit protected dashboard view and navigation item.
- Replace stale Phase 23E labeling with truthful Phase 23F labeling.
- Introduce a bounded `SPDB_Collections_View` query and projection service.
- Resolve all UI data through `SPDB_Collections_Service`; never call the repository directly from a page or template.
- Support collection list/detail/item and knowledge list/detail modes with strict allowlists and bounded pagination.
- Require parent collection authorization before item list/detail projection.
- Render explicit health, write-disabled, empty, error, and unavailable states without fabricated data.
- Use semantic headings, landmarks, table captions, definition lists, pagination labels, focusable overflow regions, and current-page attributes.
- Add responsive and RTL-aware Collections CSS with visible focus and reduced-motion support.
- Add non-sensitive Collections readiness to System Status.
- Add executable UI query/projection tests and static template/architecture controls.
- Keep the entire slice read-only: no mutation form, mutation button, direct database/native call, or REST mutation route.

## Authorized Coding Slice

This review authorizes:

- a protected `collections` dashboard view;
- server-rendered read-only collection and knowledge projections;
- collection list/detail and active item list/detail;
- knowledge list/detail;
- bounded filters and pagination;
- accessible, responsive, RTL-aware presentation;
- tests and architecture controls.

It does not authorize create/update/archive/reorder UI, a concrete native resolver, production writes, or merge.

## Acceptance Rule

After correction and coding, the new source must be independently re-reviewed and the complete PHP 8.0–8.3 exact-head matrix must pass. Any later source or documentation change invalidates earlier evidence.