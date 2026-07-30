# Changelog

All notable changes to File 23 are documented here.

## [Unreleased]

Phase 23F remains Draft, unmerged, not staging-accepted, and not production-ready.

## [0.6.2] — Phase 23F Third Corrective Review and Read API

### Fixed

- Preserved raw database scalar shapes until strict projection validation instead of permissively casting malformed stored values.
- Rejected malformed direct repository owner IDs, filters, create records, unknown fields, hashes, lists, timestamps, and actors.
- Made JSON encoding failure explicit instead of silently persisting empty arrays.
- Cached repository health and Schema Version 3 inspection for one request.
- Replaced ambiguous aggregate write diagnostics with truthful collection-write, knowledge-write, and any-write readiness.
- Enforced mathematically consistent page, per-page, total, count, and continuation metadata.
- Added parent collection authorization before every collection-item repository query.
- Excluded archived collection items consistently from default list and detail reads.
- Added strict collection-item projection and lifecycle validation.
- Enforced created, updated, archived, and status lifecycle consistency for collections and knowledge links.
- Returned deterministic `409` conflicts for duplicate canonical knowledge relationships.

### Added

- Six explicit read-only REST routes for collections, collection details, collection items, item details, knowledge links, and link details.
- Strict REST query allowlists, private/no-store protection, and truthful pagination headers.
- Executable item-IDOR, malformed-persistence, continuation, lifecycle, duplicate-relation, health-cache, and read-REST tests.
- `docs/AUDIT-PHASE-23F-THIRD-REVIEW-2026-07-31.md`.

### Restrictions retained

- No Phase 23F REST mutation route.
- No production write enablement.
- No collection update/archive, item create/reorder/update/archive, or knowledge-link update/archive execution.
- No concrete native resolver before its separate review and staging gate.

## [0.6.1] — Phase 23F Runtime and Repository Second Corrective Review

### Fixed

- Required an approved current File 00 account for collection and knowledge metadata reads.
- Rejected unauthorized institution scope instead of silently rewriting it to own scope.
- Bound status filters to their selected collection or campaign record type.
- Validated repository envelopes and every projected row before returning metadata.
- Separated read readiness, collection-write readiness, and knowledge-write readiness.
- Required verified repository and Schema Version 3 health before any enabled staging write.
- Re-verified required tables, columns, and indexes on every schema lifecycle check.
- Added request fingerprints to distinguish exact idempotent replay from same-key/different-payload conflict.
- Added bounded created and last audit-reason persistence.
- Added observed native-version persistence without native destination persistence.
- Rejected malformed native owner IDs, versions, contexts, and cross-user repository records.
- Kept update, reorder, archive, mutation REST, and production writes explicitly fail-closed.

### Added

- Concrete `SPDB_WP_Collections_Repository` for verified reads and idempotent creates.
- Schema Version 3 migration and verification requirements.
- Corrective Phase 23F runtime-authority tests.
- Corrective Phase 23F concrete-repository, replay, payload-conflict, and missing-index tests.
- `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`.

## [0.6.0] — Phase 23F Foundation Corrective Review

- Corrected eighteen initial Phase 23F foundation defects.
- Added bounded metadata schema, policy, native-reference contract, and fail-closed runtime foundation.

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
