# File 23 Test Report — Version 1.3.0 Candidate

## Scope

This report separates source/CI evidence from Hostinger staging and production evidence. Automated success does not replace real-role, browser, cache, load, recovery, deployment-parity or Founder acceptance. Version 1.3.0 specifically adds regression gates for the 6–7 August 2026 amended central/File 23 plans.

## Automated source gates

The final-release workflow must execute against the exact PR/head under review and fail on any unexplained error.

| Gate | Required evidence |
|---|---|
| PHP syntax | All repository PHP files pass `php -l` on PHP 8.0–8.3 where the matrix applies. |
| JavaScript syntax | Dashboard and operations JavaScript pass `node --check`. |
| Repository suites | Every `tests/*-tests.php` suite passes. |
| 2026 governing-plan gate | Exact 70 CV IDs, F23-CEN-01, 16 AJ journeys, File 00–26 ownership, File 26 search ownership, Sabri Green, studio capabilities and donor/free/AI guardrails pass `tests/governing-plan-2026-tests.php`. |
| Architecture guard | No duplicate/native-domain backend or direct companion mutation. |
| Full-plan traceability | Required routes, capabilities, schemas, services, integrations, ownership and release tooling present. |
| Security/adversarial | Nonce, same-origin, IDOR/replay/audit/privacy/export/delegation/automation controls pass. |
| Operational mutation model | 10,000+ deterministic modeled requests have stable unique fingerprints and safe transaction/audit boundaries. |
| File 00 contract | Exact pinned authority contract and minimum supported version verify. |
| File 21/22 contracts | Real pinned publication/composer contract suites pass. |
| File 25/File 26 boundaries | File 25 token ownership and File 26 Search/Discovery/Ranking ownership are consumed without duplicate backend. |
| Accessibility/cache static gates | Focus, RTL, reduced motion, reduced-data, forced colors and private/no-store markers present. |
| Reproducible installable package | Two clean Version 1.3.0 builds are byte-identical; ZIP/top folder/version/checksum verify. |
| Complete-source package | Git-tracked source/docs/tests/workflows packaged with source checksum and manifest. |
| Final deliverables | Every required plan deliverable exists, including `docs/GOVERNING-PLAN-2026-TRACEABILITY.json`. |
| Prior forty-round gate | Historical 40-round hardened baseline continues to pass; it is not a substitute for fresh 1.3.0 reviews. |
| Fresh 1.3.0 review law | Two separate fresh post-change review/fix records and their executable gate must pass before repository release closure. |

## Exact-head evidence law

The authoritative final source head, companion pins, package checksums, workflow run and artifact digest must be generated after the last source/documentation/review change. Historical Version 1.2.0 run data is not reused as Version 1.3.0 evidence. CI writes the exact head and pins to `FILE23-1.3.0-RELEASE-EVIDENCE.txt`; package checksums and per-file manifests are generated from that same checkout.

A green PR head does not prove a later merge commit is green. If merged, the exact resulting `main` HEAD must be checked separately before classifying the repository as exact-head Automated-QA Green.

## Mandatory Hostinger staging evidence

Pending until executed and attached to the release sign-off:

- exact deployed package/checksum and deployed-source parity;
- fresh install, supported upgrades, deactivation/reactivation and migration state;
- real Founder, Doctor, Teacher, Administrator, Reviewer, Pending and Suspended journeys for roles/capabilities actually present;
- real File 00/19/20/21/22/24/25/26 and optional-provider integrations;
- LiteSpeed cross-user cache isolation;
- desktop/mobile/tablet, RTL/LTR, keyboard, screen-reader, 200% zoom, contrast, reduced-motion and low-bandwidth acceptance;
- real 10,000+ object database measurements and response/query budgets;
- provider/cron/queue/database/filesystem failure and graceful-degradation tests;
- backup restore, deletion/rights reconciliation and rollback rehearsal;
- Founder visual/functional staging acceptance.

## Defect policy

Every discovered defect is recorded with requirement, severity, root cause, fixing commit and affected regression. Review continues as **Review → Fix → Fresh Review** until zero known unresolved blocker/critical defects remain for repository release scope, or a specific residual risk is explicitly accepted by the Founder. No source/CI result is represented as staging/live/operational evidence.
