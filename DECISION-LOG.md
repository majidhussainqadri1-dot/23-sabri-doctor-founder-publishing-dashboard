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

## 2026-07-30 — D-009 — Membership Core Fail-Closed Contract

**Decision:** Every File 23 capability check requires the canonical File 00 contract and an `approved` or `verified` account. Pending, rejected, suspended, signed-out, or dependency-unavailable states are denied.

**Reason:** WordPress capabilities alone do not establish current institutional eligibility.

## 2026-07-30 — D-010 — Guarded Operation Broker

**Decision:** Dashboard controllers must execute provider mutations through the File 23 operation broker. Direct controller calls to adapter mutation methods are prohibited.

**Reason:** The broker enforces registration, environment acceptance, capability, Membership Core status, object type, object version, idempotency, audit reason, reserved authority fields, native authorization, exception isolation, and post-action re-read.

## 2026-07-30 — D-011 — No Merge before Completed Review

**Decision:** No branch, phase, pull request, package, or release may be merged until its review is complete, every discovered defect is corrected, affected tests are rerun on the exact corrected head, and the acceptance record is complete.

**Reason:** Review findings must be resolved before integration; green checks from an earlier head are not evidence for corrected code.

**Test impact:** Repository CI and status documents must retain an explicit review/merge gate. Draft PR #1 remains unmerged.
