=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, workflow
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and verified doctors while preserving each native module as the source of truth.

Version 0.2.0 implements the reviewed Phase 23A security contracts plus the Phase 23B private dashboard core:

* protected `/publishing-dashboard/` route;
* authenticated and capability-checked access;
* Founder, trusted-doctor, doctor, and restricted read-only workspace resolution;
* truthful overview cards and provider-readiness projections;
* private no-store/noindex response headers;
* graceful no-provider and dependency-failure states;
* personal non-clinical saved views with bounded REST storage;
* accessible responsive shell and system-status projection.

No production publishing action is enabled in this phase. A provider cannot self-declare staging or production acceptance.

== Installation ==

1. Install on staging first.
2. Confirm WordPress and PHP requirements.
3. Confirm Sabri Membership Core 1.0.1 or a formally accepted compatible version is active.
4. Activate the plugin.
5. Assign `spdb_view_dashboard` through the approved File 00 capability integration.
6. Open `/publishing-dashboard/` and verify the correct workspace and private headers.
7. Do not enable or merge later phases until their separate review, correction, and acceptance gates are complete.

== Frequently Asked Questions ==

= Does File 23 replace File 21 or File 22? =

No. File 21 owns Social/News publishing and Newsroom records. File 22 owns Composer and drafts. File 23 federates operational views and authorized provider actions.

= Does activation enable publishing actions? =

No. Production write actions require File 23-controlled Production-Accepted status, an approved Membership Core account, a canonical File 23 capability, a registered operation, native provider authorization, current object version, and all provider policy checks.

= Can an adapter mark itself Production-Accepted? =

No. An adapter declares technical capability only. Staging and production acceptance belong to File 23 governance after review and testing.

= Can pending or suspended accounts enter the dashboard? =

Only when File 00 explicitly grants the restricted dashboard capability. Their workspace remains read-only and does not expose publishing mutations.

== Changelog ==

= 0.2.0 =

* Added the protected front-end dashboard route, role-aware workspace resolver, accessible responsive shell, truthful overview, provider readiness, non-sensitive system status, personal saved views, private cache/indexing controls, and executable dashboard-core tests.

= 0.1.1 =

* Corrective audit build: split provider capability from institutional acceptance, removed caller-controlled environment selection, added File 00 fail-closed authorization, operation broker, contract tests, architecture guards, and PHP 8.0–8.3 CI.

= 0.1.0 =

* Superseded pre-audit Phase 23A bootstrap. Do not merge or release independently.
