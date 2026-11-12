# File 23 V12.1 — Storage / Privacy Source Review A & B
**Review date:** 12 November 2026, Pakistan.  
**Reviewed source/test HEAD before this document:** `60c052469566c4381d9a99972e0674c82879497a` on Draft PR #29; parent PR #26 `df59cb2be8c1a69dc3539bc7e0a58d5d79125ac3`.  
**Governing scope:** central V12.1 STO-05, STO-07, STO-09; File23 plan WP-04, WP-05, WP-09, WP-12, WP-13.  
**Evidence boundary:** two sequential analytical review passes, **not** two independent external/human reviewers. No Hostinger, installed ZIP, DB/migration, Nginx/LiteSpeed, File00/20/24/CF04 or live evidence is asserted here.

## A — Erasure ordering and residual private ciphertext

### Reproducible source defect: V12.1-STO09-ERASE-01
Prior File23 source `SPDB_Export_Service::erase_user_files()` returned `0` if `private_directory()` failed, treated DB query failure like an empty result, skipped invalid refs / deletion errors, and returned a number despite incomplete traversal. `SPDB_Privacy_Integration::erase_personal_data()` then proceeded to `privacy_erase()`, which deletes `export_jobs` owner rows. **Resulting possible source sequence:** ciphertext remains, only DB storage reference is lost, while WordPress privacy handler does not expose the incomplete erasure.

### Correction and proof
1. File enumeration is owner-scoped and paginated, with monotonically increasing cursor. SQL unreadable/errors, invalid storage references, symlinks, wrong real paths, unlink failures and traversal-limit exhaustion now return a typed `WP_Error`, never a silent zero.
2. Paths must be exactly `export_[a-z0-9]{32}.spdb` within the private real directory. Missing artifact is a retry-safe no-op; unexpected on-disk file type fails closed.
3. The WordPress privacy eraser checks `is_wp_error($files)` before `privacy_erase()`, preserving storage references and returning `items_retained=true` with a bounded error message when deletion is incomplete.
4. Executable `tests/v12-1-personal-erasure-safety-tests.php`: unavailable storage, privacy callback failure propagation, DB read failure, malformed ref, valid ciphertext removal, symlink target preservation, queued/empty artifact, idempotent missing-file retry, nonmonotonic cursor and 20k-batch hard limit.

### Review limitations
- Deletion of bytes and deletion of DB rows cannot be a single filesystem+InnoDB atomic transaction. Concurrent export worker races, backup expiry, retention holds and cross-user upload/outside-process changes still need host-state tests and owner-defined operational handling.
- More than 20,000 owned export records hits a bounded traversal limit and **must be escalated for a controlled multi-pass procedure**; this is safer than claiming full erasure. Never delete job rows on a truncated pass.
- A failed metadata transaction after successful artifact unlink remains retry-safe as files are absent on the next pass; final UI must report partial/retained accurately.
- File23 does not own CF04's canonical object deletion, scanner, recovery, tombstone ledger or private clinical files.

## B — Exclusive ciphertext creation and no-overwrite guarantee

### Reproducible source defect: V12.1-STO05-EXPORT-02
Prior `SPDB_Export_Service::process()` called `file_put_contents($path,$sealed,LOCK_EX)` on predictable `export_ID.spdb` without refusing pre-existing file or symlink. A reused ID or filesystem link could overwrite existing ciphertext or follow a link outside the intended folder. Encryption does not eliminate that filesystem-integrity defect.

### Correction and proof
1. Encrypt with the existing AES-256-GCM `seal_content()` **before any report artifact write**.
2. Call private `write_ciphertext_exclusive()` to validate canonical filename and real directory, refuse pre-existing path/symlink, and create only via exclusive `fopen(...,'xb')`.
3. Check write length, flush, close, private mode `0600`, real-path containment. Failure removes a partial generated artifact and returns `WP_Error`, rather than overwriting.
4. Dedicated executable `tests/v12-1-export-exclusive-write-tests.php` verifies new-file content, same-ID retry refusal, retained original bytes, symlink destination untouched and invalid traversal filename denied.
5. Existing `tests/forty-round-review-gate-tests.php` was repaired because an old fixed-string check required the unsafe `file_put_contents` implementation. The new assertion demands correct **seal-before-exclusive-write** ordering, `xb` mode and absence of legacy overwrite call.

### Review limitations
- A concurrent actor who can write directly into the PHP worker's private directory can still attempt filesystem races. The exclusive create is a narrower source guarantee; real Hostinger owner/permission/isolation and HTTP denial must be proven.
- Legitimate export retry when an earlier crash left ciphertext will now fail closed until stale artifact reconciliation/cleanup. Do not silently overwrite it; operator recovery and idempotent job lifecycle need real staging tests.
- Cryptographic encryption/decryption uses WordPress salt; backup/key rotation recovery and checksum manifest require owner-controlled restore acceptance. Green CI proves neither Hostinger filesystem security nor end-to-end access control.

## Defect accounting after source review

| Category | Count | Status |
|---|---:|---|
| New source defects examined in this session | **2** | Both patched in Draft PR #29 |
| Previously patched source guard-integrity defect | **1** | Source patch already in PR #29 |
| Total targeted source corrections in PR #29 | **3** | Final exact-head CI and independent reviewer approval remain release gates |
| New executable suites this session | **2** | Personal erasure, exclusive ciphertext |
| Old continuity-test source check updated | **1** | Asserts stronger invariant, not bypassed |
| Open trusted deployed attestation owner integration | **1** | File23 issue #30 / File20 issue #45 |
| Existing other operational limitations | **13** | `F23-LIM-001..013`, all still require evidence |
| Proven live fixes | **0** | No live deployment/retest performed |

**Review decision:** retain Draft PR; release still blocked by external independent reviews, File20 attestator, Hostinger roles/cache/private URL, CF04/File00/24 integration, real restore/rollback, deployed checksum and DB/migration parity. Repeat CI on the exact final HEAD after all code/docs/test changes. Do not mark the live incident “resolved” from this source audit.
