# Phase 23J Collections Service Readiness Gate — Independent Review — 2026-07-31

## Verdict

The current `SPDB_Collections_Service` treats a non-null native-reference resolver as sufficient for `knowledge_write_ready` and for the knowledge-write gate. Phase 23H and Phase 23I established a formal, bounded readiness contract, but the service does not yet consume it.

**DO NOT MERGE.** This review authorizes only an isolated fail-closed service-readiness decision boundary, executable tests, dedicated CI, and a separate Draft PR. It does not authorize changing `SPDB_Collections_Service`, plugin boot wiring, provider acceptance, mutation REST/UI, production writes, staging acceptance, or merge.

## Findings

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

## Authorized Coding Slice

- `SPDB_Collections_Service_Readiness_Gate` as a pure fail-closed decision boundary;
- resolver-presence and readiness-contract separation;
- bounded readiness projection and generic service codes;
- collection-write and knowledge-write readiness calculation;
- explicit `require_knowledge_write_ready()` errors;
- executable abuse/failure tests;
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

After initial coding, corrective source review and final exact-head QA are mandatory. A later separately reviewed Phase may integrate this gate into `SPDB_Collections_Service`; another later review is required before plugin injection. All PRs remain Draft and unmerged.
