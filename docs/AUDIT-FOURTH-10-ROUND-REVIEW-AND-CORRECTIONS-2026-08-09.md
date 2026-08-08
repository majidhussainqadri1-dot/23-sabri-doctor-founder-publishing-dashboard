# File 23 — Fourth Fresh Ten-Round Review and Corrections — 2026-08-09

## Governing basis

This fourth independent ten-round review follows the Version 1.2.4 candidate and applies the current consolidated central plan, the current File 23 federated-dashboard specification, the current File 00 identity/session/publishing contract, current reviewed File 21/File 22 integration heads, canonical ownership, fail-closed authorization and the zero-known-unresolved-defect release law.

Every evidence-backed defect was corrected and retested before moving to the next round. Source/code completion remains distinct from Hostinger staging acceptance, live deployment and operational acceptance.

## Round ledger

| Round | Review theme | Finding | Immediate correction | Post-fix status |
|---:|---|---|---|---|
| 1 | Canonical File 00 doctor/trusted-publisher identity | **DEFECT** — verified-doctor detection still allowed `membership_type=doctor` fallback and trusted-publisher identity mixed legacy/session semantics with canonical publishing authority. | Removed membership-type doctor fallback; canonical `publishing.doctor_verification_claim` / `authority_class=verified_doctor` is authoritative; trusted publisher is authority-class based; Founder derives from canonical authority with legacy compatibility only where necessary. | Corrected |
| 2 | Writable workspace and current session assurance | **DEFECT** — approved Founder/Doctor/reviewer/moderator workspaces could be treated as writable without current File 00 strong-session assurance; Create visibility did not require canonical `can_open_composer`. | Bound writable human workspaces to `has_sensitive_session()`; institutional AI always read-only; Create requires current File 00 composer assertion; sensitive navigation is strong-session gated. | Corrected |
| 3 | Independent role-workspace projection | **DEFECT** — `SPDB_Role_Workspace_Service::derive_context()` independently fell approved non-Founder/non-trusted accounts through to Doctor context. | Added explicit institutional-AI, Founder, verified Doctor/trusted Doctor, Reviewer and Moderator classification; generic approved non-doctors now remain restricted. | Corrected |
| 4 | Sensitive File 23 operational REST mutations | **DEFECT** — several operational mutation routes relied on WordPress capability/nonce but did not centrally require current File 00 strong-session assurance. | Added `SPDB_Sensitive_Session_Guard`, registered before dispatch, and required current File 00 strong-session assurance for sensitive operational mutations; export download reauthorization also requires strong session. | Corrected |
| 5 | Native review/calendar mutations and browser origin | **DEFECT** — review approve/request-changes/reject/assign and calendar schedule/reschedule/unschedule were outside the central mutation route set. | Added those native mutation routes to the sensitive-session guard and required same-origin browser evidence as well as strong session. | Corrected |
| 6 | Exact File 21 companion-contract drift | **DEFECT** — release workflows still pinned File 21 to `d00f60ce...` while the latest reviewed File 21 head had advanced. | Refreshed File 21 exact integration pin to `e9bf82f78fc7b4b327ac4c6be4ed7147ea155cd8`; retained reviewed File 00 and File 22 pins. | Corrected |
| 7 | Stale WordPress doctor capability vs canonical File 00 identity | **DEFECT** — a migrated/stale WordPress doctor role could retain File 23 publishing capabilities even when File 00 no longer asserted current Founder/verified-Doctor identity. | Publishing-identity capabilities now additionally require current File 00 Founder or verified-Doctor identity; sensitive capabilities use canonical `has_sensitive_session()` so institutional AI cannot inherit human sensitive authority. | Corrected |
| 8 | Historical regression-gate drift | **DEFECT** — the second-ten-round regression test froze the **current** File 21 pin to its historical SHA; refreshing File 21 correctly therefore failed exact-head CI. | Preserved the historical audit SHA only inside the immutable historical audit while making the live regression gate require an immutable 40-character File 21 SHA that may advance to a later reviewed head. | Corrected |
| 9 | Release-truth / upstream identity dependency | **DEFECT** — the limitations register was stale and did not explicitly surface that the currently pinned File 00 head has a separate engineering audit reporting unresolved Critical/High defects; contract compatibility could be mistaken for production safety. | Rewrote the limitation scope, added an explicit File 00 production blocker, added it to release sign-off, and clarified that green File 23 CI cannot close a required dependency's production defects. | Corrected |
| 10 | Immutable release identity and permanent regression evidence | **DEFECT** — after the new source/security/documentation corrections, Version 1.2.4 could no longer truthfully identify an immutable corrected artifact; workflows/tests/build metadata also needed a permanent fourth-review gate. | Promoted runtime/package identity to Version `1.2.5`; updated readme/changelog/build/release evidence; made the third-review gate forward-compatible; added this audit and the permanent fourth-ten-round regression gate to CI/package evidence. | Corrected |

## Result

- Rounds with defects: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**.
- Rounds without a newly discovered defect: **none**.
- Defect-bearing rounds: **10 of 10**.
- Known unresolved **File 23 source defects in this review scope after correction**: **0**, subject to final exact-head CI.
- Production acceptance remains blocked independently by required staging evidence and by the currently documented File 00 production-blocking audit findings until a corrected/accepted File 00 head is pinned and the integration/real-role gates are rerun.

## Release truth

This audit does not claim staging, live or operational completion. Hostinger fresh install/upgrade, real companion-plugin integration, Founder/verified Doctor/Reviewer/Pending/Suspended/institutional-AI real-role tests, IDOR/security negative paths, LiteSpeed isolation, browsers/devices/RTL/accessibility, 10,000+ object performance, backup/restore, migration/rollback rehearsal, corrected File 00 production-safety evidence and Founder acceptance remain mandatory before production completion.
