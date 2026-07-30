# Decision Log

## 2026-07-30 — D-001 — Separate File 23 Plugin

**Decision:** File 23 is a separate plugin, ZIP, and repository.

## 2026-07-30 — D-002 — Federated Dashboard, No Duplicate Backend

**Decision:** File 23 is an operational façade over native providers. Publication bodies, drafts, review decisions, schedules, sources, media, comments, corrections, retractions, and raw analytics remain with native owners.

## 2026-07-30 — D-003 — File Numbering

- File 21 — Complete Home and News Feed
- File 22 — Universal Post Composer
- File 23 — Doctor and Founder Publishing Dashboard
- File 24 — Complete Public UI, Profile Timeline and Visual Experience

## 2026-07-30 — D-004 — Four-Dimensional State Projection

**Decision:** File 23 maps native states into lifecycle, review, visibility, and operational dimensions for display only. Native state remains authoritative.

## 2026-07-30 — D-005 — Split Capability from Acceptance

**Decision:** Providers declare technical capability only. File 23 governance separately controls `unreviewed`, `staging_accepted`, `production_accepted`, and `revoked` acceptance.

## 2026-07-30 — D-006 — Capabilities over Roles

**Decision:** File 23 defines capabilities but creates no editorial roles. File 00 or an approved administrator assigns capabilities.

## 2026-07-30 — D-007 — Phase 23A Scope

**Decision:** Phase 23A establishes governance, contracts, bootstrap code, and integrity checks without enabling production publishing.

## 2026-07-30 — D-008 — Server-Controlled Environment

**Decision:** File 23 resolves environment server-side. Request, controller, adapter, URL, or payload values cannot downgrade production to staging.

## 2026-07-30 — D-009 — Membership Core Fail-Closed and Restricted View

**Decision:** Every capability check requires compatible File 00 state. Non-approved accounts may receive only explicitly assigned restricted read-only dashboard and owned-content viewing.

## 2026-07-30 — D-010 — Guarded Operation Broker

**Decision:** All future provider mutations execute through the File 23 operation broker; direct controller mutation calls are prohibited.

## 2026-07-30 — D-011 — No Merge before Completed Review

**Decision:** No branch, phase, pull request, package, or release may be merged until review, correction, corrective re-review, exact-head QA, and applicable acceptance are complete.

## 2026-07-30 — D-012 — Stacked Phase Branches

**Decision:** Continued work may use stacked branches from exact reviewed heads while parent and child PRs remain Draft and unmerged.

## 2026-07-30 — D-013 — Protected Virtual Dashboard Route

**Decision:** File 23 uses a deterministic protected rewrite route plus an approved shortcode fallback and does not overwrite database pages automatically.

## 2026-07-30 — D-014 — Saved Views as Bounded Preferences

**Decision:** Personal saved views use versioned user meta, a narrow non-clinical allowlist, read-time validation, and a 25-view limit.

## 2026-07-30 — D-015 — Runtime Projection, Not Content Import

**Decision:** Phase 23C reads bounded runtime projections through Adapter Contract 2.0.0 and does not import or synchronize native publication/workflow records.

## 2026-07-30 — D-016 — Phase 23C Inventory Is Read-Only

**Decision:** Phase 23C exposes list and inspector reads only. Operation metadata may be displayed, but no mutation endpoint, form, button, or direct adapter execution is exposed.

## 2026-07-30 — D-017 — Defense-in-Depth Ownership and Filter Enforcement

**Decision:** Native provider authorization remains mandatory, but File 23 also requires a validated `owner_user_id`, independently enforces own versus Founder institution scope, and revalidates every returned item against normalized filters before rendering or inspection.

## 2026-07-30 — D-018 — Bounded Pagination Must Be Truthful

**Decision:** File 23 distinguishes native reported total, accessible total within the 200-item safety window, and validated-window count. Pagination must never imply that results outside the retrieved safety window are reachable.

## 2026-07-30 — D-019 — Strict Runtime URL and Timestamp Contract

**Decision:** Inventory timestamps require absolute RFC 3339. Canonical, thumbnail, edit, preview, and public URLs require exact same origin, no credentials, no fragment, and no secret-bearing or nested-redirect query data.

## 2026-07-30 — D-020 — Role Workspaces Are Native Projections

**Decision:** Founder and Doctor workspaces render validated native cards, policy, profile, knowledge, activity, alerts, and safe destinations. File 23 does not import or become the master owner of those records.

**Reason:** Role-specific operations require a unified command center without creating parallel publication, profile, knowledge, or activity backends.

## 2026-07-30 — D-021 — Official Publishing Is Founder-Only

**Decision:** `official_create` destinations require server-verified Founder identity in addition to current capability, approved account state, safe destination, environment, and File 23-controlled adapter acceptance. A Doctor or trusted Doctor cannot obtain official-publishing authority from a provider label or browser parameter.

## 2026-07-30 — D-022 — Workspace Launch Actions Are Gated Native Destinations

**Decision:** Phase 23D may display a safe native launch destination but does not execute native mutation from the workspace. Mutating destinations are hidden unless all current authority and adapter-acceptance gates pass.

**Reason:** File 22 and the native providers remain responsible for creation and publication execution, while File 23 supplies role-aware orchestration and oversight.

## 2026-07-30 — D-023 — Missing Workspace Data Must Remain Unavailable

**Decision:** Measured cards require numeric native values and absolute source timestamps. Missing profile, knowledge, activity, or count data is shown as unavailable; File 23 does not estimate or fabricate operational numbers.
