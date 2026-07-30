# Sabri Doctor and Founder Publishing Dashboard

File 23 of the **Sabri Social Homeopathy Platform**.

## Governing Principle

> One Federated Publishing Command Center — Role-Specific Workspaces — Native Ownership Preserved — Every Action Executed through an Authorized Versioned Adapter.

## Architectural Boundaries

- File 00 owns identity, verification, roles, capabilities, and account state.
- File 03 owns Founder and Doctor profile records.
- File 20 owns the global application shell and global Safe Mode.
- File 21 owns social/news publications, Newsroom review, sources, comments, corrections, retractions, and public feed behavior.
- File 22 owns creation, drafts, autosave, editing, preview, validation, submission, and native adapter orchestration.
- File 23 owns operational projections, dashboard preferences, saved views, later cross-module metadata, bounded aggregate caches, adapter health, and dashboard audit events.
- File 24 owns public profile timelines and visual presentation.

File 23 does not own publication bodies, native drafts, profile identities, knowledge records, review decisions, schedules, source records, media binaries, comments, corrections, retractions, raw analytics events, or notification delivery records.

## Current Phase

**Phase 23D — Founder and Doctor Role Workspaces**

Development version `0.4.0` adds an initial stacked candidate for:

- Founder, trusted-doctor, Doctor, and restricted publishing workspaces;
- server-derived role context and scope;
- optional native workspace projections over Adapter Contract `2.0.0`;
- truthful measured cards with source timestamps;
- Founder official-publishing and Doctor reviewed-publishing policies;
- capability-, account-, owner-, Founder-, destination-, environment-, and acceptance-gated native launch destinations;
- native profile completion, verification, eligibility, edit, and public destinations;
- native knowledge portfolio and successful-case aggregate projections;
- bounded native activity and alerts;
- strict same-origin non-secret destinations;
- provider failure isolation and explicit unavailable states;
- responsive and accessible workspace UI;
- executable Phase 23D tests.

The workspace view does not execute native mutations. Mutating native launch destinations are hidden unless every current authority and adapter-acceptance gate passes.

## Implemented Views

1. **Overview** — workspace, account state, provider readiness, and safety notices.
2. **Founder/Doctor Workspace** — role policy, native cards, safe actions, profile, knowledge, and activity projections.
3. **Content Inventory** — validated native projections and read-only inspector.
4. **Saved Views** — bounded non-clinical personal filters.
5. **System Status** — capability-protected, non-sensitive diagnostics.

## Repository Workflow

- Default branch: `main`
- Phase 23A Draft PR #1: unmerged
- Phase 23B Draft PR #2: unmerged
- Phase 23C Draft PR #3: reviewed, corrected, QA-green, unmerged
- Active stacked branch: `phase/23d-role-workspaces`
- **No merge before completed review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance.**

## Versions

- Plugin: `0.4.0`
- Adapter Contract: `2.0.0`
- WordPress: 6.5+
- PHP: 8.0+
- Sabri Membership Core: 1.0.1 or formally accepted compatible 1.x

## Phase 23D Documentation

- `docs/ROLE-WORKSPACES.md`
- `docs/PHASE-23D-REVIEW-GATE.md`
- `tests/workspace-tests.php`

## Previous Review Records

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

Phase 23D initial coding is in progress. Independent source review, defect correction, corrective re-review, exact-head QA, real native adapters, Hostinger staging, real-account/privacy/accessibility/rollback testing, and Founder acceptance remain mandatory. All pull requests remain Draft and unmerged.
