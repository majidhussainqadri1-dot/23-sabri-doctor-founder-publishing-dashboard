# Phase 23B Corrective Source Audit — 30 July 2026

## Audit Status

- Scope: private dashboard core and personal saved views
- Branch: `phase/23b-dashboard-core`
- Parent: reviewed but unmerged `phase/23a-governance-contracts`
- Merge status: **DO NOT MERGE**
- Staging acceptance: not established
- Production acceptance: not established

## Reviewed Scope

The review covered:

- virtual route registration, activation, upgrade, dispatch, and private headers;
- shortcode fallback behavior;
- File 00 account-state and capability checks;
- Founder, trusted-doctor, doctor, restricted, denied, and dependency-failure workspace resolution;
- overview and system-state projections;
- provider-readiness output;
- saved-view REST routes, permissions, storage, validation, display, and JavaScript behavior;
- responsive and accessibility baseline;
- PHP 8.0–8.3 compatibility;
- JavaScript syntax;
- native-data ownership boundaries;
- documentation and merge controls.

## Defects Found and Corrected

### B-001 — Early private-header route detection

**Defect:** `send_headers` could run before the final WordPress query object exposed the dashboard query variable.

**Correction:** Route detection now checks the early `$wp->query_vars` state and the final query state.

### B-002 — Shortcode page privacy gap

**Defect:** A normal WordPress page containing the dashboard shortcode did not automatically receive the same no-store and noindex policy as the virtual route.

**Correction:** The shortcode container page is detected during `template_redirect` and receives the shared private response policy before output.

### B-003 — Shortcode assets enqueued too late

**Defect:** Enqueueing styles only while the shortcode rendered could occur after `wp_head`, leaving the dashboard unstyled.

**Correction:** Authorized shortcode pages enqueue assets during `template_redirect`, before theme header output.

### B-004 — Restricted workspace mutation

**Defect:** A pending or suspended account with restricted dashboard access could pass the saved-view create/delete permission callback.

**Correction:** Saved-view read and write permissions are separate. Restricted accounts may list existing views but cannot create or delete them.

### B-005 — Read-time preference validation gap

**Defect:** Values already present in user meta were projected without passing the full incoming validation boundary.

**Correction:** Every saved view is revalidated on read; invalid IDs, labels, fields, and values are discarded.

### B-006 — Failed deletion persistence not reported

**Defect:** A failed user-meta update during deletion could still return success.

**Correction:** Delete persistence failure now returns a bounded server error.

### B-007 — Empty-list JavaScript dead start

**Defect:** When no saved views existed, the template emitted no list element; JavaScript therefore could not create the first view.

**Correction:** JavaScript creates the list container when needed and manages empty-state transitions after create/delete.

### B-008 — Cross-user status projection

**Defect:** Workspace resolution read membership status before rejecting an attempt to resolve another user ID.

**Correction:** Resolution is bound to the current authenticated user before File 00 status is read.

### B-009 — Untyped saved-view filter values

**Defect:** Allowed filter names accepted overly broad free-text values, permitting malformed values or private URLs to enter preferences.

**Correction:** Filters now use typed validation: canonical keys, strict sort/direction allowlists, valid ISO dates, bounded arrays, and sensitive contact/URL detection.

### B-010 — Date values misclassified as phone numbers

**Defect:** The sensitive-number detector classified valid ISO dates as possible phone numbers.

**Correction:** Date fields are parsed and validated before the generic sensitive-number check.

### B-011 — Saved-view REST cache policy

**Defect:** Private File 23 REST responses did not have an explicit module-level no-store policy.

**Correction:** All `/spdb/v1` responses now receive private no-store, noindex, noarchive, and nosniff headers.

### B-012 — Existing active installation route upgrade

**Defect:** A plugin already active before the route was introduced would not run the activation hook and could retain stale rewrite rules.

**Correction:** A versioned one-time rewrite flush runs after an upgrade.

### B-013 — Asset registration ordering

**Defect:** Conditional enqueue could run before handles had been registered.

**Correction:** The enqueue method now idempotently registers assets before enqueueing them.

### B-014 — Insufficient dashboard-core regression tests

**Defect:** Initial tests did not cover cross-user denial, early route detection, restricted saved-view mutation, typed filter privacy, invalid dates, or REST privacy scope.

**Correction:** Executable tests now cover each of these conditions, and the CI matrix runs them on PHP 8.0, 8.1, 8.2, and 8.3.

## Corrective Re-review Result

The corrected source was re-read against the Phase 23B scope. The current implementation:

- exposes only implemented navigation destinations;
- does not fabricate content or analytics counts;
- does not enable native publishing mutations;
- does not create a duplicate publication, Composer, Newsroom, media, source, interaction, correction, retraction, or analytics backend;
- keeps restricted account workspaces read-only;
- keeps File 23 REST and dashboard responses private;
- preserves File 00 as the authority for identity, status, and capabilities.

## Remaining Acceptance Work

Source review and automated checks do not establish WordPress staging acceptance. Before merge consideration, the following remain mandatory:

- activation and upgrade test on Hostinger staging;
- virtual route and shortcode route test;
- response-header verification through the actual cache stack;
- real Founder, verified doctor, pending doctor, and suspended doctor tests;
- responsive verification at the approved width matrix;
- keyboard and screen-reader acceptance;
- theme and File 20 shell integration review;
- Founder review and explicit acceptance.

## Final Audit Rule

A green workflow is evidence only for the exact tested head. Any later source commit requires affected-scope re-review and a complete exact-head rerun. This audit is not permission to merge.
