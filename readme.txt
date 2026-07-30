=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, workflow
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.4.0 is the initial Phase 23D role-workspace candidate. It includes:

* protected Founder, Doctor, trusted-doctor, and restricted publishing workspaces;
* server-derived workspace context with no client-supplied user, role, Founder flag, scope, status, capability, or environment authority;
* optional native workspace projections layered over Adapter Contract 2.0.0;
* truthful native summary cards requiring measured values and absolute source timestamps;
* Founder official-publishing and Doctor reviewed-publishing policy projections;
* capability-, account-state-, ownership-, Founder-policy-, destination-, environment-, and adapter-acceptance-gated native launch destinations;
* native profile completion, verification, eligibility, public-profile, and edit-route projections;
* native knowledge portfolio and successful-case aggregate projections;
* bounded recent activity and operational alerts;
* strict same-origin, matching-port, fragment-free, non-secret destinations;
* provider failure isolation and explicit unavailable states;
* responsive and accessible role-workspace UI;
* PHP 8.0–8.3 exact-head CI with retained QA evidence.

File 23 does not copy publication bodies, native drafts, profile records, knowledge records, review decisions, schedules, sources, media, comments, corrections, retractions, or raw analytics. Native modules remain authoritative.

No production publication mutation is executed by Phase 23D. Mutating native launch destinations remain hidden unless every current gate, including File 23-controlled adapter acceptance, is satisfied.

== Installation ==

1. Install on staging first.
2. Confirm WordPress and PHP requirements.
3. Confirm Sabri Membership Core 1.0.1 or a formally accepted compatible version is active.
4. Activate the plugin; it creates no new WordPress roles.
5. Register only reviewed adapters implementing Adapter Contract 2.0.0 and, where needed, the optional role-workspace projection interface.
6. Verify Founder, trusted-doctor, doctor, pending, and suspended workspaces with real accounts.
7. Verify native profile, knowledge, Composer, and public-profile destinations through LiteSpeed and hosting cache layers.
8. Verify that no action appears without current capability, ownership, account-state, Founder-policy, and adapter-acceptance authority.
9. Do not merge before review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance are complete.

== Frequently Asked Questions ==

= Does File 23 replace File 21, File 22, File 03, or the knowledge modules? =

No. File 21 owns Social/News and Newsroom records. File 22 owns creation and drafts. File 03 owns profiles. Encyclopedia, Learning, Research, Video, Reels, and PDF modules own their native knowledge and media records. File 23 renders validated projections and safe native destinations.

= Can a Doctor use the Founder official-publishing action? =

No. Official creation is Founder-only and is independently gated by server-verified Founder identity, current capability, approved account state, safe native destination, environment, and File 23-controlled adapter acceptance.

= Are dashboard counts estimates? =

No. A displayed measured card requires a numeric provider value and an absolute source timestamp. Missing native data is displayed as unavailable rather than fabricated.

= Does Phase 23D publish content directly? =

No. This phase creates role-specific operational projections and safe native launch destinations. File 23 does not write native publication state through the workspace view.

== Changelog ==

= 0.4.0 =

* Added the initial Phase 23D Founder and Doctor role-workspace candidate with native cards, policy projections, safe actions, profile/knowledge projections, activity, alerts, failure isolation, and executable tests.

= 0.3.1 =

* Corrective Phase 23C review: fixed cross-doctor IDOR, provider filter bypass, duplicate projections, error-code disclosure, malformed totals, internal query reflection, misleading pagination, lost filter context, silent key normalization, ambiguous timestamps, destination/thumbnail leakage, malformed operation metadata, mobile status loss, and total wording.

= 0.2.1 =

* Second corrective Phase 23B review and secure Dashboard Core.

= 0.1.1 =

* Corrective Phase 23A contracts, authorization, operation broker, and architecture guards.
