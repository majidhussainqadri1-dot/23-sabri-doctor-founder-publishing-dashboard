=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, review, calendar, collections
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.7.0 is the Phase 23G native-reference governance foundation. It retains the corrected Phase 23F metadata, read API, and read-only UI, and adds:

* a provider-specific native-reference resolver contract;
* a File 23-owned resolver registry implementing the aggregate resolver boundary;
* exact provider-key, provider-version, resolver-version, and object-type binding;
* existing-adapter dependency and separate adapter/resolver acceptance;
* default-denied staging and production acceptance;
* an isolated resolver-registration hook that cannot self-accept a provider;
* request-cached bounded resolver health;
* strict current-user, scope, Founder, capability, and environment context reconstruction;
* exact provider/type/ID/scope response matching;
* reconstruction of a bounded safe response instead of relaying provider payloads;
* bounded generic translation of provider exceptions and provider errors;
* safe current-request destination validation without destination persistence;
* non-sensitive registered, ready, and registration-error diagnostics;
* PHP 8.0–8.3 tests for duplicate registration, missing adapter, version/type mismatch, malformed governance, acceptance separation, context forgery, callback isolation, provider exceptions, sensitive errors, malformed responses, scope mismatch, unsafe destinations, and health caching.

File 23 still does not copy native publication bodies, drafts, profiles, knowledge objects, review decisions, schedules, media, comments, corrections, retractions, patient data, or analytics. It stores only bounded cross-module metadata and canonical references.

The registry is booted with an empty File 23-owned acceptance map and is deliberately not injected into `SPDB_Collections_Service` yet. Therefore no provider resolver is operational by default, knowledge-link write readiness remains false, no real native provider is integrated, no mutation REST or mutation UI is exposed, and production mutation remains disabled.

== Installation ==

1. Install on staging first.
2. Confirm WordPress, PHP, and Sabri Membership Core requirements.
3. Activate the plugin; it creates no editorial WordPress roles.
4. Activation installs only the three declared File 23 metadata tables and verifies their tables, columns, and indexes.
5. Keep `SPDB_PHASE23F_WRITES_ENABLED` undefined or false outside an explicitly reviewed local, development, or staging test.
6. Register only independently reviewed provider adapters and provider-specific resolvers.
7. Do not add staging or production resolver acceptance until provider-specific review and real staging evidence are complete.
8. Verify privacy, IDOR, stale-reference, permission-loss, provider outage, cache, accessibility, backup, restore, migration, and rollback behavior.
9. Do not merge before review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, Founder acceptance, and explicit authorization are complete.

== Frequently Asked Questions ==

= Does File 23 copy native content? =

No. It stores bounded organizational metadata and canonical references only. Native content remains with its owner.

= Is a real native resolver active? =

No. Version 0.7.0 provides the default-denied registry and isolation foundation only. The plugin acceptance map is empty, the registry is not injected into collection writes, and no real provider implementation is included.

= Can a provider accept itself? =

No. Technical registration and File 23-owned acceptance are separate. A provider must also have an accepted adapter, exact matching version, declared object type, healthy resolver, and environment-appropriate resolver acceptance.

= Can provider errors or health details leak? =

Provider health and errors are reconstructed into bounded states. Object identifiers, destinations, callback details, patient information, provider error text, and secrets are not included in diagnostics.

= Are writes enabled? =

Not in production. No Phase 23G mutation route or UI exists. Collection creation remains separately gated under the earlier non-production constant; knowledge-link creation still has no injected resolver in the default plugin runtime.

= Which read routes exist? =

Only the six Phase 23F GET projections for collections, collection details, collection items, item details, knowledge links, and link details. The protected Collections dashboard remains read-only.

== Changelog ==

= 0.7.0 =

* Added a default-denied provider-specific native-reference registry, isolated registration, acceptance separation, strict context and response privacy, truthful diagnostics, and dedicated PHP 8.0–8.3 tests without enabling any real provider or mutation.

= 0.6.2 =

* Corrected persistence, pagination, lifecycle, item authorization, read API, and Collections UI defects; added six GET-only REST routes and an accessible read-only dashboard projection.

= 0.6.1 =

* Corrected Phase 23F runtime and Schema Version 3 defects and added the verified WordPress repository.

= 0.6.0 =

* Began the fail-closed Phase 23F metadata and resolver-contract foundation.

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
