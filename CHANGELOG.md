# Changelog

All notable changes to File 23 are documented here.

## [Unreleased]

Phase 23E remains Draft, unmerged, not independently reviewed, not staging-accepted, and not production-ready.

## [0.5.0] — Phase 23E Initial Review and Calendar Candidate

### Added

- Optional `SPDB_Review_Calendar_Provider_Adapter` interface for native review queues and publishing schedules.
- Universal Review Inbox with bounded native review state, reviewer assignment, due dates, privacy/safety/source/copyright flags, version, freshness, and safe destinations.
- Federated Publishing Calendar with bounded native UTC schedules, IANA timezones, conflicts, failures, version, freshness, and safe destinations.
- Explicit approve, request-changes, reject, assign-reviewer, schedule, reschedule, and unschedule REST routes.
- Guarded operation-broker execution requiring provider declaration, current capability, approved account, environment acceptance, object version, idempotency key, and audit reason.
- Native object re-fetch before an operation is reported as confirmed.
- Separation-of-duties protection against final self-approval and self-rejection.
- Provider failure isolation, truthful unavailable states, global safety limits, and responsive review/calendar views.
- Executable Phase 23E tests and architecture guards.
- `docs/REVIEW-CALENDAR.md` and `docs/PHASE-23E-REVIEW-GATE.md`.

### Security

- Browser-supplied user, role, Founder flag, reviewer, author, scope, account state, capability, environment, and acceptance are non-authoritative.
- Review items assigned to another reviewer fail closed for non-Founder reviewers.
- Schedule mutations remain hidden or denied for unaccepted providers and unauthorized accounts.
- Exact same-origin destinations, RFC 3339 timestamps, IANA timezones, strict totals, bounded flags, and privacy-safe text are required.
- Provider failures never become approval, schedule, or success states.

### Architecture

- File 23 creates no review, reviewer-assignment, schedule, or cron table.
- Native review and schedule owners remain authoritative.
- Projection services and templates do not execute native mutations directly.

## [0.4.1] — Phase 23D Corrective Review

- Corrected sixteen role-workspace authority, action-semantic, destination, global-bound, profile/knowledge, timestamp, localization, and failure-isolation defects.
- Added the Phase 23D corrective audit, expanded tests, and architecture guards.

## [0.4.0] — Phase 23D Initial Role-Workspace Candidate

- Added the initial optional role-workspace projection interface, role views, cards, actions, profile/knowledge projections, activity, alerts, and tests.
- Superseded by corrective version `0.4.1`.

## [0.3.1] — Phase 23C Corrective Review

- Fixed cross-doctor IDOR, provider filter bypass, duplicate projections, provider error disclosure, malformed totals, internal query reflection, misleading pagination, lost filter context, silent key normalization, ambiguous timestamps, unsafe destinations, malformed operation metadata, mobile status loss, and total wording.
- Added reported/accessed/validated total separation, non-enumerating inspector denial, strict timestamps, URL validation, and expanded tests.

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
