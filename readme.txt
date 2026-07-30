=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, review, calendar, collections
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.6.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.6.1 is the second corrective Phase 23F development candidate. It provides:

* metadata-only cross-module collection, Founder campaign, and knowledge-link contracts;
* exactly three File 23-owned metadata tables under Schema Version 3;
* lifecycle verification of required tables, columns, and indexes;
* no persisted native destinations, publication bodies, reports, clinical records, media, or raw analytics;
* current approved-account, capability, scope, Founder, and object-level visibility gates;
* actor-scoped idempotency plus request fingerprints that distinguish exact replay from payload conflict;
* persisted bounded audit reasons and observed native versions;
* Unicode-aware bounded text and sensitive-data rejection;
* campaign-only field separation and defense-in-depth dark-pattern screening;
* a versioned native-reference resolver contract;
* a concrete WordPress repository for verified reads and idempotent creates;
* strict repository-envelope validation and cross-user record rejection;
* explicit read readiness, collection-write readiness, and knowledge-write readiness;
* update, reorder, archive, REST mutation, and production writes kept fail-closed;
* restored Phase 23A–23E architecture guards plus Phase 23F-specific checks;
* executable policy, runtime-authority, schema, replay, and repository tests.

File 23 does not copy native publication bodies, drafts, profiles, knowledge objects, review decisions, schedules, media, comments, corrections, retractions, patient data, or analytics. It stores only bounded cross-module metadata and canonical references.

The concrete File 23 repository is injected for verified server-side reads. A native-reference resolver is deliberately not injected yet. Collection creates can run only when the explicit Phase 23F write constant is enabled in local, development, or staging; knowledge-link creates additionally require a reviewed resolver. No Phase 23F REST mutation route is exposed, and production mutation remains disabled.

== Installation ==

1. Install on staging first.
2. Confirm WordPress, PHP, and Sabri Membership Core requirements.
3. Activate the plugin; it creates no editorial WordPress roles.
4. Activation installs only the three declared File 23 metadata tables and verifies their tables, columns, and indexes.
5. Keep `SPDB_PHASE23F_WRITES_ENABLED` undefined or false outside an explicitly reviewed local, development, or staging test.
6. Register only reviewed native adapters and native-reference resolvers.
7. Verify Founder, Doctor, trusted-doctor, pending, suspended, reviewer, and contributor accounts.
8. Verify privacy, IDOR, stale-reference, permission-loss, cache, backup, restore, migration, and rollback behavior.
9. Do not merge before review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance are complete.

== Frequently Asked Questions ==

= Does File 23 copy content into a collection? =

No. It stores canonical provider, object-type, object-ID, relation, ordering, scope, and governance metadata only. Native content remains with its owner.

= Does File 23 store a native edit or preview URL? =

No. Native destinations must be freshly re-resolved and safety-validated. Signed, expiring, private, or stale destinations are not persisted.

= Are Phase 23F writes enabled? =

Not in production. The explicit write constant must be enabled in an approved local, development, or staging test. Collection create is resolver-independent; knowledge-link create also requires a reviewed native resolver. Update, reorder, archive, and REST mutation remain disabled.

= Is automated ethical screening sufficient for a campaign? =

No. The bounded phrase screen is defense in depth only. Founder governance, human moderation, medical policy, and native-module authorization remain mandatory.

== Changelog ==

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
