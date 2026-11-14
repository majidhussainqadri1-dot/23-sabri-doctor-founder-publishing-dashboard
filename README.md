# File 23 — Doctor and Founder Publishing Dashboard

## V12.3 evidence-first work (14 November 2026, Draft PR #33)

- The active governing source is Master Plan **V12.3**. The earlier 1.3.0 source/plan baseline is retained rather than silently renamed; `docs/V12.3-REQUIREMENT-CROSSWALK.json` maps 187 inherited/additive requirement IDs (including V12.3 Chapter 10's ten corrections) at **identity/path level, not behavioral acceptance**.
- `tests/v12-3-source-crosswalk-tests.php` enforces the corrected 187-ID census, exact M01–M12 to QA-15–QA-24 mapping and the explicit absence of staging/live proof.
- `assets/js/operations.js` rejects sensitive mutation dispatch without secure browser randomness, blocks in-flight duplicate submits and preserves numeric-looking or boolean-looking task/audit text. `includes/class-spdb-governance-service.php` interprets GMT-labelled dates in explicit UTC, rejects malformed dates and validates task/delegation user IDs canonically; `includes/class-spdb-operations-repository.php` rejects overflowing task-pagination offsets. `tests/v12-3-browser-mutation-regression.js` is executed by baseline, full-plan and final-release CI.
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
