# Phase 23O Collections Service Internal Readiness Consumption — Independent Review — 2026-07-31

## Verdict

`SPDB_Collections_Service` still contained a parallel legacy readiness implementation after the corrected Phase 23N consumer became available. The legacy service directly read repository health, treated resolver presence as knowledge readiness, duplicated repository probes, and allowed public native resolution outside the reviewed readiness boundary.

Phase 23O integrates the reviewed consumer **inside the service only**. The plugin container, System State, REST controllers, provider acceptance, mutation UI/routes, and production-write configuration remain unchanged.

**DO NOT MERGE.** This is an initial source candidate. Corrective source review, exact-head PHP 8.0–8.3 QA, Hostinger staging, real accounts/providers, rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory.

## Findings

1. `SPDB_Collections_Service::health()` manually invoked raw repository health.
2. Legacy health accepted an incomplete and permissive repository-health shape.
3. Legacy health could relay repository-specific fields into public System State.
4. Legacy knowledge readiness used resolver object presence rather than formal readiness capability and current readiness.
5. `read_repository()` performed a second independent health implementation.
6. Read paths therefore did not consume the reviewed Phase 23L/23N state machine.
7. `write_gate()` checked write configuration and repository health outside the reviewed consumer.
8. Resolver-required writes checked only `null !== resolver`.
9. A resolver implementing resolution without formal readiness could reach a native call.
10. Public `resolve_reference()` could execute independently of reviewed write readiness.
11. Direct resolution could therefore bypass disabled-write, repository, and formal-readiness gates.
12. Calling public `resolve_reference()` twice from knowledge preparation after `write_gate()` would double- or triple-probe readiness once direct gating was added.
13. Knowledge preparation needs one readiness decision followed by two private gated resolutions.
14. The service must construct one readiness consumer from its exact repository/resolver pair.
15. The service must not accept an externally supplied consumer or probe.
16. The service file must load the reviewed readiness dependencies for standalone and plugin construction paths.
17. Service health must retain the existing exact nine-field top-level contract.
18. Nested repository health must retain the exact bounded six-field contract.
19. Reads must remain resolver-independent.
20. Collection writes must remain resolver-independent.
21. Knowledge writes and direct native resolution require formal resolver readiness.
22. Invalid, disabled, malformed, unavailable, degraded, exception, and unready states must stop before native resolution.
23. Public direct resolution must validate current authority and canonical reference before readiness acquisition.
24. Private post-gate resolution must repeat authority/reference validation as defense in depth but must not repeat readiness acquisition.
25. Legacy Phase 23F runtime fixtures used an obsolete three-field repository-health shape.
26. Legacy resolver fixtures implemented resolution but not the formal readiness contract.
27. Existing inherited workflows still encoded the superseded rule that service-side consumer integration was forbidden.
28. Retained workflows must authorize only this reviewed service-internal integration, not plugin/System State/REST wiring.
29. `SPDB_Plugin` must remain unchanged in this phase.
30. Provider acceptance must remain empty/default-denied.
31. No mutation REST route or UI may be added.
32. No production write may be enabled.
33. The temporary source-export and patch workflows must not remain in the final diff.
34. Exact-head CI must prove the current Phase 23N base SHA is an ancestor.
35. Automated source QA is not staging or production acceptance.

## Initial Corrections Applied

- Added self-contained loading of the reviewed repository, resolver, readiness, gate, integration, probe, and consumer dependencies.
- Added one private `SPDB_Collections_Service_Readiness_Consumer` constructed from the exact service repository/resolver pair.
- Replaced manual `health()` logic with the consumer's stable health projection.
- Replaced manual `read_repository()` logic with consumer-owned read readiness.
- Replaced manual `write_gate()` logic with consumer-owned collection/knowledge readiness.
- Added a reviewed write-readiness gate to public `resolve_reference()`.
- Added private `resolve_reference_after_gate()` for post-gate native execution.
- Changed knowledge-link preparation to gate once and resolve source/target through the private helper.
- Removed every direct `health_check()` call from `SPDB_Collections_Service`.
- Updated the legacy Phase 23F runtime repository fixture to the exact six-field health contract.
- Updated the legacy native resolver fixture to implement formal readiness.
- Added dedicated Phase 23O executable tests for identity, stable health shape, call bounds, direct-resolution denial/success, missing/unready readiness, malformed repository health, privacy, and zero native execution on denial.
- Removed the temporary source-export and patch workflows after the controlled patch commit.

## Authorized Phase 23O Slice

- `includes/class-spdb-collections-service.php` internal readiness consumption;
- `tests/phase23f-runtime-tests.php` strict fixture migration;
- dedicated Phase 23O tests;
- this audit;
- a dedicated exact-head/exact-base PHP 8.0–8.3 workflow;
- narrowly updated inherited evidence gates that recognize the reviewed Phase 23O boundary;
- separate stacked Draft PR.

## Explicitly Deferred

- modifying `SPDB_Plugin` construction or resolver injection;
- modifying `SPDB_System_State`;
- modifying REST route registration or mutation behavior;
- accepting any real native provider/resolver;
- enabling production writes;
- changing plugin/stable version;
- Hostinger staging or production claims;
- merge.

## Acceptance Rule

The final documentation-inclusive exact head must descend from the accepted Phase 23N head and pass PHP 8.0, 8.1, 8.2, and 8.3 across the dedicated Phase 23O workflow and every retained regression. Any source, test, workflow, or documentation change invalidates prior evidence.

A later separately reviewed phase may modify plugin construction and inject a default-denied registry/readiness adapter. Hostinger staging, real File 00 accounts and native providers, privacy, IDOR, cache, backup/restore, rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory. All PRs remain Draft and unmerged.
