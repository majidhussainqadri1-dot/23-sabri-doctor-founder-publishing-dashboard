=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, workflow
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational dashboard for the Founder and doctors while preserving each native module as the source of truth.

Version 0.4.1 is the corrective Phase 23D role-workspace build. It includes:

* protected Founder, Doctor, trusted-doctor, and restricted publishing workspaces;
* role, Founder, trusted-publisher, account-state, and read-only authority re-derived from File 00 for the current user;
* optional native workspace projections layered over Adapter Contract 2.0.0;
* truthful native summary cards requiring bounded measured values and absolute source timestamps;
* Founder official-publishing and Doctor reviewed-publishing policy projections;
* one canonical semantic contract for each native action type;
* provider-declared capability, current account, ownership, Founder policy, destination, environment, and adapter-acceptance gates;
* profile and knowledge destinations routed through the same centralized action gate;
* native profile completion, verification, eligibility, knowledge portfolio, and successful-case aggregate projections;
* global provider, card, action, profile, knowledge, activity, and alert safety limits;
* strict exact-origin HTTP(S), matching-port, fragment-free, non-secret destinations with raw-query and recursive-decoding checks;
* provider failure isolation, duplicate-action suppression, and explicit unavailable/truncated states;
* responsive and accessible role-workspace UI;
* PHP 8.0–8.3 exact-head CI with retained QA evidence.

File 23 does not copy publication bodies, native drafts, profile records, knowledge records, review decisions, schedules, sources, media, comments, corrections, retractions, or raw analytics. Native modules remain authoritative.

The workspace view does not execute native publication mutations. Mutating native launch destinations remain hidden unless every current gate, including File 23-controlled adapter acceptance, is satisfied.

== Installation ==

1. Install on staging first.
2. Confirm WordPress and PHP requirements.
3. Confirm Sabri Membership Core 1.0.1 or a formally accepted compatible version is active.
4. Activate the plugin; it creates no new WordPress roles.
5. Register only reviewed adapters implementing Adapter Contract 2.0.0 and, where needed, the optional role-workspace projection interface.
6. Verify Founder, trusted-doctor, doctor, pending, and suspended workspaces with real accounts.
7. Verify native profile, knowledge, Composer, and public-profile destinations through LiteSpeed and hosting cache layers.
8. Verify that no action appears without its canonical semantic contract, provider declaration, current capability, ownership, account state, Founder policy, and adapter acceptance.
9. Do not merge before review, correction, corrective re-review, exact-head QA, staging acceptance, rollback evidence, and Founder acceptance are complete.

== Frequently Asked Questions ==

= Does File 23 replace File 21, File 22, File 03, or the knowledge modules? =

No. File 21 owns Social/News and Newsroom records. File 22 owns creation and drafts. File 03 owns profiles. Encyclopedia, Learning, Research, Video, Reels, and PDF modules own their native knowledge and media records. File 23 renders validated projections and gated native destinations.

= Can a Doctor use the Founder official-publishing action? =

No. Official creation is Founder-only and is independently gated by server-verified Founder identity, current capability, approved account state, safe native destination, environment, and File 23-controlled adapter acceptance.

= Can a provider label a create action as non-mutating? =

No. Every action type has a canonical mutability, capability, and Founder-only contract. Contradictory provider projections are rejected.

= Are dashboard counts estimates? =

No. A displayed measured card requires a bounded numeric provider value and an absolute source timestamp. Missing native data is displayed as unavailable rather than fabricated.

= Does Phase 23D publish content directly? =

No. This phase creates role-specific operational projections and gated native launch destinations. File 23 does not write native publication state through the workspace view.

== Changelog ==

= 0.4.1 =

* Corrected sixteen Phase 23D defects: re-derived role authority from File 00; enforced canonical action semantics and provider-declared capabilities; centralized profile and knowledge destinations; added global bounds and duplicate suppression; hardened raw query and recursive URL decoding; required exact-origin HTTP(S) and mandatory profile/knowledge timestamps; localized management disclosure; and expanded tests and architecture guards.

= 0.4.0 =

* Added the initial Phase 23D Founder and Doctor role-workspace candidate; superseded by corrective version 0.4.1.

= 0.3.1 =

* Corrective Phase 23C review: fixed cross-doctor IDOR, provider filter bypass, duplicate projections, error-code disclosure, malformed totals, internal query reflection, misleading pagination, lost filter context, silent key normalization, ambiguous timestamps, destination/thumbnail leakage, malformed operation metadata, mobile status loss, and total wording.

= 0.2.1 =

* Second corrective Phase 23B review and secure Dashboard Core.

= 0.1.1 =

* Corrective Phase 23A contracts, authorization, operation broker, and architecture guards.
