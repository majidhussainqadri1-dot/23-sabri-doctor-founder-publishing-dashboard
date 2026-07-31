# Phase 23J Collections Service Readiness Gate — Independent Review and Corrective Re-review — 2026-07-31

## Verdict

The pre-Phase-23J `SPDB_Collections_Service` treats a non-null native-reference resolver as sufficient for `knowledge_write_ready` and for the knowledge-write gate. Phase 23H and Phase 23I established a formal, bounded readiness contract, but the service does not yet consume it.

The initial Phase 23J slice correctly introduced an isolated fail-closed decision boundary. Two corrective reviews then found additional defects in the gate, its executable evidence, and its workflow. Those defects have now been corrected on the same Draft branch.

**DO NOT MERGE.** Phase 23J remains an isolated source candidate. It does not modify `SPDB_Collections_Service`, plugin boot wiring, provider acceptance, mutation REST/UI, production writes, staging state, or live behavior.

## Initial Findings

1. `resolver_available` currently means object presence, not operational readiness.
2. `knowledge_write_ready` currently becomes true when any resolver object is non-null.
3. `write_gate( true )` checks only resolver presence.
4. A resolver that does not implement the formal readiness contract is not distinguished from a ready resolver.
5. Resolver absent, readiness missing, resolver unavailable, resolver not ready, malformed readiness, and readiness exception require distinct bounded states.
6. Resolver readiness text/data must never be relayed into service health or errors.
7. Readiness projection shape and scalar types must be revalidated.
8. The readiness code must be bounded and canonical even though it is not relayed.
9. `is_ready()` and `readiness_snapshot()` disagreement must fail closed.
10. Collection-write readiness must remain independent of native-reference readiness.
11. Knowledge-write readiness must require write configuration, repository readiness, resolver presence, formal readiness availability, and current resolver readiness.
12. The decision boundary must not resolve native references or invoke mutation callbacks.
13. The decision boundary must not add REST routes, database writes, options, metadata, or persistent state.
14. Existing Phase 23H and Phase 23I regressions must remain green.

## First Corrective Re-review Findings

The complete coded slice was re-reviewed after initial implementation. Eleven further defects or evidence gaps were found:

1. The gate exposed collection readiness in `snapshot()` but had no executable `require_collection_write_ready()` boundary for later service integration.
2. Missing readiness, invalid readiness, readiness exceptions, resolver unavailability, and operational not-ready states were collapsed into one error path.
3. A readiness source could claim `ready=true` while returning a non-`ready` code and still be accepted.
4. A readiness source could claim `ready=false` while returning the canonical `ready` code and still pass structural validation.
5. Recursive readiness callbacks had no re-entrancy guard and could cause unbounded recursion or denial of service.
6. Snapshot-method exceptions were not covered independently from declaration-method exceptions.
7. Empty and overlong readiness codes were validated in code but lacked executable regression evidence.
8. Valid associative readiness snapshots with reordered keys lacked an explicit regression test.
9. The collection-write denial code was tied to the old Phase 23F label rather than a stable service-level contract.
10. CI syntax coverage omitted the concrete Phase 23H bridge and Phase 23I registry-readiness dependencies used by the gate.
11. CI lacked bounded runtime/concurrency controls and did not assert the newly required semantic and re-entrancy protections.

## Second Corrective Re-review Findings

A further source review of the corrected head found six additional defects or evidence gaps:

1. PHP weak scalar coercion could convert values such as the string `false` or integer `1` into trusted boolean authority inputs.
2. Invalid authority inputs could still trigger resolver readiness calls even though no write decision should be evaluated.
3. The snapshot had no bounded field declaring whether write-configuration and repository-readiness inputs were valid.
4. An unexpected `Throwable` during readiness validation could leave the internal evaluation flag latched and cause permanent re-entrant denial.
5. Exception and re-entrancy tests proved denial but did not prove that the same gate instance recovered after the failed evaluation ended.
6. Tests did not prove that one decision invokes each readiness method at most once and never invokes native resolution.

## Corrections Applied

- Added `require_collection_write_ready()` as a stable, resolver-independent write boundary.
- Preserved collection-write readiness independently from knowledge-write readiness.
- Added distinct bounded errors for resolver absence, missing readiness conformance, unavailable resolver, operational not-ready, invalid readiness, readiness failure, and re-entrant readiness.
- Added exact semantic validation: a ready snapshot must use `code=ready`; a non-ready snapshot must not use `code=ready`.
- Added an internal re-entrancy guard that fails closed without invoking native resolution.
- Replaced the Phase-23F-specific disabled-write code with `spdb_collections_writes_disabled`.
- Removed weakly typed boolean authority signatures and added exact runtime `is_bool()` validation.
- Added `inputs_valid` and bounded `gate_code` fields to the snapshot.
- Invalid authority inputs now return `gate_input_invalid`, set all write readiness false, expose `resolver_not_evaluated`, and do not call readiness methods.
- Added `spdb_collections_readiness_input_invalid` to both collection and knowledge write requirements.
- Wrapped the complete readiness evaluation in `try/catch/finally`, guaranteeing that the re-entrancy flag is released after every outcome.
- Expanded executable tests for collection gating, error distinction, reordered keys, empty/overlong/unsafe codes, semantic code mismatches, both exception locations, recursive readiness, invalid scalar inputs, no-probe behavior, bounded call counts, recovery, and zero native-resolution calls.
- Expanded PHP 8.0–8.3 CI syntax and regression coverage to the readiness interface, bridge, registry conformance, gate, and all dependent tests.
- Added workflow concurrency cancellation and a 15-minute job timeout.
- Added static guards for exact input validation, invalid-input states, `finally` recovery, and absence of weak boolean method signatures.
- Preserved the isolation guard: the gate is still not loaded by `SPDB_Plugin` and is still not referenced by `SPDB_Collections_Service`.

## Corrected Authorized Slice

- `SPDB_Collections_Service_Readiness_Gate` as a pure fail-closed decision boundary;
- exact runtime validation of write-configuration and repository-readiness authority inputs;
- resolver-presence and readiness-contract separation;
- bounded gate and resolver projections;
- independent collection-write and knowledge-write requirements;
- exact readiness-code semantics;
- re-entrancy denial and guaranteed evaluation recovery;
- executable abuse/failure/recovery tests;
- dedicated exact-head PHP 8.0–8.3 workflow;
- separate stacked Draft PR.

## Explicitly Deferred

- modifying `SPDB_Collections_Service`;
- modifying `SPDB_Plugin` load order or dependency injection;
- enabling a resolver acceptance map;
- enabling metadata mutation routes or UI;
- changing plugin/stable version;
- staging or production claims.

## Acceptance Rule

The corrected exact-current-head workflow must pass on PHP 8.0, 8.1, 8.2, and 8.3. Any later source change invalidates that evidence and requires affected-scope review and a complete rerun.

A later separately reviewed phase may integrate this gate into `SPDB_Collections_Service`; another separately reviewed phase is required before plugin injection. Hostinger staging, real File 00 accounts/providers, privacy/IDOR/cache/rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory. All PRs remain Draft and unmerged.
