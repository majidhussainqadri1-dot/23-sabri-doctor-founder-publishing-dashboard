# File 23 — V12.1 Fresh Source Review A/B, Defect Ledger and Acceptance Boundary
**Review date:** 2026-11-12 (PKT)  
**Review baseline:** PR #26 source HEAD `df59cb2be8c1a69dc3539bc7e0a58d5d79125ac3`.  
**Post-change source/test HEAD reviewed:** `3e549f766597e90f64bc9b5edccaf7cc6621880f`.  
**Scope:** child Draft PR #29 only; no production, migration or staging evidence.

> These are two documented, sequential source-review **passes** with different test lenses, not a claim of two independent external/human reviewers. Formal independent release sign-off and real Hostinger tests remain pending.

## Review A — private export attack surface

Read: `includes/class-spdb-export-service.php::private_directory()`, `download()`, `request_export()`, `cleanup_expired_files()` and the new executable `tests/v12-1-export-guard-integrity-tests.php`.

| Risk / invariant | Result at reviewed source |
|---|---|
| Existing `.htaccess` overwritten silently | **No**; mismatched content fails closed and remains available for investigation |
| Existing `.htaccess` / `web.config` content validated | **Yes**; `hash_equals` after regular/readable-file checks |
| Symlink guard accepted | **No**; rejected before file access |
| Missing protection file | Recreated and validated before returning the directory |
| Export encryption / owner HMAC / File 00 session MFA | Existing controls retained; no authority relaxation introduced |
| Backend-independent web access prevention | **Unverified**; depends on actual serving configuration, especially if `.htaccess` is ignored by a proxy or Nginx |
| Atomic no-race host integrity | Not certified; the guard verification is defense in depth, not filesystem containment proof |

**Result:** one earlier source defect reproduced by inspection, one targeted source correction, no additional confirmed source defect identified within Review A scope. Non-regular or corrupted guards now produce a specific error rather than a storage path.

## Review B — V12.1 governance, test isolation and release-truth audit

Read: governing V12.1 Chapter 12 STO-01–10/ACT-01–07; original 1.3.0 traceability JSON; appended `v12_1_amendment` block; `tests/v12-1-governing-traceability-tests.php`; `docs/V12.1-FILE23-SECURITY-RECONCILIATION-2026-11-12.md`; `docs/KNOWN-LIMITATIONS.md`.

| Question | Reviewed outcome |
|---|---|
| Count of inherited CV requirements retained | 70, unchanged |
| New V12.1 mappings | Exactly 10 STO + 7 ACT = **17 unique requirements** |
| F23-FPI 24 catalogue and 16 AJ journeys overwritten | **No**; existing baseline object preserved |
| File 23 becomes File 00/02 or CF04 canonical authority | **No**; mappings explicitly name the native owners |
| Source evidence paths | New executable test checks existence |
| Source mapping treated as live compliance | **No**; each row says staging pending/live unverified |
| Deploy parity or installed version invented | **No**; deployed version/hash/DB/migration explicitly unverified |
| Cross-module File 02/24/CF04 guarantees proven | **No**; deferred to actual owner contracts and staging |

**Result:** the dated documentation/traceability gap is addressed at source level, with a machine-executable guard. No additional confirmed File 23 source defect identified within Review B scope.

## Exact-head automated checks on source/test HEAD (before this audit-only commit)

- **Baseline Integrity #1517:** success on `3e549f766597e90f64bc9b5edccaf7cc6621880f`.
- **Full Plan Completion #568:** success on the same SHA.
- **Final Release Candidate #322:** success on the same SHA.

Those three automated results establish source/test workflow success on the **reviewed code/test commit**, not staging, production, or this later audit-document commit. Repeat exact-head CI after any further documentation or code change before considering repository closure.

## Reopened CI finding and immediate correction — 2026-11-12

After Review B, a third runtime consumer regression was added as `tests/v12-1-export-auth-consumer-tests.php`. It checks current File 00 two-factor, suspension, eligibility, approval and export-capability revocation at the File 23 consumer boundary. This did not change production source.

- Initial new test fixture duplicated `SPDB_Membership_Guard`, already loaded by `tests/bootstrap.php`. **Baseline Integrity #1519 and Full Plan Completion #570 failed** on PHP 8.0–8.3. This is a **test-fixture defect**, not a runtime export defect.
- Logs identified the exact PHP fatal class redefinition. Correction commit: `12ff57e14b105623615808357d8eeedf0acce327`; the test now uses an isolated File 00 mock rather than the full bootstrap.
- Same exact corrected commit passed **Baseline Integrity #1520**, **Full Plan Completion #571** and **Final Release Candidate #325**. All three workflows were independently checked as success.
- These notes make clear that the two review passes must be repeated if later production code changes, and that independent external review remains outstanding.

## Quantified result and remaining gates

- Confirmed File 23 source defects found in this targeted audit: **1** (`V12.1-GUARD-01`).
- Targeted source defects corrected: **1**.
- Targeted regressions added: **2 executable suites** (guard-integrity + 17-requirement mapping).
- Known uncorrected source defects in the narrow reviewed area: **0**, subject to independent review and exact final-head CI.
- Other V12.1 requirements not validated on real companion plugins: **17 mapped requirements**, with per-row source/owner statuses rather than treating every mapping as a bug.
- Existing staging/hosting/integration/operational limitations: **11** inherited `F23-LIM-001..011`, still subject to the existing evidence register.
- Independent release review and Founder sign-off: **pending**.
- Hostinger private file URL denial, active plugin package hash, DB schema, migration, backup/restore and real role journeys: **unverified**.

**Release decision:** Draft PR; DO NOT MERGE OR DEPLOY. This is a bounded source improvement, not a declaration of live safety or 100% completion.
