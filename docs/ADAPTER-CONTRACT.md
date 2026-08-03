# Versioned Provider Adapter Contract — 2.0.0

## Purpose

File 23 is a federated operational interface. Every native module integrates through a registered, versioned adapter. Native data and native state remain authoritative.

## Required Adapter Metadata

An adapter must provide:

- an immutable canonical provider key;
- provider/plugin name and semantic version;
- minimum and maximum supported File 23 contract versions;
- provider-declared technical capability state;
- supported object types;
- privacy classifications;
- supported File 23 capability keys;
- complete operation definitions;
- a bounded non-sensitive health callback.

Provider keys, object types, privacy classes, capability keys, and operation keys must arrive already canonical. File 23 must reject, not silently normalize, non-canonical identifiers.

## Contract Compatibility

Compatibility is an explicit inclusive range:

`minimum_contract <= SPDB_CONTRACT_VERSION <= maximum_contract`

All three values must be valid semantic versions. File 23 must not infer compatibility from a loose integer cast or from provider activation.

## Technical Capability and Institutional Acceptance

These are separate domains.

### Provider-declared technical capability

- `unavailable`
- `detected`
- `incompatible`
- `read_only`
- `write_capable`
- `review_capable`
- `temporarily_suspended`

A provider may declare technical ability only. It may not self-declare staging or production acceptance.

### File 23-controlled acceptance

- `unreviewed`
- `staging_accepted`
- `production_accepted`
- `revoked`

Acceptance is supplied by File 23-owned governance after review and testing. Production writes require `production_accepted`. Non-production writes require `staging_accepted` or `production_accepted`. The environment is resolved server-side through WordPress and is never supplied by a request or adapter.

## Required Read Operations

An adapter provides, where supported:

- paginated privacy-filtered item listing;
- one item projection;
- native lifecycle/review/visibility/operational states;
- current object version or ETag;
- stable non-secret edit, preview, and public destination descriptors;
- currently allowed operation keys;
- bounded analytics aggregates;
- interaction summary;
- audit destination or summary;
- source/media compliance summaries.

## Operation Definition

Every mutable operation must be declared before execution with:

- stable operation key;
- required File 23 capability;
- ownership requirement;
- approved-account requirement;
- native current-state guard requirement;
- object-version/ETag requirement;
- idempotency-key requirement;
- audit-reason requirement;
- exact payload schema;
- rate-limit policy;
- success schema;
- error schema.

Generic unregistered action strings are prohibited. Dashboard controllers must execute provider operations through the File 23 operation broker, never by calling the adapter mutation method directly.

## Authorization Layers

Environment acceptance is only one gate. Every operation must also pass:

1. registered provider and operation;
2. supported object type;
3. current File 00 approved/verified account state;
4. current server-side File 23 capability;
5. provider ownership and policy decision;
6. native state guard;
7. object-version/ETag conflict check;
8. idempotency/replay control;
9. required audit reason;
10. provider-side validation and rate limit.

No client-supplied role, author, status, provider, capability, or environment value is authority.

## Failure and Isolation Rules

- One adapter failure must not crash the dashboard.
- Registration and operation exceptions become bounded non-sensitive `WP_Error` results.
- Failed sections show an explicit unavailable/stale state.
- Failure must never be interpreted as approved, published, public, or deleted.
- The native provider remains the source of truth after every action.
- File 23 must re-read the native object before reporting confirmed success.
- Registration failures must be available to System Check without exposing stack traces or patient data.

## Concurrency and Idempotency

Versioned mutation must fail on stale object versions rather than overwrite newer decisions. Required idempotency keys must be bounded and single-purpose. A provider result is not final dashboard truth until the native object is successfully re-read.
