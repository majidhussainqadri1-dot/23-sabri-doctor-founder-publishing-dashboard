# Phase 23I Registry Readiness Conformance — Independent Review — 2026-07-31

## Verdict

Phase 23H introduced a formal readiness contract and a fail-closed bridge. The Phase 23G registry already exposes `is_ready()` and `health_snapshot()`, but it does not formally implement the new contract. Directly changing the large registry class before an isolated compatibility layer is tested would unnecessarily widen regression risk.

**DO NOT MERGE.** This review authorizes only a bounded registry-readiness conformance adapter, executable tests, and dedicated CI. It does not authorize service injection, plugin boot wiring, provider acceptance, mutation REST/UI, version promotion, staging acceptance, production writes, or merge.

## Findings

1. The registry has readiness semantics but no formal Phase 23H contract conformance.
2. Empty registry, unavailable registry, and no-ready-provider states require distinct bounded projections.
3. Registry health output is richer than the readiness contract and must not be relayed wholesale.
4. Provider names, versions, object types, health codes, and registration details are unnecessary at the service readiness boundary.
5. Registry health shape must be revalidated before it is trusted.
6. Resolver count, ready count, provider list count, and aggregate ready flag must be mutually consistent.
7. Negative, non-integer, overflowing, or impossible counts must fail closed.
8. Registry health exceptions must not escape.
9. Registration errors must remain counts only and must not expose provider error text/data.
10. A valid accepted healthy provider may make aggregate readiness true.
11. An unaccepted or unhealthy provider must not make aggregate readiness true.
12. Contract drift after registration must revoke readiness immediately.
13. One ready provider may keep aggregate readiness true even if another provider is unready; the adapter must not require every provider to be ready.
14. The conformance adapter must not resolve references itself or persist native data.
15. The adapter must not add REST routes or WordPress writes.
16. Existing Phase 23G registry and Phase 23H bridge tests must remain green.

## Authorized Coding Slice

- `SPDB_Native_Reference_Registry_Readiness` implementing `SPDB_Native_Reference_Readiness`;
- fixed projection with only `available`, `ready`, and `code`;
- validation of registry aggregate health shape and count consistency;
- bounded codes for empty, no-ready-provider, registration-errors, ready, invalid-health, and exception states;
- executable tests for empty, unaccepted, accepted, unhealthy, contract-drifted, malformed, and exception behavior;
- dedicated exact-head PHP 8.0–8.3 workflow;
- separate Draft PR.

## Explicitly Deferred

- changing `SPDB_Native_Reference_Registry` itself;
- changing `SPDB_Collections_Service` health or write gate;
- loading/injecting the conformance adapter from `SPDB_Plugin`;
- changing plugin/stable version;
- provider acceptance or real-provider integration;
- collection/knowledge mutation enablement;
- staging or production claims.

## Acceptance Rule

After coding, corrective source review and exact-head PHP 8.0–8.3 QA are mandatory. Only a later separately reviewed slice may use this adapter in service health/write-gate or plugin injection. All PRs remain Draft and unmerged.