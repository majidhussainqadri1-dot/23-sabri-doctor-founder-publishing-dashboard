# Phase 23C — Independent Source Review and Corrective Audit

Date: 2026-07-30
Scope: Draft PR #3 — `phase/23c-federated-inventory`
Status: Source review completed; defects corrected; exact-head re-test required

## Governing Decision

> **DO NOT MERGE.** Phase 23C remains Draft and unmerged until corrective re-review, exact-current-head automated QA, WordPress staging, real-account/privacy/accessibility/rollback acceptance, and Founder acceptance are complete.

## Review Basis

The review examined all Phase 23C changed files, Adapter Contract 2.0.0 boundaries, the File 00 capability/account-state contract, native ownership rules, read-only REST behavior, inventory query and projection validation, list/inspector authorization, pagination, destinations, responsive UI, tests, CI, and the no-merge rule.

## Findings and Corrections

### F23C-01 — Cross-doctor list and inspector IDOR

**Defect:** Own-content scope depended primarily on the provider honoring `user_id` and `scope`. The inspector fetched a native object directly and did not independently compare its owner with the authenticated user.

**Correction:** Every projection now carries a validated `owner_user_id`. File 23 independently excludes cross-owner list items and returns a non-enumerating unavailable response for cross-owner inspection. Only a server-verified Founder institution scope may include other owners.

### F23C-02 — Provider filter bypass

**Defect:** A provider could return items that did not match lifecycle, review, visibility, operational, language, topic, search, object-type, or date filters, and File 23 rendered them.

**Correction:** File 23 now revalidates every returned projection against the normalized query before rendering it.

### F23C-03 — Duplicate canonical references

**Defect:** The same provider/type/object reference could appear more than once.

**Correction:** Canonical references are deduplicated and a bounded `duplicate_projection` diagnostic is recorded.

### F23C-04 — Provider error-code disclosure

**Defect:** Native provider error codes were reflected into the dashboard envelope and could reveal implementation details.

**Correction:** Provider list failures now use the generic `provider_query_failed` diagnostic; item failures return a non-enumerating unavailable response.

### F23C-05 — Malformed provider totals

**Defect:** Arrays, objects, negative values, or other malformed totals could be silently cast.

**Correction:** Totals now accept only bounded non-negative integers or digit strings. An invalid total rejects that provider response.

### F23C-06 — Internal query authority reflected to clients

**Defect:** The response `query` included server-injected `user_id` and internal `window` fields.

**Correction:** A client-safe query projection exposes only approved display/filter fields.

### F23C-07 — Misleading pagination outside the safety window

**Defect:** `pages` was calculated from the full reported total although File 23 only retrieved a maximum 200-item federated window.

**Correction:** The response distinguishes `total`, `accessible_total`, and `validated_window_count`; pagination is capped to the 200-item window and out-of-range pages fail closed.

### F23C-08 — Filters lost in inspector and pagination links

**Defect:** Moving to another page or opening the inspector discarded the active filter context.

**Correction:** All normalized non-sensitive filters, sort, direction, page size, and effective scope are preserved in generated links.

### F23C-09 — Silent key normalization

**Defect:** Query and projection keys could be lowercased or stripped by `sanitize_key()`, changing caller/provider semantics silently.

**Correction:** Canonical identifiers must already be canonical. Invalid sort, direction, scope, filter, provider, type, state, language, topic, privacy, and operation keys fail closed or are omitted safely.

### F23C-10 — Ambiguous timestamps

**Defect:** `strtotime()` accepted relative or locale-dependent values such as `tomorrow`.

**Correction:** Projection timestamps must be absolute RFC 3339 values and are normalized to UTC.

### F23C-11 — Destination and thumbnail leakage surface

**Defect:** URL fragments, nested redirects, and external thumbnail hosts could escape the earlier destination checks.

**Correction:** Canonical, edit, preview, public, and thumbnail URLs are now strict same-origin values with matching scheme/host/effective port, no embedded credentials, no fragments, no secret-bearing query keys, and no redirect/secret-bearing query values.

### F23C-12 — Malformed operation projection

**Defect:** Nested, noncanonical, or duplicate operation values could be normalized or mishandled.

**Correction:** Only scalar, exact-canonical, registered, capability-authorized, unique operation keys are projected; execution remains unavailable.

### F23C-13 — Mobile information loss

**Defect:** Mobile CSS hid review, visibility, and modified columns, removing material status information.

**Correction:** No semantic status columns are removed. The table uses horizontal scrolling with its accessible region and focus treatment intact.

### F23C-14 — Total wording overstated validated results

**Defect:** UI wording could imply all native reported items had been validated and were pageable.

**Correction:** The interface separately states native reported total and the number validated inside the bounded window.

## Regression Coverage Added

Executable tests now cover:

- cross-doctor list and inspector denial;
- Founder institution scope;
- query filter revalidation;
- duplicate reference suppression;
- malformed and oversized totals;
- client-safe query projection;
- bounded pagination;
- exact canonical query/reference requirements;
- fragment, nested redirect, secret, external thumbnail, and ambiguous timestamp rejection;
- malformed/noncanonical/duplicate operation values;
- generic provider failure codes;
- restricted read-only account behavior.

## Native Ownership Result

No publication body, native draft, review decision, schedule, source record, media binary, comment, correction, retraction, raw analytics event, profile, identity, or notification delivery record was added to File 23 ownership. No inventory mutation route, form, or action button was introduced.

## Corrective Re-review Result

The affected source was re-read after correction. The identified source-level defects are addressed in code and executable tests. This is not WordPress staging acceptance, production acceptance, or merge permission.

## Remaining Gates

- complete exact-current-head PHP 8.0–8.3 workflow and retain artifacts;
- real File 21 and File 22 adapter tests on Hostinger staging;
- real Founder, doctor, pending, suspended, reviewer, and moderator accounts;
- cross-doctor/privacy checks against native records;
- LiteSpeed/hosting cache verification;
- desktop/tablet/mobile, keyboard, screen-reader, zoom, contrast, and RTL acceptance;
- upgrade, backup/restore, rollback, and no-native-data-change evidence;
- Founder review and acceptance.

## Final Audit Decision

**DO NOT MERGE.** Source review and corrective implementation do not replace the remaining exact-head, staging, accessibility, reliability, and Founder acceptance gates.
