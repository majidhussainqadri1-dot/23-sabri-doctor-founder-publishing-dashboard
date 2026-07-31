# Phase 23M Collections Service Probe Consumption — Independent Corrective Review — 2026-07-31

## Verdict

Phase 23L provides a corrected repository-health acquisition probe, but `SPDB_Collections_Service` still contains its older manual repository and resolver checks. Directly adding an arbitrary probe parameter to that service would create a dependency-mismatch risk because the probe could wrap a different repository or resolver.

The initial Phase 23M binding correctly created a service, gate, integration, and probe from one dependency pair, but its public `service()` accessor exposed the raw legacy service. That accessor allowed callers to bypass the reviewed readiness chain and reach the old service health, write-gate, and native-resolution paths. Preliminary green QA did not make that design acceptable.

**DO NOT MERGE.** The corrected Phase 23M slice authorizes only a closed composition binding with reviewed readiness methods. The raw service remains private until `SPDB_Collections_Service` itself consumes the probe in a separately reviewed phase. This phase does not authorize plugin wiring, provider acceptance, mutation REST/UI, production writes, staging acceptance, or merge.

## Findings

1. `SPDB_Collections_Service::health()` still manually checks repository health.
2. Existing knowledge-write health still treats a non-null resolver as sufficient.
3. `read_repository()` does not consume the corrected Phase 23L probe.
4. `write_gate()` does not consume the corrected Phase 23L probe.
5. Direct public native-reference resolution requires a later explicit review so it cannot bypass the readiness chain.
6. An arbitrary public probe constructor parameter could bind unrelated repository/resolver dependencies.
7. Gate, integration, probe, and service must be built from the exact same repository/resolver objects.
8. The existing two-argument service constructor must remain backward compatible until plugin wiring has its own review.
9. `SPDB_Plugin` must remain on the existing constructor in this slice.
10. `SPDB_System_State` depends on current service-health field names and must not be silently broken.
11. The binding itself must not execute service mutations.
12. The binding must not resolve native references.
13. The binding must not add REST routes or persistence.
14. Invalid and disabled write authority must preserve Phase 23L short-circuit behavior.
15. Read and collection readiness must remain resolver-independent.
16. Knowledge readiness must remain repository-and-formal-resolver-readiness dependent.
17. The binding constructor must remain private.
18. Dependency replacement methods must not exist.
19. The preliminary public `service()` accessor exposed the raw service and bypassed the reviewed probe.
20. Returning the raw service contradicted the stated bounded-readiness-only boundary.
21. Exact object identity was not proved between the service repository, probe repository, service resolver, and readiness-gate resolver.
22. The binding remained clonable despite representing one bounded dependency lifecycle.
23. Serialization could export private component state or create invalid reconstructed bindings.
24. Unserialization could bypass the private constructor and produce untrusted lifecycle state.
25. A resolver implementing native resolution without the formal readiness interface needed an exact bound-path denial test.
26. Multiple bindings needed cross-binding isolation evidence so one decision could not touch another dependency pair.
27. CI did not forbid raw service exposure or enforce clone, serialization, exact identity, missing readiness contract, and cross-binding isolation evidence.
28. Exact-head CI must prove the current Phase 23L base SHA is an ancestor of the tested head.
29. Phase 23L/K/J/I/H and architecture regressions must remain green.
30. Preliminary Phase 23M head `e52db6755063a39f2ff7276a9a15682e3499c41b` and its artifacts are invalidated for final acceptance.

## Corrections Applied

- Removed the public raw service accessor.
- Kept one private `SPDB_Collections_Service` instance for exact future service-side integration.
- Kept all public binding methods limited to reviewed readiness decisions.
- Made the binding non-clonable with a private `__clone()` method.
- Made the binding non-serializable and non-unserializable with fail-closed magic methods.
- Added reflection-based tests proving exact object identity for:
  - the service repository and factory repository;
  - the probe repository and factory repository;
  - the service resolver and factory resolver;
  - the readiness-gate resolver and factory resolver.
- Added an explicit test that the raw service is not publicly exposed.
- Added a resolver-without-readiness test proving collection-only readiness may remain available while knowledge readiness fails closed.
- Added proof that a missing readiness contract never falls through to native resolution.
- Added cross-binding isolation tests with separate repository/resolver pairs.
- Expanded deterministic CI guards for raw service exposure, non-clonable lifecycle, non-serializable lifecycle, exact object identity, missing readiness contract, cross-binding isolation, and zero native resolution.

## Authorized Corrected Coding Slice

- closed `SPDB_Collections_Service_Probe_Binding` with a private constructor;
- static creation from one repository/resolver pair;
- one private service instance;
- internally constructed Phase 23J gate, Phase 23K integration, and Phase 23L probe;
- reviewed readiness delegation only;
- no raw service exposure;
- non-clonable and non-serializable lifecycle;
- executable PHP 8.0–8.3 tests and exact-head/exact-base workflow;
- separate stacked Draft PR.

## Explicitly Deferred

- modifying `SPDB_Collections_Service` internals;
- making the service consume the probe;
- exposing the bound service before internal probe consumption exists;
- modifying `SPDB_Plugin` load order or construction;
- changing `SPDB_System_State`;
- enabling resolver/provider acceptance;
- enabling mutation REST routes or UI;
- changing plugin/stable version;
- Hostinger staging or production claims.

## Acceptance Rule

The documentation-inclusive exact head must descend from the current Phase 23L PR base SHA and pass PHP 8.0, 8.1, 8.2, and 8.3 across the dedicated Phase 23M workflow and every inherited regression. Any later source change invalidates that evidence.

Only a later separately reviewed phase may modify `SPDB_Collections_Service` to consume the bound probe. Plugin injection requires another separate review. Hostinger staging, real File 00 accounts and native providers, privacy/IDOR/cache/backup/restore/rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory. All PRs remain Draft and unmerged.
