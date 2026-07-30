=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, workflow
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.3.1 is the corrective Phase 23C inventory build. It includes:

* protected `/publishing-dashboard/` route and private REST responses;
* File 00 account-state and capability-checked access;
* read-only federated inventory over native provider adapters;
* independent current-user ownership enforcement and Founder-only institution scope;
* bounded search, filters, sorting, provider selection, and a 200-item pagination window;
* canonical lifecycle, review, visibility, and operational state projections;
* provider-filter revalidation, duplicate suppression, strict totals, and bounded generic diagnostics;
* strict RFC 3339 timestamps and exact canonical identifiers;
* same-origin, fragment-free, non-secret edit, preview, public, canonical, and thumbnail URLs;
* read-only item inspector and operation metadata with execution disabled;
* responsive inventory table retaining all material status columns;
* PHP 8.0–8.3 exact-head CI with retained QA evidence.

No production publishing action is enabled. A provider cannot self-declare staging or production acceptance.

== Installation ==

1. Install on staging first.
2. Confirm WordPress and PHP requirements.
3. Confirm Sabri Membership Core 1.0.1 or a formally accepted compatible version is active.
4. Activate the plugin; it creates no roles.
5. Register only reviewed adapters implementing Adapter Contract 2.0.0.
6. Verify inventory results against native records, including cross-doctor denial.
7. Verify private headers through LiteSpeed and hosting cache layers.
8. Test real Founder, doctor, pending, and suspended accounts.
9. Do not merge before all review and acceptance gates are complete.

== Frequently Asked Questions ==

= Does File 23 replace File 21 or File 22? =

No. File 21 owns Social/News publishing and Newsroom records. File 22 owns Composer and drafts. File 23 reads validated operational projections and links back to safe native destinations.

= Does the inventory copy native content into File 23? =

No. Publication bodies, drafts, review records, schedules, sources, media, comments, corrections, retractions, and analytics events remain with native owners.

= How is own-content scope protected? =

File 23 injects the authenticated user ID, requires a validated `owner_user_id` in each projection, rechecks ownership before list rendering and inspection, and permits institution scope only to the server-verified Founder.

= Does activation enable publishing actions? =

No. Production writes require later reviewed operation UX, File 23-controlled acceptance, current account eligibility, native authorization, concurrency, audit, and staging approval.

== Changelog ==

= 0.3.1 =

* Corrective Phase 23C review: fixed cross-doctor IDOR, provider filter bypass, duplicate projections, error-code disclosure, malformed totals, internal query reflection, misleading pagination, lost filter context, silent key normalization, ambiguous timestamps, destination/thumbnail leakage, malformed operation metadata, mobile status loss, and total wording.

= 0.3.0 =

* Initial read-only federated inventory candidate; superseded by corrective version 0.3.1.

= 0.2.1 =

* Second corrective Phase 23B review and secure Dashboard Core.

= 0.1.1 =

* Corrective Phase 23A contracts, authorization, operation broker, and architecture guards.
