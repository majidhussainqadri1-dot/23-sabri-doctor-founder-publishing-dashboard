# Background Jobs and Exports

File 23 uses a bounded five-minute WP-Cron fallback; a real Hostinger cron may invoke the same hook. Every job has an idempotency key, lock token, bounded attempts, exponential retry and dead-letter state.

Built-in jobs cover export generation, aggregate snapshot refresh, adapter health and retention. Calendar reconciliation, failed-schedule detection, broken-source checks and campaign reminders execute only through registered native handlers.

Exports are queued, owner-scoped, capability filtered, temporary, integrity hashed and delivered through owner-bound short-lived HMAC links. CSV formula injection is neutralized. Private export files are protected by server guard files and expire automatically.

## Version 1.2.0 encrypted export lifecycle

Generated export bytes are sealed before filesystem persistence with an `SPDBEXP1` AES-256-GCM envelope. The database records the ciphertext SHA-256 and owner/expiry metadata. Download rechecks the authenticated owner, expiring signature, canonical real path, file hash and export state before decryption. Failed generation, encryption, persistence, completion or audit transition is explicit; orphan artifacts are removed where possible and the job is failed/retried without false success.

Job claims and every retry/completion/dead-letter transition require current lock ownership and checked persistence. Transition failures emit privacy-safe operational evidence instead of being silently ignored.
