# Phase 23M Collections Service Probe Consumption — Independent Review — 2026-07-31

## Verdict

Phase 23L provides a corrected repository-health acquisition probe, but `SPDB_Collections_Service` still has its older manual repository and resolver checks. Directly adding an arbitrary probe parameter to the service constructor would create a dependency-mismatch risk: the probe could wrap a different repository or resolver than the service itself.

**DO NOT MERGE.** This first Phase 23M slice authorizes only a bounded composition binding that constructs one service and one readiness probe from the same repository and resolver dependencies. It does not yet authorize changing `SPDB_Collections_Service`, changing `SPDB_Plugin`, enabling provider acceptance, adding mutation REST/UI, enabling production writes, or claiming staging acceptance.

## Findings

1. `SPDB_Collections_Service::health()` still manually checks repository health.
2. Its existing knowledge-write readiness still treats a non-null resolver as sufficient.
3. `read_repository()` does not consume the corrected Phase 23L probe.
4. `write_gate()` does not consume the corrected Phase 23L probe.
5. Direct public native-reference resolution requires a later explicit review so it cannot bypass the new readiness chain.
6. An arbitrary public probe constructor parameter could bind a service to unrelated repository/resolver dependencies.
7. A probe factory must build gate, integration, and probe from the exact same repository and resolver passed to the service.
8. The existing two-argument service constructor must remain backward compatible until plugin wiring has its own review.
9. `SPDB_Plugin` must remain on the existing constructor in this slice.
10. `SPDB_System_State` depends on the current service-health field names and must not be silently broken.
11. The initial composition layer must not execute service mutations.
12. The composition layer must not resolve native references.
13. The composition layer must not add REST routes or persistence.
14. Readiness calls must remain delegated to the reviewed Phase 23L probe.
15. Invalid and disabled write authority must preserve Phase 23L short-circuit behavior.
16. Read and collection readiness must remain resolver-independent.
17. Knowledge readiness must remain repository-and-resolver dependent.
18. The binding constructor must be private so callers cannot inject mismatched components.
19. The binding must not expose a method that replaces repository, resolver, integration, or probe after construction.
20. Exact-head CI must prove current Phase 23L ancestry and inherited Phase 23L/K/J/I/H regressions.

## Authorized Initial Coding Slice

- `SPDB_Collections_Service_Probe_Binding` with a private constructor;
- static creation from one repository and one resolver pair;
- internally constructed Phase 23J gate, Phase 23K integration, and Phase 23L probe;
- access to the paired service plus bounded readiness delegation;
- executable tests for dependency composition, short-circuit behavior, exact call counts, recovery, and zero native resolution;
- dedicated exact-head and exact-base PHP 8.0–8.3 workflow;
- separate stacked Draft PR.

## Explicitly Deferred

- modifying `SPDB_Collections_Service` internals;
- making the service consume the probe;
- modifying `SPDB_Plugin` load order or construction;
- changing `SPDB_System_State`;
- enabling resolver/provider acceptance;
- enabling mutation REST routes or UI;
- changing plugin/stable version;
- Hostinger staging or production claims.

## Acceptance Rule

This initial binding slice requires independent corrective review after coding and complete exact-head PHP 8.0–8.3 QA. A later separately reviewed slice may modify `SPDB_Collections_Service` to consume the bound probe. Plugin injection requires another separate review.

Hostinger staging, real File 00 accounts and native providers, privacy/IDOR/cache/backup/restore/rollback evidence, Founder acceptance, and explicit merge authorization remain mandatory. All PRs remain Draft and unmerged.
