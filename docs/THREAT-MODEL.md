# File 23 Threat Model — Version 1.2.0

## Assets

- Current File 00 identity/approval/suspension assertions.
- Native provider publication/review/calendar/source/media/interaction state.
- File 23-owned tasks, delegations, rules, collections, metrics, exports, jobs and audit evidence.
- Private dashboard responses and signed export files.
- Provider credentials/tokens, which must never be exposed to File 23 clients or logs.

## Trust boundaries

1. Browser ↔ WordPress REST/private route.
2. File 23 ↔ File 00 authority contract.
3. File 23 operation broker ↔ native provider adapters.
4. File 23 ↔ database/filesystem/cron.
5. File 23 ↔ File 19 notifications and File 24 assurance evidence.
6. Staging acceptance ↔ release artifact identity and Founder approval.

## Threats and mandatory controls

| Threat | Control | Verification |
|---|---|---|
| Unauthenticated/private-route access | Login, explicit capability, File 00 current-state check, noindex/noarchive/no-store | Guest/account-state tests and cache headers. |
| CSRF | Valid `wp_rest` nonce plus same-origin Origin/Referer enforcement | Mutation-guard adversarial tests. |
| IDOR | Current actor derived server-side; owner/scope/provider authorization and native recheck | Cross-user negative tests. |
| Forged role/author/provider/status/environment | Ignore client authority claims; canonical registries and File 00/native assertions only | Payload-forgery tests. |
| XSS | Sanitization, escaping, structured projection validators, no unsafe HTML contracts | Static/runtime tests and browser acceptance. |
| SQL injection | `$wpdb->prepare`, canonical identifiers, bounded allowlists and schema guards | Architecture/static review. |
| Replay/duplicate effects | Canonical idempotency key, request hash, in-progress conflict and bounded replay receipt | Replay/conflict tests. |
| Concurrent overwrite | Object version/ETag/native state recheck; transactional File 23 writes | Stale-version and race tests. |
| Audit bypass | File 23 transaction contains mutation and canonical hash-chained audit; rollback on failure | Audit-failure tests. |
| Export abuse/leakage | Capability/scope filtering, owner-bound HMAC, short expiry, file hash, spreadsheet neutralization, audit | Export auth/expiry/tamper tests. |
| Bulk/rate abuse | Bounded pagination/payloads, provider rate policy, job queues and retry limits | Load/rate tests. |
| Sensitive data in logs/cache/replay | Redaction and sensitive-key suppression; private no-store responses; bounded cache keys | Privacy/cache/log inspection. |
| Provider outage/exception | Per-provider isolation, bounded `WP_Error`, stale/unknown state, no success inference | Throwing-adapter/outage matrix. |
| Malicious/overbroad delegation | MFA, scope/action allowlist, expiry, revocation, no publish/export/patient impersonation grant | Delegation negative tests. |
| Unsafe automation/AI | Human confirmation, disabled-by-default execution, prohibited medical/destructive/autonomous actions, native provider mediation | Rule replay/red-team tests. |
| Cache cross-user leak | `DONOTCACHEPAGE`, private/no-store, viewer/role/privacy/state/version-aware caches | LiteSpeed staging isolation tests. |
| Supply-chain/package substitution | Exact commit, deterministic build, SHA-256 manifests/checksums and signed release evidence | CI/package verification. |
| Destructive repair/rollback overreach | File 20 owns global Safe Mode/rollback; File 23 repair is local, reversible, dry-run/read-first | Ownership and rollback tests. |

## Residual risks requiring staging evidence

- Hostinger/LiteSpeed behavior under real sessions.
- Browser, assistive-technology and device differences.
- Real companion-provider contract drift or outage behavior.
- Real database size, latency and backup/restore timings.
- Operational key/secret management and incident response.

These residual risks do not authorize production writes. Production remains fail-closed until the release sign-off form is completed with exact evidence.

## Version 1.2.0 forty-round hardening additions

| Threat | Required control | Evidence |
|---|---|---|
| Encrypted export disclosure | AES-256-GCM envelope, owner-bound expiring signature, realpath and ciphertext-hash verification; no plaintext generated export at rest. | Export service and forty-round gate. |
| Commit-failure replay false success | Rollback, option-cache invalidation and failed receipt finalization before replay. | Mutation guard regressions. |
| Audit-chain race/fork | Serialized application lock plus row lock, previous-hash verification and transaction/savepoint rollback. | Repository audit tests/markers. |
| Privacy erasure omission | Paginated export/erasure across receipts, tasks, delegations, rules, exports, collections and institutional pseudonymization. | Privacy integration/repository tests. |
| Non-transactional table rollback illusion | Actual engine inspection and InnoDB upgrade/verification for all 13 owned tables. | Schema installers and architecture tests. |
