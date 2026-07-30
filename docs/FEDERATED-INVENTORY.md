# Phase 23C — Federated Content Inventory

## Purpose

Phase 23C implements the read-only operational inventory required by File 23 Harmonized Draft 2. It queries native provider adapters, validates their projections, and renders one private content library without copying native publication data into File 23.

## Governing Boundary

> Native ownership remains authoritative. File 23 stores no publication body, native draft, review decision, schedule, source record, media binary, comment, correction, retraction, or raw analytics event.

The inventory is a runtime projection. A native provider must enforce the current user's ownership, visibility, privacy, and policy rules in every `list_items()` and `get_item()` call.

## Query Contract

The dashboard normalizes and bounds:

- page and page size;
- a maximum federated read window of 200 items;
- up to 10 provider keys;
- up to 20 object types;
- search text up to 100 characters;
- lifecycle, review, visibility, operational, language, and topic filters;
- valid ISO date ranges;
- allowlisted sort and direction values;
- `own` or Founder-only `institution` scope.

Client-supplied user IDs or authority values are ignored. File 23 injects the authenticated user ID and resolves the effective scope server-side.

## Projection Contract

Every item must include:

- canonical object type and native object ID;
- native object version/concurrency token;
- title;
- declared privacy classification;
- lifecycle state;
- review state;
- visibility state;
- operational state.

Optional fields include summary, author, language, topic, timestamps, thumbnail, canonical destination, compliance alerts, and native edit/preview/public destinations.

Unknown native states map to `unknown` and create a mapping-required warning. File 23 never guesses a more favorable state.

## Destination Safety

Native destinations are runtime-only and are never stored by File 23. They must:

- remain on the platform origin;
- contain no embedded credentials;
- contain no nonce, token, signature, secret, password, authorization, expiry, or key query parameter;
- use bounded valid URLs.

The item inspector may display safe `Continue Editing`, `Preview`, and `View Public Page` links. These links return the user to the native owner, including File 22 where the provider supplies a compliant Composer destination.

## Provider Failure Isolation

Each provider query is isolated. A throwing, malformed, unavailable, incompatible, revoked, or temporarily suspended provider cannot crash the dashboard or falsify another provider's results.

The response includes bounded provider error codes and any valid partial results. It does not expose exception messages, stack traces, secrets, or patient data.

## Actions Boundary

Phase 23C may project operation keys that the native provider reports for the current object, but:

- the operation must exist in the registered adapter definition;
- the current user must hold the required File 23 capability;
- environment eligibility is shown truthfully;
- no mutation REST route, form, or button is exposed;
- `execution_exposed` remains false.

Actual review, scheduling, correction, retraction, and other mutations require later reviewed phases and must execute through the guarded operation broker.

## REST Endpoints

Read-only endpoints:

- `GET /wp-json/spdb/v1/inventory`
- `GET /wp-json/spdb/v1/inventory/{provider}/{object_type}/{object_id}`

Both require:

- authentication;
- compatible File 00 status;
- `spdb_view_dashboard`;
- `spdb_view_own_content`;
- private no-store/noindex REST response policy.

## Explicit Non-Scope

Phase 23C does not implement:

- provider acceptance persistence;
- production write actions;
- universal review decisions;
- scheduling or calendar mutation;
- corrections or retractions;
- analytics collection;
- comments or interactions;
- cross-module collection storage;
- File 22 draft ownership.

## Staging Acceptance Requirements

- real File 21 and File 22 adapters return accurate projections;
- own versus institution scope matches native records;
- another doctor's private item is never exposed;
- unknown states remain unknown;
- malformed or sensitive destinations are rejected;
- partial provider failure leaves healthy results usable;
- list and inspector REST responses remain private through LiteSpeed and hosting caches;
- mobile, keyboard, screen-reader, contrast, RTL, and weak-connection checks pass;
- no duplicate content database or Composer exists.
