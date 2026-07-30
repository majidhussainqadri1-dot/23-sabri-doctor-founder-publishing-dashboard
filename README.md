# Sabri Doctor and Founder Publishing Dashboard

File 23 of the **Sabri Social Homeopathy Platform**.

## Purpose

This plugin provides one private, role-aware, federated publishing command center for the Founder and doctors. It unifies operational views while preserving every native module as the source of truth.

## Governing Principle

> One Federated Publishing Command Center — Role-Specific Workspaces — Native Ownership Preserved — Every Action Executed through an Authorized Versioned Adapter.

## Architectural Boundaries

- File 00 owns identity, verification, roles, and account state.
- File 20 owns the global application shell and global Safe Mode.
- File 21 owns social/news publications, Newsroom review, sources, comments, corrections, retractions, and public feed behavior.
- File 22 owns creation, drafts, autosave, editing, preview, validation, submission, and native adapter orchestration.
- File 23 owns dashboard preferences, saved views, cross-module tasks and collections, bounded aggregate caches, adapter health, and dashboard-specific audit events.
- File 24 owns public profile timelines and visual presentation.

File 23 must not duplicate publication bodies, native drafts, review decisions, schedules, source records, media binaries, comments, corrections, retractions, raw analytics events, profiles, identities, or notification delivery records.

## Security Baseline

Corrective Phase 23A established Adapter Contract `2.0.0`, File 00 fail-closed account checks, independent provider acceptance, server-resolved environment, the guarded native operation broker, version/idempotency controls, and PHP 8.0–8.3 executable tests.

## Current Phase

**Phase 23B — Dashboard Core**

Corrective version `0.2.1` includes:

- protected `/publishing-dashboard/` route;
- directly protected shortcode fallback `[sabri_publishing_dashboard]`;
- activation/upgrade capability reconciliation on existing File 00 roles without creating roles;
- Founder, trusted-doctor, doctor, reviewer, moderator, restricted, denied, and dependency-failure boundaries;
- current-user-bound workspace and saved-view projection;
- private no-store/noindex policy for route, shortcode, and REST responses;
- cache-plugin `DONOTCACHEPAGE` interoperability;
- responsive accessible dashboard shell with unique render IDs and visible focus;
- truthful overview and provider-readiness projection;
- isolated provider registration callbacks;
- non-sensitive system status;
- bounded personal saved views with typed validation and concurrent-update conflict protection;
- exact PR-head PHP 8.0–8.3 CI with retained QA artifacts.

No production publishing action is enabled in Phase 23B.

## Implemented Views

1. **Overview** — current workspace, account state, provider readiness, and safety notices.
2. **Saved Views** — personal non-clinical filters stored under File 23 ownership.
3. **System Status** — capability-protected, non-sensitive environment and adapter diagnostics.

Navigation exposes only implemented destinations. Content inventory, Composer launch, review, calendar, analytics, interactions, media, sources, and reports require later reviewed phases.

## Repository Workflow

- Default branch: `main`
- Phase 23A branch and Draft PR: `phase/23a-governance-contracts`, PR `#1`, unmerged
- Active stacked branch and Draft PR: `phase/23b-dashboard-core`, PR `#2`, unmerged
- **No merge is permitted before review completion, defect correction, corrective re-review, exact-head QA, staging acceptance, and Founder acceptance.**

## Package Target

`23-sabri-doctor-founder-publishing-dashboard-1.0.0.zip`

## Current Development Versions

- Plugin: `0.2.1`
- Adapter Contract: `2.0.0`

## Minimum Environment

- WordPress 6.5+
- PHP 8.0+
- Sabri Membership Core 1.0.1 or a formally accepted compatible 1.x version

## Documentation

- `docs/DASHBOARD-CORE.md`
- `docs/AUDIT-PHASE-23B-2026-07-30.md`
- `docs/AUDIT-PHASE-23B-SECOND-REVIEW-2026-07-30.md`
- `docs/PHASE-23B-REVIEW-GATE.md`
- `docs/ADAPTER-CONTRACT.md`
- `docs/DATA-OWNERSHIP.md`
- `docs/CAPABILITIES.md`
- `docs/STATE-PROJECTION.md`
- `SECURITY.md`

## Status

Phase 23B source review and second corrective implementation are complete. Exact-current-head QA, Hostinger staging, real-account, cache-stack, accessibility, responsive, rollback, and Founder acceptance remain mandatory. The pull requests remain Draft and unmerged.
