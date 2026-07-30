# Decision Log

## 2026-07-30 — D-001 — Separate File 23 Plugin

**Decision:** File 23 is a separate plugin, ZIP, and repository.

**Reason:** The dashboard has distinct security, operational, analytics, review-projection, and cross-module coordination responsibilities. Combining it with File 22 would create a monolithic Composer/dashboard system with wider regression and rollback risk.

## 2026-07-30 — D-002 — Federated Dashboard, No Duplicate Backend

**Decision:** File 23 is an operational façade over native providers.

**Consequences:** Publication bodies, drafts, review decisions, schedules, sources, media, comments, corrections, retractions, and raw analytics remain with their native owners.

## 2026-07-30 — D-003 — File Numbering

**Decision:**

- File 21 — Complete Home and News Feed
- File 22 — Universal Post Composer
- File 23 — Doctor and Founder Publishing Dashboard
- File 24 — Complete Public UI, Profile Timeline and Visual Experience

A parent Master Plan amendment remains a project-governance task outside this repository.

## 2026-07-30 — D-004 — Four-Dimensional State Projection

**Decision:** File 23 maps native states into lifecycle, review, visibility, and operational dimensions for display only. Native provider state remains authoritative.

## 2026-07-30 — D-005 — Split Capability from Acceptance

**Old rule:** One adapter maturity list included technical ability and staging/production acceptance, and the provider returned its own maturity.

**New rule:** Providers declare technical capability only. File 23-owned governance separately supplies `unreviewed`, `staging_accepted`, `production_accepted`, or `revoked` acceptance.

**Reason:** A provider must not approve itself for production writes.

**Migration impact:** Adapter Contract 2.0.0 replaces the original 1.0.0 interface.

**Test impact:** Contract tests must prove self-declared acceptance is rejected and environment gates use File 23-controlled acceptance.

## 2026-07-30 — D-006 — Capabilities over Roles

**Decision:** File 23 defines capabilities but does not automatically create editorial WordPress roles. File 00 or an approved administrator assigns capabilities.

## 2026-07-30 — D-007 — Phase 23A Scope

**Decision:** The first phase establishes governance, contracts, bootstrap code, and baseline integrity checks. It does not enable production publishing actions.

## 2026-07-30 — D-008 — Server-Controlled Environment

**Old rule:** A caller supplied an `is_production` Boolean to the registry write gate.

**New rule:** File 23 resolves the environment server-side through WordPress. Request, controller, adapter, URL, or payload values cannot downgrade production to staging.

**Reason:** Prevent environment-confusion authorization bypass.

**Test impact:** CI must prove staging acceptance does not authorize production.

## 2026-07-30 — D-009 — Membership Core Fail-Closed and Restricted-View Contract

**Decision:** Every File 23 capability check requires the canonical compatible File 00 contract. Mutable, editorial, analytics, export, delegation, repair, and institution-wide capabilities require an `approved` or `verified` account. Pending, rejected, expired-document, appeal-review, and suspended accounts may receive only explicitly assigned `spdb_view_dashboard` and `spdb_view_own_content` restricted read-only access.

**Reason:** WordPress capabilities alone do not establish current institutional eligibility, while the governing plan still requires a safe status, appeal, and owned-content read-only path for non-approved doctors.

## 2026-07-30 — D-010 — Guarded Operation Broker

**Decision:** Dashboard controllers must execute provider mutations through the File 23 operation broker. Direct controller calls to adapter mutation methods are prohibited.

**Reason:** The broker enforces registration, environment acceptance, capability, Membership Core status, object type, object version, idempotency, audit reason, reserved authority fields, native authorization, exception isolation, and post-action re-read.

## 2026-07-30 — D-011 — No Merge before Completed Review

**Decision:** No branch, phase, pull request, package, or release may be merged until its review is complete, every discovered defect is corrected, affected tests are rerun on the exact corrected head, and the acceptance record is complete.

**Reason:** Review findings must be resolved before integration; green checks from an earlier head are not evidence for corrected code.

**Test impact:** Repository CI and status documents must retain an explicit review and merge gate.

## 2026-07-30 — D-012 — Stacked Phase Branches without Premature Merge

**Decision:** When continued construction is authorized before a reviewed parent PR is merged, the next phase may start as a stacked branch from the exact reviewed parent head. The parent and child PRs remain Draft and unmerged until their own review and acceptance gates are complete.

**Reason:** This permits orderly progress without violating the permanent no-merge-before-review rule.

## 2026-07-30 — D-013 — Protected Virtual Dashboard Route

**Decision:** Phase 23B provides `/publishing-dashboard/` through a dedicated WordPress rewrite route plus the approved shortcode fallback, rather than creating or overwriting a database page automatically.

**Reason:** The route is deterministic, private, theme-compatible, reversible, upgrade-safe, and does not risk replacing existing WordPress content. A later activation wizard may create a page only after separate review.

## 2026-07-30 — D-014 — Saved Views as Bounded Personal Preferences

**Decision:** Phase 23B stores personal saved views in File 23-owned user meta under a versioned key. Only a narrow non-clinical filter allowlist is accepted, data is revalidated on read, and the limit is 25 views per user.

**Reason:** A custom table is unnecessary at this scale, user meta follows account ownership naturally, and the bounded contract prevents patient or clinical data from entering dashboard preferences.

## 2026-07-30 — D-015 — Phase 23C Runtime Projection, Not Content Import

**Decision:** The federated inventory reads bounded runtime projections through Adapter Contract 2.0.0. It does not import, synchronize, or persist native publication bodies or workflow records in File 23.

**Consequences:** Native providers remain responsible for ownership, privacy, query policy, and authoritative state. File 23 validates the returned projection before rendering it and isolates invalid or failed providers.

**Test impact:** Inventory tests must prove malformed projections are omitted, provider failures degrade gracefully, current-user scope is server-injected, and no duplicate native-domain storage exists.

## 2026-07-30 — D-016 — Phase 23C Inventory Is Read-Only

**Decision:** Phase 23C exposes only inventory list and inspector reads. Native operation keys may be projected for inspection, but no REST mutation route, form, button, or direct adapter execution is exposed.

**Reason:** Review, scheduling, correction, retraction, and other writes require later phase-specific UX, policy, concurrency, audit, provider acceptance, and staging review. Exposing writes early would bypass the approved phased architecture.

**Security impact:** Native destinations must be same-origin and free of credentials, nonces, tokens, signatures, secrets, passwords, authorization fields, expiry fields, and key material. Mismatched native object IDs or undeclared privacy classifications are rejected.
