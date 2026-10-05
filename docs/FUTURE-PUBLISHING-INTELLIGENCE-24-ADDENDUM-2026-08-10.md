# File 23 — Future Publishing Intelligence 24 Addendum

**Governing date:** 10 August 2026  
**Implementation reconciliation:** 5 October 2026  
**Status:** Repository/source implementation specification and code mapping. This document does not claim staging, live deployment, DB migration, or operational acceptance.

## Governing boundary

File 23 remains a private federated publishing operations and advisory command center. It does not become a second canonical backend. File 00 owns identity/authorization assertions; File 19 owns notification delivery; File 21 owns social/news publication, comments, revisions and correction truth; File 22 owns final create/edit/draft/preview/submit orchestration; File 24 owns cross-cutting assurance; File 25 owns public visual presentation; File 26 owns Search/Discovery/Ranking/Recommendations/Knowledge Graph/Classification.

Every intelligence feature has `canonical_write_authority=false`. Availability, AI output, a dashboard projection, or an intelligence recommendation is never authorization.

## Approved catalogue

1. F23-FPI-01 — Publishing Mission Control (P0)
2. F23-FPI-02 — Experiment Lab (P0)
3. F23-FPI-03 — Best-Time-to-Publish Intelligence (P0)
4. F23-FPI-04 — Ask Publishing Dashboard (P0)
5. F23-FPI-05 — Content Opportunity Radar (P0; File 26 remains canonical search/ranking owner)
6. F23-FPI-06 — Editorial Bottleneck Detector (P0)
7. F23-FPI-07 — Review SLA & Escalation Engine (P0; File 19 remains delivery owner)
8. F23-FPI-08 — Semantic Change Impact Map (P0)
9. F23-FPI-09 — Evidence Freshness Monitor (P0)
10. F23-FPI-10 — Medical & Ethical Preflight (P0)
11. F23-FPI-11 — Privacy Leak Guard (P0)
12. F23-FPI-12 — Role & Permission Simulator (P0)
13. F23-FPI-13 — Semantic Version Diff (P1)
14. F23-FPI-14 — Editorial Playbooks (P1)
15. F23-FPI-15 — Cross-Format Repurposing Studio (P1; final creation remains File 22-owned)
16. F23-FPI-16 — Audience Intelligence Board (P1)
17. F23-FPI-17 — Internal Benchmarking (P1)
18. F23-FPI-18 — External Public Benchmark Watch (P1)
19. F23-FPI-19 — Comment & Question Intelligence (P1)
20. F23-FPI-20 — Evergreen Content Health (P1)
21. F23-FPI-21 — Localization Command Center (P1)
22. F23-FPI-22 — Accessibility Publishing Lab (P1; File 25 remains public visual owner)
23. F23-FPI-23 — Content Provenance & Authenticity Ledger (P2)
24. F23-FPI-24 — Editorial Digital Twin / What-if Planner (P2)

## Runtime contracts

Private routes:

- `GET /wp-json/spdb/v1/intelligence/catalog`
- `GET /wp-json/spdb/v1/intelligence/{feature}`
- `POST /wp-json/spdb/v1/intelligence/ask`
- `POST /wp-json/spdb/v1/intelligence/simulate`

Federated signal filter: `spdb/publishing_intelligence_signals`.

Approved conversational provider filter: `spdb/publishing_intelligence_ask`. If no approved provider answers, the request fails safely and no action occurs.

## Privacy, medical safety, neutrality and ownership

The default minimum cohort threshold is 20 and may only be made stricter. Patient identifiers, private messages, appointment details, credentials/secrets and donor/payment/ranking-boost signals are rejected from the File 23 intelligence projection. No intelligence feature may diagnose, prescribe, recommend dosage/potency, guarantee cure, auto-publish, auto-schedule, impersonate a user, grant capability, alter a canonical owner record, or create paid/donor visibility advantage.

## Code mapping

- `includes/class-spdb-publishing-intelligence.php` — catalogue, private REST routes, sanitized federated signal intake, privacy suppression, Ask-provider boundary and read-only simulation.
- `sabri-publishing-dashboard.php` — public-safe helper `spdb_get_publishing_intelligence_catalog()`.
- `includes/class-spdb-plugin.php` — service registration.
- `templates/analytics.php` — accessible 24-feature catalogue presentation.
- `tests/publishing-intelligence-24-tests.php` — 24/24 IDs and architectural safety assertions.
- `includes/class-spdb-module-manifest.php` — File 26 relationship.
- `assets/css/dashboard.css` — Sabri Green primary visual token; orange retained only as secondary/contextual accent.
- `includes/class-spdb-admin-settings.php` — minimum cohort floor 20.

## Evidence law

Repository CI can establish source-level coding and automated-QA status only. Staging, live deployment, DB/schema state, migration state, backup/restore, rollback, real provider behavior, real role behavior and Founder acceptance require separate evidence.
