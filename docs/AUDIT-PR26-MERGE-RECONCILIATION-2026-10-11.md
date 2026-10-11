# PR #26 / Main Reconciliation — Read-Only Audit and Candidate Ledger
Date: 2026-10-11 (Asia/Karachi)
Scope: File 23 repository candidate only. No staging/live/deployment evidence.

## Frozen exact inputs
- Main: dcae138e6073f4d0ff596623deb05b9940b8271b (1.2.0)
- PR #26: 40124e3fd6efa9c062fc98813dbaaf024601225b (1.3.0)
- Merge base: a8a8c805f4730998ccb44bd95c87591836561759
- The main and PR Git trees have 16 paths independently changed since the base; 6 main-only paths also exist.
- Full File 23 10 August 2026 governing plan and the consolidated central plan were reviewed. They explicitly require source-of-truth boundaries, cohort minimum 20, no duplicate native owner, separate staging/live sign-off and safe merge.

## Three-way review ledger
1. baseline-integrity.yml — same concurrency change on both sides; PR candidate preserves it.
2. file23-final-release-candidate.yml — RECONCILED IN INTEGRATION SHA 57c84: File 20/24/25/26 exact checkout/contract checks and PR 1.3.0 packaging/review gates coexist; separate Final Release Candidate workflow run remains unobserved.
3. file23-full-plan-completion.yml — combined PR 1.3.0 suite with Main File 20/24/25/26 exact checkout and contracts; File 25 refreshed to 347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e. Candidate requires fresh CI.
4. CHANGELOG.md — PR history retained; 5 October historical review preserved in its original dated audit document.
5. README.md — PR 1.3.0 documentation retained; Main File 26/19/22 ownership reviewed against PR.
6. STATUS.md — PR status retained; not promoted to merged or deployed.
7. class-spdb-admin-settings.php — PR already preserves Main >=20 analytics cohort floor.
8. class-spdb-module-manifest.php — PR 00–26 manifest extended with Main File 26 search_ranking_owner alias.
9. readme.txt — PR 1.3.0 documentation retains the Main 24-feature and ownership boundaries.
10. sabri-publishing-dashboard.php — PR retains public-safe catalog helper and registers intelligence once.
11. templates/analytics.php — PR's accessible 24-feature UI retained; Main alternative UI not duplicated.
12. tests/final-deliverables-tests.php — RECONCILED IN INTEGRATION SHA 57c84: dated October audit/addendum gates and PR 1.3.0 checks coexist, with full-plan CI passing at that SHA.
13. tests/full-plan-completion-tests.php — PR 1.3.0 tests extended with Main File 26, >=20 privacy, Sabri Green, 24-feature checks.
14. FUTURE-PUBLISHING-INTELLIGENCE-24-ADDENDUM-2026-08-10.md — PR addendum extended with Main ownership, cohort, green token and release evidence law.
15. class-spdb-publishing-intelligence.php — PR 24-feature hardened implementation retained and extended with Main owner metadata, no-donor/no-autopublish invariants, and admin-configured stricter privacy cohort floor.
16. tests/publishing-intelligence-24-tests.php — PR tests extended with Main owner metadata and stricter admin privacy threshold regression.

## Main-only source reconciliation
- assets/css/dashboard.css — preserve Main Sabri Green primary token and orange secondary.
- includes/class-spdb-operational-mutation-guard.php — preserve Main exact intelligence ask/simulate mutation route policy.
- tests/bootstrap.php — preserve Main publishing intelligence bootstrap registration.
- tests/architecture-guard.php — preserve Main security guard, adapt 24-ID and privacy constant checks to PR 1.3.0 implementation.
- docs/AUDIT-20-ROUND-PLAN-RECONCILIATION-2026-10-05.md — preserve original historical review; do not count it as any of the new 20 independent sequential rounds.
- includes/class-spdb-plugin.php — choose PR version after manual comparison: Main added a duplicate intelligence instance/register hook while PR registers the static service in the plugin bootstrap. Avoid double REST registration.

## Outstanding integration defects and release gates
- M1: Repository-corrected in integration SHA 57c84, Full Plan CI passed; separate Final Release Candidate workflow execution remains UNVERIFIED.
- M2: Repository-corrected and Full Plan CI tested at integration SHA 57c84; dated 5 October audit and FPI addendum gates retained.
- M3: Legacy ID-to-slug mapping and Founder-only guard committed at 035138, but exact-SHA CI FAILED because the new route test stub omitted the leading slash in its route registry key. Production service registered both routes; fix test harness without weakening security checks, rerun exact-SHA CI.
- M4: Original PR #26 remains dirty/unmergeable; full new 20 sequential rounds, Final Release Candidate workflow, and staging/live/DB gates remain unverified. Prior 57c84 Full Plan CI and companion parity were successful, but 035138 CI failed.
- Do not merge PR #26 or deploy on this ledger alone.

## 20 sequential audit rounds
- New round 01: in progress; plan/trees/ownership/security/contracts and frozen ledger reviewed, M1/M2 corrected; M3 test-harness failure at exact SHA 035138; NOT GREEN.
- New rounds 02–20: NOT STARTED; never infer completion from historical 40/80-round review documents or a single green workflow.

## Live-first status
- Deployed plugin version: unknown.
- Deployed code SHA/checksum parity: unknown.
- DB/schema version: unknown.
- Migration state: unknown.
- Hostinger staging/live acceptance: unverified.

## 11 October 2026 — 11:20 PKT read-only reconciliation checkpoint
- Main dcae138e6073f4d0ff596623deb05b9940b8271b, original PR #26 40124e3fd6efa9c062fc98813dbaaf024601225b, integration 33751036b92121e7b1ad0e4601e2f9b173f620a7 verified unchanged.
- All 18 companion HEADs unchanged. Existing integration CI 10/10 green, but Final Release Candidate job has not been run at this SHA.
- Main final workflow File25 SHA is stale and contains a literal backslash-n between grep commands; do not copy either defect into the integration workflow.
- M1/M2 patch retains 1.3.0 and adds File20/24/25/26 exact contracts plus historical October audit and August FPI addendum checks. This ledger records intent, not proof of commit or CI.
- Round 01 NOT GREEN; rounds 02–20 NOT STARTED. No live/staging/DB evidence.

## 11 October 2026 — 14:16 PKT exact-SHA CI defect checkpoint
- Main dcae138e6073f4d0ff596623deb05b9940b8271b; original PR #26 40124e3fd6efa9c062fc98813dbaaf024601225b (dirty); integration 03513814d8838f6f94451c84d6ce364aae90c446.
- All 18 companion main HEADs re-fetched, 0 changes.
- New SHA 035138 Baseline Integrity run 38124377161 FAILED (PHP 8.0/8.1/8.2/8.3); Full Plan run 38124377169 FAILED (real-contracts SUCCESS, PHP matrix failed, canonical-package skipped).
- Job logs isolate three failed assertions in new tests/publishing-intelligence-legacy-route-tests.php: its register_rest_route stub stored namespace+route without a leading slash, while assertions used /spdb/v1/...; service registers both ID and slug routes correctly at source lines 73–74. Existing 124 security/privacy regressions passed. Correct test fixture normalization; do not weaken assertions.
- No Final Release Candidate exact-SHA run or staging/live/DB evidence. Round 01 NOT GREEN; rounds 02–20 NOT STARTED.


## 11 October 2026 — 20:22 PKT independent evidence checkpoint (supersedes older provisional status; historical entries retained)
- Main HEAD `dcae138e6073f4d0ff596623deb05b9940b8271b` (1.2.0), original open PR #26 HEAD `40124e3fd6efa9c062fc98813dbaaf024601225b` (1.3.0; mergeable=false), integration HEAD `862b42a0486899ecc4307c837ba990e1e5dc4705` (1.3.0). All three refs independently fetched; no new merge.
- Main...integration comparison: 74 commits ahead / 35 behind, merge base `a8a8c805f4730998ccb44bd95c87591836561759`, 61 changed paths. Sixteen original conflict paths remain different at blob level; this does not substitute for full semantic three-way reconciliation.
- All 18 companion Main refs re-fetched, **0 changes** versus the pinned source manifest. File00/03/04/05/06/09/10/11/12/16/17/19/20/21/22/24/25/26 exact-head parity holds.
- Current integration exact-SHA GitHub Actions: Baseline Integrity run `38146590269` **4/4 success**; Full Plan Completion run `38146590267` **6/6 success**, including real-contracts, PHP 8.0–8.3 and canonical-package; total **10/10**. Candidate ZIP artifact `file23-1.3.0-governing-plan-candidate`, size 274008 bytes, artifact digest `sha256:e7b43fb0b9ae5236e08ed95cc2a121378bad0500a95ad9f48503e46fdb603e9d`; **NOT** a deployed checksum.
- M1 source reconciled and general CI-tested; **separate Final Release Candidate workflow at exact integration SHA NOT OBSERVED**. M2 dated audit/addendum final-deliverables gates reconciled and tested. M3 legacy ID + slug GET and Founder-only/elevated permission regressions CI-tested. M6 File19 exact-SHA notification owner/contract gates and M7 eight companion SHA release evidence reconciled in commit `862b42a0486899ecc4307c837ba990e1e5dc4705`, general CI-tested; no separate final-release or staging/live evidence.
- M4 **OPEN**: original PR #26 remains dirty/unmergeable; no forced merge, no 2-parent merge and no production deployment. M5 historical ledger lines describing the earlier 035138 CI failure remain as historical facts; this newer checkpoint records the subsequent successful b8fd and 862b42 runs.
- Potential documentation drift: `docs/DATABASE-SCHEMA-MANIFEST.md` header says 1.2.0 while plugin says 1.3.0. Manifest explicitly identifies **distinct** operational schema 1.1.0 and collections schema 4; do not conflate plugin, document and installed DB versions. No live DB/migration proof.
- New sequential round **01 NOT GREEN** (original PR reconciliation, separate Final Release Candidate, full sequential audit, staging/DB/live gates outstanding); rounds **02–20 NOT STARTED**. Historical 20/40/80 reviews do not count.
- Deployed version/SHA/checksum UNKNOWN; DB/schema installed version UNKNOWN; migration state UNKNOWN; staging and live acceptance UNVERIFIED. Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
