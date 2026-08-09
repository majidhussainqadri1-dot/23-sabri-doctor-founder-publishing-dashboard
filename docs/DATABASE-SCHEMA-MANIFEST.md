# File 23 Database Schema Manifest — Current Source Candidate

## Scope law

File 23 owns bounded dashboard-operational metadata only. It does not own publication bodies, review truth, source bodies, media binaries/private URLs, comments/messages, notification delivery, profiles, clinical records, payments or raw analytics events.

## Schema versions

| Schema | Version | Installer |
|---|---:|---|
| Operational metadata | `1.1.0` | `SPDB_Operations_Schema` |
| Collections and knowledge pointers | `4` | `SPDB_Collections_Schema` |
| REST rate-limit counters | `1` | `SPDB_REST_Rate_Limiter` |

## Operational metadata tables

WordPress prefix is represented as `{prefix}`.

| Table | Purpose | Primary privacy/ownership boundary |
|---|---|---|
| `{prefix}spdb_preferences` | Per-user dashboard preferences | One row per user; no native content. |
| `{prefix}spdb_saved_views` | Saved filter definitions | Owner-bound, allowlisted filters only. |
| `{prefix}spdb_tasks` | Collaboration tasks | Canonical native object pointer, title/description and workflow metadata. |
| `{prefix}spdb_delegations` | Scoped delegated authority | Principal/delegate, scope, expiry, MFA and revocation metadata. |
| `{prefix}spdb_automation_rules` | Human-governed rule definitions | Disabled-by-default governance; no autonomous medical/publication authority. |
| `{prefix}spdb_metric_snapshots` | Bounded aggregate metric snapshots | Cohort count, threshold, expiry; no raw event warehouse. |
| `{prefix}spdb_export_jobs` | Export request and delivery metadata | Owner-bound, expiring storage reference and file hash. |
| `{prefix}spdb_adapter_health` | Bounded provider health cache | Non-sensitive status only, short-lived. |
| `{prefix}spdb_background_jobs` | Retryable internal jobs | Idempotency key, lock token, attempt/dead-letter state. |
| `{prefix}spdb_dashboard_audit` | Hash-chained File 23 audit evidence | Hashed object reference, bounded redacted payload, previous/event hash. |
| `{prefix}spdb_rest_rate_limits` | Fixed-window REST admission counters | Hashed bucket, numeric actor ID when authenticated, bounded policy/count/timestamps only; no raw IP/request data. |

## Collections and knowledge-pointer tables

| Table | Purpose | Boundary |
|---|---|---|
| `{prefix}spdb_collections` | Series/campaign/collection organization | File 23 metadata only; no publication copy. |
| `{prefix}spdb_collection_items` | Immutable provider/object pointers in collections | Provider key, object type/id, native version and reference hash. |
| `{prefix}spdb_knowledge_links` | Source-to-target knowledge relationships | Canonical pointers and native versions; no copied knowledge body. |

## Installation and verification

- Operational and collection installation uses WordPress `dbDelta()` and is idempotent.
- The REST rate-limit table is separately installed/verified by `SPDB_REST_Rate_Limiter` so rate admission can fail closed independently of higher-level operational services.
- All **14 File 23-owned tables** must use transactional `InnoDB`.
- Required tables, columns and indexes are verified after installation; the rate-limit table also verifies its actual storage engine before its schema marker is accepted.
- Schema versions are recorded only after the relevant verification succeeds.
- Missing database/upgrade APIs fail closed with bounded errors; a File 23 REST request is not silently admitted if rate-limit persistence cannot be established.
- Upgrade and reactivation must not destroy native or File 23 metadata.
- Uninstall does not purge records by default; destructive removal requires a separate authenticated, retention-aware process.

## Rate-limit data minimization

`spdb_rest_rate_limits` stores only `bucket_hash`, optional authenticated `actor_user_id`, `policy_key`, bounded request count and UTC window timestamps. Anonymous network pressure is converted to a keyed pseudonymous subject before the bucket hash is calculated. Raw IP addresses, forwarding headers, cookies, nonces, tokens, query strings and request bodies are not persisted. Expired counters are cleanup-eligible and are not permanent audit records.

## Prohibited columns and domains

Architecture tests reject native-domain or sensitive storage such as publication/post body, patient identifiers, diagnosis, prescription, message body, notification delivery, source body, media binary, identity documents, payment secrets and raw analytics events.

## Canonical implementation

- `includes/class-spdb-operations-schema.php`
- `includes/class-spdb-collections-schema.php`
- `includes/class-spdb-rest-rate-limiter.php`
- `tests/architecture-guard.php`
- `tests/full-plan-completion-tests.php`
- `tests/rest-rate-limit-tests.php`
