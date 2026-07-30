# Changelog

All notable changes to File 23 will be documented here.

## [Unreleased]

Phase 23B remains under review and is not merge-ready, staging-accepted, or production-ready.

## [0.2.0] — Phase 23B Dashboard Core

### Added

- Protected `/publishing-dashboard/` front-end route and shortcode fallback.
- Upgrade-safe rewrite registration for already active installations.
- Founder, trusted-doctor, doctor, restricted, denied, and dependency-failure workspace resolution.
- Current-user binding for workspace authority.
- Responsive and accessible dashboard shell.
- Truthful operational overview without fabricated publication or analytics counts.
- Provider-readiness and non-sensitive system-state projections.
- Personal saved views with bounded REST endpoints and File 23-owned user-meta storage.
- Write-time and read-time validation of saved-view definitions.
- Explicit patient-sensitive filter exclusion.
- Keyboard-operable saved-view creation and deletion.
- Executable Phase 23B dashboard-core tests.
- JavaScript syntax checks and expanded CI file/private-route verification.
- Dedicated Phase 23B review and no-merge gate.

### Security

- Dashboard access requires authentication, compatible File 00 status, and the canonical `spdb_view_dashboard` capability.
- Restricted accounts retain read-only workspace access only when explicitly assigned.
- Dashboard responses emit private `no-store` and search-index exclusion headers.
- Production publishing writes remain disabled.
- Saved views accept only a narrow non-clinical filter allowlist and are revalidated on read.
- System status excludes secrets, patient data, private messages, appointments, and clinical records.

### Accessibility

- Added semantic navigation and main landmarks, skip link, focus visibility, live status region, responsive layouts, reduced-motion support, and keyboard-operable controls.

## [0.1.1] — Corrective Audit Build

### Added

- Independent Phase 23A audit and mandatory no-merge gate.
- Versioned File 00 Membership Core dependency and account-state guard.
- Guarded native operation broker.
- Executable adapter and authorization contract tests.
- Executable non-duplication architecture guard.
- PHP 8.0, 8.1, 8.2, and 8.3 CI matrix.
- Registration error diagnostics and adapter exception isolation.
- Verified File 00 source provenance and package checksum.

### Changed

- Adapter Contract upgraded from `1.0.0` to `2.0.0`.
- Provider technical capability separated from File 23-controlled institutional acceptance.
- Caller-supplied production Boolean replaced with server-resolved WordPress environment.
- Provider keys and metadata now require exact canonical identifiers and semantic versions.
- Adapter compatibility now uses declared minimum/maximum contract versions.
- Operation definitions now require capability, ownership, verification, state, version, idempotency, audit, schema, and rate-limit metadata.
- Stored canonical URL changed to a non-secret destination descriptor contract.
- File 00 compatibility is explicitly `>= 1.0.1` and `< 2.0.0` pending future review.
- Pending, rejected, expired, and suspended accounts may retain only explicitly assigned restricted dashboard and owned-content read-only capabilities; mutation remains denied.
- Native object identifiers and audit reasons are bounded and control-character safe.

### Security

- Providers can no longer self-declare staging or production acceptance.
- Staging acceptance cannot authorize production writes.
- All File 23 access fails closed when compatible File 00 is unavailable.
- Mutable and institution-wide capabilities require an approved or verified File 00 account.
- Restricted non-approved accounts cannot obtain create, edit, review, schedule, analytics, export, delegation, repair, or policy authority.
- Unregistered operation keys and reserved client authority fields are rejected.
- Provider exceptions no longer escape the registry or operation broker.
- Native object re-read is required before confirmed success.
- Direct native post, comment, attachment, forbidden-domain table ownership, and direct adapter mutation outside the broker are blocked by CI.

## [0.1.0] — Superseded Pre-Audit Bootstrap

Initial Phase 23A governance and bootstrap implementation. Superseded by corrective build `0.1.1`; it must not be merged independently.
