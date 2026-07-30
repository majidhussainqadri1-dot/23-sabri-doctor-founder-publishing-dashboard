# Phase 23C — Federated Content Inventory

## Purpose

Phase 23C implements a read-only operational inventory over native provider adapters. It validates runtime projections and renders one private content library without importing native publication or workflow data into File 23.

## Governing Boundary

> Native ownership remains authoritative, but File 23 applies defense-in-depth validation before any projection is displayed.

File 23 stores no publication body, native draft, review decision, schedule, source record, media binary, comment, correction, retraction, or raw analytics event.

## Authorization and Scope

Every request requires authentication, compatible File 00 state, `spdb_view_dashboard`, and `spdb_view_own_content`.

- `own` scope: File 23 injects the authenticated user ID and independently requires each validated projection's `owner_user_id` to match.
- `institution` scope: available only when `smc_is_founder()` verifies the current user server-side.
- The inspector repeats the ownership check and returns a non-enumerating unavailable response for cross-owner objects.
- Client-supplied user, owner, role, capability, status, or environment values are never authoritative.

Native providers must also enforce their own ownership, visibility, privacy, and policy rules. The two checks are cumulative.

## Query Contract

The dashboard accepts bounded exact-canonical values for page, page size, up to 10 providers, up to 20 object types, search, lifecycle, review, visibility, operational, language, topic, ISO dates, sort, direction, and scope.

- Maximum 50 items per page.
- Maximum accessible federated window: 200 items.
- Search is limited to 100 characters and excludes contact details and URLs.
- Provider output is rechecked against normalized filters before rendering.

## Projection Contract

Every item must provide exact canonical object type, native object ID, object version, title, declared privacy class, validated `owner_user_id`, and all four state dimensions.

Optional fields include summary, author, language, topic, absolute RFC 3339 timestamps, compliance alerts, canonical URL, thumbnail, and edit/preview/public destinations.

Unknown native states map to `unknown` and create a mapping-required warning. Duplicate provider/type/object references render once and produce a bounded diagnostic.

## Totals and Pagination

The response distinguishes:

- `total`: native reported total;
- `accessible_total`: reachable count inside the 200-item safety window;
- `validated_window_count`: projections that passed File 23 validation in the retrieved window.

Page links never imply that results outside the bounded window are available. Active non-sensitive filters are preserved during pagination and inspection.

## Destination and Thumbnail Safety

Canonical, thumbnail, edit, preview, and public URLs are runtime-only and must:

- use the platform's exact scheme, host, and effective port;
- contain no embedded credentials or fragment;
- contain no nonce, token, signature, secret, password, authorization, expiry, JWT, API-key, or key material;
- contain no nested redirect URL or secret-bearing query value;
- pass WordPress URL sanitization.

External thumbnails are not accepted in Phase 23C because they can disclose private dashboard access through third-party requests.

## Provider Failure Isolation

Throwing, malformed, unavailable, incompatible, revoked, or suspended providers cannot crash the dashboard. Provider failures become bounded generic codes. Provider-defined internal codes, exception messages, stack traces, secrets, and patient data are not reflected.

Malformed totals invalidate that provider response. Healthy providers' valid results remain usable.

## Actions Boundary

Operation metadata is displayed only when the key is scalar, exact-canonical, unique, registered, and capability-authorized. `execution_exposed` remains false. No mutation REST route, form, button, or direct adapter execution exists.

## REST Endpoints

- `GET /wp-json/spdb/v1/inventory`
- `GET /wp-json/spdb/v1/inventory/{provider}/{object_type}/{object_id}`

Responses inherit File 23's private no-store/noindex REST policy.

## Mobile and Accessibility

The inventory table remains a horizontally scrollable, keyboard-focusable region on narrow screens. Lifecycle, review, visibility, and modified status columns are not removed from mobile access.

## Explicit Non-Scope

Phase 23C does not implement provider acceptance persistence, production writes, review decisions, calendar mutation, corrections, retractions, analytics collection, interactions, cross-module collection storage, or File 22 draft ownership.

## Review and Acceptance

The corrective source review is recorded in `docs/AUDIT-PHASE-23C-2026-07-30.md`. Exact-head QA, real File 21/File 22 adapters, Hostinger staging, real-account IDOR/privacy tests, cache verification, accessibility, rollback, and Founder acceptance remain mandatory.
