# File 23 Requirements-to-Code Traceability Matrix — Version 1.2.0

Canonical plans:

1. Sabri Social Homeopathy Platform Definitive Master Plan v3.0.
2. Consolidated All-Chats Recovered Directive Register v2.1 — 5 August 2026.
3. File 23 Final Central-Plan-Harmonized v3.0.

| Requirement | Implemented evidence | Automated / staging evidence |
|---|---|---|
| F23-R001 Private authenticated noindex/no-store dashboard | Dashboard router, membership guard, cache/index headers | Dashboard-core and cache-boundary tests; LiteSpeed staging pending |
| F23-R002 Every action rechecks server assertions and object version | Capabilities, mutation guard, operation broker, native re-read | Authorization/stale/replay tests |
| F23-R003 No duplicate native backend | Two bounded schemas and architecture guard | Architecture/schema inspection |
| F23-R004 Full 00–25 manifest and compatibility | Module manifest and adapter registry | Full-plan/activation health tests |
| F23-R005 File 21 canonical publication/review | Versioned provider contract and broker | Pinned real File 21 contract suite |
| F23-R006 File 22 sole create/edit orchestration | Create route/deep link only; no File 23 editor | Pinned real File 22 and duplicate-form tests |
| F23-R007 Founder direct publish only through native policy | Founder workspace plus native operation declaration/authorization | Policy and negative operation tests; real staging pending |
| F23-R008 Verified doctor defaults to review | File 00 state/capability and native workflow contracts | Role tests; real account staging pending |
| F23-R009 Trusted direct publish explicit allowlist | Native operation definition and File 00/21 capability gates | Capability/category boundary tests |
| F23-R010 Pending/suspended denied/read-only | Restricted capability set and workspace resolver | Lifecycle/dashboard tests; real accounts pending |
| F23-R011 Scoped delegation expiry/revocation/MFA/audit | Governance service and delegation table | Delegation adversarial tests |
| F23-R012 Immutable provider/object references | Inventory, collections/items/links and native reference registry | Delete/rebuild/reconcile tests |
| F23-R013 Fresh/unknown/stale projections | Projection validators and provider isolation | Outage/throwing-adapter tests |
| F23-R014 Review inbox remains projection | Review/calendar service and operation broker | Decision-source/architecture tests |
| F23-R015 Native calendar/timezone/status validation | Review/calendar adapter contract and validator | Timezone/conflict/version tests |
| F23-R016 Collections/campaigns store pointers | Collections schema/policy/service | Architecture and collection tests |
| F23-R017 Knowledge promotion routes to owners | Knowledge links and native destinations | Cross-link/provider tests |
| F23-R018 Sources/evidence from native registries | Operational projection provider and validators | Permission/version/outage tests |
| F23-R019 Asset viewer stores no media/private URL | Projection validator, data-ownership guard | Schema/log/privacy tests |
| F23-R020 Interactions use native APIs | Operational provider contract and native destinations | Provider/architecture tests |
| F23-R021 Aggregate analytics only | Analytics provider, metric snapshots, privacy thresholds | Validator/privacy tests; real aggregates pending |
| F23-R022 Native corrections/retractions/restoration | Native operation contract/broker | Lifecycle/re-read tests |
| F23-R023 Public profile/timeline through File 25 | Module manifest and native destination boundary | Ownership/static tests; real File 25 pending |
| F23-R024 Notifications through File 19 | Privacy-safe notification event boundary | Dedup/deep-link integration staging pending |
| F23-R025 Bounded reversible idempotent automation | Governance, automation engine, jobs and human confirmation | Replay/loop/prohibited-action tests |
| F23-R026 AI assistance through File 16, no autonomous action | Optional AI provider interface and confirmation gates | Adversarial/static tests; real provider pending |
| F23-R027 Filtered signed expiring audited exports | Export service/repository/background jobs | Auth/expiry/tamper/formula tests |
| F23-R028 Idempotent jobs/retry/dead-letter/reconcile | Background jobs schema/service | Queue failure/idempotency tests |
| F23-R029 File 24 sanitized evidence only | Assurance filter in plugin/module manifest | Payload privacy tests |
| F23-R030 File 20 global Safe Mode; local reversible repair | Local repair and ownership markers | Architecture/rollback-boundary tests |
| F23-R031 File 25 visual ownership | Structural templates and visual-owner manifest | Static token/ownership tests; visual staging pending |
| F23-R032 WCAG 2.2 AA objective, RTL, weak connection | Semantic views, focus/RTL/reduced-motion/forced-colors CSS and bounded JS | Static gates; browser/AT staging pending |
| F23-R033 Viewer/role/privacy/state/version cache isolation | Private route, bounded cache contracts and invalidation hooks | Static/privacy tests; LiteSpeed staging pending |
| F23-R034 Retention and erasure propagation | Privacy integration, expiry/cleanup and retention documentation | Export/erase/retention tests; legal-hold integration pending |
| F23-R035 Idempotent non-destructive activation | Schema installers, capability reconciliation and activation wizard | Fresh/upgrade/reactivation staging pending |
| F23-R036 One-Stop Doctor appointments surface remains native-owner controlled | Accepted versioned professional-surface contract; no guessed route | Three-plan harmonization gate; real File 08/provider staging pending |
| F23-R037 Smail/messages surface remains File 17-owned | Accepted versioned professional-surface contract; message metadata minimization | Three-plan harmonization gate; real File 17 staging pending |
| F23-R038 Doctor/clinic reviews and followers remain native projections | Accepted contract, capability recheck, no duplicate truth | Static contract tests; real owner providers pending |
| F23-R039 Rights-aware downloads remain native and revocable | Accepted download contract and secure same-origin destination only | Static gate; entitlement/revocation staging pending |
| F23-R040 Support/appeals remain CF-02 or current native-owner controlled | Accepted support contract; no case record stored by File 23 | Static gate; real support provider pending |
| F23-R041 Books/courses/learning remain native module assets | Accepted learning contract; dashboard only routes/projections | Static gate; File 05/12 provider staging pending |
| F23-R042 Green identity accent with semantic status colors and visible labels | Green CSS tokens, focus treatment and text-label preservation | Three-plan CSS regression; File 25 visual acceptance pending |
| F23-R043 Professional surface contract must be accepted, enabled, versioned, capability-checked and same-origin | `spdb_native_professional_surface_contract`, provider key/version/capability/URL validation | Three-plan adversarial regression and exact-head CI |

## Release-state rule

“Implemented” in this matrix means code and automated source evidence exist. Rows explicitly marked staging pending cannot be classified as staging-accepted, live or operational until their external evidence is attached to the release sign-off.

## Forty-round review evidence

F23-R001–F23-R035 remain cross-checked by `docs/AUDIT-40-ROUND-REVIEW-AND-CORRECTIONS-2026-08-04.md` and `tests/forty-round-review-gate-tests.php`. F23-R036–F23-R043 are additionally enforced by `docs/THREE-PLAN-HARMONIZATION-IMPLEMENTATION-2026-08-05.md`, `tests/three-plan-harmonization-tests.php` and `.github/workflows/file23-three-plan-harmonization.yml`.
