# Phase 23G Review and Acceptance Gate

## Permanent Rule

**DO NOT MERGE before completed review.**

Phase 23G is a stacked Draft candidate built from the exact accepted Phase 23F source head. Provider contracts, registry code, registration dispatch, acceptance policy, diagnostics, tests, documentation, any later provider implementation, and any later resolver injection must each follow:

review → defect record → immediate correction → corrective re-review → exact-head QA → staging acceptance → Founder acceptance → explicit merge authorization.

## Current Candidate

- Branch: `phase/23g-native-reference-resolution`
- Base: `phase/23f-collections-knowledge`
- Draft PR: #8
- Plugin development version: `0.7.0`
- Metadata schema: `3` unchanged
- Adapter contract: `2.0.0` unchanged
- Real native providers: none
- Resolver acceptance map: empty in the plugin runtime
- Registry injection into `SPDB_Collections_Service`: absent
- Knowledge-write readiness in the default runtime: false
- Mutation REST/UI: absent
- Production mutation: disabled
- Staging readiness: not ready
- Merge readiness: blocked

## Source Review Gate

- [x] Sixteen initial Phase 23G defects documented
- [x] Seven corrective re-review defects documented
- [x] Provider-specific resolver contract implemented
- [x] File 23-owned aggregate registry implemented
- [x] Existing-adapter dependency implemented
- [x] Exact provider key/version and resolver-version validation implemented
- [x] Resolver object types restricted to the adapter declaration
- [x] Adapter acceptance and resolver acceptance separated
- [x] Staging and production acceptance separated
- [x] Default-denied empty acceptance runtime retained
- [x] Duplicate and malformed registration fail closed
- [x] Registration callbacks isolated
- [x] Current-user and scope context reconstructed server-side
- [x] Unknown and forged context authority rejected
- [x] Provider exceptions and provider errors bounded
- [x] Exact provider/type/ID/scope response matching implemented
- [x] Exact safe response reconstruction implemented
- [x] Safe current-request destination validation implemented
- [x] No destination persistence introduced
- [x] Health output reconstructed and cached
- [x] Immutable provider contract rechecked against drift
- [x] Registry diagnostics contain no object IDs, destinations, patient data, provider error text, callback details, or secrets
- [x] Registry is not injected into collection or knowledge writes
- [x] No real provider implementation included
- [x] No resolver REST route or mutation UI included
- [x] Dedicated governance, privacy, isolation, and drift tests implemented
- [ ] Final documentation-inclusive exact-head matrices successful
- [ ] Final-head artifacts and checksums retained

## Final Automated Source Gate

The same final commit must pass all three matrices on PHP 8.0, 8.1, 8.2, and 8.3:

- [ ] Baseline Integrity
- [ ] Collections UI Regression
- [ ] Phase 23G Native Reference Resolution

The matrices must prove:

- exact checked head;
- PHP and JavaScript syntax;
- Phase 23A–23F regressions;
- Phase 23G registration, acceptance, authority, privacy, response, health, callback, runtime, and contract-drift behavior;
- no provider self-acceptance;
- no registry injection into writes;
- no REST mutation or persistence ownership;
- version, required-file, audit, merge, checksum, and artifact controls.

Static checkboxes remain conservative; GitHub checks attached to the current exact head are authoritative.

## WordPress Staging Gate

- [ ] Fresh activation without fatal error
- [ ] Upgrade from the accepted Phase 23F candidate without metadata loss
- [ ] Empty resolver registry reports zero registered and zero ready providers
- [ ] Invalid registration callback remains isolated
- [ ] Unreviewed adapter and resolver remain unavailable
- [ ] Staging acceptance cannot authorize production
- [ ] Real File 00 Founder and Doctor authority verified
- [ ] Real provider-specific resolver reviewed separately
- [ ] Missing, private, deleted, stale, permission-lost, and outage states verified
- [ ] Provider error privacy verified
- [ ] Cross-doctor IDOR and enumeration resistance verified
- [ ] LiteSpeed/no-store/private cache behavior verified
- [ ] Backup, restore, deactivation/reactivation, and application rollback verified

## Final Acceptance Gate

- [ ] Founder review completed
- [ ] Founder acceptance recorded
- [ ] PR moved from Draft only after every preceding gate passes
- [ ] Merge explicitly authorized

Any source or documentation change requires all three exact-head matrices to run again. PR #8 and all predecessor PRs remain Draft and unmerged.
