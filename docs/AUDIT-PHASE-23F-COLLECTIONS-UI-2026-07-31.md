# Phase 23F Collections UI Independent Review — 2026-07-31

## Verdict

The corrected Phase 23F repository, service, and read-only REST slice was suitable as a source foundation, but the first Collections UI candidate was **not acceptable**. Independent review found sixteen principal UI-integration defects. Corrective re-review then found five additional defects in actual integration, CI coverage, malformed routing, scope-aware ergonomics, and diagnostics.

**DO NOT MERGE.** The corrected UI remains a Draft source candidate. This audit does not authorize item creation, campaign mutation, updates, reorder, archive, a native resolver, production writes, WordPress staging acceptance, Founder acceptance, or merge.

## Principal Findings

1. The protected router did not accept a `collections` view.
2. Dashboard navigation had no Collections and Knowledge entry.
3. The dashboard header still displayed the stale Phase 23E label.
4. The dashboard page injected the collections service but never resolved a projection.
5. No strict UI query allowlist existed.
6. No view model distinguished collection list/detail/item and knowledge list/detail.
7. The render path did not exercise parent collection authorization before item reads.
8. No bounded collection or knowledge pagination UI existed.
9. The dashboard did not explain repository readiness or the absence of mutation controls.
10. Truthful empty, malformed, forbidden, not-found, and unavailable UI states were absent.
11. Semantic captions, result summaries, landmarks, current-page attributes, and definition lists were absent.
12. No mobile-safe table treatment existed.
13. Collections-specific focus, reduced-motion, high-contrast, long-text, and RTL treatment was absent.
14. System Status did not display Phase 23F readiness.
15. No executable UI query, projection, IDOR, or mutation-absence tests existed.
16. No static source gate prevented future direct mutation controls in the Collections template.

## Corrective Re-review Findings

17. New UI files existed on the branch, but the dashboard renderer and main template still did not use them.
18. The baseline workflow remained green because it did not execute the new UI suite; a dedicated exact-head matrix was required.
19. An array-valued `view` query could reach `sanitize_key()` and produce a warning instead of failing closed.
20. Institution scope appeared to ordinary doctors even though the server correctly rejected it; the UI had to reflect Founder-only authority.
21. Back links did not preserve authorized scope, hard-coded a left-pointing symbol that was wrong in RTL, and System Status omitted the already-available readiness projection.

## Corrections Implemented

- Added the protected `collections` route and approved-account navigation entry.
- Added `SPDB_Collections_View`, with strict query allowlists and list/detail/item/knowledge modes.
- Routed every projection through `SPDB_Collections_Service`; templates never call the repository.
- Required parent collection authorization before item list or detail projection.
- Integrated Collections into `SPDB_Dashboard_Page` and `templates/dashboard.php`.
- Added conditional Collections CSS loading and truthful Phase 23F labeling.
- Added bounded GET filters, pagination, captions, definition lists, focusable overflow regions, empty/error states, and readiness notices.
- Added responsive, visible-focus, RTL, reduced-motion, and forced-color CSS.
- Added non-sensitive collection read/write readiness to System Status.
- Added Founder-only institution scope rendering and scope-preserving back links.
- Removed direction-specific back symbols and free-text status filtering.
- Made malformed array-valued view selectors fail closed to Overview.
- Added executable UI boundary, corrective static, malformed-route, IDOR, navigation, and no-mutation tests.
- Added a dedicated exact-head PHP 8.0–8.3 Collections UI workflow with artifacts and checksums.

## Retained Restrictions

- No POST, PUT, PATCH, or DELETE Collections UI path.
- No create, update, archive, reorder, publish, approve, or native-write control.
- No persisted or rendered native destination.
- No production write enablement.
- No concrete native resolver.
- No merge before exact-head automated QA, WordPress staging, privacy/cache/accessibility/rollback evidence, Founder acceptance, and explicit authorization.

## Acceptance Rule

The documentation-inclusive final source head must pass both the complete Baseline Integrity matrix and the dedicated Collections UI matrix on PHP 8.0, 8.1, 8.2, and 8.3. Any later source or documentation change invalidates that evidence and requires both matrices to run again.
