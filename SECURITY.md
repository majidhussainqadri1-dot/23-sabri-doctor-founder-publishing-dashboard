# Security Policy and Threat Model

## Security Objective

File 23 is a private operational dashboard that can expose high-impact publishing actions. Its default posture is fail-closed, least privilege, explicit provider contracts, and native source-of-truth verification after every action.

## Primary Threats

- IDOR across doctors, authors, reviews, tasks, exports, or campaigns;
- forged role, author, provider, or status values;
- CSRF on publish, schedule, correction, retraction, bulk, export, or delegation actions;
- stored/reflected XSS in titles, excerpts, notes, filters, source summaries, and adapter errors;
- SQL injection in federated filters and File 23-owned queries;
- privilege escalation through generic action endpoints;
- stale-object overwrite and review-decision races;
- replayed or duplicated write requests;
- sensitive data leakage through analytics, notifications, logs, exports, or cache;
- malicious/incompatible provider adapters;
- unsafe bulk actions;
- scheduled-job tampering or silent failure;
- audit-log deletion or alteration;
- public indexing/caching of private routes.

## Mandatory Controls

- authenticated private routes;
- server-side capability and ownership checks;
- current Membership Core verification and suspension checks;
- WordPress nonces for browser actions;
- explicit operation schemas and allowlists;
- prepared SQL, sanitization, and contextual escaping;
- safe redirects and canonical route resolution;
- object-version/ETag conflict checks;
- idempotency keys for write operations;
- bounded payloads and pagination;
- per-operation rate limits;
- high-risk confirmation and audit reason;
- short-lived, single-purpose export tokens;
- `noindex`, `noarchive`, sitemap exclusion, and private/no-store caching;
- adapter maturity gates;
- read-only Safe Mode;
- graceful adapter isolation;
- append-only, access-controlled, tamper-evident audit ledger.

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

These operations require dedicated capabilities, current-state validation, object version checks, confirmation, reason capture, idempotency, and audit events.

## Patient and Clinical Safety

File 23 must not store patient records, prescriptions, private messages, appointment details, identity documents, or consent evidence. It may display privacy/compliance status supplied by native providers. Any patient-identifying excerpt in logs, analytics, notifications, tasks, or exports is prohibited.

## Vulnerability Reporting

Security issues should be reported privately to the repository owner. Public issues must not include credentials, encryption keys, patient data, access tokens, or exploit details for an unpatched vulnerability.
