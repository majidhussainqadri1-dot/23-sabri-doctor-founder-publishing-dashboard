# Changelog

All notable changes to File 23 are documented here.

## [Unreleased]

Phase 23D remains Draft, unmerged, not staging-accepted, and not production-ready.

## [0.4.1] — Phase 23D Corrective Review

### Fixed

- Re-derived Founder, trusted-doctor, Doctor, restricted, account-state, and read-only authority from File 00 instead of trusting a supplied workspace array.
- Prevented forged Founder policy and forged writable workspace exposure.
- Added a canonical action-type contract for mutability, required capability, and Founder-only semantics.
- Rejected action capabilities that the provider did not declare.
- Routed profile edit, public-profile, and knowledge destinations through the centralized action gate.
- Added global provider, card, action, profile, knowledge, activity, and alert limits with truthful truncation notices.
- Suppressed duplicate native launch actions.
- Replaced ambiguous `parse_str()` destination handling with strict raw-query validation.
- Rejected duplicate/bracketed parameters, malformed percent encoding, recursively encoded nested URLs, unsafe schemes, secrets, signatures, and expiry data.
- Required exact-origin HTTP(S), matching port, no credentials, and no fragments.
- Required absolute source timestamps for profile and knowledge projections and RFC 3339 UTC generation time.
- Rejected malformed verification-state values instead of silently hiding them.
- Removed user-facing generated text from CSS and localized it in PHP markup.
- Expanded corrective tests and architecture guards for identity, semantic, destination, direct-link, and global-bound failures.

### Security

- No caller or provider may elevate role authority, weaken create/edit action semantics, or bypass adapter acceptance through a direct profile or knowledge link.
- Restricted accounts may receive only safe non-mutating views whose canonical contract and capability pass.
- Provider exceptions remain isolated and non-sensitive.

### Documentation

- Added `docs/AUDIT-PHASE-23D-2026-07-30.md` recording sixteen findings and their corrections.

## [0.4.0] — Phase 23D Initial Role-Workspace Candidate

- Added the initial optional role-workspace projection interface, role views, cards, actions, profile/knowledge projections, activity, alerts, and tests.
- Superseded by corrective version `0.4.1`.

## [0.3.1] — Phase 23C Corrective Review

- Fixed cross-doctor IDOR, provider filter bypass, duplicate projections, provider error disclosure, malformed totals, internal query reflection, misleading pagination, lost filter context, silent key normalization, ambiguous timestamps, unsafe destinations, malformed operation metadata, mobile status loss, and total wording.
- Added reported/accessed/validated total separation, non-enumerating inspector denial, strict timestamps, URL validation, and expanded tests.

## [0.3.0] — Phase 23C Initial Candidate

- Added the first read-only federated inventory, projection validator, item inspector, REST reads, bounded query window, and initial tests.
- Superseded by corrective version `0.3.1`.

## [0.2.1] — Phase 23B Second Corrective Review

- Added real File 00 role capability provisioning, exact-head CI, retained artifacts, provider callback isolation, REST error privacy, shortcode fail-closed protection, saved-view conflict controls, unique dashboard instances, and visible focus.

## [0.2.0] — Phase 23B Dashboard Core

- Added the protected dashboard route, role-aware workspaces, responsive shell, truthful overview, provider readiness, system status, personal saved views, private cache/index controls, and executable tests.

## [0.1.1] — Corrective Phase 23A

- Added the reviewed Adapter Contract 2.0.0, File 00 fail-closed authorization, provider acceptance separation, guarded operation broker, architecture guards, and PHP 8.0–8.3 CI.

## [0.1.0] — Superseded Bootstrap

Initial pre-audit bootstrap. It must not be merged or released independently.
