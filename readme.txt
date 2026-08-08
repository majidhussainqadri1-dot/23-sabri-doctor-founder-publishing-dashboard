=== Sabri Doctor and Founder Publishing Dashboard ===
Contributors: majidhussainqadri1-dot
Tags: publishing, dashboard, editorial, doctors, founder, review, calendar, collections
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private, role-aware, federated publishing operations dashboard for the Sabri Social Homeopathy Platform.

== Description ==

File 23 provides one operational command center for the Founder, verified doctors, reviewers and restricted read-only account states while preserving each native module as the source of truth.

Version 1.2.4 is the third fresh ten-round corrective pass. It reconciles File 23 with the current File 00 role and publishing-assertion contract, prevents approved non-doctors or the institutional AI Teacher from being mislabeled as verified doctors, adds a bounded human-only AI Teacher oversight projection owned by File 23 while preserving Files 16/22/21 native ownership, recognizes the current `guardian_pending` membership lifecycle state, refreshes the File 22 exact contract pin to the latest reviewed candidate, and makes historical review gates forward-compatible instead of freezing future corrected releases to old package identities. Version 1.2.3 was the second fresh ten-round corrective pass. Version 1.2.2 combined the live File 21/File 22 integration with a ten-round corrective review covering least privilege, provider isolation, exact destinations, projection truth, mutation replay, client reliability, lifecycle cleanup, and operational domain routing.

Current File 23 scope includes:

* File 00 canonical membership assertions with fail-closed compatibility and current publishing/institutional-AI assertion consumption;
* Founder, verified Doctor, Reviewer, Moderator, Pending/Suspended and explicitly non-doctor institutional-AI-aware workspace classification;
* federated inventory, review inbox and publishing calendar;
* File 23-owned collections, campaigns, tasks, saved views, scoped delegations, automation-rule metadata, aggregate snapshots, export jobs, health cache, background jobs and append-only audit evidence;
* guarded native operations plus strict Sources, Media Usage, Interactions, Knowledge Gaps, Revisions, Notifications and AI Teacher oversight projections through accepted, versioned provider adapters;
* privacy-thresholded provider aggregate analytics, secure expiring CSV/JSON/HTML/PDF/iCalendar exports and optional source-linked File 16 assistance;
* idempotent jobs, retries, exponential backoff, dead-letter state, operator notifications, retention cleanup, privacy export/erasure and non-destructive local repair;
* explicit operational mutation REST nonce, approved-account, same-origin and payload-bound idempotency enforcement;
* transaction commit/rollback with request and outcome evidence in the canonical hash-chained dashboard audit;
* bounded non-autoload replay receipts with private/no-store responses and sensitive-field suppression;
* high-entropy browser idempotency keys and required audit reasons for high-risk settings and acceptance writes;
* activation readiness, File 23-only settings and full plan navigation without duplicating File 20, File 21, File 22, File 24, File 25 or File 26 ownership;
* reviewed native-reference registry and readiness consumer wired into Collections;
* strict IDOR, privacy, no-store/noindex, same-origin and capability boundaries;
* responsive, RTL, keyboard, reduced-motion and forced-color presentation controls;
* deterministic release packaging, exact-head PHP 8.0–8.3 verification and a 10,000-operation behavioral integrity model.

File 23 does not copy native publication bodies, drafts, profiles, knowledge objects, review decisions, schedules, media, comments, corrections, retractions, patient data, search/ranking truth, AI-generated publication bodies or raw analytics. File 21 remains the native social/news publication owner, File 22 remains the universal creation workflow owner, File 16 remains AI generation/policy owner, and File 26 remains the search/discovery/ranking owner.

Provider and resolver acceptance is File 23-controlled and default-denied. Production writes remain disabled until the relevant real provider, staging, rollback and Founder acceptance gates are recorded.

== Installation ==

1. Install the canonical ZIP on a backed-up staging site first.
2. Confirm compatible File 00, File 21 and File 22 versions and contracts.
3. Activate File 23; activation idempotently installs or reconciles the Collections schema, capability schema and declared File 23 operational metadata schema without altering native provider records.
4. Test Founder, verified Doctor, Reviewer, Pending, Suspended and institutional-AI identity boundaries.
5. Verify IDOR, nonce, origin, replay, privacy, LiteSpeed/no-store, RTL, mobile, keyboard, screen-reader, zoom and contrast behavior.
6. Rehearse database backup, restore, schema upgrade, plugin rollback and application rollback.
7. Record Founder staging acceptance before production activation.

== Frequently Asked Questions ==

= Does File 23 own publications? =

No. Native publications remain with File 21 and other canonical domain owners. File 23 presents projections and executes accepted native actions through guarded adapters.

= Does File 23 replace File 22? =

No. File 22 is the creation, draft, autosave, preview and submission orchestrator. File 23 is the private operational dashboard.

= Does File 23 generate AI Teacher posts? =

No. File 16 owns AI generation/policy, File 22 owns creation orchestration and File 21 owns publication lifecycle. File 23 provides bounded human oversight projections and never lets the institutional AI account authorize or oversee itself.

= Are production writes enabled automatically? =

No. Provider acceptance, environment, membership, capability, ownership, object version, nonce, same-origin, idempotency and native authorization must all pass. The default state is fail-closed.

== Changelog ==

= 1.2.4 =

* Completed a third independent ten-round review with correction before progression to each subsequent round.
* Reconciled the capability installer with current File 00 role keys: `sabri_doctor_pending`, `sabri_doctor_verified`, `sabri_membership_reviewer`, and `sabri_membership_senior_reviewer`; historical aliases remain migration-only.
* Consumed current File 00 institutional-AI and publishing assertions and prevented institutional AI/non-doctor accounts from inheriting Doctor workspace identity.
* Added a human-only `ai_teacher` operational oversight projection while preserving File 16/File 22/File 21 canonical ownership.
* Added the current File 00 `guardian_pending` lifecycle state to the restricted read-only status model.
* Refreshed exact File 22 integration evidence to reviewed head `4008521f9860e6181560ac07ff1c7e75868f1982`.
* Made earlier ten-round regression evidence forward-compatible with later corrected package/companion revisions.
* Promoted deterministic package/release identity to Version 1.2.4.

= 1.2.3 =

* Completed a second independent ten-round review with immediate correction of every evidence-backed defect.
* Added File 26 Search/Discovery/Ranking to the File 00–26 dependency manifest without duplicating search ownership.
* Required current File 00 session MFA for privileged interactive File 23 capabilities.
* Revalidated export-report authorization at download time so suspension, capability loss or session-step-up loss invalidates previously issued download access.
* Revalidated automation owner identity, current approval, capability and owner binding before background action dispatch.
* Refreshed exact companion pins to current File 00, File 21 and the plan-complete File 22 core candidate.

= 1.2.2 =

* Combined the complete Version 1.2.1 File 21/File 22 provider integration with the three-plan harmonization branch.
* Completed ten documented review/fix rounds with executable regression coverage.
* Added resource-specific REST capability gates and bounded privacy-safe provider error isolation.
* Centralized exact-origin secret-free URL validation and fail-closed operational projection/analytics validation.
* Guarded saved-view mutations, rejected split idempotency identities, and removed insecure browser randomness.
* Completed operational domain routing, exact provider-domain validation, and all-event lifecycle cleanup.

= 1.2.1 =

* Added exact File 21 and File 22 provider discovery consumption through the versioned File 23 registry.
* Removed the guessed /create/ fallback; Create now resolves only the canonical File 22 page or reports unavailable.
* Changed the central visual identity to accessible green while retaining semantic warning, critical and informational colors.
* Added local WordPress Dashicons navigation icons and corrected card spacing/wrapping for responsive, RTL and accessible layouts.

= 1.2.0 =

* Forty independent thematic review/fix rounds with executable 40/40 CI gate.
* Strict File 00 authority, exact capability reconciliation and File 20 Safe Mode ownership.
* Verified InnoDB schemas, serialized audit chain, atomic jobs/exports and provider acceptance evidence.
* AES-256-GCM encrypted-at-rest exports and expanded privacy export/erasure.
* Deterministic installable and complete-source Version 1.2.0 packages.
* Preserved fail-closed native ownership and kept staging/live acceptance as separate evidence gates.

= 1.0.0 =
* Consolidated Phase 23A–23O.
* Completed canonical Collections readiness consumption and registry wiring.
* Added canonical File 00 assertion consumption.
* Reconciled inherited tests and release workflows.
* Added deterministic package, cross-repository contract and rollback evidence gates.
