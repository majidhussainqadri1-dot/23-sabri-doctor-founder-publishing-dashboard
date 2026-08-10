# File 23 — Future Publishing Intelligence Superset — 24 Enhancements

Date: 2026-08-10
Status: Founder-approved coding addendum for the File 23 feature branch; staging/live evidence remains separate.

## Governing law

File 23 remains the federated private publishing command center. These enhancements may orchestrate, project, summarize, simulate or provide advisory intelligence, but they do not take canonical ownership of File 00 identity/authorization, File 19 delivery truth, File 21 publication/comments/revisions, File 22 creation writes, File 24 security/privacy decisions, File 25 public presentation, File 26 search/discovery/ranking, patient records, raw analytics events, media binaries or clinical diagnosis/prescription authority.

All privileged actions remain subject to native owner authorization and File 00 server-side revalidation. AI output is advisory/draft-only. Donation/payment may not influence ranking, reach or intelligence recommendations.

## Approved requirements

| ID | Facility | Priority | File 23 implementation boundary |
|---|---|---|---|
| F23-FPI-01 | Publishing Mission Control | P0 | Prioritized federated action projection; no native-state mutation. |
| F23-FPI-02 | Experiment Lab | P0 | Governed experiment orchestration over provider-owned metrics; privacy-threshold suppression; no automatic winner application. |
| F23-FPI-03 | Best-Time-to-Publish Intelligence | P0 | Advisory windows from privacy-safe aggregates; no automatic scheduling. |
| F23-FPI-04 | Ask Publishing Dashboard | P0 | Read-only conversational answers through approved provider hook; no write execution. |
| F23-FPI-05 | Content Opportunity Radar | P0 | Demand/coverage projection; File 26 remains search/discovery/ranking owner. |
| F23-FPI-06 | Editorial Bottleneck Detector | P0 | Queue-stage delay analysis across provider projections. |
| F23-FPI-07 | Review SLA and Escalation Engine | P0 | SLA status projection; File 19 owns delivery/escalation notifications. |
| F23-FPI-08 | Semantic Change Impact Map | P0 | Downstream reference/dependency projection before correction/retraction. |
| F23-FPI-09 | Evidence Freshness Monitor | P0 | Broken/withdrawn/superseded/review-due evidence projection; native source registry remains owner. |
| F23-FPI-10 | Medical and Ethical Preflight | P0 | Advisory safety flags from approved policy/clinical providers; no diagnosis/prescription/dosage authority. |
| F23-FPI-11 | Privacy Leak Guard | P0 | Advisory privacy/security exposure flags; no raw sensitive-record ownership. |
| F23-FPI-12 | Role and Permission Simulator | P0 | Read-only authorization outcome projection; no impersonation or delegated authority creation. |
| F23-FPI-13 | Semantic Version Diff | P1 | Meaning-level change projection from native revision/evidence providers. |
| F23-FPI-14 | Editorial Playbooks | P1 | File 23 governed checklist metadata for successful cases, research, lessons, announcements, corrections and media. |
| F23-FPI-15 | Cross-Format Repurposing Studio | P1 | AI-assisted draft suggestions only; File 22/native owner executes final creation. |
| F23-FPI-16 | Audience Intelligence Board | P1 | Privacy-safe aggregate audience cohorts with minimum-threshold suppression. |
| F23-FPI-17 | Internal Benchmarking | P1 | Anonymous cohort comparison; donor/payment/visibility influence forbidden. |
| F23-FPI-18 | External Public Benchmark Watch | P1 | Lawful public-source comparison signals for knowledge opportunity only. |
| F23-FPI-19 | Comment and Question Intelligence | P1 | Privacy-safe recurring-question/complaint/correction/FAQ clusters from File 21 projections. |
| F23-FPI-20 | Evergreen Content Health | P1 | Fresh/review-soon/outdated/broken-evidence/superseded lifecycle projection. |
| F23-FPI-21 | Localization Command Center | P1 | Translation/terminology/source-version mismatch projection. |
| F23-FPI-22 | Accessibility Publishing Lab | P1 | Advisory pre-publication readiness for alt text, captions, headings, RTL/LTR and approved accessibility checks. |
| F23-FPI-23 | Content Provenance and Authenticity Ledger | P2 | Displays human/AI-assisted/translated/imported/authenticity metadata without duplicating source truth. |
| F23-FPI-24 | Editorial Digital Twin / What-if Planner | P2 | Read-only scenario simulation for calendar collisions, reviewer load and campaign distribution. |

## Runtime code map

- `includes/class-spdb-publishing-intelligence.php` — canonical File 23 service for the 24-feature catalog, sanitized federated signal contract, privacy thresholds, advisory evaluators, Ask hook and What-if simulation.
- `sabri-publishing-dashboard.php` — loads/registers the service and exposes `spdb_get_publishing_intelligence_catalog()`.
- `templates/analytics.php` — surfaces all 24 facilities inside the private Analytics workspace using existing accessible File 23 card patterns.
- `tests/publishing-intelligence-24-tests.php` — standalone regression suite covering exact 24 IDs, no canonical write authority, privacy suppression, File 26 ownership, donor neutrality, playbooks, safe AI degradation and read-only simulation.

## Private REST contract

- `GET /wp-json/spdb/v1/intelligence/catalog`
- `GET /wp-json/spdb/v1/intelligence/{feature}`
- `POST /wp-json/spdb/v1/intelligence/ask`
- `POST /wp-json/spdb/v1/intelligence/simulate`

The REST permission gate requires authenticated File 23 dashboard eligibility plus analytics capability. Feature snapshots consume sanitized signals via `spdb/publishing_intelligence_signals`. Conversational providers integrate through `spdb/publishing_intelligence_ask`.

## Hard safety invariants

1. `canonical_write_authority = false` for all 24 features.
2. `advisory_or_projection = true` for all 24 features.
3. Audience/benchmark/comment intelligence suppresses cohorts below the File 23 minimum privacy threshold (20 by default).
4. Experiment recommendations never auto-apply a winner.
5. Best-time recommendations never auto-schedule.
6. Ask Dashboard never executes an action.
7. What-if Planner never changes the real calendar.
8. Medical/ethical and privacy preflight remain advisory inputs to native owner/human review decisions.
9. File 26 remains canonical owner of search/discovery/ranking.
10. Paid/donor influence is explicitly excluded from opportunity/ranking intelligence.

## Completion truth

This addendum establishes code-level implementation on its feature branch. It does not itself prove Hostinger staging acceptance, deployed artifact parity, live database/migration state or production operational acceptance. Those remain separate release gates under the platform's Evidence-First and Live-First rules.
