# Phase 23N Collections Service Readiness Consumer — Independent Review — 2026-07-31

## Verdict

The accepted Phase 23M binding proves exact repository/resolver composition, but `SPDB_Collections_Service` still uses legacy manual health and gate logic. Directly replacing those internals without first defining a stable mapping from the reviewed Phase 23L probe would risk health-field drift, private repository-detail relay, resolver availability/readiness confusion, and denial-code regressions.

Phase 23N therefore begins with an isolated `SPDB_Collections_Service_Readiness_Consumer`. It converts reviewed probe decisions into the exact existing service-health field names and delegates read, collection-write, and knowledge-write requirements without performing any service operation.

**DO NOT MERGE.** This initial Phase 23N slice does not modify `SPDB_Collections_Service`, `SPDB_Plugin`, `SPDB_System_State`, REST controllers, provider acceptance, production writes, or staging configuration. It is a reviewed internal contract foundation for a later service-internal consumption slice.

## Findings

1. The current service manually acquires repository health in `health()`.
2. The current service manually acquires repository health again in `read_repository()`.
3. The current `write_gate()` checks resolver presence, not formal resolver readiness.
4. The current service health reports knowledge readiness from resolver presence alone.
5. Direct service modification without an explicit mapping contract could silently change `SPDB_System_State` expectations.
6. The stable service-health top-level keys must remain exactly: `repository_available`, `resolver_available`, `read_ready`, `write_configured`, `collection_write_ready`, `knowledge_write_ready`, `any_write_ready`, `write_enabled`, and `repository_health`.
7. The bounded nested repository-health keys must remain exactly: `healthy`, `database_ready`, `schema_ready`, `schema_version`, `code`, and `cached_for_request`.
8. Raw database/provider exception text or private repository detail must never enter service health.
9. Resolver presence is not resolver readiness.
10. A writes-disabled or invalid-input health decision must report resolver presence truthfully without evaluating resolver readiness.
11. The initial consumer incorrectly derived resolver availability from a read-only probe snapshot, which intentionally does not evaluate a resolver.
12. Resolver presence therefore had to be stored separately as a bounded boolean.
13. The consumer must construct gate, integration, and probe from the exact repository/resolver pair rather than accepting an arbitrary external probe.
14. Invalid write-authority input must fail before repository or resolver readiness calls.
15. Invalid resolver-requirement input must fail before repository or resolver readiness calls.
16. Disabled collection and knowledge writes must fail before repository or resolver readiness calls.
17. Read readiness must remain resolver-independent.
18. Collection-write readiness must remain resolver-independent.
19. Knowledge-write readiness must require a present resolver implementing the formal readiness contract and reporting ready.
20. A resolver implementing native resolution without readiness metadata must fail closed for knowledge readiness.
21. No consumer path may invoke `resolve_reference()`.
22. Repository absence, malformed health, stale schema, contradictory aggregate health, degraded health, and exceptions must remain distinct bounded states.
23. Invalid/degraded repository states must short-circuit resolver readiness.
24. Repository health code must be non-empty, canonical, and at most 64 characters.
25. A ready repository must use `code=ready` and the current `SPDB_Collections_Schema::VERSION`.
26. A non-ready repository must not use `code=ready`.
27. Schema version in bounded service health may be empty only when no valid current version can be asserted; otherwise it must equal the current schema version.
28. The service-health write booleans must be internally consistent.
29. Collection readiness requires configured writes and repository read readiness.
30. Knowledge readiness implies collection readiness and resolver availability.
31. The consumer must not expose probe, repository, resolver, service, integration, or gate objects.
32. The consumer must be non-clonable and non-serializable.
33. Multiple consumers must remain dependency- and call-isolated.
34. Existing Phase 23M/L/K/J/I/H and architecture regressions must remain green.
35. Exact-head CI must prove that the current Phase 23M PR base SHA is an ancestor of the tested Phase 23N head.

## Corrections Applied During Initial Coding Review

- Replaced arbitrary-probe construction with exact repository/resolver pair composition.
- Added internal construction of the Phase 23J gate, Phase 23K integration, and Phase 23L probe.
- Stored only resolver presence as a private bounded boolean.
- Preserved truthful resolver availability in invalid-input and writes-disabled health without readiness probing.
- Added exact stable top-level service-health keys.
- Added exact bounded six-field repository-health projection.
- Intentionally collapsed database/schema readiness to aggregate reviewed repository readiness because the Phase 23K public projection does not expose private subsystem detail.
- Added canonical code length/key validation.
- Added current-or-empty schema-version validation.
- Added ready-code/current-schema invariants.
- Added read/write/knowledge boolean consistency checks.
- Added strict runtime boolean validation for `requires_resolver`.
- Added private constructor and clone path plus serialization/unserialization denial.
- Added exact dependency-identity tests.
- Added tests for resolver presence without readiness evaluation.
- Added stale-schema, malformed scalar, contradictory aggregate, noncanonical code, overlong code, write-without-read, and knowledge-without-resolver adversarial tests.
- Added repository exception recovery, private-detail suppression, degraded-detail reduction, cross-consumer isolation, and zero-native-resolution tests.

## Authorized Initial Coding Slice

- `SPDB_Collections_Service_Readiness_Consumer`;
- primary and adversarial executable tests;
- this audit;
- dedicated exact-head/exact-base PHP 8.0–8.3 workflow;
- separate stacked Draft PR.

## Explicitly Deferred

- modifying `SPDB_Collections_Service` constructor, `health()`, `read_repository()`, `write_gate()`, or `resolve_reference()`;
- exposing a raw probe or service from the consumer;
- modifying `SPDB_Plugin` load order or construction;
- modifying `SPDB_System_State`;
- enabling resolver/provider acceptance;
- adding or changing mutation REST routes or UI;
- changing plugin/stable version;
- enabling production writes;
- Hostinger staging or production claims.

## Acceptance Rule

The documentation-inclusive exact head must descend from the current Phase 23M base head and pass PHP 8.0, 8.1, 8.2, and 8.3 across the dedicated Phase 23N workflow and all inherited regressions. Any later source change invalidates that evidence.

A later separately reviewed phase may make `SPDB_Collections_Service` consume this consumer internally. Plugin injection remains a separate phase. Hostinger staging, real File 00 accounts and native providers, privacy/IDOR/cache/backup/restore/rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory. All PRs remain Draft and unmerged.
