# Phase 23N Collections Service Readiness Consumer — Independent and Corrective Review — 2026-07-31

## Verdict

The accepted Phase 23M binding proves exact repository/resolver composition, but `SPDB_Collections_Service` still uses legacy manual health and gate logic. Directly replacing those internals without first defining a stable mapping from the reviewed Phase 23L probe would risk health-field drift, private repository-detail relay, resolver availability/readiness confusion, and denial-code regressions.

Phase 23N therefore provides an isolated `SPDB_Collections_Service_Readiness_Consumer`. It converts reviewed probe decisions into the exact existing service-health field names and owns the bounded read, collection-write, and knowledge-write readiness decisions without performing a service operation.

The preliminary head `bbe4071dc84c65f8b2c6a68695d70d67a57b0057` passed automated tests but was not final source acceptance. Corrective review found seven additional source and evidence defects. Every preliminary Phase 23N run and artifact is invalidated after the corrective source changes.

**DO NOT MERGE.** Phase 23N does not modify `SPDB_Collections_Service`, `SPDB_Plugin`, `SPDB_System_State`, REST controllers, provider acceptance, production writes, or staging configuration. It is a reviewed internal contract foundation for a later service-internal consumption slice.

## Initial Findings

1. The current service manually acquires repository health in `health()`.
2. The current service manually acquires repository health again in `read_repository()`.
3. The current `write_gate()` checks resolver presence, not formal resolver readiness.
4. The current service health reports knowledge readiness from resolver presence alone.
5. Direct service modification without an explicit mapping contract could silently change `SPDB_System_State` expectations.
6. Stable service health requires exactly nine top-level fields.
7. Bounded repository health requires exactly six nested fields.
8. Raw database/provider exception text or private repository detail must never enter service health.
9. Resolver presence is not resolver readiness.
10. Writes-disabled or invalid-input health must report resolver presence truthfully without evaluating readiness.
11. Resolver availability cannot be derived from a deliberately suppressed read-only resolver snapshot.
12. The exact repository/resolver pair must construct gate, integration, and probe internally.
13. Invalid write-authority input must fail before repository or resolver calls.
14. Invalid resolver-requirement input must fail before repository or resolver calls.
15. Disabled collection and knowledge writes must fail before repository or resolver calls.
16. Read readiness must remain resolver-independent.
17. Collection-write readiness must remain resolver-independent.
18. Knowledge-write readiness requires the formal readiness contract and a ready result.
19. A resolver that supports resolution but not readiness must fail closed for knowledge writes.
20. No consumer path may invoke `resolve_reference()`.
21. Repository absence, malformed health, stale schema, contradictory health, degraded health, and exceptions require distinct bounded states.
22. Invalid or degraded repository states must short-circuit resolver readiness.
23. Repository health codes must be bounded and non-sensitive.
24. Ready repository health requires `code=ready` and the current schema version.
25. Non-ready repository health may not use `code=ready`.
26. Public write booleans must be internally consistent.
27. Collection readiness requires configured writes and repository read readiness.
28. Knowledge readiness implies collection readiness and formal resolver readiness.
29. The consumer must expose no probe, repository, resolver, service, integration, or gate object.
30. The consumer must be non-clonable and non-serializable.
31. Multiple consumers must remain dependency- and call-isolated.
32. Exact dependency-identity must be executable evidence.
33. Existing Phase 23M/L/K/J/I/H and architecture regressions must remain green.
34. Exact-head CI must prove that the current Phase 23M PR base SHA is an ancestor.
35. No consumer path may invoke persistence, mutation REST/UI, or native resolution.
36. The first evidence run failed an audit-marker assertion and was invalidated.
37. Automated success is not staging or production acceptance.

## Corrective Review Findings

38. The preliminary consumer mapped probe fields permissively with null-coalescing instead of validating the exact raw snapshot contract.
39. Any syntactically canonical repository code was accepted; public and internal repository codes require exact allowlists.
40. Integration, repository, and resolver codes were not bound to their corresponding boolean states.
41. Resolver presence was stored, but formal readiness capability was not retained as a separate consumer authority fact.
42. `require_read_ready()` and `require_write_ready()` delegated directly to the probe and bypassed the consumer's own projection validation.
43. The lifecycle denied serialization but lacked an explicit wakeup reconstruction guard.
44. CI did not enforce raw snapshot allowlists, consumer-owned requirement paths, formal readiness capability, exact code/state semantics, or wakeup denial.
45. Phase 23L uses a no-resolver integration for writes-disabled and repository-denied paths; these states legitimately carry the bounded suppression marker `resolver_absent`, while invalid authority carries `resolver_not_evaluated`.
46. Conflating the suppression marker with the input-not-evaluated marker would reject valid upstream snapshots.
47. Preliminary head, runs, and artifacts cannot remain authoritative after source changes.

## Corrections Applied

- Added an exact seventeen-field raw snapshot allowlist.
- Added exact integration, repository, and resolver code allowlists.
- Added strict runtime type checks for every snapshot scalar.
- Added repository code/state invariants for not-evaluated, unavailable, invalid, not-ready, and ready states.
- Added resolver code/state invariants for absent, missing-readiness, unavailable, unready, invalid, exception, re-entrant, not-evaluated, invalid-state, and ready states.
- Separated invalid-input `resolver_not_evaluated` from no-resolver suppression `resolver_absent`.
- Added exact integration-state invariants for input-invalid, writes-disabled, repository-denied, gate-invalid, collection-ready, and knowledge-ready states.
- Stored resolver presence and formal readiness capability as separate private booleans.
- Required formal readiness capability for every knowledge-ready public projection.
- Replaced direct probe requirement delegation with consumer-owned validated snapshot decisions.
- Preserved stable repository and resolver denial codes without a second readiness acquisition.
- Added exact runtime boolean validation for both requirement inputs.
- Added consumer-level exception isolation and a bounded projection-invalid error.
- Added `__wakeup()` denial in addition to clone, serialize, and unserialize denial.
- Expanded primary tests for exact raw shape, code allowlists, lifecycle closure, formal readiness, stable errors, one-call bounds, privacy, and isolation.
- Expanded adversarial tests for every suppressed/denied state, unknown canonical codes, forged readiness, stale schema, malformed health, and contradictory health.
- Strengthened CI static guards and exact-source checksums.

## Authorized Phase 23N Slice

- `SPDB_Collections_Service_Readiness_Consumer`;
- corrected primary and adversarial executable tests;
- this audit;
- dedicated exact-head/exact-base PHP 8.0–8.3 workflow;
- separate stacked Draft PR.

## Explicitly Deferred

- modifying `SPDB_Collections_Service` constructor, `health()`, `read_repository()`, `write_gate()`, or `resolve_reference()`;
- modifying `SPDB_Plugin` load order or construction;
- modifying `SPDB_System_State`;
- enabling resolver/provider acceptance;
- adding or changing mutation REST routes or UI;
- changing plugin/stable version;
- enabling production writes;
- Hostinger staging or production claims.

## Acceptance Rule

The documentation-inclusive exact head must descend from the current Phase 23M base head and pass PHP 8.0, 8.1, 8.2, and 8.3 across the dedicated Phase 23N workflow and all inherited regressions. Any later source change invalidates that evidence.

A later separately reviewed phase may make `SPDB_Collections_Service` consume this consumer internally. Plugin injection remains a separate phase. Hostinger staging, real File 00 accounts and native providers, privacy, IDOR, cache, backup/restore, rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory. All PRs remain Draft and unmerged.
