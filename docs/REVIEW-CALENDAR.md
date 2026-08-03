# Phase 23E — Universal Review Inbox and Federated Publishing Calendar

## Status

Initial implementation candidate. Independent source review, defect correction, corrective re-review, exact-head QA, real native adapters, WordPress staging, real-account validation, accessibility, rollback, and Founder acceptance remain mandatory.

**DO NOT MERGE.**

## Governing Ownership Rule

File 23 owns only the private federated operational projection and explicit guarded routing. It does not own or duplicate:

- native review rows, decisions, notes, appeals, reviewer assignments, or review audit ledgers;
- native schedules, time zones, cron jobs, reconciliation state, or failed-schedule records;
- publication bodies, drafts, consent evidence, source records, media, comments, corrections, or retractions.

Every review action or schedule action is executed by the native provider through the guarded operation broker, followed by a native object re-fetch. File 23 never treats optimistic UI state as final truth.

## Optional Provider Contract

A compatible provider may implement `SPDB_Review_Calendar_Provider_Adapter` in addition to Adapter Contract `2.0.0`.

```php
public function get_review_queue( array $context, array $query );
public function get_calendar_entries( array $context, array $query );
```

The context is generated server-side from the current authenticated user, File 00 account state, Founder state, capabilities, scope, and environment. Browser-supplied user, role, reviewer, author, capability, acceptance, or environment fields are non-authoritative.

## Universal Review Inbox

Each native queue item includes only bounded validated fields:

- provider key, native object type and object ID;
- title and author projection;
- native review state;
- assigned reviewer and due date;
- privacy, safety, source, and copyright flags;
- native review destination;
- native version and freshness timestamp;
- adapter-declared allowed operations.

Non-Founder reviewers may see only items assigned to them or explicitly permitted by the native provider and File 23 capability boundary. Where separation of duties is required, an author cannot perform final approval or rejection on their own content.

Supported explicit operation routes are:

- `approve_review`;
- `request_changes`;
- `reject_review`;
- `assign_reviewer`.

## Federated Publishing Calendar

Each native calendar item includes:

- provider and native object reference;
- author and scope;
- scheduled UTC timestamp;
- native IANA time zone;
- native operational schedule state;
- conflict flags;
- native edit destination;
- native version and last-sync timestamp;
- adapter-declared allowed operations.

Supported explicit operation routes are:

- `schedule`;
- `reschedule`;
- `unschedule`.

File 23 creates no schedule table. Visual movement or a REST request is not success until the native adapter validates the request, executes it, and the operation broker successfully re-fetches the native object.

## Security and Integrity Controls

- exact canonical provider and object-type keys;
- bounded provider and item counts;
- exact same-origin destinations with no credentials, fragments, secrets, signatures, expiry data, or nested redirects;
- absolute RFC 3339 timestamps;
- IANA time zones only;
- current File 00 approval and capability checks;
- provider operation declaration and native `get_allowed_operations()` intersection;
- File 23-controlled environment acceptance before mutation;
- object version, idempotency key, and audit reason requirements;
- generic failure normalization with no provider exception details;
- native object re-fetch after every successful operation;
- no patient identifiers, contact details, URLs, or secrets in projected text.

## Initial Limitations

- No real File 21 Newsroom adapter is included.
- No real File 22 schedule/composer adapter is included.
- Provider acceptance is not persisted by this candidate; production mutations therefore fail closed by default.
- Native action UI remains a safe native destination plus projected authorized-operation list; direct dashboard mutation UX requires later reviewed acceptance and staging evidence.
- Real cron failure, author suspension, permission loss, privacy hold, and timezone reconciliation require native provider integration.
