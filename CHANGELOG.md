# Changelog

All notable changes to File 23 are documented here.

## [Unreleased]

Phase 23C remains Draft, unmerged, not staging-accepted, and not production-ready.

## [0.3.1] — Phase 23C Corrective Review

### Fixed

- Cross-doctor list and inspector IDOR by requiring a validated `owner_user_id` and rechecking ownership at the File 23 boundary.
- Provider filter bypass by revalidating every item against normalized object-type, state, language, topic, search, and date filters.
- Duplicate provider/type/object projections.
- Provider-defined error-code disclosure.
- Silent casting of malformed provider totals.
- Reflection of internal `user_id` and `window` query fields.
- Pagination that implied results beyond the 200-item safety window were reachable.
- Loss of active filters in inspector and pagination links.
- Silent normalization of query and projection keys.
- Ambiguous and relative timestamp acceptance.
- Fragment, nested redirect, signed, secret-bearing, cross-origin, wrong-port, and external-thumbnail URL surfaces.
- Nested, noncanonical, and duplicate operation projection values.
- Mobile removal of review, visibility, and modified columns.
- UI wording that conflated native reported totals with validated bounded-window results.

### Added

- Reported total, accessible total, and validated-window count separation.
- Non-enumerating cross-owner inspector response.
- Strict RFC 3339 timestamp validation.
- Same-origin canonical, destination, and thumbnail policy.
- Expanded IDOR, filter-integrity, pagination, duplicate, total, URL, timestamp, operation, and provider-failure tests.
- `docs/AUDIT-PHASE-23C-2026-07-30.md`.

### Security

- Client-supplied scope, user, owner, role, capability, status, and environment remain non-authoritative.
- Phase 23C remains read-only and exposes no native mutation endpoint.
- Native provider failures are reduced to bounded generic diagnostics.

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
