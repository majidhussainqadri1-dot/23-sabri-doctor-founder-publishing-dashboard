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
2. Activate and confirm that exactly the three File 23 metadata tables are installed or reconciled.
3. Re-run activation; verify idempotency and absence of duplicate tables, roles, pages or capabilities.
4. Confirm that File 23 creates no native publication, review, schedule, profile, media or analytics store.
5. Confirm private dashboard routes are noindex and no-store and are excluded from public LiteSpeed caching.

## Upgrade and migration rehearsal

1. Restore the pre-upgrade staging snapshot.
2. Install the previous accepted File 23 candidate and seed representative own-scope and institution-scope metadata.
3. Upgrade to 1.0.0 without deleting the previous data.
4. Confirm schema version 3, required columns/indexes, row/version integrity and preserved owner/scope relationships.
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

1. Confirm File 00 1.2.7 / contract 1.1.2 assertions are consumed and revalidated server-side.
2. Confirm File 21 1.0.3.2 package / 1.0.3 runtime remains the native publication owner.
3. Confirm File 22 0.3.0 remains the creation, draft, preview and submission orchestrator.
4. Exercise draft, preview, submit, review, schedule, reschedule and native status re-read through accepted adapters.
5. Disable each provider in turn; confirm graceful degradation and no widened access.
6. Test stale, deleted, private, ownership-lost and permission-lost native objects.

## Security, privacy and cache rehearsal

- Cross-doctor, cross-reviewer and guessed-ID IDOR tests.
- CSRF/nonce, capability, object version, idempotency and audit-reason tests.
- Same-origin destination and nested-redirect rejection.
- No sensitive data in REST errors, HTML, logs, cache keys or browser storage.
- LiteSpeed anonymous/authenticated cache separation; private dashboard responses must never be served to another account.
- Rate-limit and repeated-submit tests; no duplicate native action.
- Dependency outage and exception isolation.

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
2. Deactivate 1.0.0 without purging data.
3. Restore the previous plugin package and, where schema compatibility requires, the matching database snapshot.
4. Clear LiteSpeed/object caches and permalinks safely.
5. Verify public reading remains available and protected writes fail closed during the rollback window.
6. Re-run Founder/Doctor/Reviewer/Pending/Suspended smoke tests.

## Stop conditions

Do not promote when any Critical, High, Medium or Low known defect remains unresolved unless the Founder records an explicit bounded risk acceptance. Do not call the release staging-accepted until every item above has dated evidence and an approver. Do not call it production-operational until live monitoring, backups, support ownership and rollback readiness are proven.
