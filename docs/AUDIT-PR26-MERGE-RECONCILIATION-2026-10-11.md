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


## 12 October 2026 — Source-confirmed privacy and staging-rehearsal findings

The source checks below are **not** twenty completed independent green audit rounds. Main, original PR #26, integration and the 18 companion HEADs must remain separate immutable audit inputs. No production/staging evidence or deployed code parity is available.

- **M8 critical / cached analytics:** `includes/class-spdb-operations-service.php` cached fallback previously returned `$snapshot['value']` even with `suppressed=true`; `SPDB_Operations_Repository::normalize_metric_row()` decoded old snapshots under historical threshold >=2 and `store_metric_snapshot()` serialized values before computing the minimum cohort. Proposed isolated integration correction masks at persist/read/REST boundaries under `max(20, configured admin threshold, stored threshold)`. Do not claim live exploitation or resolved production defect.
- **M9 / scope isolation:** `SPDB_Operations_Repository::list_metric_snapshots()` institution query lacked `scope='institution'` while own query filtered by owner and scope; this could allow doctor-own aggregates into an institutional fallback. Preserve capabilities and File 00 authorizations; institution reads only institution-scoped snapshots. Verify multi-doctor dynamic regression.
- **M10 / stale release runbook:** `docs/STAGING-BACKUP-RESTORE-MIGRATION-ROLLBACK-RUNBOOK.md` incorrectly said 3 operational tables, upgrade to 1.0.0 and schema version 3, while `docs/DATABASE-SCHEMA-MANIFEST.md` lists **13** File 23-owned tables, operational schema **1.1.0** and collections schema **4**; candidate plugin version is 1.3.0. Old File 00/21/22 runtime version assumptions must be measured on staging rather than presumed from a repository. Correct staging checklist, migration/rollback rehearsal, privacy and scope fixtures without asserting live acceptance.
- **Dynamic regression:** `tests/analytics-cached-privacy-tests.php` must exercise storage, historical read and REST masking at cohort 8/20, configured admin threshold 50 with cohort 30/60, and doctor A/B versus institutional scope. Only count as tested after exact new commit PHP 8.0–8.3 CI passes.
- **Release/merge gate:** Original PR #26 is unmergeable relative to current Main. The isolated integration branch is a provisional review candidate, not a merged PR, staging package or deployed build. Separate Final Release Candidate workflow and exact Main semantic reconciliation remain unresolved.
- **Sequential rounds:** R01 incomplete/NOT GREEN until frozen source audit, exact SHA dynamic CI, safe original-PR reconciliation and staging acceptance; R02–R20 NOT STARTED. Prior 20/40/80/320 topic reviews do not count. A privacy-specific fix/CI does not establish full plan completion.
- **Live-first:** Repository HEAD must be listed apart from Deployed Version (UNKNOWN), DB Version (UNKNOWN), Migration State (UNKNOWN), and Live Verification Status (UNVERIFIED).

## 2026-10-12 — New review, additional File 23 FPI-02 privacy defect (M11)

- Evidence freeze: source `includes/class-spdb-publishing-intelligence.php` exact blob `ff6b93ee3f8377d4cfce9eaa7ed74b93da6717bf`, method `experiments()` checks only total experiment `cohort_count`; method `privacy_safe_metric_tree()` previously returned nested metric numeric values without independently enforcing nested arm cohort. An experiment with a total cohort >=20 could include an 8-person arm exposing a raw numeric `value`. Existing tests covered total small experiment suppression but did not cover nested arm cohorts.
- Plan basis: File 23 updated 2026-08-10, F23-FPI-02 Experiment Lab requires privacy-threshold protection for experiments, no automatically applied winner, and governed provider-owned metrics; central privacy minimum >=20, stricter authorized admin setting governs.
- Proposed correction: each nested variant metric arm with `cohort_count` or `observations` must independently meet the current privacy threshold; metric-bearing arms with no cohort proof fail closed to `suppressed=true`. Nonmetric metadata remains advisory; donor/auto-publish and ownership boundaries unchanged.
- Regression: `tests/publishing-intelligence-24-tests.php` exercises total 120 with nested arm cohort 8 and 60, unknown cohort, plus current threshold 50 with arm counts 30 and 60. Do not mark code corrected until these tests and the full exact-new-SHA workflows pass.
- Review status: Source/comparison and plan check are **partial audit round 01, NOT GREEN**. Subsequent genuinely comprehensive independent rounds 02–20 NOT STARTED. Current repository candidate is NOT live state. Original PR #26 remains OPEN/unmergeable against Main at time of review. No force-merge or deployment authorized.

## 2026-10-12 — M12 source/document release-version parity
- Freeze: plugin entry file declares `SPDB_VERSION=1.3.0` at current candidate, but `tests/bootstrap.php` defined `SPDB_VERSION=1.2.0` and `docs/DATABASE-SCHEMA-MANIFEST.md` heading still said `Version 1.2.0`. This is a test-environment/document release parity defect, not evidence of a live database schema defect.
- Correct isolated repository source/test bootstrap and manifest heading to 1.3.0, while explicitly preserving operational schema `1.1.0` and collections schema `4`. Add executable final-deliverables regression for version parity and physical-schema distinction.
- Require fresh exact SHA full CI and final release package before source gate closure. Original PR #26/Main semantic merge and deployed/staging/DB evidence remain separate and unverified. Do not count this targeted check as a full independent 20-round review.

## 12 October 2026 — Candidate-only two-parent semantic reconciliation gate (M4)

**Exact frozen source references:** Current Main `dcae138e6073f4d0ff596623deb05b9940b8271b`; isolated File23 candidate `662dbf55ea29c9921435337119161225f641c864`; merge base `a8a8c805f4730998ccb44bd95c87591836561759`. Do not use the original PR base metadata as the current Main HEAD.

Git-tree 3-way classification: 162 unchanged, 42 candidate-only, 4 byte-identical changes on both branches, 18 differing blobs changed on both branches and 1 Main-only changed source. The Main-only path `includes/class-spdb-plugin.php` registers an instance of `SPDB_Publishing_Intelligence`, while current File23 candidate registers the static service once from `sabri-publishing-dashboard.php`. Importing both would double-register private REST callbacks. Keep the existing single static registration and its complete current route/security test coverage.

**Semantic conflict resolution plan (all 18 divergent paths, no blind overwrite):**
- `.github/workflows/baseline-integrity.yml`, `.github/workflows/file23-final-release-candidate.yml`, `.github/workflows/file23-full-plan-completion.yml`: preserve tested candidate 1.3.0 workflows, fixed File25 and File00/19/20/21/22/24/25/26 pinned exact-head contract checkouts, release package creation and version gates; obsolete Main 1.2.0 artifact names are not carried into the current release.
- `CHANGELOG.md`, `README.md`, `STATUS.md`, `readme.txt`: retain current 1.3.0 candidate documentation and the separately preserved historical October 5 audit. Do not elevate a historical Main audit to a new 20-round review.
- `includes/class-spdb-admin-settings.php`, `includes/class-spdb-module-manifest.php`, `includes/class-spdb-publishing-intelligence.php`, `sabri-publishing-dashboard.php`, `templates/analytics.php`: retain candidate's 1.3.0 complete 24-feature implementation, >=20 and stricter admin cohort privacy, File 26 ranking ownership, File 19 notification, File 22 composer, File 24 assurance, File 25 visual, neutral donors, safe single registration and founder-only permissions.
- `tests/architecture-guard.php`, `tests/bootstrap.php`, `tests/final-deliverables-tests.php`, `tests/full-plan-completion-tests.php`, `tests/publishing-intelligence-24-tests.php`: preserve the tested 1.3.0 gate including M8/M9/M11/M12 regressions, current PHP 8.0–8.3 CI, and bootstrap/plugin version parity.
- `docs/FUTURE-PUBLISHING-INTELLIGENCE-24-ADDENDUM-2026-08-10.md`: retain the later approved 10-Aug amendment and its current canonical owner safety boundaries.

**Byte-identical Main improvements preserved:** `assets/css/dashboard.css`, `includes/class-spdb-operational-mutation-guard.php`, `templates/dashboard.php`, `docs/AUDIT-20-ROUND-PLAN-RECONCILIATION-2026-10-05.md`.

**Operation guard:** Construct a non-forced **isolated integration-branch-only** two-parent merge candidate with first parent current candidate SHA and second parent frozen Main SHA; preserve reviewed candidate source tree except this ledger. Run full exact merge SHA CI and check branch parity before ever fast-forwarding the original PR. Do not merge to Main or deploy/stage on repository-only evidence. A successful merge candidate does not itself complete twenty independent full audits; R01 not accepted and R02–R20 not started until each complete and passed.

**Deployed Version:** UNKNOWN. **Installed DB/Schema Version:** UNKNOWN. **Migration State:** UNKNOWN. **Live Verification Status:** UNVERIFIED.

## 12 Oct 2026 — new genuinely sequential review R01: M13 malformed provider cohort

- New independent review R01 froze exact Main `dcae138e6073f4d0ff596623deb05b9940b8271b`, original PR+isolated Integration `ff322080bc4d16c45082e7c689d3479fbb8fbc04` and 18 unchanged companion owner Main heads. Reviewed current Library/Project File23+central plans, 10-Aug FPI addendum and source code. Previous Oct12 20/20 sweeps are historical and **do not count** in this audit run.
- M13 **source-proven and dynamically reproduced**: FPI methods `experiments()`, `best_time()`, `audience()`, `internal_benchmark()`, `comment_intelligence()`, `privacy_thresholded()` and nested `privacy_safe_metric_tree()` used PHP loose integer casts of provider counts. Repro: `cohort_count='20people'` exposed an audience-board numeric value with admin cohort 20 and `observations='25fake'` generated best-time windows. As the counts are malformed, this incorrectly treats unproven audiences as above minimum privacy cohort.
- Safe coherent correction: `SPDB_Publishing_Intelligence::verified_cohort_count()` accepts only real nonnegative ints or canonical unsigned decimal strings up to nine digits, rejecting suffixes, floats and exponents (unproven count=>0). All relevant metric/cohort branches use this helper. Add dynamic M13 regressions in `tests/publishing-intelligence-24-tests.php`: malformed audience, experiment parent/arm, best-time, comment, benchmark and stricter admin threshold 50; valid canonical decimal string preserved for compatibility.
- **R01 acceptance gate:** Do not mark GREEN until full exact-new-SHA PHP8.0–8.3 CI and release artifact pass. Continue sequential R02–R20 after correction and frozen ledger; no Main merge or live change without separate acceptance.
- Repository HEAD is not deployed code. Deployed Version UNKNOWN; DB Version UNKNOWN; Migration State UNKNOWN; Live Verification UNVERIFIED.

## 2026-10-12 Evening — M14 newly reproduced nested FPI-02 privacy boundary
- Frozen repository Main `dcae138e6073f4d0ff596623deb05b9940b8271b`, PR+integration `b8c29b4d640e9e68e684a04000a8ec021c4a419f`; all 18 companion Main HEADs still exact pinned. This is a new audit run; prior 20/40/80/320-round evidence cannot count.
- **Source- and dynamically-proven M14**: `SPDB_Publishing_Intelligence::privacy_safe_metric_tree()` failed to suppress an arm `array('custom_ctr' => '0.975')` with no arm cohort; the numeric string/unknown provider key bypassed `contains_numeric_measure` and fixed `contains_metric` checks. Reproduced with parent experiment cohort 120; raw arm value was returned. Root-level scalar arm values also inherited parent cohort and could be exposed. This is a conditional source privacy flaw, NOT a demonstrated live exposure.
- Coherent fail-closed remedy: require independent verified `cohort_count` or `observations` for every nested arm/metric group, including unknown provider-defined metric keys; root scalar arms are suppressed rather than inheriting the parent count. Current admin minimum is honored; global minimum remains >=20; no new native ownership, autonomous publication, search ranking, donor or capability paths.
- Added 7 dynamic M14 assertions to `tests/publishing-intelligence-24-tests.php` covering numeric string arm without count, root numeric/string scalar, group cohort 8, valid independent cohort 60, unproven nested metric, and configured admin minimum 50 with cohorts 30/60.
- Require exact-new-SHA PHP matrix, real native contracts, deterministic packaging, and subsequent 20 new sequential full source/plan audits before source acceptance. Production Version UNKNOWN; DB Version UNKNOWN; Migration State UNKNOWN; Live Verification Status UNVERIFIED.
