# File 23 Release Sign-Off Form — Version 1.3.0

This form must remain **Pending** until every required evidence field is complete. A green CI run, merged source or built ZIP alone is not staging/live approval.

## Artifact identity

- Release version: `1.3.0`
- Governing plan revision represented: `2026-08-10` (original Version 1.3.0 baseline; preserve historical test/source truth)
- Additive governing V12.1 amendment: `2026-11-10` (File23 consumer mapping; not deployed/live acceptance)
- Exact Git head: ________________________________
- Source baseline main (12 Nov 2026): `dcae138e6073f4d0ff596623deb05b9940b8271b`; parent PR #26 head: `df59cb2be8c1a69dc3539bc7e0a58d5d79125ac3`; re-verify before sign-off
- Installable ZIP: `23-sabri-doctor-founder-publishing-dashboard-1.3.0.zip`
- Installable ZIP SHA-256: _________________________
- Complete-source ZIP: `23-Doctor-Founder-Publishing-Dashboard-Source-1.3.0.zip`
- Complete-source ZIP SHA-256: _____________________
- Source manifest SHA-256: _________________________
- CI run ID/reference: _____________________________
- Deployed artifact checksum/parity: _______________

## Environment / live-reality freeze

- Hostinger staging URL: ___________________________
- WordPress version: _______________________________
- PHP version: ____________________________________
- Database/schema version: _________________________
- Migration state: _________________________________
- LiteSpeed version/config evidence: _______________
- File 00 version/head: ____________________________
- File 19 version/head: ____________________________
- File 20 version/head: ____________________________
- File 21 version/head: ____________________________
- File 22 version/head: ____________________________
- File 24 version/head: ____________________________
- File 25 version/head: ____________________________
- File 26 version/head: ____________________________

## Repository acceptance evidence

- [ ] Exact final-head automated suites all pass with no unexplained skip.
- [ ] `tests/governing-plan-2026-tests.php` passes exact 70 CV IDs, F23-CEN-01, 16 AJ journeys and current owner/brand/studio guardrails.
- [ ] Fresh 1.3.0 Review A completed after coding; every found defect fixed and retested.
- [ ] Fresh 1.3.0 Review B completed after Review A fixes; every found defect fixed and retested.
- [ ] Historical forty-round baseline gate remains green; it is not substituted for the fresh 1.3.0 or current 80-round reviews.
- [ ] Current 2026-08-10 eighty-round review/fix record and executable gate pass on the exact final candidate head.
- [ ] F23-FPI-01–F23-FPI-24 security/privacy regression suite passes, including scope authorization, sensitive-data minimization, cohort suppression and side-effect-free simulation.
- [ ] Database schema/capability/data-ownership/threat-model/current-plan traceability documents reviewed.
- [ ] Deterministic installable/source packages and checksums generated from the exact accepted repository head.
- [ ] Zero unresolved repository blocker/critical defects; any residual risk explicitly documented.

## WP-12 V12.1 large-volume source QA and staging acceptance gates

- [ ] Source HEAD, SHA, CI and **two independent external reviews** of the final WP-12 implementation recorded; internal A/B review passes do not substitute for external approval.
- [ ] Live Hostinger/MySQL staging clone with >20,001 synthetic encrypted export rows proves that repeated WordPress privacy erasure pages resume with owner-specific signed checkpoints, DELETE/VERIFY passes and metadata deletion only at the end.
- [ ] Repeated and concurrent eraser requests, forged cursor, missing/modified HMAC, missing File00 authorization, MySQL GET_LOCK contention, unavailable SQL/filesystem and malicious symlink produce fail-closed outcomes with recoverable owner records.
- [ ] Export generators that have already rendered content may not publish ciphertext after an owner's privacy erasure starts; test critical-section locking and current owner/job reauthorization with genuine concurrent PHP workers.
- [ ] Retention job chain survives >20,001 rows, missed cron, retries, idempotent requeue, exception, dead-letter and backup/restore; frozen cutoff **and max SQL row ID** prevent later inserted rows (even earlier-expired ones) entering old deletion work.
- [ ] Real DB query plan, lock latency, 10k+ performance, backup/restore of old progress options, private-file HTTP denial through Hostinger/CDN and durable audit reviewed; attached dated test evidence required.
- [ ] File23 issue #31 not closed from source CI alone; File20/#45 and File23/#30 deployed artifact parity still pending.

## 13 November 2026 — Additional V12.1 WP-00/STO-07/schema validation gates

- [ ] Source exact final SHA passes all new `tests/v12-1-strict-private-download-path-tests.php`, `tests/v12-1-readonly-host-evidence-tests.php`, and `tests/v12-1-operations-schema-structure-tests.php` on PHP 8.0–8.3 where offered, **after** all follow-up commits.
- [ ] Strict private ciphertext download resolves only `export_ID.spdb` for that exact authorized job, refuses raw path normalization, malformed hash and same-directory symlinks, and refuses corrupted or missing bytes.
- [ ] All **ten operations tables** have actually measured required columns and indexes, not merely name/InnoDB/version-option equality. `SPDB_Operations_Schema::requirements()` must match current install DDL.
- [ ] Same-version recorded schema drift does **not** silently execute dbDelta on unrelated admin requests; exact deployed state/database snapshot preserved before any authorized migration repair.
- [ ] File23 read-only operator tool `tools/file23-readonly-host-evidence.php` is executed under authenticated **staging WP-CLI** only, not through HTTP; the output is stored privately with source helper checksum and operator identity.
- [ ] Measured installed tree fingerprint from the operator tool is independently compared with the **exact approved installable ZIP extraction** using the same relative-path+size+SHA-256 algorithm. Tool output `is_attestation=false` is not promoted to a verified attestation by assumption.
- [ ] Real DB tables/columns/indexes/type/collation, schema versions `1.1.0`/`4`, migration row state and deployment package match the File20 trusted measured provider; partial/failed migration blocks stage acceptance.
- [ ] Direct Hostinger **origin and CDN** unauthenticated GET and HEAD for synthetic ciphertext return 403/404 and no bytes; URL revocation, per-user cache and permission reversal are separately recorded.
- [ ] Two genuinely independent post-source reviews and Founder approval are attached. Internal analytical A/B notes are **not** a substitute.
- [ ] File23 #30 and File20 #45 remain unresolved until real trusted owner-attestation implementation, staging/live exact-byte parity and Founder sign-off; File23 #31 remains unresolved until real 20k+ staging/restore proof.

**Source handoffs:** `docs/AUDIT-V12.1-FILE23-SOURCE-REVIEWS-2026-11-13.md` and `docs/V12.1-FILE23-HOSTINGER-READONLY-PARITY-HANDOFF-2026-11-13.md`. These source files do not prove that a disconnected Jetpack site or an unconnected WPVibe account has access to deployed plugin bytes.

## Staging / deployment acceptance evidence

- [ ] Exact package/checksum installed; deployed-source parity verified.
- [ ] Direct unauthenticated access to File 23 private export ciphertext URLs is denied by the actual Hostinger origin and any CDN/proxy (do not infer from .htaccess contents).
- [ ] Same-version package/code replacement invalidates acceptance unless a trusted exact deployed-code attestation and DB/migration parity remain verified (see issue #30).
- [ ] Fresh install and supported upgrades pass; migration state verified.
- [ ] Founder/Doctor/Teacher/Admin/Reviewer/Pending/Suspended journeys pass for roles/capabilities actually present.
- [ ] File 19/20/21/22/24/25/26 and required companion integrations pass with real plugins.
- [ ] File 26 remains Search/Discovery/Ranking owner; no File 23 duplicate search/ranking backend exists.
- [ ] IDOR/CSRF/replay/concurrency/export/privacy/security negative tests pass.
- [ ] V12.1-STO-09: File 23 personal privacy erasure **retains export metadata** on storage/DB/invalid-reference/unlink/partial-batch failure; all deleted artifacts are confirmed before owner records are removed. Test real Hostinger permission failures and idempotent retries.
- [ ] V12.1-STO-09: expired export retention rejects symlinks and malformed references; never unlinks another owner's resolved target; never purges metadata on truncated 20,000-row cleanup.
- [ ] V12.1-STO-05/07: report ciphertext generated only after AES-GCM seal, created exclusively (no overwrite/symlink follow), chmod 0600 and checked for real-path containment; failed/retried jobs and interrupted artifacts have an operational reconciliation plan.
- [ ] Owner File20/Hostinger deployment attestor contract is independently implemented and tested (File20 issue #45, File23 issue #30); zero accepted staging state without real versioned source/package/schema/migration parity.
- [ ] LiteSpeed cross-user cache isolation passes.
- [ ] Mobile/tablet/desktop, RTL/LTR, keyboard, screen-reader, 200% zoom, contrast, reduced-motion and low-bandwidth tests pass.
- [ ] Real 10,000+ object performance/SLO evidence is accepted.
- [ ] Backup restore, rights/deletion reconciliation, migration and rollback rehearsal passes.
- [ ] Cache purge and post-deployment smoke tests pass.
- [ ] Founder staging visual/functional acceptance recorded.

## Evidence references

- Current-plan traceability: `docs/GOVERNING-PLAN-2026-TRACEABILITY.json`
- Role/capability matrix: `docs/CAPABILITY-MATRIX.md`
- Data ownership: `docs/DATA-OWNERSHIP-MATRIX.md`
- Provider/contracts: ______________________________
- Cache/privacy: __________________________________
- Accessibility/browser/device: ____________________
- Performance/load: _______________________________
- Backup/restore: __________________________________
- Migration/rollback: ______________________________
- Security/privacy review: _________________________
- Fresh Review A: _________________________________
- Fresh Review B: _________________________________
- V12.1 source Review A/B/C (same-author passes, not independent approvals): `docs/AUDIT-V12.1-STORAGE-EXPORT-REVIEW-2026-11-12.md`
- Independent post-change external reviews (two): **PENDING**
- Defect register and retests: ______________________
- Historical forty-round audit: `docs/AUDIT-40-ROUND-REVIEW-AND-CORRECTIONS-2026-08-04.md`
- Current eighty-round audit: `docs/AUDIT-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md`

## Approvals

| Responsibility | Name | Decision | Date/time PKT | Signature/reference |
|---|---|---|---|---|
| Engineering/release operator |  | Pending |  |  |
| Security/privacy assurance |  | Pending |  |  |
| Clinical/medical-safety boundary |  | Pending |  |  |
| Accessibility/visual acceptance |  | Pending |  |  |
| Founder | Dr. Allamah Majid Hussain Sabri | Pending |  |  |

## Merge and deployment authorization

- Repository PR number: ____________________________
- Approved merge method: ___________________________
- Expected/merged head SHA: ________________________
- Staging package/checksum: ________________________
- Deployment window: _______________________________
- Rollback window/owner: ___________________________
- Monitoring owner and thresholds: _________________
- Live deployed version: **UNVERIFIED**
- Live DB/migration state: **UNVERIFIED**
- Live verification status: **NOT VERIFIED**
- Final decision: **PENDING — DO NOT MERGE OR DEPLOY**
