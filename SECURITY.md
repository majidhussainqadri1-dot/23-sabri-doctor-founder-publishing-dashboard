# Security Policy and Threat Model

## Security Objective

File 23 is a private operational dashboard that can expose high-impact publishing actions. Its default posture is fail-closed, least privilege, explicit provider contracts, independent provider acceptance, and native source-of-truth verification after every action.

## Primary Threats

- IDOR across doctors, authors, reviews, tasks, exports, or campaigns;
- forged role, author, provider, status, capability, or environment values;
- provider self-promotion to staging/production acceptance;
- accidental mutation authority for pending, rejected, expired-document, or suspended accounts;
- CSRF on publish, schedule, correction, retraction, bulk, export, or delegation actions;
- stored/reflected XSS in titles, excerpts, notes, filters, source summaries, and adapter errors;
- SQL injection in federated filters and File 23-owned queries;
- privilege escalation through generic or unregistered action keys;
- stale-object overwrite and review-decision races;
- replayed or duplicated write requests;
- sensitive data leakage through analytics, notifications, logs, exports, cache, or signed destinations;
- malicious, throwing, or incompatible provider adapters;
- unsafe bulk actions;
- scheduled-job tampering or silent failure;
- audit-log deletion or alteration;
- public indexing/caching of private routes;
- direct mutation of provider-owned posts, comments, media, schedules, corrections, or retractions.

## Mandatory Controls

- authenticated private routes;
- compatible Membership Core `>= 1.0.1` and `< 2.0.0`, unless a later range is formally reviewed;
- server-side canonical File 23 capability and ownership checks;
- current Membership Core status checks on every capability decision;
- only `spdb_view_dashboard` and `spdb_view_own_content` may be used for policy-assigned restricted non-approved views;
- every mutable/institution-wide capability requires an `approved` or `verified` account;
- WordPress nonces for browser actions;
- explicit operation definitions, schemas, and allowlists;
- independent File 23-controlled staging/production acceptance;
- server-side `wp_get_environment_type()` resolution;
- prepared SQL, sanitization, and contextual escaping;
- exact canonical identifiers with no silent normalization;
- safe redirects and on-demand native destination resolution;
- object-version/ETag conflict checks;
- idempotency keys for write operations;
- bounded payloads and pagination;
- per-operation rate limits;
- high-risk confirmation and audit reason;
- short-lived, single-purpose export tokens stored hashed where applicable;
- `noindex`, `noarchive`, sitemap exclusion, and private/no-store caching;
- adapter exception isolation and diagnostics;
- read-only Safe Mode;
- graceful adapter isolation;
- append-only, access-controlled, tamper-evident audit ledger;
- native object re-read before confirmed success.

## Restricted Account Boundary

Pending, rejected, expired-document, appeal-review, and suspended accounts may retain an explicitly assigned status/appeal and owned-content read-only workspace. That state must not expose create, submit, edit, review, scheduling, interactions, analytics, export, delegation, repair, policy, or Safe Mode actions. UI visibility is not authorization; every route and API operation must repeat the server-side account-state and capability checks.

## Adapter Trust Boundary

A native provider may declare only technical capability. It cannot self-declare `staging_accepted` or `production_accepted`. Acceptance belongs to File 23 governance after compatibility, security, integration, staging, and production review.

The File 23 operation broker is mandatory for dashboard mutations. Calling a provider adapter mutation method directly from a controller, REST route, AJAX handler, or template is prohibited.

## High-Risk Operations

- publish or unpublish;
- publish as another author;
- approve/reject review;
- bulk schedule or reschedule;
- global pin/feature;
- material correction or retraction;
- export reports;
- create/revoke delegation;
- change publication policy;
- repair or destructive cleanup.

These operations require dedicated capabilities, approved/verified File 00 status, provider acceptance, current-state validation, object-version checks, confirmation, reason capture, idempotency, native authorization, rate limiting, and audit events.

## Patient and Clinical Safety

File 23 must not store patient records, prescriptions, private messages, appointment details, identity documents, consent evidence, patient-document destinations, or patient-identifying excerpts. It may display a bounded privacy/compliance status supplied by a native provider. Patient-identifying content in logs, analytics, notifications, tasks, exports, caches, or signed links is prohibited.

## Vulnerability Reporting

Security issues should be reported privately to the repository owner. Public issues must not include credentials, encryption keys, patient data, access tokens, signed URLs, or exploit details for an unpatched vulnerability.
