# Phase 23L Collections Repository Readiness Probe — Independent Review and Corrective Re-review — 2026-07-31

## Verdict

The original Phase 23L branch did not descend from the final accepted Phase 23K head. Comparison against `53b67cff3c830002cad0d4a8a76932872b28d110` showed the old branch was nine commits ahead and nine commits behind, with merge base `09ef2d9497bcc83649d5b54aa9e1ac30e5ad8155`. Every earlier Phase 23L run, artifact, head, and acceptance claim is invalid.

The branch was force-reset to the exact accepted Phase 23K head and the repository-readiness probe was rebuilt from the corrected integration contract. A subsequent corrective source review found and corrected resolver short-circuit and fallback-load-order gaps.

**DO NOT MERGE.** Phase 23L authorizes only an isolated repository-health acquisition boundary, executable tests, and exact-head CI. It does not authorize modification of `SPDB_Collections_Service`, plugin wiring, provider acceptance, mutation REST/UI, production writes, staging acceptance, or merge.

## Initial Findings

1. The original Phase 23L branch had stale and divergent ancestry.
2. Its old base branch was not the accepted Phase 23K branch.
3. All previous Phase 23L QA omitted the final Phase 23K corrections.
4. The original exception and re-entrancy fallback contained only four health fields.
5. Corrected Phase 23K requires exactly six fields: `healthy`, `database_ready`, `schema_ready`, `schema_version`, `code`, and `cached_for_request`.
6. The incomplete fallback was therefore interpreted as malformed health rather than a valid bounded degraded state.
7. Fallback schema version must equal current `SPDB_Collections_Schema::VERSION`.
8. Fallback health codes must be bounded canonical non-sensitive values.
9. Invalid write-authority input must fail before repository or resolver probing.
10. Disabled collection and knowledge writes must fail before repository or resolver probing.
11. A read-only snapshot must acquire repository health once but must not evaluate resolver readiness.
12. `require_read_ready()` must not evaluate resolver readiness.
13. Collection-write readiness must acquire repository health once and remain independent from resolver readiness.
14. Knowledge-write readiness must acquire repository health once and resolver readiness once.
15. No probe path may resolve a native object.
16. Repository exceptions must be isolated without relaying exception text.
17. The same probe instance must recover after a repository exception.
18. Recursive repository health acquisition must fail closed without a second `health_check()` call.
19. The same probe instance must recover after re-entrant denial.
20. Raw malformed, unknown-field, stale-schema, and contradictory repository health must remain delegated to the corrected Phase 23K validator.
21. Valid degraded repository detail must not be relayed into the public projection.
22. Repository absence must remain distinct from invalid or not-ready repository health.
23. The probe must not cache health across public decisions; each decision receives one current acquisition.
24. `SPDB_Collections_Service` and `SPDB_Plugin` must remain unchanged in this slice.
25. Exact-head CI must prove that the current PR base SHA is an ancestor of the tested head.
26. Phase 23K, Phase 23J, Phase 23I, Phase 23H, and architecture regressions must remain green.

## Corrective Re-review Findings

1. A write-enabled full snapshot could still evaluate resolver readiness after repository exception, malformed health, stale schema, or a valid degraded state because the Phase 23K integration also projects resolver state.
2. Repository readiness must be established through the no-resolver integration before a resolver-aware full snapshot is permitted.
3. The read-only and repository-denied branches require explicit resolver zero-call regression evidence.
4. Exception and re-entrancy fallback directly accessed `SPDB_Collections_Schema::VERSION`; an incorrect future load order could make the fallback itself fatal.
5. Missing schema dependency must fail closed rather than throwing from an exception handler.
6. Malformed, unknown-field, stale-schema, and valid-degraded health each require an explicit test proving that resolver readiness is not called.

## Corrections Applied

- Reset the Phase 23L branch to accepted Phase 23K head `53b67cff3c830002cad0d4a8a76932872b28d110`.
- Invalidated all earlier Phase 23L runs and artifacts.
- Rebuilt `SPDB_Collections_Repository_Readiness_Probe` from the corrected Phase 23K integration.
- Added exact runtime boolean validation for write configuration.
- Added invalid/disabled write short-circuiting before repository or resolver probes.
- Added a dedicated no-resolver read integration so read-only decisions never probe resolver readiness.
- Added a two-stage full-snapshot decision: repository readiness is validated without a resolver first; only a read-ready repository reaches resolver-aware integration.
- Preserved resolver-independent collection-write readiness.
- Restricted resolver readiness to write-enabled full snapshots with a read-ready repository and to knowledge-write requirements after repository validation.
- Added complete six-field exception and re-entrancy fallback health.
- Added guarded schema-version fallback; a missing schema dependency cannot crash the failure path.
- Added `try/catch/finally` recovery for every repository acquisition.
- Added re-entrancy detection that marks the outer decision not ready without making a second repository call.
- Added executable tests for exact call counts, invalid and disabled authority, read/collection/knowledge separation, repository absence, exception isolation and recovery, malformed/unknown/stale/degraded health, resolver zero-call denial paths, re-entrancy and recovery, and zero native resolution.

## Authorized Coding Slice

- `SPDB_Collections_Repository_Readiness_Probe`;
- corrected Phase 23L executable tests;
- this audit;
- a dedicated exact-head and exact-base PHP 8.0–8.3 workflow;
- separate stacked Draft PR.

## Explicitly Deferred

- modifying `SPDB_Collections_Service`;
- modifying `SPDB_Plugin` load order or dependency injection;
- enabling provider acceptance;
- enabling metadata mutation REST routes or UI;
- changing plugin/stable version;
- Hostinger staging or production claims.

## Acceptance Rule

The documentation-inclusive exact head must descend from the current Phase 23K PR base SHA and pass PHP 8.0, 8.1, 8.2, and 8.3 across the dedicated Phase 23L workflow and all inherited regressions. Any source change invalidates prior evidence.

Only a later separately reviewed phase may make `SPDB_Collections_Service` consume this probe. Plugin injection requires another separate review. Hostinger staging, real File 00 accounts and native providers, privacy/IDOR/cache/backup/restore/rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory. All PRs remain Draft and unmerged.
