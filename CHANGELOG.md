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
- File 00 Membership Core dependency and approved-account guard.
- Guarded native operation broker.
- Executable adapter and authorization contract tests.
- Executable non-duplication architecture guard.
- PHP 8.0, 8.1, 8.2, and 8.3 CI matrix.
- Registration error diagnostics and adapter exception isolation.

### Changed

- Adapter Contract upgraded from `1.0.0` to `2.0.0`.
- Provider technical capability separated from File 23-controlled institutional acceptance.
- Caller-supplied production Boolean replaced with server-resolved WordPress environment.
- Provider keys and metadata now require exact canonical identifiers and semantic versions.
- Adapter compatibility now uses declared minimum/maximum contract versions.
- Operation definitions now require capability, ownership, verification, state, version, idempotency, audit, schema, and rate-limit metadata.
- Stored canonical URL changed to a non-secret destination descriptor contract.

### Security

- Providers can no longer self-declare staging or production acceptance.
- Staging acceptance cannot authorize production writes.
- File 23 capabilities fail closed when File 00 is unavailable or the account is not approved/verified.
- Unregistered operation keys and reserved client authority fields are rejected.
- Provider exceptions no longer escape the registry or operation broker.
- Native object re-read is required before confirmed success.
- Direct native post, comment, attachment, and forbidden-domain table ownership is blocked by CI.

## [0.1.0] — Superseded Pre-Audit Bootstrap

Initial Phase 23A governance and bootstrap implementation. Superseded by corrective build `0.1.1`; it must not be merged independently.
