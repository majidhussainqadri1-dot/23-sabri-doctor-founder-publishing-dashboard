# Changelog

All notable changes to File 23 are documented here.

## [Unreleased]

Phase 23D remains Draft, unmerged, not staging-accepted, and not production-ready.

## [0.4.0] — Phase 23D Initial Role-Workspace Candidate

### Added

- Optional `SPDB_Workspace_Provider_Adapter` projection interface layered over Adapter Contract 2.0.0.
- Server-derived Founder, trusted-doctor, Doctor, and restricted workspace context.
- Truthful measured cards with numeric values and absolute source timestamps.
- Founder official-publishing and Doctor reviewed-publishing policy projections.
- Capability-, account-state-, ownership-, Founder-policy-, destination-, environment-, and adapter-acceptance-gated native launch destinations.
- Native profile completion, verification, eligibility, edit, and public-profile projections.
- Native knowledge portfolio, unlinked-content, and successful-case aggregate projections.
- Bounded recent activity and operational alerts.
- Strict same-origin, matching-port, fragment-free, non-secret destination validation.
- Responsive and accessible role-workspace UI.
- Executable role-workspace authorization, privacy, destination, failure-isolation, and truthful-empty-state tests.
- `docs/ROLE-WORKSPACES.md` and `docs/PHASE-23D-REVIEW-GATE.md`.

### Security

- Browser-supplied user, role, Founder flag, scope, owner, status, capability, and environment are non-authoritative.
- Doctor own-scope projections require the current canonical user ID.
- Institution scope requires the server-verified Founder.
- Official creation is Founder-only.
- Restricted accounts receive no mutating native launch action.
- Mutating destinations remain hidden unless File 23-controlled environment acceptance is satisfied.
- Patient-identifying and contact data are rejected from workspace text projections.
- Provider exceptions are isolated and reduced to generic diagnostics.

### Architecture

- The workspace view renders validated projections and safe native destinations only.
- It does not call `execute_operation()` or own publication, profile, knowledge, review, schedule, source, media, interaction, correction, retraction, or analytics records.

## [0.3.1] — Phase 23C Corrective Review

### Fixed

- Cross-doctor list and inspector IDOR by requiring a validated `owner_user_id` and rechecking ownership at the File 23 boundary.
- Provider filter bypass, duplicate projections, provider error disclosure, malformed totals, internal query reflection, misleading pagination, lost filter context, silent key normalization, ambiguous timestamps, unsafe destinations, malformed operation metadata, mobile status loss, and total wording.

### Added

- Reported total, accessible total, and validated-window count separation.
- Non-enumerating cross-owner inspector response.
- Strict RFC 3339 timestamp and same-origin URL validation.
- Expanded inventory security and integrity tests.

## [0.3.0] — Phase 23C Initial Candidate

- Added the first read-only federated inventory, projection validator, item inspector, REST reads, bounded query window, and initial tests.
- Superseded by corrective version `0.3.1`.

## [0.2.1] — Phase 23B Second Corrective Review

- Added real File 00 role capability provisioning, exact-head CI, retained artifacts, provider callback isolation, REST error privacy, shortcode fail-closed protection, saved-view conflict controls, unique dashboard instances, and visible focus.

## [0.2.0] — Phase 23B Dashboard Core

- Added the protected dashboard route, role-aware workspaces, responsive shell, truthful overview, provider readiness, system status, personal saved views, private cache/index controls, and executable tests.

## [0.1.1] — Corrective Phase 23A

- Added the reviewed Adapter Contract 2.0.0, File 00 fail-closed authorization, provider acceptance separation, guarded operation broker, architecture guards, and PHP 8.0–8.3 CI.

## [0.1.0] — Superseded Bootstrap

Initial pre-audit bootstrap. It must not be merged or released independently.
