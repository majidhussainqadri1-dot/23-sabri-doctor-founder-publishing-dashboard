# Phase 23C — Independent Source Review and Corrective Audit

Date: 2026-07-30
Scope: Draft PR #3 — `phase/23c-federated-inventory`
Status: Source review completed; defects corrected; exact-head re-test required

## Governing Decision

> **DO NOT MERGE.** Phase 23C remains Draft and unmerged until corrective re-review, exact-current-head automated QA, WordPress staging, real-account/privacy/accessibility/rollback acceptance, and Founder acceptance are complete.

## Review Basis

The review examined all Phase 23C changed files, Adapter Contract 2.0.0 boundaries, File 00 capability/account-state integration, native ownership, read-only REST behavior, inventory query/projection validation, list/inspector authorization, pagination, destinations, responsive UI, tests, CI, and the no-merge rule.

**Fourteen defects were identified and corrected.**

## Findings and Corrections

### F23C-01 — Cross-doctor list and inspector IDOR

**Defect:** Own-content scope depended primarily on the provider honoring `user_id` and `scope`; the inspector did not independently compare owner with the authenticated user.

**Correction:** Every projection carries validated `owner_user_id`. File 23 excludes cross-owner list items and returns a non-enumerating unavailable response for cross-owner inspection. Only a server-verified Founder institution scope may include other owners.

### F23C-02 — Provider filter bypass

**Defect:** A provider could return items that did not match normalized filters.

**Correction:** File 23 revalidates every returned projection against object type, lifecycle, review, visibility, operational, language, topic, search, and date filters.

### F23C-03 — Duplicate canonical references

**Defect:** The same provider/type/object reference could appear more than once.

**Correction:** Canonical references are deduplicated and a bounded diagnostic is recorded.

### F23C-04 — Provider error-code disclosure

**Defect:** Native provider error codes were reflected into the dashboard envelope.

**Correction:** List failures use generic `provider_query_failed`; item failures return a non-enumerating unavailable response.

### F23C-05 — Malformed provider totals

**Defect:** Arrays, objects, negatives, and malformed totals could be silently cast.

**Correction:** Totals accept only bounded non-negative integers or digit strings; invalid totals reject that provider response.

### F23C-06 — Internal query authority reflected to clients

**Defect:** Response query data included server-injected `user_id` and internal `window`.

**Correction:** A client-safe projection exposes only approved display/filter fields.

### F23C-07 — Misleading pagination outside the safety window

**Defect:** Pages were calculated from the full reported total despite a 200-item retrieval window.

**Correction:** `total`, `accessible_total`, and `validated_window_count` are separated; pagination is capped to the 200-item window.

### F23C-08 — Filters lost in inspector and pagination links

**Defect:** Paging or inspecting discarded active filters.

**Correction:** Normalized non-sensitive filters, sort, direction, page size, and effective scope are preserved.

### F23C-09 — Silent key normalization

**Defect:** Query and projection keys could be lowercased or stripped silently.

**Correction:** Identifiers must already be canonical; invalid sort, direction, scope, filter, provider, type, state, language, topic, privacy, and operation keys fail closed or are omitted safely.

### F23C-10 — Ambiguous timestamps

**Defect:** `strtotime()` accepted relative or locale-dependent values.

**Correction:** Projection timestamps require absolute RFC 3339 and normalize to UTC.

### F23C-11 — Destination and thumbnail leakage surface

**Defect:** URL fragments, nested redirects, and external thumbnail hosts escaped earlier checks.

**Correction:** Canonical, edit, preview, public, and thumbnail URLs require exact same origin, no credentials, no fragments, no secret keys, and no redirect/secret-bearing values.

### F23C-12 — Malformed operation projection

**Defect:** Nested, noncanonical, or duplicate operation values could be mishandled.

**Correction:** Only scalar, exact-canonical, registered, capability-authorized, unique operation keys are projected; execution remains unavailable.

### F23C-13 — Mobile information loss

**Defect:** Mobile CSS hid review, visibility, and modified columns.

**Correction:** No material status column is removed; the accessible table remains horizontally scrollable.

### F23C-14 — Total wording overstated validated results

**Defect:** UI wording implied all reported items were validated and pageable.

**Correction:** The interface separately states native reported total and validated bounded-window count.

## Regression Coverage Added

Tests cover cross-doctor denial, Founder scope, filter revalidation, duplicate suppression, malformed/oversized totals, client-safe query projection, bounded pagination, canonical requirements, unsafe URLs, ambiguous timestamps, malformed operations, generic failures, and restricted read-only accounts.

## Native Ownership Result

No publication body, native draft, review decision, schedule, source, media, comment, correction, retraction, raw analytics event, profile, identity, or notification delivery record was added to File 23 ownership. No inventory mutation route, form, or action button was introduced.

## Corrective Re-review Result

The affected source was re-read after correction. The identified source-level defects are addressed in code and executable tests. This is not staging acceptance, production acceptance, or merge permission.

## Remaining Gates

- exact-current-head PHP 8.0–8.3 workflow and artifacts;
- real File 21/File 22 adapters on Hostinger staging;
- real Founder, doctor, pending, suspended, reviewer, and moderator accounts;
- native-record cross-doctor/privacy verification;
- LiteSpeed/hosting cache verification;
- accessibility, responsive, RTL, upgrade, backup/restore, and rollback acceptance;
- Founder review and acceptance.

## Final Audit Decision

**DO NOT MERGE.** Source review and corrective implementation do not replace exact-head, staging, accessibility, reliability, and Founder acceptance gates.
