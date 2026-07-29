# Native Data-Ownership Contract

## File 23-Owned Data

File 23 may own only the minimum operational metadata required to provide a federated dashboard:

- private dashboard preferences;
- saved filters and views;
- dashboard layout preferences;
- cross-module object references;
- cross-module tasks and assignments;
- cross-module collection and campaign metadata;
- bounded aggregate metric snapshots;
- adapter discovery and health cache;
- export job metadata and short-lived export files;
- dashboard-specific, append-only audit events;
- private operational notes that contain no patient-identifying clinical content.

## Data File 23 Must Never Own

- publication bodies or canonical post records;
- native drafts, autosaves, or revisions;
- native review decisions or editorial ledgers;
- native schedules or cron truth;
- source/evidence master records;
- media binaries, encrypted PDFs, videos, Reels, or consent evidence;
- comments, reactions, reports, corrections, or retractions;
- raw analytics events;
- profiles, verification records, identity documents, or user credentials;
- notification delivery queues;
- appointments, clinical records, prescriptions, or private messages.

## Universal Object Reference

A federated item reference must contain only:

- `provider_key` — registered adapter key;
- `object_type` — provider-defined stable type;
- `object_id` — provider-defined immutable identifier;
- `object_version` — provider version/ETag when available;
- `canonical_url` — resolved native public or private destination;
- `author_id` — canonical Membership Core user identifier where applicable;
- `privacy_classification` — adapter-provided classification;
- `last_synced_at` — cache freshness marker.

A universal reference is not a copy of the native object.

## Deletion and Deactivation

- Deactivating File 23 must not delete or unpublish native content.
- Uninstall must preserve data by default.
- Destructive cleanup requires a separate explicit administrator action, capability check, confirmation, backup notice, and audit event.
- Broken references may be marked unresolved; they must not cause native deletion.

## Retention Baseline

| Data | Default retention |
|---|---|
| Preferences and saved views | Account lifetime or user deletion |
| Completed tasks | 24 months |
| Temporary exports | 48 hours |
| Aggregate metric snapshots | 25 months, configurable |
| Failed job records | 90 days |
| Adapter health logs | 90 days |
| Cross-module campaigns | Institutional archive |
| Security audit events | Approved security/legal policy |
| Patient-related pointers | Minimum necessary duration; no patient content |
