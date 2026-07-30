=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, review, calendar
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.5.0 is the initial Phase 23E Review and Calendar candidate. It adds:

* a Universal Review Inbox that projects native queues without owning review rows or decisions;
* a Federated Publishing Calendar that projects native schedules without creating a schedule table;
* a server-derived reviewer, Founder, account-state, capability, scope, and environment context;
* bounded native review items with reviewer assignment, due date, safety/privacy/source/copyright flags, version, and freshness;
* bounded native calendar entries with UTC time, IANA native timezone, conflicts, failures, version, and freshness;
* explicit approve, request-changes, reject, assign-reviewer, schedule, reschedule, and unschedule REST routes;
* File 23 operation-broker enforcement with provider-declared operations, current capability, environment acceptance, object version, idempotency key, and audit reason;
* native object re-fetch before a mutation is reported as confirmed;
* separation-of-duties protection against final self-approval or self-rejection;
* exact same-origin native destinations, RFC 3339 timestamps, IANA timezones, strict totals, bounded flags, and privacy-safe projected text;
* provider failure isolation, truthful empty states, and global safety limits;
* responsive review and calendar views;
* executable Phase 23E authorization, privacy, timezone, destination, failure, and re-fetch tests.

File 23 does not copy publication bodies, drafts, review decisions, reviewer assignments, native schedules, cron state, profile records, knowledge records, sources, media, comments, corrections, retractions, or raw analytics. Native modules remain authoritative.

Production mutation remains fail-closed until File 23-controlled adapter acceptance, real native integration, staging evidence, review, corrective re-review, and Founder acceptance are complete.

== Installation ==

1. Install on staging first.
2. Confirm WordPress and PHP requirements.
3. Confirm Sabri Membership Core 1.0.1 or a formally accepted compatible version is active.
4. Activate the plugin; it creates no new WordPress roles.
5. Register only reviewed adapters implementing Adapter Contract 2.0.0 and the optional projection interfaces they support.
6. Verify the Founder, Doctor, trusted-doctor, pending, suspended, and reviewer accounts.
7. Verify real File 21 Newsroom and File 22 Composer/schedule integration.
8. Verify native object re-fetch, timezone behavior, conflicts, failures, suspension, permission loss, and privacy holds.
9. Do not merge before review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance are complete.

== Frequently Asked Questions ==

= Does File 23 replace File 21 Newsroom? =

No. File 21 remains the native owner of its review records and decisions. File 23 validates and projects review items and calls only declared native operations through the guarded broker.

= Does File 23 create a universal schedule database? =

No. It projects native schedules, timezones, conflicts, and failures. Schedule state and cron reconciliation remain with the native owner.

= Is a review or schedule action considered successful immediately? =

No. The native adapter must authorize and execute the explicit operation, after which File 23 re-fetches the native object. Failed confirmation never becomes a displayed success state.

= Can an author approve their own content? =

Not where separation of duties is required. Final self-approval and self-rejection operations are removed at the File 23 boundary and must also be denied by the native provider.

== Changelog ==

= 0.5.0 =

* Added the initial Phase 23E Universal Review Inbox and Federated Publishing Calendar candidate with native ownership preservation, explicit guarded operations, object re-fetch, timezone and conflict projections, failure isolation, responsive UI, documentation, and tests.

= 0.4.1 =

* Corrected sixteen Phase 23D role-workspace defects and expanded security and architecture guards.

= 0.3.1 =

* Corrective Phase 23C federated inventory review.

= 0.2.1 =

* Second corrective Phase 23B review and secure Dashboard Core.

= 0.1.1 =

* Corrective Phase 23A contracts, authorization, operation broker, and architecture guards.
