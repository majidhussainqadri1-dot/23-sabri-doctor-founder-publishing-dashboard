# Four-Dimensional State Projection

## Principle

File 23 must not replace native provider states with a universal database status. It may map native states into four independent display dimensions. Native status and provider metadata remain the source of truth.

## 1. Content Lifecycle

- `draft`
- `submitted`
- `published`
- `archived`
- `withdrawn`
- `retracted`
- `unknown`

## 2. Review State

- `not_required`
- `awaiting_review`
- `under_review`
- `changes_requested`
- `approved`
- `rejected`
- `on_hold`
- `unknown`

## 3. Visibility State

- `private`
- `restricted`
- `public`
- `hidden`
- `embargoed`
- `unknown`

## 4. Operational State

- `healthy`
- `processing`
- `scheduled`
- `failed`
- `adapter_unavailable`
- `stale`
- `unknown`

## Projection Record

A projection should include:

- provider key;
- native object type and ID;
- native state code;
- native state label;
- four projected dimensions;
- projection timestamp;
- adapter contract version;
- mapping version;
- object version/ETag;
- stale flag.

## Mapping Rules

1. Mapping is adapter-owned and versioned.
2. Unknown native values map to `unknown`, never to an optimistic state.
3. `approved` does not imply `published`.
4. `published` does not imply `public`.
5. `scheduled` is operational information, not a lifecycle replacement.
6. `failed` must not erase the last confirmed native lifecycle state.
7. File 23 caches projections only within bounded freshness limits.

## UI Rule

The dashboard may combine dimensions for human-readable labels, but filters and security decisions must use provider truth and registered capability callbacks, not the combined label.
