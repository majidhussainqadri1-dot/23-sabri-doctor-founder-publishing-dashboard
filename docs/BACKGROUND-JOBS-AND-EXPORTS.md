# Background Jobs and Exports

File 23 uses a bounded five-minute WP-Cron fallback; a real Hostinger cron may invoke the same hook. Every job has an idempotency key, lock token, bounded attempts, exponential retry and dead-letter state.

Built-in jobs cover export generation, aggregate snapshot refresh, adapter health and retention. Calendar reconciliation, failed-schedule detection, broken-source checks and campaign reminders execute only through registered native handlers.

Exports are queued, owner-scoped, capability filtered, temporary, integrity hashed and delivered through owner-bound short-lived HMAC links. CSV formula injection is neutralized. Private export files are protected by server guard files and expire automatically.
