=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, review, calendar, collections
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.6.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.6.2 is the corrected Phase 23F development candidate. It provides:

* metadata-only cross-module collection, Founder campaign, collection-item, and knowledge-link contracts;
* exactly three File 23-owned metadata tables under Schema Version 3;
* lifecycle verification of required tables, columns, and indexes;
* request-cached repository health instead of repeated schema inspection inside one request;
* no persisted native destinations, publication bodies, reports, clinical records, media, or raw analytics;
* current approved-account, capability, scope, Founder, parent-collection, and object-level visibility gates;
* actor-scoped idempotency plus request fingerprints that distinguish exact replay from payload conflict;
* deterministic duplicate canonical-relation conflicts;
* persisted bounded audit reasons and observed native versions;
* Unicode-aware bounded text and sensitive-data rejection;
* campaign-only field separation and defense-in-depth dark-pattern screening;
* a versioned native-reference resolver contract;
* a concrete WordPress repository for verified reads and idempotent creates;
* strict repository input, envelope, lifecycle, collection-item, and output projection validation;
* six explicit read-only collection, collection-item, and knowledge-link REST routes;
* private/no-store REST policy and truthful pagination headers;
* a protected read-only Collections and Knowledge dashboard view;
* collection list/detail, parent-authorized active item list/detail, and knowledge-link list/detail;
* bounded filters, pagination, Founder-only institution scope presentation, and scope-preserving navigation;
* semantic captions, landmarks, definition lists, responsive tables, visible focus, RTL, reduced-motion, and forced-color support;
* non-sensitive Phase 23F readiness in System Status;
* explicit read, collection-write, knowledge-write, and any-write readiness;
* update, reorder, archive, REST mutation, mutation UI, and production writes kept fail-closed;
* executable policy, runtime-authority, schema, replay, repository, item-IDOR, read-REST, UI, malformed-route, privacy, and architecture tests.

File 23 does not copy native publication bodies, drafts, profiles, knowledge objects, review decisions, schedules, media, comments, corrections, retractions, patient data, or analytics. It stores only bounded cross-module metadata and canonical references.

The concrete File 23 repository is injected for verified server-side reads. A native-reference resolver is deliberately not injected yet. Collection creates can run only when the explicit Phase 23F write constant is enabled in local, development, or staging; knowledge-link creates additionally require a reviewed resolver. The REST and dashboard Collections surfaces are read-only. No Phase 23F mutation route or mutation UI is exposed, and production mutation remains disabled.

== Installation ==

1. Install on staging first.
2. Confirm WordPress, PHP, and Sabri Membership Core requirements.
3. Activate the plugin; it creates no editorial WordPress roles.
4. Activation installs only the three declared File 23 metadata tables and verifies their tables, columns, and indexes.
5. Keep `SPDB_PHASE23F_WRITES_ENABLED` undefined or false outside an explicitly reviewed local, development, or staging test.
6. Register only reviewed native adapters and native-reference resolvers.
7. Verify Founder, Doctor, trusted-doctor, pending, suspended, reviewer, and contributor accounts.
8. Verify privacy, IDOR, stale-reference, permission-loss, cache, accessibility, backup, restore, migration, and rollback behavior.
9. Do not merge before review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, Founder acceptance, and explicit authorization are complete.

== Frequently Asked Questions ==

= Does File 23 copy content into a collection? =

No. It stores canonical provider, object-type, object-ID, relation, ordering, scope, and governance metadata only. Native content remains with its owner.

= Does File 23 store a native edit or preview URL? =

No. Native destinations must be freshly re-resolved and safety-validated. Signed, expiring, private, or stale destinations are not persisted.

= Are Phase 23F writes enabled? =

Not in production. The explicit write constant must be enabled in an approved local, development, or staging test. Collection create is resolver-independent; knowledge-link create also requires a reviewed native resolver. Update, reorder, archive, REST mutation, and dashboard mutation controls remain disabled.

= Which Phase 23F REST routes exist? =

Only explicit GET projections for collections, collection details, collection items, collection-item details, knowledge links, and knowledge-link details. Every route requires an approved current account and `spdb_view_own_content`; institution scope additionally requires current Founder identity and `spdb_manage_campaigns`.

= What does the Collections dashboard show? =

Only validated File 23 organizational metadata: accessible collections, campaigns, active canonical item references, and knowledge links. It does not render native content bodies or native destinations, and it exposes no mutation control.

= Is automated ethical screening sufficient for a campaign? =

No. The bounded phrase screen is defense in depth only. Founder governance, human moderation, medical policy, and native-module authorization remain mandatory.

== Changelog ==

= 0.6.2 =

* Corrected persistence, pagination, lifecycle, item-authorization, read-API, and Collections UI defects; added strict item services, six GET-only REST routes, an accessible read-only dashboard projection, and dedicated PHP 8.0–8.3 UI QA.

= 0.6.1 =

* Corrected eighteen Phase 23F runtime and schema defects, introduced Schema Version 3, added the verified WordPress repository, separated read/write readiness, enforced IDOR-safe projections, and added concrete runtime/repository tests.

= 0.6.0 =

* Corrected eighteen Phase 23F foundation defects and began the fail-closed runtime service and native-reference resolver contract.

= 0.5.1 =

* Corrected Phase 23E Review Inbox and Publishing Calendar defects.

= 0.4.1 =

* Corrected Phase 23D role-workspace defects.

= 0.3.1 =

* Corrected Phase 23C federated inventory defects.

= 0.2.1 =

* Corrected Phase 23B Dashboard Core defects.

= 0.1.1 =

* Corrected Phase 23A contracts, authorization, operation broker, and architecture guards.
