# Phase 23G Native Reference Resolution Independent Review — 2026-07-31

## Verdict

Phase 23F defines a strict aggregate native-reference resolver contract and validates returned native truth, but it deliberately injects no concrete resolver. The current source is therefore safe and fail-closed, yet it is **not ready for multi-provider resolver integration**. Independent review found sixteen architecture, acceptance, isolation, readiness, and test defects that must be corrected before any native module may participate.

**DO NOT MERGE.** This audit authorizes only a provider-specific resolver contract, File 23-owned registry, isolated registration dispatcher, truthful readiness, and executable test foundation. It does not authorize a real provider implementation, knowledge-link production writes, mutation REST, staging acceptance, or merge.

## Findings

1. The aggregate resolver contract assumes one resolver for all providers but provides no provider registry.
2. Companion modules have no reviewed hook through which to register provider-specific resolvers.
3. No provider-specific contract binds a resolver to one immutable provider key.
4. No resolver version is declared or validated against the registered provider adapter version.
5. No resolver object-type declaration is validated against the adapter’s canonical object types.
6. Duplicate resolver registration has no deterministic conflict behavior.
7. A resolver could be registered for a provider that has no accepted File 23 adapter.
8. Provider-declared technical capability and File 23-owned acceptance are not separated for resolver activation.
9. A provider callback exception could interrupt later resolver registrations without an isolation dispatcher.
10. A provider resolver exception has no registry-level bounded failure translation.
11. A non-array/non-error resolver response has no registry-level invalid-response state.
12. The aggregate service currently treats any non-null resolver as knowledge-write readiness, even when no usable provider resolver exists.
13. System diagnostics cannot distinguish resolver object presence from accepted resolver readiness.
14. Registration errors and per-provider resolver health are not available as bounded non-sensitive diagnostics.
15. No source guard prevents resolver registration from silently normalizing provider keys or widening object types.
16. No executable tests cover duplicate registration, missing adapter, version mismatch, object-type mismatch, acceptance denial, callback isolation, runtime exception isolation, invalid response, or truthful readiness.

## Required Corrections

- Introduce a provider-specific native-reference resolver contract.
- Add a File 23-owned native-reference registry implementing the aggregate resolver contract.
- Bind each provider resolver to an already registered adapter, exact provider version, and an object-type subset.
- Keep resolver acceptance File 23-owned and default-denied; providers may not self-accept.
- Add deterministic duplicate, unregistered-provider, version, object-type, and acceptance errors.
- Add an isolated registration dispatcher on a dedicated hook.
- Translate provider exceptions and malformed responses into bounded `WP_Error` states.
- Add non-sensitive registry health without object IDs, destinations, user data, or secrets.
- Make `SPDB_Collections_Service` distinguish resolver presence from resolver readiness.
- Keep production writes, mutation REST, real provider integration, and persisted destinations disabled.
- Add PHP 8.0–8.3 tests and architecture controls.

## Authorized Coding Slice

This review authorizes:

- `SPDB_Native_Reference_Provider`;
- `SPDB_Native_Reference_Registry`;
- `SPDB_Native_Reference_Registration`;
- default-denied File 23-owned acceptance injection;
- plugin boot integration and truthful health fields;
- provider isolation and registration tests;
- documentation and an exact-head QA workflow.

It does not authorize:

- any real File 03, 06, 21, 22, or other provider resolver;
- client-supplied acceptance or environment authority;
- native content import;
- destination persistence;
- collection, item, or knowledge mutation routes;
- production mutation;
- staging, Founder, or merge acceptance.

## Acceptance Rule

Every correction and coding change must be re-reviewed. The final documentation-inclusive head must pass the full baseline matrix and a dedicated Phase 23G PHP 8.0–8.3 matrix. PRs remain Draft and unmerged.
