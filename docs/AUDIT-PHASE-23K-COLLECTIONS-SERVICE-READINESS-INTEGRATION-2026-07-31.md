# Phase 23K Collections Service Readiness Integration — Independent Review — 2026-07-31

## Verdict

The first Phase 23K branch was not based on the final corrected Phase 23J head. Its merge base was `4a6926e68653f243c448303a60933a4a1b2101cf`, while the accepted Phase 23J head became `60c2f08e37678c28d550c6c2aeebf4181aacdcdc`. Comparison proved that the Phase 23K branch was ten commits ahead but four corrective Phase 23J commits behind. All earlier Phase 23K runs and evidence were therefore invalidated.

The Phase 23K branch was force-reset to the exact accepted Phase 23J head, and the integration boundary was rebuilt from repository source truth.

**DO NOT MERGE.** This phase authorizes only an isolated repository-and-resolver readiness integration projection, executable tests, and exact-head CI. It does not authorize modification of `SPDB_Collections_Service`, plugin boot wiring, provider acceptance, mutation REST/UI, production writes, staging acceptance, or merge.

## Findings

1. The original Phase 23K branch had stale and divergent ancestry.
2. Previous Phase 23K QA did not include the final six Phase 23J authority and recovery corrections.
3. The original integration reintroduced weakly typed boolean authority parameters.
4. The original repository-health validator did not require an exact field set.
5. Unknown repository-health fields could be silently accepted.
6. Missing repository-health fields were not explicitly rejected.
7. The concrete WordPress repository health contract contains exactly `healthy`, `database_ready`, `schema_ready`, `schema_version`, `code`, and `cached_for_request`.
8. Repository schema readiness must be tied to the current `SPDB_Collections_Schema::VERSION`, not merely a truthy health flag.
9. Repository aggregate `healthy` must equal the conjunction of database and schema readiness.
10. Ready repository health must use `code=ready`, while non-ready health must not use `code=ready`.
11. Repository health codes must remain canonical, bounded, and non-sensitive.
12. Raw repository health detail must not be relayed into the public integration projection.
13. Writes-disabled decision precedence must match the executable collection and knowledge requirements.
14. Invalid authority inputs must fail before repository or resolver authority is trusted.
15. The downstream Phase 23J gate projection must be exact-shape and semantic validated before integration consumes it.
16. A malformed gate projection must fail closed with all write states false.
17. Collection-write readiness must remain independent from resolver readiness.
18. Knowledge-write readiness must require valid inputs, configured writes, current repository readiness, and formal current resolver readiness.
19. Integration must never call native reference resolution.
20. Integration must not persist repository health, resolver health, native objects, destinations, or credentials.
21. Integration must not add REST mutation routes or WordPress writes.
22. `SPDB_Collections_Service` and `SPDB_Plugin` must remain unchanged in this slice.
23. Exact-head CI must prove that the PR head descends from the current PR base SHA.
24. Phase 23J, Phase 23I, Phase 23H, and architecture regressions must remain green.

## Corrections Applied

- Reset the Phase 23K branch to final Phase 23J head `60c2f08e37678c28d550c6c2aeebf4181aacdcdc`.
- Invalidated every earlier Phase 23K run and artifact tied to the stale ancestry.
- Removed weak scalar authority signatures and added exact runtime boolean validation.
- Added bounded `inputs_valid` and `integration_code` states.
- Added `integration_input_invalid` and `repository_not_evaluated` fail-closed projections.
- Implemented the exact six-field concrete repository-health contract.
- Added exact scalar validation for all repository-health fields.
- Added current schema-version verification against `SPDB_Collections_Schema::VERSION`.
- Added aggregate healthy/database/schema consistency validation.
- Added exact ready-code semantics and bounded canonical code validation.
- Reduced valid degraded repository detail to `repository_not_ready`.
- Added writes-disabled-first decision precedence matching executable requirements.
- Added exact Phase 23J gate-projection shape, code allowlists, and semantic consistency validation.
- Added bounded `gate_state_invalid` and `resolver_state_invalid` fallback states.
- Preserved independent collection readiness and resolver-dependent knowledge readiness.
- Added executable tests for invalid authority scalars, missing/unknown fields, malformed types, stale/noncanonical schema versions, health/code contradictions, key-order variation, unavailable/degraded/ready repository states, ready and unready resolver states, decision precedence, and zero native-resolution calls.

## Corrective QA Finding

The first rebuilt exact-head run proved that all Phase 23K executable tests, Phase 23J/23I/23H regressions, syntax checks, exact-base ancestry, and architecture guard passed. The run nevertheless failed its static evidence step because GNU `grep -E` was given PCRE-only non-capturing groups such as `(?:...)`. The resulting regex warnings and ambiguous exit behavior made the evidence gate unreliable.

That failed run is not final evidence. The fragile shell-regex block was replaced by deterministic PHP static assertions covering required controls, weak authority signatures, service/plugin isolation, forbidden REST/persistence/native-resolution calls, executable evidence markers, and the audit merge gate. A complete new exact-head matrix is mandatory.

## Authorized Coding Slice

- `SPDB_Collections_Service_Readiness_Integration` as an isolated pure decision boundary;
- exact repository-health validation based on the concrete repository contract;
- current schema-version enforcement;
- exact downstream Phase 23J gate validation;
- bounded integration and repository states;
- collection and knowledge write requirements;
- executable PHP 8.0–8.3 tests;
- exact-head and exact-base ancestry workflow;
- separate stacked Draft PR.

## Explicitly Deferred

- changing `SPDB_Collections_Service`;
- loading the integration from `SPDB_Plugin`;
- injecting a concrete registry/readiness adapter;
- enabling provider acceptance;
- enabling metadata mutation routes or UI;
- changing plugin or stable version;
- Hostinger staging or production claims.

## Acceptance Rule

The documentation-inclusive exact head must descend from the current Phase 23J PR base SHA and pass PHP 8.0, 8.1, 8.2, and 8.3 across the dedicated workflow and all inherited regressions. Any later source change invalidates that evidence.

Only a later separately reviewed phase may modify `SPDB_Collections_Service`; plugin injection requires another separately reviewed phase. Hostinger staging, real File 00 accounts and native providers, privacy/IDOR/cache/backup/restore/rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory. All PRs remain Draft and unmerged.
