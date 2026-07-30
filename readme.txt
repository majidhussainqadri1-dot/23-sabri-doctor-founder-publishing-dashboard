=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, review, calendar, collections
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.6.0 is the corrected Phase 23F development candidate. It adds:

* metadata-only cross-module collection, Founder campaign, and knowledge-link contracts;
* exactly three File 23-owned metadata tables with post-install verification;
* no persisted native destinations, publication bodies, reports, clinical records, media, or raw analytics;
* current approved-account, capability, scope, and Founder authority gates;
* actor-scoped idempotency and owner/scope-scoped canonical relation hashes;
* Unicode-aware bounded text and sensitive-data rejection;
* campaign-only field separation and defense-in-depth dark-pattern screening;
* a versioned native-reference resolver contract;
* a fail-closed runtime service with truthful repository/resolver health;
* production writes disabled and no Phase 23F mutation REST route;
* restored Phase 23A–23E architecture guards plus Phase 23F-specific checks;
* expanded executable Phase 23F policy and runtime-foundation tests.

File 23 does not copy native publication bodies, drafts, profiles, knowledge objects, review decisions, schedules, media, comments, corrections, retractions, patient data, or analytics. It stores only bounded cross-module metadata and canonical references.

The runtime repository and native-reference resolver are not yet injected. The Collections view and write routes are not production-enabled. Production mutation remains fail-closed until separate implementation review, staging evidence, rollback evidence, and Founder acceptance are complete.

== Installation ==

1. Install on staging first.
2. Confirm WordPress, PHP, and Sabri Membership Core requirements.
3. Activate the plugin; it creates no editorial WordPress roles.
4. Activation installs only the three declared File 23 metadata tables and verifies their existence.
5. Keep `SPDB_PHASE23F_WRITES_ENABLED` undefined or false outside an explicitly reviewed development or staging test.
6. Register only reviewed native adapters and native-reference resolvers.
7. Verify Founder, Doctor, trusted-doctor, pending, suspended, reviewer, and contributor accounts.
8. Verify privacy, IDOR, stale-reference, permission-loss, cache, backup, restore, and rollback behavior.
9. Do not merge before review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance are complete.

== Frequently Asked Questions ==

= Does File 23 copy content into a collection? =

No. It stores canonical provider, object-type, object-ID, relation, ordering, scope, and governance metadata only. Native content remains with its owner.

= Does File 23 store a native edit or preview URL? =

No. Native destinations must be freshly re-resolved and safety-validated. Signed, expiring, private, or stale destinations are not persisted.

= Are Phase 23F writes enabled? =

No. The current service is fail-closed by default and exposes no Phase 23F mutation REST route.

= Is automated ethical screening sufficient for a campaign? =

No. The bounded phrase screen is defense in depth only. Founder governance, human moderation, medical policy, and native-module authorization remain mandatory.

== Changelog ==

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
