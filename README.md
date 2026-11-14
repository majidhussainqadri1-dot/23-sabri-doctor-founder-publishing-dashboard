# File 23 — Doctor and Founder Publishing Dashboard

## V12.3 current source-only status, 14 November 2026 — 20 fixes, 210 mapped IDs

**Current in-progress candidate:** Draft PR #33 based on unmerged PR #29. **20 distinct red-first source defects** (DEF-001..020) have candidate corrective patches and tests in `docs/V12.3-DEFECT-LEDGER.json`. Source checkpoint `7df2d117db6e512892bb851710b63d54d62dc677` passed all five workflows; use exact current PR HEAD for any newer proof. No fix is verified on Hostinger Live.

**V12.3 evidence crosswalk:** `docs/V12.3-REQUIREMENT-CROSSWALK.json` has **210 unique requirement identities**: original 187 plus 11 Chapter 12 central CEN policies and 12 retained G01–G12. They are *identity/source-path coverage*, **not** 210 completed features or a percentage of verified V12.3 behavior.

**Latest source hardening DEF-015..020:** fresh queued-automation owner/approval/capability; strict immutable rule version; no audit chain genesis on failed SQL/malformed hash; exact-origin production provider acceptance both producer/registry; private export worker owner identity; idempotency owner and byte-equal payload in lookup/race. Synthetic PHP tests cover red-first/green checkpoints.

**Release:** **NO-GO**. Read `docs/V12.3-WP00-WP15-EXECUTION-REGISTER.md` and `docs/V12.3-INTERNAL-SOURCE-REVIEW-E-F-2026-11-14.md` for 16 work package states and independent review limits. Hostinger installed code/ZIP checksum, database/schema/migration, File20 attestation, 10 native owner contracts, File23 #30/#31/#32, 2 external reviews, Staging, Founder authorization and live parity/retest are **UNVERIFIED/PENDING**. No main merge or deploy. Earlier 14/187 and related values below are historical checkpoints.



## 14 November 2026 — later V12.3 source review (14 proven source corrections)

**This is source-only PR #33 work, not a deploy.** Compared to the earlier eight-fix checkpoint, six additional reproducible source failures were discovered and corrected with red-first PHP/Node tests:

- Failed native projection or cached SQL analytics could masquerade as healthy empty/live; typed errors, source completeness and UI warnings now differentiate failure and empty.
- Exported reports could silently discard unknown remaining pages/failed providers; `partial`/source metadata, human limitations and marked partial CSV status now persist.
- Impossible or missing cached GMT timestamps could become the current time; strict UTC round-trip validation rejects counterfeit freshness.
- More than 500 valid aggregate metrics were silently truncated; the cap remains but exposes `more_metrics_available` and incomplete status.
- An append-only audit health check read the entire audit database unbounded; bounded 500-row keyset verification with hash continuity, SQL-failure fail-closed and 100,000-event per-invocation scan budget replaces it.
- Native source `has_more=true` wrongly left `data_complete=true`; intermediate pages now explicitly remain incomplete.

**PR #33 cumulative:** 14 distinct new V12.3 source defects corrected; 187 first-pass requirement identity mappings; 0 live-verified fixes. The 100,000-event per-scan audit bound, CSV consumer contract, 3 open File23 external tickets, 10 owner contract checks, 13 historical limits, two *external* independent reviews, File20 true Hostinger attestation and Founder staging/live approval all remain pending.

Evidence: `docs/V12.3-DEFECT-LEDGER.json`, `docs/V12.3-WP00-WP15-EXECUTION-REGISTER.md`, `docs/V12.3-INTERNAL-SOURCE-REVIEW-C-D-2026-11-14.md`. Internal two-pass review is NOT the required pair of independent outside signoffs. **NO-GO: never merge or deploy on CI alone.**



## V12.3 evidence-first work (14 November 2026, Draft PR #33)

- The active governing source is Master Plan **V12.3**. The earlier 1.3.0 source/plan baseline is retained rather than silently renamed; `docs/V12.3-REQUIREMENT-CROSSWALK.json` maps 187 inherited/additive requirement IDs (including V12.3 Chapter 10's ten corrections) at **identity/path level, not behavioral acceptance**.
- `tests/v12-3-source-crosswalk-tests.php` enforces the corrected 187-ID census, exact M01–M12 to QA-15–QA-24 mapping and the explicit absence of staging/live proof.
- `assets/js/operations.js` rejects sensitive mutation dispatch without secure browser randomness, blocks in-flight duplicate submits and preserves numeric-looking or boolean-looking task/audit text. `includes/class-spdb-governance-service.php` interprets GMT-labelled dates in explicit UTC, rejects malformed dates and validates task/delegation user IDs canonically; `includes/class-spdb-operations-repository.php` rejects overflowing task-pagination offsets. `SPDB_Governance_Service::snapshot()` now records per-source read availability; `templates/tasks.php` announces task/delegation/rule SQL outages instead of falsely claiming empty lists. `tests/v12-3-browser-mutation-regression.js` is executed by baseline, full-plan and final-release CI.
- All 24 V12.3 FPI catalogue IDs are additionally traced to their dedicated source evaluators in `docs/V12.3-FPI24-SOURCE-AUDIT.json` with `tests/v12-3-fpi24-source-audit-tests.php`; this proves source mapping only, **not live native-owner readiness**.
- WP-00, the 16-work-package status and known defects are tracked in `docs/V12.3-LIVE-REALITY-FREEZE.json`, `docs/V12.3-DEFECT-LEDGER.json` and `docs/V12.3-WP00-WP15-EXECUTION-REGISTER.md`.
- **No approval for merge/deploy:** true installed artifact/DB/migration identity, File20 attestation, File23 issues #30/#31/#32, 10 native-owner contract gates, 13 known operational limitations, 2 genuinely independent reviews, Founder authorization, staging and live retest all remain unresolved. Repository checks must be read at the latest exact SHA.



Private, role-aware, federated publishing operations for the Sabri Social Homeopathy Platform.

## Governing boundary

File 23 is the operational command center. It does not own native publication bodies, Composer drafts, profile identities, review decisions, schedules, sources, media binaries, comments, corrections, notification delivery, clinical records, or raw analytics events.

- File 00: identity, capabilities, verification, suspension and MFA assertions.
- File 20: shell, global Safe Mode, platform repair and rollback.
- File 21: canonical social/news publication, review, interactions, corrections and sources.
- File 22: sole create/edit/draft/preview/submit orchestration.
- File 24: security/privacy/compliance assurance evidence.
- File 25: public visual/profile/timeline presentation.

## 1.3.0 governing-plan + Future Publishing Intelligence candidate

The source includes the forty-round hardened baseline, the 6–10 August governing-plan reconciliation, and the Founder-approved Future Publishing Intelligence Superset. The current corrective candidate is subjected to a new eighty-round review/fix closure before exact-head release evidence is accepted:

- Sources/evidence, asset usage, interactions, content gaps, revisions and publishing notifications as strict native projections.
- Provider-owned privacy-thresholded analytics and bounded rebuildable snapshots.
- Future Publishing Intelligence F23-FPI-01 through F23-FPI-24 with federated/advisory-only contracts, scope-safe authorization, privacy minimization, File 26 ranking ownership, File 19 escalation delivery ownership and side-effect-free simulation.
- Doctor/Founder reports and queued CSV, JSON, printable HTML, PDF and private-calendar exports encrypted at rest and delivered through owner-bound expiring signatures.
- Cross-module tasks, scoped MFA-bound delegation, bounded automation rules and a reviewed native-action execution boundary.
- Background jobs with idempotency, locks, retries, exponential backoff, dead-letter state and privacy-safe File 19 notifications.
- WordPress privacy export/erasure for File 23-owned data, retention cleanup, append-only audit integrity, local reversible repair and explicit staging acceptance evidence.
- Complete File 00–26 dependency manifest, File 24 sanitized assurance manifest and read-only File 04 migration diagnostics.
- Full private navigation and responsive, RTL and accessibility treatment.

## Eighty-round corrective review over the forty-round baseline

The historical forty-round hardening remains a regression baseline. The 10 August eighty-round pass additionally hardens all 24 Future Publishing Intelligence contracts: dashboard/scope authorization, Founder-only permission simulation, privacy-minimized provider signals, strict small-cohort suppression, timezone-safe advisory output, no raw reviewer/patient/comment leakage, File 19/24/25/26 ownership boundaries, deterministic What-if behavior, documentation parity and current-plan exact-head release evidence.

## Operational mutation integrity

Every File 23-owned operational write is protected by an explicit mutation guard requiring an approved dashboard account, REST nonce, same-origin browser context and a payload-bound idempotency key. Local writes use transaction commit/rollback with request/outcome evidence in the canonical hash-chained dashboard audit. Bounded non-autoload replay receipts are private, no-store, time-limited and remove credential-, secret-, patient-, clinical- and message-shaped fields. The browser retains one high-entropy key for one logical submission and retry.

Native review/calendar commands remain under their separate object-version, idempotency, audit-reason and provider-confirmation broker; File 23 does not replace their canonical owners.

## Truthful status

- Specified: complete.
- Coded: full File 23-owned 1.3.0 candidate including F23-FPI-01 through F23-FPI-24.
- Automated QA: exact-head GitHub Actions required.
- Staging accepted: pending Hostinger execution.
- Live deployed/operational: pending.

Production writes remain fail-closed until real providers independently attain the required acceptance maturity and the Hostinger staging matrix is recorded.
