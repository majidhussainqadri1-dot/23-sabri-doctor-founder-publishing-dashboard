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

## 2026-07-30 — D-005 — Adapter Maturity Gates

**Decision:** Production writes are permitted only through Production-Accepted adapters. Adapter activation alone does not imply compatibility or production readiness.

## 2026-07-30 — D-006 — Capabilities over Roles

**Decision:** File 23 defines capabilities but does not automatically create editorial WordPress roles. File 00 or an approved administrator assigns capabilities.

## 2026-07-30 — D-007 — Phase 23A Scope

**Decision:** The first phase establishes governance, contracts, bootstrap code, and baseline integrity checks. It does not enable production publishing actions.
