# Privacy, Retention and Local Repair

File 23 stores only dashboard preferences, saved views, tasks, scoped delegations, automation metadata, cross-module pointer metadata, bounded aggregate snapshots, export metadata, adapter health, background jobs and dashboard audit evidence.

WordPress privacy exporters/erasers cover File 23-owned user data only. Native provider content is never copied or erased by File 23. Temporary exports expire in 1–72 hours; metrics, jobs, tasks and health evidence follow versioned bounded retention.

## Mutation replay privacy

File 23-owned operational mutations use short-lived non-autoload replay receipts keyed by a one-way hash of actor, route, method and client idempotency key. Receipts contain only bounded request fingerprints, state, status and a privacy-filtered response projection. Password-, secret-, token-, nonce-, cookie-, authorization-, OTP-, card-, patient-, clinical- and message-shaped response fields are excluded. Receipts expire after seven days and a daily bounded cleanup removes expired or malformed entries.

The mutation guard does not create a second content backend or audit table. Request and outcome evidence is appended to the canonical File 23 hash-chained dashboard audit. Local operational writes, receipt state and audit evidence are committed or rolled back together where the operation is File 23-owned and transactional.

## Local repair boundary

Local repair is non-destructive: capability reconciliation, schema verification, job rescheduling, route refresh, retention cleanup and audit verification. Global Safe Mode, platform repair and rollback remain File 20 responsibilities. Native publication, source, review, schedule, media, notification-delivery, profile and clinical data remain with their canonical owners.

## Version 1.2.0 privacy completeness

Privacy export is cursor/pagination aware and covers preferences, saved views, tasks, delegations, automation rules, exports, collections/knowledge pointers and bounded mutation receipts. Erasure removes owner-only data and encrypted export artifacts; institutional records that must remain for operational integrity are pseudonymized/minimized rather than silently reassigned or destroyed. Receipt options are scanned in bounded pages and removed for the subject account.

Local repair remains File 23-owned, reversible and capability-separated. It cannot enable File 20 Safe Mode, rewrite companion/native data, clear provider evidence without authority or perform platform rollback.
