# Status

## Current State

- Project: File 23 — Doctor and Founder Publishing Dashboard
- Specification: Harmonized Draft 2
- Active phase: 23F — Collections, Campaigns, Knowledge Links, Read API, and Read-Only UI
- Branch: `phase/23f-collections-knowledge`
- Parent branch: `phase/23e-review-calendar`
- Phase 23A PR #1: Draft, unmerged
- Phase 23B PR #2: Draft, twice source-reviewed, unmerged
- Phase 23C PR #3: Draft, source-reviewed, corrected, QA-green, unmerged
- Phase 23D PR #4: Draft, source-reviewed, corrected, QA-green, unmerged
- Phase 23E stacked work: source-reviewed, corrected, unmerged
- Phase 23F PR #7: Draft, open, four source-review cycles recorded, corrected, unmerged
- Plugin version: `0.6.2`
- Metadata schema: `3`
- Adapter contract: `2.0.0`
- Production readiness: Not ready
- Staging readiness: Not ready
- Merge readiness: Blocked by final documentation-inclusive exact-head QA, WordPress staging, migration, real native providers, real-account/privacy/accessibility/rollback evidence, Founder acceptance, and explicit authorization
- Exact-head rule: any source or documentation change invalidates earlier automated evidence

## Corrected Phase 23F Scope

- [x] Exactly three File 23-owned organizational metadata tables
- [x] Schema Version 3 table, column, and index verification
- [x] Request-cached schema/repository health
- [x] No native content, destination, clinical data, media, result, report, or raw analytics duplication
- [x] Current approved File 00 account, capability, scope, Founder, parent, and object authority
- [x] Actor-scoped idempotency with canonical request fingerprints
- [x] Exact replay, payload conflict, and duplicate canonical-relation conflict handling
- [x] Bounded audit-reason and observed native-version persistence
- [x] Concrete WordPress repository with strict direct-query and persistence-record validation
- [x] Repository-envelope, continuation, lifecycle, item, and projected-row validation
- [x] Overflowing numbers, malformed UTF-8, malformed timestamps, unknown fields, and inconsistent continuation rejected
- [x] Fail-closed institution scope without silent downgrade
- [x] Record-type-specific status validation
- [x] Native reference exact ID, visibility, permission, owner, version, and safe-destination validation
- [x] Parent collection authorization before item queries
- [x] Archived items excluded consistently from default reads
- [x] Six explicit GET-only Phase 23F REST routes
- [x] Strict REST query allowlists, private/no-store behavior, and truthful pagination headers
- [x] Protected Collections and Knowledge dashboard route
- [x] Approved-account navigation; pending/restricted accounts do not receive the view
- [x] Collection list/detail and parent-authorized active item list/detail
- [x] Knowledge-link list/detail
- [x] Strict UI query allowlist and malformed view fail-closed handling
- [x] Founder-only institution scope presentation
- [x] Scope-preserving and RTL-safe back navigation
- [x] Truthful read-only readiness, empty, error, and unavailable states
- [x] Accessible captions, landmarks, definition lists, current-page semantics, pagination, focusable tables, visible focus, RTL, reduced motion, and forced colors
- [x] Phase 23F readiness projected in System Status
- [x] No Phase 23F mutation UI or mutation REST route
- [x] Production writes disabled
- [x] Update, reorder, and archive execution disabled

## Review and Acceptance Gates

- [x] Initial eighteen defects documented, corrected, and re-reviewed
- [x] Second eighteen runtime/persistence defects documented, corrected, and re-reviewed
- [x] Third sixteen persistence/item/read-API defects documented, corrected, and re-reviewed
- [x] UI review documented sixteen principal defects
- [x] UI corrective re-review documented five additional integration, CI, routing, scope, and diagnostics defects
- [x] Dashboard integration, view model, navigation, CSS, System Status, and UI tests implemented
- [x] Dedicated exact-head PHP 8.0–8.3 Collections UI workflow implemented
- [x] Baseline Integrity and dedicated UI workflows were green on an earlier code-only head
- [ ] Both workflows must be green on the final documentation-inclusive head
- [ ] Final-head QA artifacts and checksums retained
- [ ] Schema Version 2 to Version 3 upgrade accepted on WordPress staging
- [ ] Real File 00 Founder, Doctor, contributor, pending, and suspended accounts accepted
- [ ] Real native-reference resolver accepted
- [ ] Real-provider stale, deleted, private, permission-lost, and outage behavior accepted
- [ ] Cross-doctor IDOR and enumeration resistance accepted
- [ ] LiteSpeed and hosting-cache privacy verified
- [ ] Desktop, tablet, mobile, keyboard, screen-reader, zoom, contrast, reduced-motion, and RTL staging acceptance
- [ ] Upgrade, deactivation/reactivation, backup/restore, and rollback accepted
- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] Explicit merge authorization recorded

## Evidence

- `docs/AUDIT-PHASE-23F-2026-07-30.md`
- `docs/AUDIT-PHASE-23F-RUNTIME-SECOND-REVIEW-2026-07-30.md`
- `docs/AUDIT-PHASE-23F-THIRD-REVIEW-2026-07-31.md`
- `docs/AUDIT-PHASE-23F-COLLECTIONS-UI-2026-07-31.md`
- `tests/phase23f-policy-tests.php`
- `tests/phase23f-runtime-tests.php`
- `tests/phase23f-repository-tests.php`
- `tests/phase23f-read-rest-tests.php`
- `tests/phase23f-collections-ui-tests.php`
- `tests/phase23f-collections-ui-corrective-tests.php`
- `tests/dashboard-router-malformed-view-tests.php`
- `.github/workflows/baseline-integrity.yml`
- `.github/workflows/phase23f-collections-ui.yml`

## Non-Negotiable Restrictions

1. No duplicate native publication, Composer, profile, knowledge, review, schedule, media, clinical, report, or analytics backend.
2. No native destination persisted in File 23 metadata.
3. No caller-supplied user, role, Founder flag, scope, capability, account state, environment, owner, version, or permission is authoritative.
4. No metadata read for a non-approved current File 00 account.
5. No collection-item query before current parent collection authorization.
6. No institution scope silently downgraded or shown as ordinary-doctor authority.
7. No malformed scalar, integer overflow, invalid UTF-8, malformed timestamp, unknown field, or inconsistent pagination treated as valid.
8. No create, update, reorder, archive, publish, approve, or native-write control in the Collections UI.
9. No production mutation and no knowledge-link create without fresh reviewed native reference resolution.
10. No merge before review, correction, re-review, exact-head QA, staging, rollback, Founder acceptance, and explicit authorization.

## Next Technical Step

After the final documentation-inclusive head passes both PHP 8.0–8.3 matrices, begin a separate independent review for the native-reference resolver integration boundary. The next slice may define resolver registration, provider isolation, fresh object authorization, outage semantics, and test doubles, but must not enable production writes or claim real-provider/staging acceptance. PR #7 and every predecessor PR remain Draft and unmerged.
