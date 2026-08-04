# File 23 Administrator and Release Operator Manual — Version 1.2.0

## Administrative boundary

Administrators may install, diagnose and maintain File 23, but may not bypass File 00 identity policy, native provider authorization or Founder release approval. File 20 owns global Safe Mode and platform rollback; File 24 owns security/privacy assurance governance.

## Pre-install checks

- Confirm exact Git commit, Version 1.2.0 package and SHA-256.
- Confirm supported WordPress/PHP versions and exact companion-plugin pins.
- Create database and files backup and verify restoration access.
- Perform first installation on Hostinger staging, never directly on Live.

## Installation

1. Upload the canonical installable ZIP.
2. Activate the plugin and capture activation logs.
3. Verify the private `/publishing-dashboard/` route.
4. Run System Check and confirm operational schema Version 1.0.0 and collections schema Version 3.
5. Verify all 13 File 23-owned metadata tables and no foreign table mutation.
6. Confirm cron/background-job scheduling and export storage protection.

## Provider registration

- Accept only canonical immutable provider keys and valid semantic versions.
- Verify File 23 contract compatibility range.
- Keep technical capability separate from institutional acceptance.
- Production writes require `production_accepted`; staging writes require at least `staging_accepted`.
- Never infer acceptance merely because a plugin is active.

## Security and privacy operations

- Preserve private/no-store/noindex route headers and LiteSpeed exclusions.
- Review capabilities by least privilege; do not grant global Safe Mode via File 23's compatibility key.
- Keep logs and health payloads bounded and redacted.
- Rotate/revoke export links and provider credentials after suspected exposure.
- Route incidents and evidence to File 24 without copying secrets or patient data.

## Jobs and exports

Monitor queued/running/retry/dead-letter jobs, provider timeouts and export expiry. Reconcile idempotently after outages. Do not manually mark provider/native success without re-reading the authoritative object.

## Repair

Use read-first diagnostics and dry-run where available. File 23 repair may reconcile its own schema/projections/jobs only. Do not use it to alter publication, review, profile, message, notification, clinical or payment data.

## Upgrade and rollback

- Test every supported upgrade path on staging.
- Preserve schema-compatible backup and exact previous package.
- On failure, disable File 23 writes, preserve evidence, revert package/schema-compatible snapshot and validate native data integrity.
- Use File 20 for global rollback coordination.
- Complete cache purge and smoke tests after deploy or rollback.

## Release

Do not merge/deploy until `docs/STAGING-CHECKLIST.md` and `docs/RELEASE-SIGNOFF.md` contain exact evidence, all defects are fixed and two fresh post-change review/fix rounds are complete.
