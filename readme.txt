=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, review, calendar
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.5.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.5.1 is the corrective Phase 23E Review and Calendar candidate. It provides:

* a Universal Review Inbox that projects native queues without owning review rows, assignments, decisions, appeals, or audit ledgers;
* a Federated Publishing Calendar that projects native schedules without creating a schedule or cron database;
* current server-derived File 00 account, Founder, reviewer, capability, scope, and environment authority;
* strict fail-closed review/calendar filters and bounded global pagination;
* truthful separation of native reported totals, accessible validated windows, and current-page counts;
* fresh item-level authorization before every explicit review or schedule mutation;
* canonical operation contracts for capability, Founder-only policy, eligible state, ownership, assignment, and separation of duties;
* REST nonce verification, strict payload allowlists, object-version checks, idempotency keys, audit reasons, and operation-specific required fields;
* mandatory privacy-safe review notes and structured reason codes for request-changes and rejection;
* Founder-only reviewer assignment with target account and capability validation;
* canonical UTC schedule timestamps and valid IANA native timezones;
* exact same-origin native destinations, strict identifiers, totals, flags, operations, continuation flags, and projected text;
* native object re-fetch and matching-reference confirmation before success is reported;
* provider failure isolation, bounded aggregate totals, responsive views, keyboard focus, reduced-motion support, and accessible pagination;
* expanded corrective authorization, privacy, IDOR, state, timezone, pagination, nonce, confirmation, and failure tests.

File 23 does not copy publication bodies, drafts, review decisions, reviewer assignments, native schedules, cron state, profile records, knowledge records, sources, media, comments, corrections, retractions, or raw analytics. Native modules remain authoritative.

Production mutation remains fail-closed until File 23-controlled adapter acceptance, real native integration, staging evidence, completed review, corrective re-review, rollback evidence, and Founder acceptance are complete.

== Installation ==

1. Install on staging first.
2. Confirm WordPress and PHP requirements.
3. Confirm Sabri Membership Core 1.0.1 or a formally accepted compatible version is active.
4. Activate the plugin; it creates no new WordPress roles.
5. Register only reviewed adapters implementing Adapter Contract 2.0.0 and the optional projection interfaces they support.
6. Verify Founder, Doctor, trusted-doctor, pending, suspended, reviewer, and reviewer-target accounts.
7. Verify real File 21 Newsroom and File 22 Composer/schedule integration.
8. Verify native object re-fetch, timezone behavior, conflicts, failures, suspension, permission loss, privacy holds, cache privacy, and rollback.
9. Do not merge before review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance are complete.

== Frequently Asked Questions ==

= Does File 23 replace File 21 Newsroom? =

No. File 21 remains the native owner of its review records and decisions. File 23 validates and projects review items and calls only declared native operations through the guarded broker.

= Does File 23 create a universal schedule database? =

No. It projects native schedules, timezones, conflicts, and failures. Schedule state and cron reconciliation remain with the native owner.

= Is a review or schedule action considered successful immediately? =

No. File 23 freshly re-authorizes the native object, the adapter must authorize and execute the explicit operation, and File 23 must re-fetch a matching native reference. Failed or mismatched confirmation never becomes success.

= Can an author approve or reject their own content? =

No. Final self-approval and self-rejection are blocked independently at validation, action-visibility, fresh operation-authorization, and native-provider boundaries.

= Can any reviewer assign another reviewer? =

No. Reviewer assignment is Founder-only in the File 23 operation contract, and the target must currently be an approved account with the assigned-review capability.

== Changelog ==

= 0.5.1 =

* Corrected Phase 23E authorization, query broadening, item-level IDOR, semantic capability, separation-of-duties, reviewer-target, nonce, payload, timestamp, confirmation, total, pagination, privacy, and accessibility defects.

= 0.5.0 =

* Added the initial Phase 23E Universal Review Inbox and Federated Publishing Calendar candidate; superseded by corrective version 0.5.1.

= 0.4.1 =

* Corrected sixteen Phase 23D role-workspace defects and expanded security and architecture guards.

= 0.3.1 =

* Corrective Phase 23C federated inventory review.

= 0.2.1 =

* Second corrective Phase 23B review and secure Dashboard Core.

= 0.1.1 =

* Corrective Phase 23A contracts, authorization, operation broker, and architecture guards.
