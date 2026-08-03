# Phase 23G Native Reference Resolution Independent Review — 2026-07-31

## Verdict

Phase 23F defined a strict aggregate native-reference resolver contract and deliberately injected no concrete resolver. It was safe and fail-closed, but it was **not ready for multi-provider resolver integration**. The independent review found sixteen architecture, acceptance, isolation, readiness, and test defects. The first corrective implementation was then reviewed again and seven additional authority, privacy, acceptance, and time-of-check/time-of-use defects were found and corrected.

**DO NOT MERGE.** This audit accepts only the corrected source foundation for provider-specific contracts, a File 23-owned default-denied registry, isolated registration, bounded diagnostics, and executable QA. It does not authorize a real provider implementation, resolver injection into knowledge writes, mutation REST/UI, production writes, WordPress staging acceptance, Founder acceptance, or merge.

## Initial Findings

1. The aggregate resolver contract assumed one resolver for all providers but provided no provider registry.
2. Companion modules had no reviewed hook through which to register provider-specific resolvers.
3. No provider-specific contract bound a resolver to one immutable provider key.
4. No resolver version was declared or validated against the registered provider adapter version.
5. No resolver object-type declaration was validated against the adapter’s canonical object types.
6. Duplicate resolver registration had no deterministic conflict behavior.
7. A resolver could be registered for a provider that had no accepted File 23 adapter.
8. Provider-declared technical capability and File 23-owned resolver acceptance were not separated.
9. A provider registration callback exception could interrupt later registrations.
10. A provider resolver exception had no registry-level bounded translation.
11. A non-array/non-error resolver response had no registry-level invalid-response state.
12. Resolver presence could be mistaken for knowledge-write readiness even when no provider was usable.
13. System diagnostics could not distinguish registry presence, registered providers, accepted providers, and ready providers.
14. Registration errors and per-provider resolver health were not available as bounded non-sensitive diagnostics.
15. No source guard prevented silent provider-key normalization or object-type widening.
16. No executable tests covered duplicate registration, missing adapters, version/type mismatch, acceptance denial, callback/runtime isolation, malformed responses, or truthful readiness.

## Corrective Re-review Findings

17. Malformed File 23 governance acceptance entries were silently ignored instead of becoming bounded configuration errors.
18. A provider-returned `WP_Error` could relay sensitive provider text or error data.
19. Direct registry callers could supply forged user, Founder, scope, capability, or environment context unless the registry reconstructed current server authority.
20. Provider responses could include extra fields that would be relayed unless the registry rebuilt an exact safe projection.
21. Provider-returned scope was not required to match the authorized scope exactly.
22. Resolver acceptance could become ready while the corresponding adapter remained unreviewed or revoked.
23. A mutable provider object could change its provider key, provider version, resolver version, or object-type declaration after registration; without a fresh immutable-contract check this created a time-of-check/time-of-use gap.

## Corrections Implemented

- Added `SPDB_Native_Reference_Provider`, binding one implementation to one canonical provider.
- Added `SPDB_Native_Reference_Registry`, implementing the aggregate resolver boundary without registering REST routes or owning native data.
- Required an existing adapter, exact provider version, valid resolver semantic version, and an exact object-type subset.
- Separated adapter acceptance from resolver acceptance; both are File 23-owned, environment-aware, and default-denied.
- Recorded malformed acceptance configuration as a bounded system error rather than silently accepting or discarding it.
- Added deterministic duplicate, missing-adapter, version, object-type, unsupported-type, and not-ready errors.
- Added isolated `spdb/register_native_reference_resolvers` registration dispatch so one invalid or throwing callback cannot block later providers.
- Reconstructed current user, approved membership, scope, Founder state, capabilities, environment, and generation time at the registry boundary.
- Rejected unknown context fields and mismatched caller-supplied authority.
- Replaced provider exceptions and provider `WP_Error` values with bounded generic errors.
- Required exact provider key, object type, object ID, and scope in every provider response.
- Rebuilt responses into the exact allowed projection; provider-added fields are never relayed.
- Strictly validated boolean authorization flags, owner identifier, native version, and same-origin safe destination.
- Kept destinations current-request only; no destination persistence was introduced.
- Added bounded health reconstruction, request caching, separate registered/ready/error counts, and adapter acceptance in diagnostics.
- Re-verified immutable provider key, provider version, resolver version, and object-type declaration before readiness, after health, and immediately before resolution.
- Converted contract drift into bounded `contract_drift` health and denied provider execution.
- Booted the registry with an empty acceptance map and deliberately did not inject it into `SPDB_Collections_Service`.
- Added PHP 8.0–8.3 tests for governance, acceptance, privacy, forged context, response reconstruction, callback/runtime isolation, unsafe destination, health caching, and contract drift.

## Authorized Source State

The corrected Phase 23G source may contain:

- provider-specific resolver contracts;
- a File 23-owned registry and registration dispatcher;
- empty/default-denied resolver acceptance in the plugin runtime;
- bounded diagnostics and source-level test providers;
- exact-head automated workflows and documentation.

It must not contain:

- any real File 03, 06, 21, 22, or other provider resolver;
- registry injection into collection or knowledge writes;
- provider self-acceptance;
- client-supplied user, Founder, scope, capability, environment, or acceptance authority;
- native content import or destination persistence;
- resolver mutation REST routes or mutation UI;
- production mutation;
- staging, Founder, or merge acceptance claims.

## Acceptance Rule

Every later source or documentation change invalidates previous automated evidence. The final documentation-inclusive head must pass:

- Baseline Integrity on PHP 8.0, 8.1, 8.2, and 8.3;
- Collections UI Regression on PHP 8.0, 8.1, 8.2, and 8.3;
- Phase 23G Native Reference Resolution on PHP 8.0, 8.1, 8.2, and 8.3;
- retained artifacts and checksums for that same head.

PR #8 and all predecessor PRs remain Draft and unmerged. WordPress staging, real providers, migration/rollback, privacy/cache/accessibility evidence, Founder acceptance, and explicit merge authorization remain mandatory.
