# Background Jobs Contract

## Purpose

File 23 may run bounded operational jobs for aggregate summaries, federated calendar reconciliation, export generation, adapter health, and maintenance of File 23-owned data. It must not take over native publication scheduling or provider-owned processing.

## Approved Job Classes

- refresh aggregate dashboard counts;
- reconcile federated calendar projections;
- detect failed or stale provider schedules through read-only checks;
- generate privacy-filtered exports;
- remove expired export files/tokens;
- prune expired health logs and completed task metadata;
- check adapter health and compatibility;
- refresh broken canonical-reference status;
- send campaign/task reminders through File 19;
- verify audit-chain integrity.

## Prohibited Job Classes

- automatic medical approval;
- automatic publication of unreviewed content;
- automatic patient-data publication;
- destructive deletion of native provider data;
- rewriting native statuses without provider action;
- copying native content into File 23 tables;
- silently retrying high-risk actions after authorization context expires.

## Execution Model

1. Prefer Hostinger real cron for production reliability.
2. Use WP-Cron as a documented fallback.
3. A queue library such as Action Scheduler may be adopted only through an approved dependency decision.
4. Every job type has a stable key, schema version, owner, lock, retry policy, and timeout.

## Reliability Requirements

- idempotent handlers;
- concurrency locks;
- bounded batches;
- maximum retry count;
- exponential backoff where appropriate;
- dead-letter state after final failure;
- operator notification for material failures;
- no patient/private content in job payloads or logs;
- audit events for administrative requeue/cancel actions;
- explicit staging and production acceptance.

## Default Retention

- successful job summaries: 30 days;
- failed/dead-letter jobs: 90 days;
- export payloads: 48 hours;
- adapter health history: 90 days.

## Native Schedule Rule

A federated calendar entry is informational. The native provider remains the schedule source of truth. File 23 may request a schedule change through a production-accepted adapter, but it must re-read the provider state before displaying success.
