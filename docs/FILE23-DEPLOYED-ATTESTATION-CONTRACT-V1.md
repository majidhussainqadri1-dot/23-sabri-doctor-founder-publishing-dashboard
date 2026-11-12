# File 23 — Deployed Artifact Attestation Contract v1 (GAP-CODE-01)
**State:** Proposed runtime *consumer* contract in Draft PR #29. The deployment/Hostinger provider is **NOT verified or installed** by this source change.  
**Source:** `includes/class-spdb-activation-wizard.php`; issue #30; V12.1 plan WP-00, WP-13, WP-14, WP-15.

## Owner separation

- **File 20 / named Hostinger deployment operator:** collect real installed File23 plugin bytes/version, pinned release package/source identities, active WP/PHP/DB/schema/migration state and protected deployment evidence; produce current attestation server-side.
- **File 24:** independent security/privacy/release assurance consumption; does not silently mark deployment safe.
- **File 23:** only verifies attested values against saved Founder staging acceptance, its current schema constants and the current runtime environment. It cannot independently certify the external provider's measuring procedure.
- **Founder:** records explicit acceptance only after real staging tests and approves rollout separately.

## Exact source consumer

The server-side provider registers a PHP callback to `spdb/file23_deployed_artifact_attestation` and returns an array **only after fresh, independent verification of installed source bytes and migration**. No browser, REST request, user option or form field may impersonate this provider.

The returned array must contain:

| Key | Expected type and meaning |
|---|---|
| `contract` | exact string `file23_deployed_artifact_v1` |
| `verified` | boolean `true` ONLY after real host inspection, not a default |
| `plugin_version` | exact `SPDB_VERSION` of installed plugin |
| `source_commit` | lowercase 40-character Git commit HEX proven by release manifest + installed files |
| `package_sha256` | lowercase 64-character SHA-256 of the approved installable ZIP, bound by deploy log and per-file installed manifest |
| `environment` | exact WordPress runtime environment `staging` |
| `operations_schema` | exact **measured** `SPDB_Operations_Schema::VERSION` plus full schema verification |
| `collections_schema` | exact **measured** `SPDB_Collections_Schema::VERSION` plus full schema verification |
| `migration_state` | exact string `complete`; any partial/deferred/unknown state is ineligible |
| `observed_at_unix` | integer Unix seconds of actual host verification; not future-dated or older than 300 seconds |

The code will fail closed for absent, malformed, mismatched, stale, future-dated, wrong-environment, migration-incomplete or wrong-schema assertions. Prior valid acceptance is invalidated when exact source/ZIP identity changes without new recorded acceptance.

## Important trust limitation

A `verified=true` field is **not proof by itself**. The implementation which sets it must be trusted server-side code backed by protected deployment records and actual checksums. File23 cannot prevent a malicious PHP plugin with full server privileges from lying, nor can a green test prove the Hostinger provider exists. Use File24 controls and operator review of the attestor's installation, data source and audit evidence.

## Necessary staging negative tests

1. Exact approved SHA and verified installed file-by-file manifest match; valid staging role account with Founder 2FA accepts.
2. Same `1.3.0` plugin header with one changed installed PHP file → source/package parity fails.
3. Old installable ZIP SHA with new source commit → no acceptance.
4. Missing provider / scanner or checksum failure / no deploy record → no acceptance.
5. Stale, future-dated or previous environment attestation → no acceptance.
6. Deferred/partial migration, schema column drift or wrong InnoDB state → no acceptance.
7. Stale revoked Founder, expired session MFA or wrong scope → no new acceptance.
8. Backup/restore of an old option plus newer files → no effective staging acceptance.
9. CDN/origin direct private report URL access unauthenticated → denied independently of application auth.
10. After successful recovery and newly measured artifact, a *new* Founder decision is recorded with audit reasons.

## Integration handoff blockers

The actual attestor provider, protected deploy log, installed file manifest, DB inspection, host secrets and maintenance of trust evidence are **outside this File23 source PR** and require a separate owner-reviewed implementation and real Hostinger acceptance. The code can only establish fail-closed behavior when missing. Keep issue #30 OPEN until owner-provided proof is attached.

## Reporting

Each incident/release report must separately specify: **Repository HEAD / Deployed Version / DB Version / Migration State / Live Verification Status**. A source fix does not close a real live incident without post-deploy parity and live retest.
