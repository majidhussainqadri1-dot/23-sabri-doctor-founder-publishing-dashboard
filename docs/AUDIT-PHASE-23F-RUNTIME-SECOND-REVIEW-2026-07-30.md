# Phase 23F Runtime Second Review — 2026-07-30

## Verdict

The corrected Phase 23F foundation at `062077d4813b99d797c85897a2f536e521208306` passed its prior source gate, but the newly introduced runtime service was **not acceptable for the next persistence stage without correction**. Independent re-review found eighteen defects in read authorization, repository trust, schema/runtime alignment, idempotency semantics, native-reference validation, health reporting, and actual repository availability.

**DO NOT MERGE.** This review authorizes correction work only. It does not constitute WordPress staging, real-provider, production, Founder, or merge acceptance.

## Findings

1. `list_collections()` trusted the repository envelope and rows without object-level visibility validation, allowing a faulty repository to inject another user’s record.
2. Collection reads required login and a capability but did not require a currently approved File 00 account; a pending or suspended account retaining a capability could read metadata.
3. A non-Founder institution query was silently downgraded to `own` instead of failing closed, producing ambiguous and potentially misleading results.
4. The status filter was validated against the union of collection and campaign states, so `collection + paused` and similar impossible combinations were accepted.
5. Runtime health could report writes enabled when the repository or schema was unhealthy or missing.
6. The write gate checked only environment configuration and dependency presence; it did not require a healthy verified schema.
7. `maybe_upgrade()` trusted the stored schema-version option and did not re-verify tables when the option already matched.
8. Schema verification proved table existence only; required columns and indexes were not checked.
9. The service produced `source_native_version` and `target_native_version`, but the knowledge-link table had no matching columns.
10. Audit reasons were validated but the schema had no canonical created/last audit-reason fields.
11. An idempotency-key hash existed, but no request fingerprint existed to distinguish an exact replay from reuse of the same key with another payload.
12. `resolve_reference()` accepted a scope directly and silently reduced unauthorized institution context to `own`.
13. Native owner identifiers were permissively cast; malformed values could become owner `0` rather than fail closed.
14. Native versions used byte length and permitted control characters or malformed values.
15. Collection creation unnecessarily required a native-reference resolver even though a collection without items has no native reference.
16. The default runtime injected no concrete repository, so even verified read operations remained unavailable after schema installation.
17. System state treated a missing native resolver as a global collection failure even though verified collection reads do not require a resolver.
18. No executable concrete-repository tests proved prepared queries, schema readiness, idempotent replay, payload-conflict rejection, bounded lists, or fail-closed unsupported operations.

## Required Corrections

- Require a currently approved File 00 account for collection and knowledge reads.
- Reject unauthorized institution scope instead of rewriting it.
- Bind statuses to their selected record type.
- Validate every repository envelope and projected row before returning it.
- Separate `read_ready`, `collection_write_ready`, and `knowledge_write_ready` health states.
- Require verified repository/schema health at the write gate.
- Upgrade to a schema that verifies tables, columns, and indexes on every lifecycle check.
- Persist bounded observed native versions, request fingerprints, and created/last audit reasons.
- Validate exact scope, native owner shape, native version shape, and safe current destinations.
- Allow resolver-independent collection creation while keeping knowledge-link creation resolver-dependent.
- Introduce a concrete WordPress repository for verified reads and idempotent creates only.
- Keep update, reorder, archive, REST mutation, production writes, and native-resolver injection disabled until their separate review gates.
- Execute runtime and repository tests in every PHP 8.0–8.3 matrix job.

## Next Coding Boundary

After all findings are corrected and re-reviewed, the next coding slice may include:

- Schema Version 3;
- a concrete `SPDB_WP_Collections_Repository`;
- verified collection, collection-item, and knowledge-link reads;
- idempotent collection and knowledge-link creates in explicitly enabled local/development/staging environments;
- no mutation REST route;
- no update, reorder, or archive execution;
- no production write enablement.

## Merge Gate

This audit does not approve the Pull Request. PR #7 must remain Draft and unmerged until corrected exact-head QA, WordPress staging, real File 00 accounts, real native providers, migration and rollback evidence, accessibility and privacy evidence, Founder review, Founder acceptance, and explicit merge authorization are complete.