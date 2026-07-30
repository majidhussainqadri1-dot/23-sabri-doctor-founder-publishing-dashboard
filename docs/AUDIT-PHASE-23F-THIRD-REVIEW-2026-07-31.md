# Phase 23F Third Independent Review — 2026-07-31

## Verdict

The twice-corrected Phase 23F source at `f0d517b46df0b2dab44da2a49a735bbb959d9cac` was suitable as a bounded read/create foundation, but it was **not yet acceptable for the next read-API slice without further correction**. This third independent review found sixteen defects in persistence-boundary strictness, repository health cost, response truthfulness, item authorization, lifecycle semantics, conflict handling, and missing read-only integration.

**DO NOT MERGE.** This review authorizes immediate correction and the next reviewed coding slice only. It is not WordPress staging, real-provider, Founder, production, or merge acceptance.

## Findings

1. Stored numeric fields were cast with `(int)` during repository hydration, so malformed values such as `7abc` could be converted into apparently valid integers before strict service validation.
2. Repository query normalization cast `owner_user_id` permissively, allowing malformed direct repository input to become another valid owner ID.
3. Repository create methods checked only that required keys existed; they did not reject unknown fields or strictly validate prepared persistence-record shapes.
4. JSON encoding failure silently became `[]`, which could convert contributor or target metadata loss into an apparent successful write.
5. The service health gate and every repository method repeatedly invoked full table/column/index verification, causing duplicate schema inspection inside one request.
6. The aggregate `write_enabled` health field became false whenever the native resolver was absent even when collection creation was otherwise ready, making diagnostics misleading.
7. Repository envelopes did not prove that `has_more` matched page, page size, returned count, and total.
8. Repository totals used permissive `is_numeric()` and casts, allowing non-canonical numeric representations to influence pagination metadata.
9. Collection-item reads existed only at repository level; there was no service-level parent collection authorization or object-level item projection boundary.
10. Item list queries excluded archived items while item-detail queries could return archived items, creating inconsistent default read semantics.
11. Collection-item projections had no strict output allowlist, canonical ID/key/version/position validation, or lifecycle timestamp validation.
12. Collection and knowledge projections did not enforce `created <= updated <= archived` or status/archive consistency.
13. Direct repository collection queries did not strictly bind record type and status, despite the service doing so.
14. A duplicate canonical knowledge relation submitted with a new idempotency key could fall through to a generic database failure instead of a deterministic conflict response.
15. Repository insert inputs could be coerced independently of the already-reviewed service policy, weakening the persistence layer as a defense-in-depth boundary.
16. Verified read services had no explicit Phase 23F read-only REST routes, strict request allowlists, pagination headers, or item endpoints; consumers would otherwise be tempted to bypass the service boundary.

## Required Corrections

- Preserve raw stored scalar shapes until strict validators accept them; never sanitize malformed persistence by casting.
- Strictly validate repository queries and create records, including exact field allowlists, canonical hashes, IDs, lists, enums, timestamps, and actor/owner equality.
- Make JSON encoding failure explicit and fail closed.
- Cache repository health per repository instance for one request and expose an explicit refresh path.
- Replace ambiguous aggregate write diagnostics with truthful collection-write and knowledge-write readiness.
- Enforce exact envelope pagination semantics, canonical totals, and lifecycle ordering.
- Add authorized service methods and strict projections for collection-item reads.
- Exclude archived items consistently from default item detail and list reads.
- Return deterministic `409` conflicts for duplicate canonical relations.
- Add an explicit read-only Phase 23F REST controller with no mutation methods.
- Execute new persistence-hardening, item-IDOR, pagination, privacy, and REST tests in every PHP 8.0–8.3 job.

## Authorized Next Coding Slice

After all sixteen defects are corrected and re-reviewed, the next coding slice may provide:

- strict repository and service hardening;
- authorized collection-item list and detail service methods;
- read-only collection, collection-item, and knowledge-link REST routes;
- private/no-store response policy and truthful pagination headers;
- no create/update/archive/reorder REST route;
- no production write enablement;
- no concrete native resolver until its own review gate.

## Merge Gate

PR #7 remains Draft and unmerged. Any source change invalidates previous exact-head evidence. The full PHP 8.0–8.3 matrix, prior regressions, new Phase 23F tests, architecture guards, artifacts, corrective source re-review, WordPress staging, real-account/provider evidence, migration and rollback evidence, accessibility/privacy evidence, Founder acceptance, and explicit merge authorization remain mandatory.