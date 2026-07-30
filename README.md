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

**Phase 23D — Corrected Founder and Doctor Role Workspaces**

Development version `0.4.1` provides:

- File 00 re-derived Founder, trusted-doctor, Doctor, restricted, and read-only authority;
- optional native workspace projections over Adapter Contract `2.0.0`;
- truthful bounded cards with mandatory RFC 3339 source timestamps;
- Founder official-publishing and Doctor reviewed-publishing policies;
- a File 23-owned semantic contract for each action type;
- provider-declared capability, current account, ownership, Founder, destination, environment, and acceptance gates;
- profile and knowledge destinations routed through the same centralized action gate;
- native profile completion, verification, eligibility, knowledge portfolio, and successful-case aggregates;
- global safety limits and duplicate launch-action suppression;
- strict exact-origin HTTP(S) destinations, raw-query validation, and recursive encoded-target rejection;
- provider failure isolation and truthful unavailable or truncated states;
- responsive, accessible, and localized workspace UI;
- expanded corrective Phase 23D tests and architecture guards.

The workspace view does not execute native publication mutations. Mutating native launch destinations are hidden unless every semantic, identity, capability, ownership, environment, and adapter-acceptance gate passes.

## Implemented Views

1. **Overview** — workspace, account state, provider readiness, and safety notices.
2. **Founder/Doctor Workspace** — role policy, native cards, gated actions, profile, knowledge, and activity projections.
3. **Content Inventory** — validated native projections and read-only inspector.
4. **Saved Views** — bounded non-clinical personal filters.
5. **System Status** — capability-protected, non-sensitive diagnostics.

## Repository Workflow

- Default branch: `main`
- Phase 23A Draft PR #1: unmerged
- Phase 23B Draft PR #2: unmerged
- Phase 23C Draft PR #3: reviewed, corrected, QA-green, unmerged
- Phase 23D Draft PR #4: source-reviewed, corrected, corrective regression matrix green, unmerged
- Active stacked branch: `phase/23d-role-workspaces`
- **No merge before completed review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance.**

## Versions

- Plugin: `0.4.1`
- Adapter Contract: `2.0.0`
- WordPress: 6.5+
- PHP: 8.0+
- Sabri Membership Core: 1.0.1 or formally accepted compatible 1.x

## Phase 23D Documentation

- `docs/AUDIT-PHASE-23D-2026-07-30.md`
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

Phase 23D independent source review, correction, corrective source re-review, and the complete corrective PHP 8.0–8.3 regression matrix are complete. One final documentation-inclusive exact-head evidence run, real native adapters, Hostinger staging, real-account/privacy/accessibility/rollback testing, and Founder acceptance remain mandatory. All pull requests remain Draft and unmerged.
