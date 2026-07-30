# Sabri Doctor and Founder Publishing Dashboard

File 23 of the **Sabri Social Homeopathy Platform**.

## Purpose

This plugin provides one private, role-aware, federated publishing command center for the Founder and doctors. It unifies operational views while preserving every native module as the source of truth.

## Governing Principle

> One Federated Publishing Command Center — Role-Specific Workspaces — Native Ownership Preserved — Every Action Executed through an Authorized Versioned Adapter.

## Architectural Boundaries

- File 00 owns identity, verification, roles, capabilities, and account state.
- File 20 owns the global application shell and global Safe Mode.
- File 21 owns social/news publications, Newsroom review, sources, comments, corrections, retractions, and public feed behavior.
- File 22 owns creation, drafts, autosave, editing, preview, validation, submission, and native adapter orchestration.
- File 23 owns operational projections, dashboard preferences, saved views, later cross-module tasks/collections, bounded aggregate caches, adapter health, and dashboard-specific audit events.
- File 24 owns public profile timelines and visual presentation.

File 23 must not duplicate publication bodies, native drafts, review decisions, schedules, source records, media binaries, comments, corrections, retractions, raw analytics events, profiles, identities, or notification delivery records.

## Security Baseline

Corrective Phase 23A established Adapter Contract `2.0.0`, File 00 fail-closed account checks, independent provider acceptance, server-resolved environment, the guarded native operation broker, version/idempotency controls, and PHP 8.0–8.3 executable tests.

Twice-reviewed Phase 23B established the protected private dashboard, real File 00 role-capability reconciliation, private route/REST cache controls, saved-view conflict protection, provider registration isolation, and exact-head evidence artifacts.

## Current Phase

**Phase 23C — Federated Content Inventory**

Development version `0.3.0` adds:

- read-only native provider inventory projections;
- bounded search, provider/type/state filters, sort, and pagination window;
- current-user-bound own-content scope;
- Founder-only institution-wide scope;
- canonical lifecycle, review, visibility, and operational state projections;
- explicit `unknown` state and mapping warnings;
- item inspector with native ID/version/privacy/status information;
- safe same-origin native edit, preview, and public destinations;
- rejection of token-, nonce-, signature-, secret-, credential-, cross-origin-, and mismatched-object destinations;
- provider exception isolation and bounded partial-result diagnostics;
- read-only REST inventory endpoints;
- responsive and accessible server-rendered inventory UI;
- operation metadata projection for inspection only, with mutation execution disabled.

No production publishing action is enabled in Phase 23C.

## Implemented Views

1. **Overview** — current workspace, account state, provider readiness, and safety notices.
2. **Content Inventory** — validated native provider projections and read-only item inspector.
3. **Saved Views** — personal non-clinical filters stored under File 23 ownership.
4. **System Status** — capability-protected, non-sensitive environment and adapter diagnostics.

Navigation exposes only implemented and authorized destinations. Composer ownership, review decisions, calendar mutation, analytics, interactions, media/source management, and reports require later reviewed phases.

## Repository Workflow

- Default branch: `main`
- Phase 23A branch and Draft PR: `phase/23a-governance-contracts`, PR `#1`, unmerged
- Phase 23B stacked branch and Draft PR: `phase/23b-dashboard-core`, PR `#2`, unmerged
- Active Phase 23C stacked branch: `phase/23c-federated-inventory`
- **No merge is permitted before review completion, defect correction, corrective re-review, exact-head QA, staging acceptance, and Founder acceptance.**

## Package Target

`23-sabri-doctor-founder-publishing-dashboard-1.0.0.zip`

## Current Development Versions

- Plugin: `0.3.0`
- Adapter Contract: `2.0.0`

## Minimum Environment

- WordPress 6.5+
- PHP 8.0+
- Sabri Membership Core 1.0.1 or a formally accepted compatible 1.x version

## Documentation

- `docs/FEDERATED-INVENTORY.md`
- `docs/PHASE-23C-REVIEW-GATE.md`
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

Phase 23C implementation has started as a stacked, unreviewed candidate. Its independent review, defect correction, corrective re-review, exact-head QA, real provider integration, Hostinger staging, accessibility, responsive, rollback, and Founder acceptance remain mandatory. All pull requests remain Draft and unmerged.
