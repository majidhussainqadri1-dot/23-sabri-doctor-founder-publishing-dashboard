# Changelog

All notable changes to File 23 are documented here.

## [Unreleased]

Phase 23E remains Draft, unmerged, not staging-accepted, and not production-ready.

## [0.5.1] — Phase 23E Corrective Review

### Fixed

- Rejected malformed filters instead of silently broadening review or calendar scope.
- Added exact surface, pagination, real-date, reversed-range, timezone, strict-list, total, and continuation validation.
- Replaced per-provider page slicing with bounded global validation, sorting, and central pagination.
- Separated native reported total, accessible validated window, current-page count, and reachable pages.
- Added saturating aggregate-total protection and truthful truncation notices.
- Required canonical projection identifiers, plain privacy-safe text, unique flags, and unique operations.
- Required explicit boolean separation metadata and blocked final self-approval/self-rejection independently of provider flags.
- Added File 23-owned operation contracts for surface, capability, Founder-only policy, eligible state, ownership, assignment, and separation.
- Added fresh object-level reauthorization before broker execution.
- Made reviewer assignment Founder-only and validated target account state and capability.
- Added explicit REST nonce verification and strict operation-specific payload allowlists.
- Required reason codes and privacy-safe notes for changes/rejection, reviewer IDs for assignment, and UTC time plus IANA timezone for schedule changes.
- Rejected impossible UTC dates, stale object versions, sensitive audit text, capability downgrade, cross-owner mutation, and mismatched native confirmation references.
- Added accessible pagination, visible focus, responsive tables, and reduced-motion support.
- Expanded corrective tests and architecture guards for all twenty-four audit findings.

### Documentation

- Added `docs/AUDIT-PHASE-23E-2026-07-30.md`.
- Updated the Phase 23E review gate, README, status, plugin metadata, and operational contract documentation.

## [0.5.0] — Phase 23E Initial Review and Calendar Candidate

- Added the first Universal Review Inbox and Federated Publishing Calendar candidate.
- Superseded by corrective version `0.5.1`.

## [0.4.1] — Phase 23D Corrective Review

- Corrected sixteen role-workspace authority, action-semantic, destination, global-bound, profile/knowledge, timestamp, localization, and failure-isolation defects.

## [0.3.1] — Phase 23C Corrective Review

- Corrected federated inventory authorization, privacy, projection, pagination, URL, and mobile defects.

## [0.2.1] — Phase 23B Second Corrective Review

- Added real File 00 capability provisioning, exact-head CI, retained artifacts, provider isolation, private REST errors, saved-view conflict controls, and accessibility corrections.

## [0.1.1] — Corrective Phase 23A

- Added reviewed contracts, File 00 fail-closed authorization, acceptance separation, guarded operation broker, architecture guards, and PHP 8.0–8.3 CI.
