# Phase 23H Resolver Readiness Bridge — Independent Review — 2026-07-31

## Verdict

Phase 23G correctly owns a default-denied provider resolver registry, but the existing `SPDB_Collections_Service` still distinguishes only between a null and non-null resolver. A non-null resolver object is not proof that any provider is registered, accepted, healthy, contract-current, or usable in the current environment.

**DO NOT MERGE.** This review authorizes only a formal readiness contract, an isolated fail-closed bridge, executable tests, and a dedicated CI foundation. It does not authorize service injection, a real provider, mutation REST, a mutation UI, staging acceptance, production writes, or merge.

## Review Findings

1. Resolver availability and resolver readiness are different states.
2. `SPDB_Collections_Service::health()` currently treats every non-null resolver as knowledge-write ready whenever repository and write configuration are ready.
3. `write_gate( true )` checks only that a resolver object exists.
4. A registered registry may contain zero accepted providers.
5. A provider may become unhealthy or contract-drifted after registration.
6. Resolver readiness exceptions must never escape into dashboard or mutation paths.
7. Readiness output must be bounded and must not expose object IDs, destinations, user data, provider exception text, or secrets.
8. A resolver that does not implement a reviewed readiness contract must remain default-denied.
9. Resolution must re-check readiness immediately before delegation.
10. Readiness loss must prevent the underlying resolver callback from executing.
11. Availability, readiness, and resolution failure require distinct bounded error codes.
12. The bridge must not persist destinations or native data.
13. The bridge must not create REST routes or write to WordPress storage.
14. Existing Phase 23G registry behavior and Phase 23F read-only surfaces must remain unchanged.
15. The first Phase 23H commit must remain runtime-unwired until corrective review of the bridge passes.
16. Exact-head PHP 8.0–8.3 evidence is required before any service/plugin injection is authorized.

## Authorized Initial Coding Slice

- `SPDB_Native_Reference_Readiness` formal contract;
- `SPDB_Native_Reference_Readiness_Bridge` implementing resolver and readiness boundaries;
- default-denied behavior for unavailable, false, malformed, or exception-producing readiness;
- bounded non-sensitive readiness snapshots;
- readiness re-check immediately before resolver delegation;
- executable tests for ready, not-ready, exception, malformed snapshot, and no-delegation behavior;
- a dedicated Phase 23H CI workflow;
- a separate Draft stacked PR.

## Explicitly Deferred

- modifying `SPDB_Collections_Service`;
- injecting the bridge from `SPDB_Plugin`;
- changing plugin version or stable tag;
- making `SPDB_Native_Reference_Registry` directly implement the readiness interface;
- enabling any provider acceptance;
- enabling collection or knowledge mutations;
- adding mutation REST/UI;
- WordPress staging or production claims.

## Acceptance Rule

After this foundation is committed, it must receive corrective source review. Only after its exact-head tests are green may the next Phase 23H slice modify service health, write gates, registry conformance, or plugin injection. Every pull request remains Draft and unmerged.