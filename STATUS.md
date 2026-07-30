# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23F — Collections, Campaigns, Knowledge Links, and Read-Only Projections
- Branch: `phase/23f-collections-knowledge`
- Parent branch: `phase/23e-review-calendar`
- Phase 23A PR #1: Draft, unmerged
- Phase 23B PR #2: Draft, twice source-reviewed, unmerged
- Phase 23C PR #3: Draft, source-reviewed, corrected, QA-green, unmerged
- Phase 23D PR #4: Draft, source-reviewed, corrected, QA-green, unmerged
- Phase 23E stacked work: source-reviewed, corrected, unmerged
- Phase 23F PR #7: Draft, open, three principal review cycles completed, corrected, unmerged
- Plugin version: `0.6.2`
- Metadata schema: `3`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked by current-head QA, WordPress staging, Schema Version 2-to-3 migration, real native providers, real-account/privacy/accessibility/rollback evidence, Founder acceptance, and explicit merge authorization
- Technical status: three Phase 23F reviews recorded eighteen foundation findings, eighteen runtime/persistence findings, and sixteen item/read-API findings; corrective re-reviews also fixed optional-filter, strict-projection, performance, overflow, invalid UTF-8, and malformed timestamp edge defects
- Exact-head rule: only GitHub checks attached to the current documentation-inclusive commit are authoritative; every later source change invalidates earlier evidence

## Corrected Phase 23F Scope

- [x] Exactly three File 23-owned organizational metadata tables
- [x] Schema Version 3 table, column, and index verification
- [x] Request-cached schema/repository health rather than repeated inspection
- [x] No native content, destination, clinical data, media, result, report, or raw analytics duplication
- [x] Current approved File 00 account, capability, scope, Founder, parent, and object authority
- [x] Actor-scoped idempotency with canonical request fingerprints
- [x] Exact replay and same-key/different-payload conflict handling
- [x] Duplicate canonical relation conflict handling
- [x] Bounded audit-reason and observed native-version persistence
- [x] Concrete WordPress repository for verified reads and idempotent creates
- [x] Strict direct repository query and persistence-record allowlists
- [x] Repository-envelope, continuation, lifecycle, item, and projected-row validation
- [x] Unknown and sensitive repository fields rejected before projection
- [x] Malformed and overflowing owner, creator, version, position, total, page, and per-page values rejected
- [x] Valid UTF-8 required for metadata text and native versions
- [x] Malformed optional database timestamps preserved as invalid and rejected
- [x] Fail-closed institution scope without silent downgrade
- [x] Record-type-specific status filters and campaign semantic validation
- [x] Native reference exact ID, visibility, permission, owner, version, and safe-destination validation
- [x] Parent collection authorization before collection-item queries
- [x] Archived items excluded consistently from default reads
- [x] Read, collection-write, knowledge-write, and any-write readiness separated
- [x] Six explicit GET-only Phase 23F REST routes
- [x] Strict REST query allowlists and truthful pagination headers
- [x] Global private/no-store REST policy applies to Phase 23F
- [x] Production writes disabled
- [x] Update, reorder, archive, and Phase 23F mutation REST routes disabled
- [x] Initial audit recorded in `docs/AUDIT-PHASE-23F-2026-07-30.md`
- [x] Runtime second audit recorded in `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`
- [x] Third item/read-API audit and re-review recorded in `docs/AUDIT-PHASE-23F-THIRD-REVIEW-2026-07-31.md`

## Phase 23F Review and Acceptance Gates

- [x] Stacked branch created from corrected Phase 23E head
- [x] Draft stacked PR #7 opened
- [x] Initial eighteen defects documented, corrected, and re-reviewed
- [x] Second eighteen defects documented, corrected, and re-reviewed
- [x] Third sixteen principal defects documented, corrected, and re-reviewed
- [x] Additional optional-filter, overflow, invalid UTF-8, malformed timestamp, projection, and health-performance findings corrected
- [x] Code-inclusive PHP 8.0–8.3 matrix completed successfully after edge correction
- [x] Phase 23A–23E regressions green on the corrected code slice
- [x] Phase 23F policy, runtime, repository, item-IDOR, read-REST, privacy, and architecture suites green on the corrected code slice
- [ ] Current documentation-inclusive commit must have green PHP 8.0, 8.1, 8.2, and 8.3 checks
- [ ] Current-head QA artifacts and source checksums retained
- [ ] Schema Version 2 to Version 3 upgrade accepted on WordPress staging
- [ ] Real File 00 Founder, Doctor, contributor, pending, and suspended accounts accepted
- [ ] Real native-reference resolver accepted
- [ ] Real-provider stale, deleted, private, permission-lost, and outage behavior accepted
- [ ] Cross-doctor IDOR and enumeration resistance accepted
- [ ] LiteSpeed and hosting-cache privacy verified
- [ ] Accessible Collections UI, desktop/tablet/mobile, keyboard, screen-reader, zoom, contrast, reduced-motion, and RTL accepted
- [ ] Upgrade, deactivation/reactivation, backup/restore, and rollback accepted
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Explicit merge authorization recorded

## Non-Negotiable Restrictions

1. No duplicate publication, Composer, profile, native knowledge, Newsroom, review, schedule, source, media, clinical, comment, correction, retraction, report, or analytics backend.
2. No native destination persisted in File 23 metadata.
3. No provider self-acceptance for staging or production.
4. No caller-supplied user, role, Founder flag, author, scope, capability, account state, environment, native state, owner, version, or permission is authoritative.
5. No generic unrestricted action endpoint.
6. No metadata read for a non-approved current File 00 account.
7. No collection-item query before current parent collection authorization.
8. No institution scope silently downgraded to own scope.
9. No integer overflow, malformed UTF-8, malformed timestamp, inconsistent continuation state, or unknown projection field treated as valid.
10. No write without verified schema/repository health, current authority, idempotency key, request fingerprint, audit reason, and operation-specific evidence.
11. No knowledge-link create without fresh native reference resolution.
12. No update, reorder, archive, or mutation REST execution before its separate review and acceptance gate.
13. No patient-identifying content, contact information, secret, signed URL, or native destination enters persistent metadata, logs, errors, or routes.
14. No merge before review, correction, re-review, exact-head QA, staging, rollback, Founder acceptance, and explicit authorization.

## Next Technical Step

After the current documentation-inclusive exact-head gate is green, begin a separately reviewed accessible Collections dashboard projection and read-only UI slice. The native resolver, item creation, updates, reorder, archive, REST mutation, and production writes remain outside that authorization. PR #7 and all predecessor PRs remain Draft and unmerged.