# File 23 — V12.1 WP-12 Large-Volume Private Export Erasure and Retention: Two Fresh Source Review Lenses

**Date:** 12 November 2026 (Pakistan).  
**Review code/test HEAD:** `d23e2246830480f7679ba32ca4405b4c7bb164c4`.  
**Parent source:** Version 1.3.0 PR #26 at `df59cb2be8c1a69dc3539bc7e0a58d5d79125ac3`.  
**Release status:** Draft PR #29, not merged, not deployed. This note records two sequential **internal analytical source review passes**, not two separate external reviewers or actual Hostinger acceptance.

## A — GDPR/WordPress personal-erasure failure semantics

**Root cause:** The earlier erasure method stopped after 100 full pages of 200 rows, leaving database references intact correctly, but every later call restarted at ID 0. Therefore >20k owner records could not complete.

**Source correction in PR29:**
- `SPDB_Export_Service::erase_user_files_page(user_id, cursor, <=200, verify_only)`: strictly owner-scoped keyset pagination, path-reference canonicalization, no symlink traversal, verified ciphertext unlink, DB failure propagation, monotonic cursor, foreign owner isolation.
- `SPDB_Privacy_Integration::erase_personal_data`: WordPress-compatible multi-call response with a **per-owner durable server-side signed checkpoint**, stages `remove` then `verify`, bounded operations, and final File23 repository erasure **only after** a full second verification scan.
- Checkpoint must be created before any destructive action; any present checkpoint blocks new owner report exports. Export generation rechecks state before and after ciphertext write; it reacquires actual native job and permissions.
- MySQL advisory lock shared across each privacy page and final export ciphertext generation/metadata transition serializes the owner-specific critical section. Busy lock cannot make the export permanently fail; it returns a retryable error. Malformed/tampered checkpoint is retained for investigation and does not authorize a skip.
- File 23 only touches its own export metadata/ciphertext and not CF04 media, File00 account state or clinical records.

**Verified source tests at `d23e224...`:**
`v12-1-resumable-erasure-tests.php`: **215 passing assertions**, with 20,001 owner records and foreign rows.  
`v12-1-privacy-erasure-progress-tests.php`: **15 passing**, covering forged signed cursor, pause, two-phase restart, concurrent lock and reappeared artifact.  
`v12-1-owner-erasure-lock-contract-tests.php`: **22 passing** source/option checks.  
Previous erasure safeguards remain green.

**Residual concerns explicitly not called resolved:** a WordPress-MySQL advisory lock coordinates cooperative File23 PHP actors but is not an OS file lock, an across-backup atomic transaction, or proof of another plugin's behavior. User-level deletion requests on a real 20k+ WordPress/Hostinger staging database, restoration, orphan reconciliation, account permission revocation and privacy request timeout need separate evidence. The current HMAC checkpoint is not a replacement for File00 native authorization.

## B — expired-retention, signed background continuation and race safety

**Root cause:** Original retention walk also stopped at 20,000 and any restart rescanned old SQL rows; the File23 background worker only knew how to call the single-pass function. A naive continuation by cutoff alone would additionally include newly inserted rows whose expiry predates the frozen cutoff.

**Source correction in PR29:**
- `cleanup_expired_files_page(frozen_cutoff, cursor, <=200, verify_only, frozen_max_id)` is bounded and read-only in verification phase. Rejects active queued/processing jobs, invalid references, filesystem symlinks and errors.
- The maintenance job captures authoritative **UTC cutoff and maximum eligible SQL row ID**, signs those with a server HMAC along with root job ID, cursor, phase and hop count; every queued continuation is independently checked for types, signature and scope.
- A native File23 background continuation is enqueued before marking the current job complete, with deterministic idempotency. No unverified external queue or browser payload is trusted as authority.
- The full independent verify pass precedes `SPDB_Operations_Repository::cleanup_retention(settings, frozen_cutoff, frozen_max_id)`; the final SQL deletion is bounded by both original constraints. Records inserted later (including records expiring before the original cutoff) are excluded from the old retention run.
- Before and after a potentially slow render, `process()` checks expiry; owner lock and current job/rights check reduce races with personal erasure.

**Verified source tests at `d23e224...`:**
`v12-1-resumable-expiry-tests.php`: **212 passing** assertions on >20k records.  
`v12-1-retention-continuation-chain-tests.php`: **30 passing** checks, six-step signed chain and a later same-cutoff inserted row retained.  
`v12-1-expired-export-retention-tests.php`: **9 passing** path/symlink and truncation regressions.  
All three full GitHub workflows on the reviewed HEAD succeeded: Baseline Integrity **#1572**, Full Plan Completion **#623**, Final Release Candidate **#377**, including PHP 8.0–8.3 where offered.

**Residual concerns:** fake SQL tests prove source behavior, not actual MySQL query plans, InnoDB isolation, Hostinger cron reliability, permissions, object-cache, symlink policy or backup recovery. A background job that reaches dead-letter or a checkpoint that cannot be committed requires operational monitoring and bounded retry. Two-phase verification is not a transaction across filesystem and DB. Signed file23 job payloads are internal continuation integrity, not deployed-artifact attestations under File20 issue #45.

## Quantified finding / correction accounting
- **Pre-existing source scalability defect targeted:** 1; >20k owner and expired retention non-progressing loops, with two entry paths under one issue **#31**.
- **Additional source safety weaknesses revealed while fixing it:** 2; (a) exports racing owner erasure; (b) records inserted after cutoff capture becoming erroneously part of retention. Both corrected in Draft PR29 source.
- **New code/test fixtures and obsolete test assumptions corrected during CI:** recorded separately, not counted as deployed defects.
- **New executable suites this review:** 5 (large personal erase; privacy pagination/checkpoint; expiry paging; signed continuation; owner/erasure coordination).
- **New live defects proven or fixed:** 0 — none were diagnosed against active deployed files and DB.
- **Open independent release gates:** File23 #31 measured Hostinger/real 20k+ acceptance; File23 #30 / File20 #45 trusted installed-file/schema attestation; 13 documented operational limitations; two external source reviews; Founder signoff; real live parity.
- **Release determination:** Candidate source improvements verified at reviewed HEAD. **Do not merge or deploy**; do not close issue #31 without real staging tests and evidenced retention recovery.

## Required stage acceptance (minimum)
1. Clone, back up and freeze exact installed File23 plugin bytes and DB/schema/migrations, native contracts and LiteSpeed stack.
2. Seed >20,001 encrypted synthetic owner report entries plus foreign-owner entries. Exercise WordPress privacy exporter/eraser end-to-end, partial/page retry, duplicate/concurrent requests, checkpoint tamper, anonymous and suspended accounts; verify no unreferenced ciphertext.
3. Inject SQL unavailable, permission-denied unlink, stale/forged HMAC, owner lock busy, reappeared artifact, lost cron and dead-letter states. Prove fail closed with recoverable references and operator-visible audit.
4. Use real InnoDB with >20k mixed future/expired rows and inject same-cutoff rows during a six-plus-step expiry run. Prove only originally frozen IDs are eligible, verify second scan and final bound DELETE, and test CDN-origin access-denial for private ciphertext.
5. Restore from backup with an old checkpoint and new plugin build; require fresh installed-artifact attestation under File20/File24, schema/migration parity and new Founder signoff. Confirm no mutation of clinical/provider-owned files.
