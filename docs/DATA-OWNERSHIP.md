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
- bounded private operational notes containing no patient-identifying clinical content.

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

A federated item reference may contain only:

- `provider_key` — exact registered adapter key;
- `object_type` — provider-defined stable canonical type;
- `object_id` — provider-defined immutable identifier;
- `object_version` — provider version/ETag when available;
- `destination_descriptor` — stable non-secret native route or public canonical URL descriptor;
- `author_id` — canonical Membership Core user identifier where applicable;
- `privacy_classification` — adapter-provided classification;
- `last_synced_at` — cache freshness marker.

A universal reference is not a copy of the native object.

### Destination Safety

File 23 must never persist:

- nonce-bearing URLs;
- signed or expiring URLs;
- access tokens or credentials;
- private download links;
- session-bound preview links;
- patient-document routes.

Such destinations must be resolved on demand by the native provider after current authentication, capability, ownership, and expiry checks.

## Patient-Related Reference Rule

File 23 must not retain a patient identifier, patient name, clinical record ID, appointment detail, prescription reference, or message reference merely for dashboard convenience. Where an approved workflow requires a provider object pointer, it must be opaque, minimum-necessary, privacy-classified, access-controlled, and retained only for the documented minimum period.

## Deletion and Deactivation

- Deactivating File 23 must not delete or unpublish native content.
- Uninstall must preserve data by default.
- Destructive cleanup requires a separate explicit administrator action, capability check, confirmation, backup notice, audit event, and rollback record.
- Broken references may be marked unresolved; they must not cause native deletion.

## Retention Baseline

| Data | Default retention |
|---|---|
| Preferences and saved views | Account lifetime or user deletion |
| Completed tasks | 24 months |
| Temporary exports | 48 hours maximum; shorter where practical |
| Aggregate metric snapshots | 25 months, configurable |
| Failed job records | 90 days |
| Adapter health logs | 90 days |
| Cross-module campaigns | Institutional archive |
| Security audit events | Approved security/legal policy |
| Opaque patient-related pointers | Minimum necessary duration; no patient content |

Retention jobs must be idempotent, audited where material, and limited to File 23-owned data.
