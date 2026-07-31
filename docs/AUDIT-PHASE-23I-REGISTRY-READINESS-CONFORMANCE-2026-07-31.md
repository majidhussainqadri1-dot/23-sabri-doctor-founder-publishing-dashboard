# Phase 23I Registry Readiness Conformance — Independent Review — 2026-07-31

## Verdict

Phase 23H introduced a formal readiness contract and a fail-closed bridge. The Phase 23G registry already exposes `is_ready()` and `health_snapshot()`, but it does not formally implement the new contract. Directly changing the large registry class before an isolated compatibility layer is tested would unnecessarily widen regression risk.

**DO NOT MERGE.** This review authorizes only a bounded registry-readiness conformance adapter, executable tests, and dedicated CI. It does not authorize service injection, plugin boot wiring, provider acceptance, mutation REST/UI, version promotion, staging acceptance, production writes, or merge.

## Initial Findings

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

## Corrective Review Findings

The preliminary implementation was not accepted after its first green matrix. Independent source re-review found the following additional defects:

1. The public constructor accepted an arbitrary `Closure`, allowing a future production caller to fabricate readiness without a registry.
2. Synthetic health readers were not explicitly restricted to the isolated executable-test runtime.
3. Provider entries were trusted by `ready` alone; provider identity was not required or validated.
4. Duplicate provider identities could inflate provider and ready counts while preserving a superficially consistent aggregate.
5. Noncanonical provider keys were not rejected.
6. Provider health-code type, format, and length were not bounded.
7. A provider could be projected as ready while its own health flag was false.
8. An unavailable aggregate could simultaneously claim ready providers without being classified as malformed.
9. Registration-error and provider-count limits were coupled rather than independently bounded.
10. Dedicated QA checksums omitted the workflow and the interface, bridge, and registry dependencies on which the adapter relies.
11. PHP syntax coverage omitted several direct readiness dependencies.
12. The initial audit had no corrective findings marker, so CI could not prove that re-review had occurred.

## Corrections Applied

- Replaced the public mixed-source constructor with a private constructor and a typed `from_registry()` production factory.
- Added `from_health_reader_for_tests()` guarded by the explicit `SPDB_TESTING` constant.
- Required every provider projection to contain a canonical unique `provider_key`, boolean `healthy`, boolean `ready`, and bounded canonical `code`.
- Rejected duplicate and noncanonical provider identities.
- Rejected `ready=true` with `healthy=false`.
- Rejected unavailable aggregate health that simultaneously claims readiness.
- Separated provider-count and registration-error bounds.
- Expanded executable tests for constructor privacy, duplicate identities, noncanonical identities, unsafe codes, ready/unhealthy inconsistency, and unavailable/ready inconsistency.
- Expanded syntax checks and checksums to cover the exact workflow, readiness interface, bridge, registry, adapter, regression tests, and audit.
- Added explicit corrective-review and no-merge workflow gates.

## Authorized Coding Slice

- `SPDB_Native_Reference_Registry_Readiness` implementing `SPDB_Native_Reference_Readiness`;
- fixed projection with only `available`, `ready`, and `code`;
- validation of registry aggregate health shape, provider identity, and count consistency;
- bounded codes for empty, no-ready-provider, registration-errors, ready, invalid-health, and exception states;
- executable tests for empty, unaccepted, accepted, unhealthy, contract-drifted, malformed, duplicate, and exception behavior;
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

Corrective source review and exact-head PHP 8.0–8.3 QA must complete on the final documentation-inclusive head. Only a later separately reviewed slice may use this adapter in service health/write-gate or plugin injection. All PRs remain Draft and unmerged.
