# File 23 — Version 1.3.0 Fresh Review A

**Review type:** first fresh post-amendment code/QA review and corrective cycle  
**Governing plan revision:** 2026-08-07  
**Baseline main before amendment work:** `a8a8c805f4730998ccb44bd95c87591836561759`  
**Review scope:** amended central plan + amended File 23 plan; File 00–26 ownership; F23-CEN-01; exact 70 inherited CV requirements; 16 acceptance journeys; source, tests, CI, packaging and release-state claims.  
**Review closure head at the end of the corrective cycle:** `01973c02ff9e49b5776fbbb8a8925d96b1732dfd`

## Review A method

This review did not treat an earlier green Version 1.2.0 run, old package, old forty-round record, PR existence or source-file presence as proof of current correctness. The amended plan was mapped to executable 1.3.0 code, then the changed code and its tests were reviewed for authorization, canonical ownership, stale assertions, privilege expansion, duplicate truth, design-token ownership and release overclaim.

## Findings and corrections

| ID | Finding | Severity | Root cause | Correction / evidence | Status |
|---|---|---|---|---|---|
| RA-01 | Capability installer regression still asserted schema `4` after the amended studio capability schema became `5`; it also omitted the existing-role Teacher Studio matrix. | High QA blocker | Test drift after capability change. | Updated `tests/capability-installer-tests.php`; added bounded `sabri_teacher` coverage, Admin/Teacher negative grants and schema-5/idempotency assertions. Commit `be12001a54ec55fc3100aeec128a11f10d04f576`. | Corrected |
| RA-02 | Full-plan completion gate still required plugin/readme Version `1.2.0`, so a correct 1.3.0 source would fail the release suite. | High QA blocker | Release-identity assertion drift. | Updated `tests/full-plan-completion-tests.php` to 1.3.0 and added Teacher/Admin/File26/current-governing-contract assertions. Commit `4cf05c6fabe89f603ca7ba80ad2936c6644fe939`. | Corrected |
| RA-03 | Historical forty-round continuity gate also hard-coded 1.2.0/schema-4/package markers. | High QA blocker | Historical evidence gate was incorrectly coupled to the old current version. | Updated it as a continuity gate for the old forty-round hardening evidence while verifying the present 1.3.0/schema-5/package workflow. Commit `9be949d7864a9e3fd943a33fafb714a6ed18f88e`. | Corrected |
| RA-04 | Admin Studio context advertised institution scope although the projection validator already reserved institution scope to the Founder. This was fail-closed but internally inconsistent and could cause a valid Admin provider projection to be rejected as a provider error. | High authorization/semantic defect | Admin operational studio was initially conflated with Founder institutional identity. | `SPDB_Role_Workspace_Service` now makes institution scope strictly `is_founder`; Admin summary explicitly states it does not inherit Founder scope. Commit `99569df3cd896fccc9e7df56dd029660a49bcbb5`. | Corrected |
| RA-05 | New Teacher/Admin studios lacked a dedicated end-to-end regression proving precedence and Founder-only action/scoping. | Medium regression gap | New role-studio feature outpaced the old Founder/Doctor-only test name/scope. | Added `tests/studio-boundary-tests.php`: explicit Admin/Teacher selection, Founder precedence, own-scope limits, Founder-only action denial and fail-closed institution projection to Admin. Commit `01973c02ff9e49b5776fbbb8a8925d96b1732dfd`. | Corrected |

## Amended-plan implementation checks retained after corrections

- File 23 remains a federated operational dashboard and does not write File 21/File 22/domain truth directly.
- File 26 is the Search/Discovery/Ranking owner; File 23 only consumes typed projection/destination contracts.
- File 25 remains design-token/public-visual owner; File 23 consumes the primary token with Sabri Green `#087A4E` fallback.
- Founder identity is resolved from File 00 before Admin/Teacher selector capabilities.
- Admin and Teacher Studio capabilities do not create WordPress roles or native-domain authority.
- Founder-only operations remain Founder-only.
- Pending/suspended/non-approved accounts remain restricted/read-only.
- Single-free-tier, donor-neutral, no-paid/donor-ranking-bias and education-only/no-diagnosis guardrails remain explicit.
- Repository/staging/live/operational states remain separate; no external acceptance was inferred.

## Review A conclusion

Review A found **5 actionable defects/gaps** and all five were corrected in the same cycle. No identified Review-A finding remains intentionally deferred. The resulting source must still pass the exact-head automated suite and a second independent fresh Review B after Review-A corrections before repository release closure can be claimed.
