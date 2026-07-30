=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, workflow
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.3.0 adds the Phase 23C federated content inventory to the reviewed Phase 23A and Phase 23B foundations:

* protected `/publishing-dashboard/` route;
* authenticated, Membership Core, and capability-checked access;
* activation-time capability reconciliation for existing File 00 roles without creating roles;
* Founder, trusted-doctor, doctor, reviewer, moderator, and restricted read-only access boundaries;
* truthful overview cards and provider-readiness projections;
* private no-store/noindex headers for virtual, shortcode, and REST responses;
* personal non-clinical saved views with typed validation and conflict protection;
* isolated provider registration callbacks;
* read-only federated inventory over native provider adapters;
* bounded search, filters, sorting, provider selection, and pagination window;
* canonical four-dimensional status projections;
* provider-failure isolation and partial-result diagnostics;
* native item inspector with same-origin edit, preview, and public destinations;
* signed, token-bearing, cross-origin, mismatched, or invalid projections rejected;
* native operation metadata visible for inspection only, with no Phase 23C mutation endpoint;
* exact-head PHP 8.0–8.3 CI with retained QA evidence.

No production publishing action is enabled in this phase. A provider cannot self-declare staging or production acceptance.

== Installation ==

1. Install on staging first.
2. Confirm WordPress and PHP requirements.
3. Confirm Sabri Membership Core 1.0.1 or a formally accepted compatible version is active.
4. Activate the plugin. Activation adds File 23 capability keys only to existing approved roles; it creates no roles.
5. Open `/publishing-dashboard/` and verify the correct workspace and private headers.
6. Register only reviewed native adapters implementing Adapter Contract 2.0.0.
7. Verify inventory accuracy against native records and confirm no patient-identifying data is projected.
8. Test real Founder, doctor, pending, and suspended accounts on staging.
9. Do not enable or merge later phases until their separate review, correction, and acceptance gates are complete.

== Frequently Asked Questions ==

= Does File 23 replace File 21 or File 22? =

No. File 21 owns Social/News publishing and Newsroom records. File 22 owns Composer and drafts. File 23 reads validated operational projections and links back to native destinations.

= Does the inventory copy native content into File 23? =

No. It validates and renders bounded runtime projections. Publication bodies, drafts, review records, schedules, sources, media, comments, corrections, retractions, and analytics events remain with their native owners.

= Does activation enable publishing actions? =

No. Activation provisions dashboard capability keys on existing roles. Production write actions still require File 23-controlled Production-Accepted status, an approved Membership Core account, a canonical capability, a registered operation, native provider authorization, current object version, and all provider policy checks.

= Can an adapter mark itself Production-Accepted? =

No. An adapter declares technical capability only. Staging and production acceptance belong to File 23 governance after review and testing.

= Can pending or suspended accounts enter the dashboard? =

They may receive restricted dashboard and owned-content viewing capabilities through an existing File 00 role. The Membership Core status guard keeps the workspace read-only and denies publishing mutations.

== Changelog ==

= 0.3.0 =

* Added the read-only federated content inventory, bounded query contract, canonical projection validator, current-user and Founder scope enforcement, partial provider degradation, item inspector, safe native destinations, read-only REST endpoints, responsive inventory UI, and executable Phase 23C tests.

= 0.2.1 =

* Second corrective review: added real File 00 role capability provisioning, exact-head CI, retained QA artifacts, provider callback isolation, REST error privacy, shortcode fail-closed protection, saved-view conflict and nested-value controls, unique dashboard instances, robust empty states, and visible skip-target focus.

= 0.2.0 =

* Added the protected front-end dashboard route, role-aware workspace resolver, accessible responsive shell, truthful overview, provider readiness, non-sensitive system status, personal saved views, private cache/indexing controls, and executable dashboard-core tests.

= 0.1.1 =

* Corrective audit build: split provider capability from institutional acceptance, removed caller-controlled environment selection, added File 00 fail-closed authorization, operation broker, contract tests, architecture guards, and PHP 8.0–8.3 CI.

= 0.1.0 =

* Superseded pre-audit Phase 23A bootstrap. Do not merge or release independently.
