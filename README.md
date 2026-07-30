# Sabri Doctor and Founder Publishing Dashboard

File 23 of the **Sabri Social Homeopathy Platform**.

## Governing Principle

> One Federated Publishing Command Center — Role-Specific Workspaces — Native Ownership Preserved — Every Action Executed through an Authorized Versioned Adapter.

## Architectural Boundaries

- File 00 owns identity, verification, roles, capabilities, and account state.
- File 20 owns the global application shell and global Safe Mode.
- File 21 owns social/news publications, Newsroom review, sources, comments, corrections, retractions, and public feed behavior.
- File 22 owns creation, drafts, autosave, editing, preview, validation, submission, and native adapter orchestration.
- File 23 owns operational projections, dashboard preferences, saved views, later cross-module metadata, bounded aggregate caches, adapter health, and dashboard audit events.
- File 24 owns public profile timelines and visual presentation.

File 23 does not own publication bodies, native drafts, review decisions, schedules, source records, media binaries, comments, corrections, retractions, raw analytics events, profiles, identities, or notification delivery records.

## Current Phase

**Phase 23C — Corrective Federated Content Inventory**

Development version `0.3.1` provides:

- read-only native provider inventory projections;
- current-user-bound own-content scope enforced again at the File 23 boundary;
- server-verified Founder-only institution scope;
- bounded search, filters, sorting, and a 200-item accessible window;
- File 23 revalidation of object type, owner, filters, totals, duplicates, privacy, status, timestamp, and destination contracts;
- separate reported total, accessible total, and validated-window counts;
- read-only item inspector with non-enumerating cross-owner denial;
- same-origin, fragment-free, non-secret canonical/edit/preview/public/thumbnail URLs;
- operation metadata for inspection only with execution disabled;
- responsive table retaining lifecycle, review, visibility, and modified information on mobile;
- expanded IDOR, filter-integrity, pagination, URL, timestamp, and failure-isolation tests.

No production publishing action is enabled in Phase 23C.

## Implemented Views

1. **Overview** — workspace, account state, provider readiness, and safety notices.
2. **Content Inventory** — validated native projections and read-only inspector.
3. **Saved Views** — bounded non-clinical personal filters.
4. **System Status** — capability-protected, non-sensitive diagnostics.

## Repository Workflow

- Default branch: `main`
- Phase 23A Draft PR #1: unmerged
- Phase 23B Draft PR #2: unmerged
- Phase 23C Draft PR #3: unmerged
- Active branch: `phase/23c-federated-inventory`
- **No merge before completed review, correction, corrective re-review, exact-head QA, staging acceptance, and Founder acceptance.**

## Versions

- Plugin: `0.3.1`
- Adapter Contract: `2.0.0`
- WordPress: 6.5+
- PHP: 8.0+
- Sabri Membership Core: 1.0.1 or formally accepted compatible 1.x

## Review Records

- `docs/AUDIT-PHASE-23C-2026-07-30.md`
- `docs/FEDERATED-INVENTORY.md`
- `docs/PHASE-23C-REVIEW-GATE.md`
- `docs/AUDIT-PHASE-23B-SECOND-REVIEW-2026-07-30.md`
- `docs/ADAPTER-CONTRACT.md`
- `docs/DATA-OWNERSHIP.md`
- `docs/CAPABILITIES.md`
- `docs/STATE-PROJECTION.md`
- `SECURITY.md`

## Status

Phase 23C source review and corrective implementation are complete. Corrected exact-head QA, real File 21/File 22 adapters, Hostinger staging, real-account/privacy/accessibility/rollback checks, and Founder acceptance remain mandatory. All pull requests remain Draft and unmerged.
