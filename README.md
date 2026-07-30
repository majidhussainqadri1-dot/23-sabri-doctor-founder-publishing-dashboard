# Sabri Doctor and Founder Publishing Dashboard

File 23 of the **Sabri Social Homeopathy Platform**.

## Purpose

This plugin provides one private, role-aware, federated publishing command center for the Founder and verified doctors. It unifies operational views of content, reviews, schedules, interactions, analytics summaries, cross-module collections, and system health while preserving each native module as the source of truth.

## Governing Principle

> One Federated Publishing Command Center — Role-Specific Workspaces — Native Ownership Preserved — Every Action Executed through an Authorized Versioned Adapter.

## Architectural Boundaries

- File 00 owns identity, verification, roles, and capabilities.
- File 20 owns the global application shell and global Safe Mode.
- File 21 owns social/news publications, Newsroom review, sources, comments, corrections, retractions, and public feed behavior.
- File 22 owns creation, draft persistence, autosave, editing, preview, validation, submission, and native adapter orchestration.
- File 23 owns dashboard preferences, saved views, cross-module tasks, cross-module collections/campaign metadata, bounded aggregate caches, adapter health, and dashboard-specific audit events.
- File 24 owns public profile timelines and visual presentation.

File 23 must not duplicate publication bodies, native drafts, review decisions, native schedules, source records, media binaries, comments, corrections, retractions, raw analytics events, profiles, identities, or notification delivery records.

## Security Baseline

Corrective Phase 23A established:

- Adapter Contract `2.0.0`;
- technical capability separated from File 23-controlled acceptance;
- server-resolved WordPress environment;
- compatible File 00 dependency and account-state checks;
- restricted read-only routes for explicitly authorized non-approved accounts;
- guarded native operation broker;
- object-version and idempotency controls;
- adapter exception isolation;
- exact native-object re-read after successful mutation;
- executable contract and architecture tests on PHP 8.0–8.3.

## Current Phase

**Phase 23B — Dashboard Core**

Version `0.2.0` adds:

- protected `/publishing-dashboard/` route;
- shortcode fallback `[sabri_publishing_dashboard]`;
- Founder, trusted-doctor, doctor, restricted, denied, and dependency-failure workspace resolution;
- current-user-bound authority;
- private no-store and noindex headers;
- responsive accessible dashboard shell;
- truthful overview and provider-readiness projection;
- non-sensitive system-status view;
- bounded personal saved views with REST permission and validation controls;
- dashboard-core executable tests and JavaScript syntax checks.

No production publishing action is enabled in Phase 23B.

## Implemented Dashboard Views

1. **Overview** — current workspace, account state, provider readiness, and safety notices.
2. **Saved Views** — personal non-clinical filters stored under File 23 ownership.
3. **System Status** — capability-protected, non-sensitive environment and adapter diagnostics.

The navigation exposes only implemented destinations. Later content, review, calendar, analytics, interaction, media, source, and report views require separate reviewed phases.

## Repository Workflow

- Default branch: `main`
- Phase 23A branch: `phase/23a-governance-contracts`
- Phase 23A Draft PR: `#1`, technically reviewed, unmerged
- Active stacked branch: `phase/23b-dashboard-core`
- Changes are promoted only through completed review and accepted pull requests.
- **No merge is permitted before review completion, defect correction, corrective re-review, exact-head QA, staging acceptance, and Founder acceptance.**

## Package Target

`23-sabri-doctor-founder-publishing-dashboard-1.0.0.zip`

## Current Development Versions

- Plugin: `0.2.0`
- Adapter Contract: `2.0.0`

## Minimum Environment

- WordPress 6.5+
- PHP 8.0+
- Sabri Membership Core 1.0.1 or a formally accepted compatible 1.x version for dashboard access

## Documentation

- `docs/DASHBOARD-CORE.md`
- `docs/PHASE-23B-REVIEW-GATE.md`
- `docs/ADAPTER-CONTRACT.md`
- `docs/DATA-OWNERSHIP.md`
- `docs/CAPABILITIES.md`
- `docs/STATE-PROJECTION.md`
- `SECURITY.md`

## Status

Phase 23B construction has started as a stacked branch so progress can continue without prematurely merging PR #1. The current implementation is not staging-accepted, production-ready, or merge-ready.
