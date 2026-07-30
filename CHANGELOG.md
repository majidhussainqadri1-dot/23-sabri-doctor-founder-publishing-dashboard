# Changelog

All notable changes to File 23 will be documented here.

## [Unreleased]

### Added

- Repository initialization and Phase 23A branch.
- Governing architecture and status gates.
- File 21–24 responsibility matrix.
- Native data-ownership and retention contract.
- Capability and scoped-delegation contract.
- Versioned provider adapter contract and state projection.
- Analytics aggregate and background-jobs contracts.
- Security threat model.
- Plugin bootstrap, capability definitions, provider interface, and guarded registry.

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
- File 00 compatibility is now explicitly `>= 1.0.1` and `< 2.0.0` pending future review.
- Pending/rejected/expired/suspended accounts may retain only explicitly assigned restricted dashboard and owned-content read-only capabilities; mutation remains denied.
- Native object identifiers and audit reasons are now bounded and control-character safe.

### Security

- Providers can no longer self-declare staging or production acceptance.
- Staging acceptance cannot authorize production writes.
- All File 23 access fails closed when compatible File 00 is unavailable.
- Mutable and institution-wide capabilities require an approved/verified File 00 account.
- Restricted non-approved accounts cannot obtain create, edit, review, schedule, analytics, export, delegation, repair, or policy authority.
- Unregistered operation keys and reserved client authority fields are rejected.
- Provider exceptions no longer escape the registry or operation broker.
- Native object re-read is required before confirmed success.
- Direct native post, comment, attachment, forbidden-domain table ownership, and direct adapter mutation outside the broker are blocked by CI.

## [0.1.0] — Superseded Pre-Audit Bootstrap

Initial Phase 23A governance and bootstrap implementation. Superseded by corrective build `0.1.1`; it must not be merged independently.
