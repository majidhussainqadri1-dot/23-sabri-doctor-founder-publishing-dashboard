# Versioned Provider Adapter Contract

## Purpose

File 23 is a federated operational interface. Every native module integrates through a registered, versioned adapter. The adapter is the only permitted bridge for queries and write operations.

## Required Adapter Metadata

- provider key;
- adapter contract version;
- provider plugin name/version;
- supported object types;
- maturity state;
- privacy classifications;
- supported capabilities;
- health-check callback.

## Required Read Operations

An adapter must be able to provide, where supported:

- paginated item listing;
- item projection;
- native lifecycle/review/visibility/operational states;
- current object version or ETag;
- canonical edit, preview, and public destinations;
- allowed operations for the current user;
- bounded analytics aggregates;
- interaction summary;
- audit destination or summary;
- source/media compliance summaries.

## Write Operation Registration

Every write operation must register:

- stable operation key;
- exact request schema;
- capability callback;
- ownership callback;
- verification/suspension callback;
- current-state guard;
- object-version/ETag requirement;
- idempotency requirement;
- rate limit;
- audit reason requirement;
- success and error schema.

Generic unregistered action strings are prohibited.

## Adapter Maturity States

1. `unavailable`
2. `detected`
3. `incompatible`
4. `read_only`
5. `write_capable`
6. `review_capable`
7. `staging_accepted`
8. `production_accepted`
9. `temporarily_suspended`

Production writes are allowed only for `production_accepted` adapters. Staging writes require at least `staging_accepted` in staging.

## Failure Rules

- One adapter failure must not crash the dashboard.
- Failed sections show a bounded, non-sensitive error state.
- Cached data must be visibly marked stale.
- No failed response may be interpreted as approved, published, public, or deleted.
- The native provider remains the source of truth after every action.
- The dashboard must re-read the native object after a successful action.

## Compatibility

Adapters must declare minimum and maximum supported contract versions. Incompatible adapters enter read-only or unavailable mode; File 23 must not guess compatibility.

## Idempotency and Concurrency

Write calls must support an idempotency key and object version/ETag when the provider supports mutation. Stale actions must fail with a conflict response rather than overwrite newer decisions.
