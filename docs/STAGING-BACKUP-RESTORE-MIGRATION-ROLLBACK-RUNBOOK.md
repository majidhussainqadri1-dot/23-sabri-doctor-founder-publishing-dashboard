# File 23 — Staging, Backup, Restore, Migration and Rollback Runbook

## Governing boundary

This runbook is mandatory before production activation. GitHub source tests and deterministic packaging prove repository integrity; they do not prove Hostinger, LiteSpeed, browser, real-account or database-restore behavior.

## Required participants

- Founder acceptance authority
- WordPress staging administrator
- database/hosting operator
- File 00 membership reviewer
- File 21 and File 22 integration operator
- accessibility tester

## Pre-deployment evidence

1. Record the exact File 23 commit SHA, package SHA-256, package manifest and release version.
2. Record installed File 00, File 21 and File 22 package/runtime/contract versions.
3. Export the complete staging database and verify that the SQL archive is readable and non-zero.
4. Archive `wp-content/plugins/sabri-publishing-dashboard` if an older version exists.
5. Record active plugins, WordPress/PHP versions, Site Health, cron state and LiteSpeed configuration.
6. Store backups outside the web root with access restricted to the authorized operator.

## Fresh-install rehearsal

1. Install the canonical File 23 ZIP on staging only.
2. Activate and confirm that all 13 File 23-owned metadata tables (10 operational + 3 collections/knowledge-link tables) are installed or reconciled as defined by docs/DATABASE-SCHEMA-MANIFEST.md.
3. Re-run activation; verify idempotency and absence of duplicate tables, roles, pages or capabilities.
4. Confirm that File 23 creates no native publication, review, schedule, profile, media or analytics store.
5. Confirm private dashboard routes are noindex and no-store and are excluded from public LiteSpeed caching.

## Upgrade and migration rehearsal

1. Restore the pre-upgrade staging snapshot.
2. Install the previous accepted File 23 candidate and seed representative own-scope and institution-scope metadata.
3. Upgrade from the previous accepted package to the exact reviewed 1.3.0 release candidate without deleting previous data; do not assume this candidate is deployed or approved.
4. Confirm actual installed operational schema version 1.1.0 and collections schema version 4, required columns/indexes, row/version integrity and preserved owner/scope relationships; reconcile any differences before proceeding.
5. Repeat the upgrade to prove idempotency.
6. Test activation failure with an intentionally incomplete schema in an isolated disposable copy; confirm fail-closed behavior and an actionable error.

## Real account matrix

Use separate real staging accounts and record screenshots plus audit IDs:

- Founder: institution scope, permitted operations and no security bypass.
- Verified Doctor: own scope only; cross-doctor inventory and object access denied without enumeration.
- Reviewer: assigned review actions only; no self-approval or unauthorized publication mutation.
- Pending account: restricted read-only workspace; every write denied.
- Suspended account: restricted read-only/status path; every write denied.

## Real File 00, File 21 and File 22 integration

1. Record the actual deployed File 00 package/runtime and contract versions. Verify SMC_Contracts assertions and minimum supported contract 1.1.2 against the deployed artifact, not against a GitHub pin alone.
2. Record the actual deployed File 21 package/runtime version and verify that File 21 remains native publication owner; compare separately with the approved exact companion HEAD.
3. Record the actual deployed File 22 version and verify its native creation, draft, preview and submission contract against the approved exact companion HEAD.
4. Exercise draft, preview, submit, review, schedule, reschedule and native status re-read through accepted adapters.
5. Disable each provider in turn; confirm graceful degradation and no widened access.
6. Test stale, deleted, private, ownership-lost and permission-lost native objects.

## Security, privacy and cache rehearsal

- Cross-doctor, cross-reviewer and guessed-ID IDOR tests.
- CSRF/nonce, capability, object version, idempotency and audit-reason tests.
- Same-origin destination and nested-redirect rejection.
- No sensitive data in REST errors, HTML, logs, cache keys or browser storage.
- Cached metrics at cohorts 8/20 and admin threshold 50 at 30/60 must be masked consistently during write, legacy read and authenticated REST serialization.
- Institutional snapshots must not contain doctor-own rows; rehearse two doctor-owned rows and one institution row and confirm scope isolation.
- LiteSpeed anonymous/authenticated cache separation; private dashboard responses must never be served to another account.
- Rate-limit and repeated-submit tests; no duplicate native action.
- Dependency outage and exception isolation.

## V12.1 private export / deletion and attestation negative rehearsal (required)

This is a **future staged procedure** only. None of these Hostinger results has yet been observed or accepted. Use synthetic non-patient report fixtures, and preserve screenshots, response headers, audit IDs, DB row states, filesystem manifest and the exact deployed SHA.

1. **Actual serving stack**: identify Apache/LiteSpeed/Nginx/CDN and its real route from `wp-content/uploads/spdb-private-exports/`; do not assume that a correct `.htaccess` is enforced by an upstream server. From an anonymous browser and direct origin/CDN URL, `GET` and `HEAD` a synthetic ciphertext and assert 403/404 and **zero file bytes**. Repeat through caches and after login/logout.
2. **Authorized report**: prove that only a current owner with approved File 00 permission and current second-factor assertion can download through the intended `admin-post.php` expiring owner-HMAC link. Try a different Doctor, suspended actor, expired token, role switch, replay and missing owner row.
3. **Guard integrity**: tamper with synthetic `.htaccess`, `web.config` and `index.php` on a disposable staging copy. Assert File 23 fails closed and reports an error; verify no public file access even when guard behavior is intentionally altered.
4. **Exclusive ciphertext write**: pre-create the expected `export_ID.spdb` file and separately create a symlink with the same name. Trigger only a synthetic, authorized report job and assert no overwrite and no change to a different file. Record failure state, retry behavior, cleanup instructions and file permission mode `0600`.
5. **Privacy erasure**: seed a synthetic user's encrypted exports; simulate unavailable private directory, SQL SELECT error, malformed ref, symlink, denied unlink and a mid-enumeration interruption. Assert `export_jobs` references remain when any file erasure fails; never show a false complete state.
6. **Expired retention**: prepare two synthetic ciphertexts owned by different users; point one expired filename symlink at the other's file. Run retention cleanup and assert it fails without deleting the target or deleting metadata. Simulate 20,000+ expired rows; bounded cleanup must not trigger metadata purge.
7. **Storage/DB reconciliation**: test crash after ciphertext creation but before export-job completion, and DB transaction failure after file deletion. Produce an operator-verifiable orphan reconciliation plan. A bounded fail-closed error is not a successful bulk erasure.
8. **File20 trusted deployed attestation**: obtain explicit evidence from File20/deployment operator (Issue #45) that active installed File23 files match source/package SHA and measured operations/collections schemas and migrations. Verify `spdb/file23_deployed_artifact_attestation` carries an authorized, fresh server-side assertion, **not** a form/option string.
9. **Same-version adverse case**: replace one installed PHP file while retaining plugin header `1.3.0`, restore a stale acceptance option, or defer a migration; acceptance must immediately fail closed. Repeat after actual restore and new Founder decision. Keep File23 issue #30 OPEN until measured proof exists.
10. **Release evidence**: attach dated role/URL/HTTP/cache/DB/migration/package hashes to `docs/RELEASE-SIGNOFF.md`; write separate **Repository HEAD / Deployed Version / DB Version / Migration State / Live Verification Status**. No source-only check authorizes a live rollout.

## V12.1 WP-12 20,001+ object resumable erasure and retention staging drill

Run in a **disposable staging database** with synthetic report ciphertext only, after exact-package and DB/schema/migration freeze. No production patient data.

1. Seed 20,001 owner-specific export_jobs, 200+ foreign-owned jobs, a mix of ready/queued/processing, future/expired, a few valid encrypted artifacts and intentionally malformed refs in separate resettable fixtures.
2. Submit a normal WordPress personal-data erasure request; invoke successive paged callbacks, restart worker between pages, and inspect HMAC checkpoint owner/stage/cursor. Confirm 200-row bounded scan, no metadata deletion during remove/verify, and complete second pass prior to final SQL deletion.
3. Run a separate private export generator concurrently, including a job already rendering before the erasure checkpoint. Confirm a common MySQL owner lock and native job/capability recheck prevent newly persisted ciphertext after erasure begins. Temporarily fail GET_LOCK, SQL select, unlink, option write and job completion: preserve metadata and report retry/retained state, never fabricate success.
4. Tamper with checkpoint max ID, stage, cursor and HMAC in a disposable copy; test two concurrent privacy page requests and user-switch. Confirm lock release, fail-closed status, no cross-account access, safe operator reset only with recorded approval.
5. Seed >20,001 expired rows; start the signed background continuation chain, then insert an additional already-expired row with higher SQL ID. Confirm the initial maximum row ID and frozen GMT cutoff preserve it. Test forged max ID, nested JSON, replayed hop, unavailable queue, resumed missed cron, duplicate idempotency, dead letter and no premature metadata purge.
6. After verified file deletion run the SQL retention stage and confirm exactly the frozen range is purged, while newly inserted and future records retain both metadata and ciphertext. Test second verification when a ciphertext reappears; abort deletion.
7. Measure actual MySQL locking, query plan, CPU, PHP memory, deadline/SLO, and time for 20,001/100,000 rows. Record Hostinger cron configuration and recovery/runbook owner. Back up and restore plugin files + database + wp_options mid-scan; prove the checkpoint is recoverable and never reauthorizes a different plugin/package.
8. Attach exact HEAD, package SHA, installed-file attestation, schema, migration, logs, restoration proof and Founder decision to `docs/RELEASE-SIGNOFF.md`. File23 #31 stays open until independently accepted staging evidence.

## Accessibility and responsive acceptance

Test 320, 360, 390, 480, 768, 1024, 1280, 1440 and 1920 CSS pixels in English and Urdu/RTL. Complete all workflows with keyboard only and at 200% and 400% zoom. Verify visible focus, landmarks, headings, labels, captions, status announcements, contrast, reduced motion, forced colors and one supported screen reader. Record each result, browser/device and evidence location.

## Backup and restore rehearsal

1. After successful staging use, create a second database and plugin-files backup.
2. Delete or alter selected File 23 metadata only in the disposable staging copy.
3. Restore database and plugin files to a clean staging clone.
4. Verify table counts, row checksums, owner/scope/version fields, saved views and audit continuity.
5. Re-run File 23 health and the full account/integration smoke matrix.
6. Mark restore successful only when restored behavior and evidence match the pre-failure record.

## Application rollback rehearsal

1. Keep the last accepted package and database backup available.
2. Deactivate the exact 1.3.0 candidate if it was installed on staging; do not purge metadata or assume any live deployment.
3. Restore the previous plugin package and, where schema compatibility requires, the matching database snapshot.
4. Clear LiteSpeed/object caches and permalinks safely.
5. Verify public reading remains available and protected writes fail closed during the rollback window.
6. Re-run Founder/Doctor/Reviewer/Pending/Suspended smoke tests.

## Stop conditions

Do not promote when any Critical, High, Medium or Low known defect remains unresolved unless the Founder records an explicit bounded risk acceptance. Do not call the release staging-accepted until every item above has dated evidence and an approver. Do not call it production-operational until live monitoring, backups, support ownership and rollback readiness are proven.
