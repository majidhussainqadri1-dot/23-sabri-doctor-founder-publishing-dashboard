# Security Policy and Threat Model

## Security Objective

File 23 is a private operational dashboard that can expose high-impact publishing actions. Its default posture is fail-closed, least privilege, explicit provider contracts, independent provider acceptance, current File 00 authority/session assertions, mandatory REST rate limiting, and native source-of-truth verification after every action.

## Primary Threats

- IDOR across doctors, authors, reviews, tasks, exports, or campaigns;
- forged role, author, provider, status, capability, session, or environment values;
- provider self-promotion to staging/production acceptance;
- accidental mutation authority for pending, rejected, expired-document, appeal-review, or suspended accounts;
- CSRF on publish, schedule, correction, retraction, bulk, export, delegation, preferences, or settings actions;
- stored/reflected XSS in titles, excerpts, notes, filters, source summaries, and adapter errors;
- SQL injection in federated filters and File 23-owned queries;
- privilege escalation through generic or unregistered action keys;
- stale-object overwrite and review-decision races;
- replayed or duplicated write requests;
- rate-limit bypass by rotating native object/provider identifiers;
- sensitive data leakage through analytics, notifications, logs, exports, cache, or signed destinations;
- malicious, throwing, or incompatible provider adapters;
- unsafe bulk actions;
- scheduled-job principal confusion, tampering, or silent failure;
- audit-log deletion or alteration;
- public indexing/caching of private routes;
- direct mutation of provider-owned posts, comments, media, schedules, corrections, or retractions.

## Mandatory Controls

- authenticated private routes;
- compatible Membership Core contract only after explicit review of the exact integration head;
- server-side canonical File 23 capability and ownership checks;
- current Membership Core status and current publishing/session assertions on sensitive capability decisions;
- only `spdb_view_dashboard` and `spdb_view_own_content` may be used for policy-assigned restricted non-approved views;
- every mutable/institution-wide capability requires an approved/verified account plus any stronger session/role assertion required by its operation;
- WordPress nonces for browser actions;
- explicit operation definitions, schemas, and allowlists;
- independent File 23-controlled staging/production acceptance;
- server-side `wp_get_environment_type()` resolution;
- prepared SQL, sanitization, and contextual escaping;
- exact canonical identifiers with no silent authority normalization;
- safe redirects and on-demand native destination resolution;
- object-version/ETag conflict checks where the native contract exposes a mutable version;
- idempotency keys for write operations;
- bounded payloads and pagination;
- runtime REST rate limiting for every `/spdb/v1` request, with tighter global actor buckets for high-impact operations and fail-closed persistence;
- high-risk confirmation and audit reason;
- short-lived, single-purpose export authorization stored/verified without public sensitive payloads;
- `noindex`, `noarchive`, sitemap exclusion, and private/no-store caching;
- adapter exception isolation and diagnostics;
- **File 20-owned global Safe Mode/Repair/Rollback**; File 23 may expose only its own bounded local reconciliation/repair and sanitized status evidence;
- graceful adapter isolation;
- append-only, access-controlled, tamper-evident audit ledger;
- native object re-read before confirmed success.

## Restricted Account Boundary

Pending, rejected, expired-document, appeal-review, and suspended accounts may retain only an explicitly assigned status/appeal and owned-content read-only workspace permitted by current File 00 policy. That state must not expose create, submit, edit, review, scheduling, interactions, analytics, export, delegation, local repair, policy, provider acceptance, or global Safe Mode actions. UI visibility is not authorization; every route and API operation repeats the server-side account-state and capability checks.

## Adapter Trust Boundary

A native provider may declare only technical capability. It cannot self-declare `staging_accepted` or `production_accepted`. Acceptance belongs to File 23 governance after compatibility, security, integration, staging, and production review.

The File 23 operation broker is mandatory for native dashboard mutations. Calling a provider adapter mutation method directly from an unrelated controller, REST route, AJAX handler, or template is prohibited.

## High-Risk Operations

- publish or unpublish through the canonical native owner;
- publish as another author when expressly authorized by the canonical owner;
- approve/reject review;
- bulk schedule or reschedule;
- global pin/feature;
- material correction or retraction;
- export reports;
- create/revoke delegation;
- change publication policy/provider acceptance;
- bounded File 23 local repair or destructive cleanup if a future approved process exists.

These operations require their dedicated capabilities, approved/verified File 00 state, applicable strong-session/MFA evidence, provider acceptance, current-state validation, object-version checks, confirmation, reason capture, idempotency, native authorization, rate limiting, and audit evidence.

## Patient and Clinical Safety

File 23 must not store patient records, prescriptions, private messages, appointment details, identity documents, consent evidence, patient-document destinations, or patient-identifying excerpts. It may display a bounded privacy/compliance status supplied by a native provider. Patient-identifying content in logs, analytics, notifications, tasks, exports, caches, rate-limit counters, or signed links is prohibited.

## Vulnerability Reporting

Security issues should be reported privately to the repository owner. Public issues must not include credentials, encryption keys, patient data, access tokens, signed URLs, or exploit details for an unpatched vulnerability.
