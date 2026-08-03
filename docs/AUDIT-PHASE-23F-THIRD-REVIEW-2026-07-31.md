# Phase 23F Third Independent Review — 2026-07-31

## Verdict

The twice-corrected Phase 23F source at `f0d517b46df0b2dab44da2a49a735bbb959d9cac` was suitable as a bounded read/create foundation, but it was **not acceptable for the next read-API slice without further correction**. This third independent review found sixteen principal defects in persistence-boundary strictness, repository health cost, response truthfulness, item authorization, lifecycle semantics, conflict handling, and missing read-only integration.

All sixteen principal findings were corrected. The mandatory corrective re-review then found four additional implementation-edge defects: one optional-filter regression exposed by CI, integer-overflow saturation, invalid UTF-8 acceptance, and malformed optional timestamp erasure. Those defects were also corrected and covered by executable regression tests.

**DO NOT MERGE.** The corrected source is a Draft candidate only. It is not WordPress staging, real-provider, Founder, production, or merge acceptance.

## Principal Findings

1. Stored numeric fields were cast with `(int)` during repository hydration, so malformed values such as `7abc` could be converted into apparently valid integers before strict service validation.
2. Repository query normalization cast `owner_user_id` permissively, allowing malformed direct repository input to become another valid owner ID.
3. Repository create methods checked only that required keys existed; they did not reject unknown fields or strictly validate prepared persistence-record shapes.
4. JSON encoding failure silently became `[]`, which could convert contributor or target metadata loss into an apparent successful write.
5. The service health gate and every repository method repeatedly invoked full table/column/index verification, causing duplicate schema inspection inside one request.
6. The aggregate `write_enabled` health field became false whenever the native resolver was absent even when collection creation was otherwise ready, making diagnostics misleading.
7. Repository envelopes did not prove that `has_more` matched page, page size, returned count, and total.
8. Repository totals used permissive numeric checks and casts, allowing non-canonical numeric representations to influence pagination metadata.
9. Collection-item reads existed only at repository level; there was no service-level parent collection authorization or object-level item projection boundary.
10. Item list queries excluded archived items while item-detail queries could return archived items, creating inconsistent default read semantics.
11. Collection-item projections had no strict output allowlist, canonical ID/key/version/position validation, or lifecycle timestamp validation.
12. Collection and knowledge projections did not enforce `created <= updated <= archived` or status/archive consistency.
13. Direct repository collection queries did not strictly bind record type and status, despite the service doing so.
14. A duplicate canonical knowledge relation submitted with a new idempotency key could fall through to a generic database failure instead of a deterministic conflict response.
15. Repository insert inputs could be coerced independently of the already-reviewed service policy, weakening the persistence layer as a defense-in-depth boundary.
16. Verified read services had no explicit Phase 23F read-only REST routes, strict request allowlists, pagination headers, or item endpoints; consumers would otherwise be tempted to bypass the service boundary.

## Corrections Applied

- Raw stored scalar shapes are preserved until strict validators accept them; malformed persistence is never repaired by permissive casting.
- Direct repository queries and create records use exact field allowlists and canonical hashes, IDs, lists, enums, timestamps, and actor/owner equality.
- JSON encoding failure is explicit and fail-closed.
- Repository health and Schema Version 3 verification are cached for one repository instance/request, with an explicit refresh method.
- Read, collection-write, knowledge-write, and any-write readiness are reported separately and truthfully.
- Page, page size, returned count, total, and continuation state must be mathematically consistent.
- Collection-item list and detail service methods require authorized parent collection resolution before any item query.
- Archived items are consistently excluded from default item list and detail reads.
- Collection, item, and knowledge projections use strict output allowlists, canonical values, and lifecycle ordering.
- Duplicate canonical knowledge relations return deterministic `409` conflicts.
- Six explicit read-only REST routes were added for collections, collection details, collection items, item details, knowledge links, and link details.
- REST requests use strict query allowlists, approved-account/capability checks, parent authorization, private/no-store policy, and truthful pagination headers.
- No Phase 23F REST create, update, reorder, archive, or generic action route was added.

## Corrective Re-review Findings

### 1. Optional-filter regression

The first code-inclusive matrix exposed an implementation regression in optional `record_type` and `status` handling. The condition used a default value but the true branch reread an absent array key, producing warnings and invalid downstream behavior. Optional filters now use explicitly derived raw values before validation.

### 2. Integer-overflow saturation

Very large decimal strings could pass a digit regex and then saturate to `PHP_INT_MAX` during integer casting. Strict positive and non-negative integer validators now require an exact decimal round trip after casting. Overflowing IDs, versions, totals, positions, pages, and per-page values fail closed.

### 3. Invalid UTF-8

Invalid UTF-8 could cause Unicode regular expressions to return `false` and escape some earlier checks. Central text-length validation now returns an invalid maximum sentinel unless the entire string is valid UTF-8. Invalid UTF-8 text and native versions are rejected in both service and persistence layers.

### 4. Malformed optional database timestamps

A non-string optional database timestamp could previously become an empty timestamp and appear valid. Hydration now preserves malformed timestamp state as an explicit invalid sentinel so service projection validation rejects it instead of erasing the defect.

## Executable Evidence Added

The PHP 8.0–8.3 matrix now executes tests for:

- direct repository malformed and overflowing owner IDs;
- strict persistence-record allowlists;
- invalid UTF-8 persistence and projection values;
- malformed optional database timestamps;
- overflowing repository totals and item versions;
- request-cached schema health;
- exact replay and payload conflict;
- duplicate canonical relation conflict;
- collection-item parent IDOR resistance;
- archived-item default exclusion;
- lifecycle ordering and strict item projection;
- six explicit GET-only REST routes;
- pending-account and institution-capability denial;
- unknown REST query rejection;
- truthful pagination headers and continuation state;
- absence of Phase 23F mutation REST routes.

The static document does not hardcode a final run number. Exact-head acceptance is determined only by the GitHub checks attached to the current documentation-inclusive commit and by retained artifacts for that same commit.

## Authorized Next Coding Boundary

The next coding slice may begin only after the current documentation-inclusive head passes the complete matrix and is re-reviewed. The next slice may address the accessible Collections dashboard projection and read-only UI. It must not enable mutation, update, reorder, archive, production writes, or a concrete native resolver without their own complete review gates.

## Merge Gate

PR #7 remains Draft and unmerged. The full PHP 8.0–8.3 matrix, prior regressions, new Phase 23F tests, architecture controls, retained artifacts, WordPress staging, real-account/provider evidence, Schema Version 2-to-3 migration, cache/privacy/accessibility evidence, backup/restore/rollback evidence, Founder review, Founder acceptance, and explicit merge authorization remain mandatory.