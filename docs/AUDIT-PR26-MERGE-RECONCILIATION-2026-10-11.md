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
2. file23-final-release-candidate.yml — BLOCKED: Main includes File 20/24/25/26 exact checkout/contract checks, PR includes later 1.3.0 packaging and reviews. Both sets must coexist in a future verified file revision.
3. file23-full-plan-completion.yml — combined PR 1.3.0 suite with Main File 20/24/25/26 exact checkout and contracts; File 25 refreshed to 347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e. Candidate requires fresh CI.
4. CHANGELOG.md — PR history retained; 5 October historical review preserved in its original dated audit document.
5. README.md — PR 1.3.0 documentation retained; Main File 26/19/22 ownership reviewed against PR.
6. STATUS.md — PR status retained; not promoted to merged or deployed.
7. class-spdb-admin-settings.php — PR already preserves Main >=20 analytics cohort floor.
8. class-spdb-module-manifest.php — PR 00–26 manifest extended with Main File 26 search_ranking_owner alias.
9. readme.txt — PR 1.3.0 documentation retains the Main 24-feature and ownership boundaries.
10. sabri-publishing-dashboard.php — PR retains public-safe catalog helper and registers intelligence once.
11. templates/analytics.php — PR's accessible 24-feature UI retained; Main alternative UI not duplicated.
12. tests/final-deliverables-tests.php — BLOCKED: Main added dated audit/addendum file gates; PR retains 1.3.0 gates. Both should be reconciled.
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
- M1: Final Release Candidate workflow must retain both Main's File 20/24/25/26 contract checks and PR's 1.3.0 package/review gates.
- M2: Final-deliverables tests must also gate the dated 5 October audit and Future Publishing Intelligence addendum.
- M3: Main's F23-FPI-ID REST feature selector and PR's slug-based selector require compatibility/authorization review; do not add an unvalidated alias that could bypass Founder-only feature permissions.
- M4: Candidate exact-head CI, 18 companion contract parity, canonical package, full new 20 sequential reviews and all staging/live gates remain separately unverified until tested.
- Do not merge PR #26 or deploy on this ledger alone.

## 20 sequential audit rounds
- New round 01: in progress; read-only plan/trees/ownership/security/contract review and defect ledger frozen; correction candidate prepared, exact-head CI pending.
- New rounds 02–20: NOT STARTED; never infer completion from historical 40/80-round review documents or a single green workflow.

## Live-first status
- Deployed plugin version: unknown.
- Deployed code SHA/checksum parity: unknown.
- DB/schema version: unknown.
- Migration state: unknown.
- Hostinger staging/live acceptance: unverified.
