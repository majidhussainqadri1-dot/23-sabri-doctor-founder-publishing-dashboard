# File 23 Hostinger Staging Acceptance Checklist — Version 1.1.0

This checklist is an execution record, not a declaration of success. Every row requires dated evidence, tester identity, environment/version and defect reference where applicable.

## Release identity

- [ ] Exact Git commit recorded.
- [ ] Installable ZIP name and SHA-256 match CI evidence.
- [ ] Complete-source ZIP and source-manifest SHA-256 match CI evidence.
- [ ] WordPress, PHP, database, LiteSpeed and companion-plugin versions recorded.
- [ ] Pre-change database/files backup created and restore access verified.

## Installation and lifecycle

- [ ] Fresh install activates without fatal error or unexpected schema mutation.
- [ ] Upgrade from every supported File 23 package succeeds.
- [ ] Deactivation/reactivation is non-destructive and idempotent.
- [ ] Schema versions and all 13 File 23-owned tables verify.
- [ ] Uninstall does not silently purge retained metadata.
- [ ] Migration dry run, execution, restartability and rollback are recorded.

## Route, cache and privacy

- [ ] `/publishing-dashboard/` requires authentication.
- [ ] Route emits noindex/noarchive and private/no-store headers.
- [ ] Guest and unauthorized requests reveal no account/object existence.
- [ ] LiteSpeed does not cache one user's private page for another user.
- [ ] Cache keys/invalidation respect viewer, role, privacy, native state and version.
- [ ] Exports, logs, audit and replay receipts contain no patient/clinical/message/secret data.

## Real account matrix

- [ ] Founder workspace and explicitly allowed direct-publish routes are correct.
- [ ] Approved/verified Doctor defaults to Submit for Review unless native policy explicitly allows otherwise.
- [ ] Medical Reviewer sees and acts only on assigned/authorized objects.
- [ ] Pending account is denied or restricted read-only according to File 00 policy.
- [ ] Suspended account loses privileged actions immediately and cannot bypass through stale session/cache.
- [ ] Cross-user/object IDOR attempts fail.
- [ ] Verification/suspension changes invalidate downstream actions without deleting attribution/history.

## Real integrations

- [ ] File 00 authority contract and current account assertions verified.
- [ ] File 21 draft/review/publish/correction/source/interaction operations use the native adapter.
- [ ] File 22 is the only create/edit/draft/preview/submit surface; no duplicate composer appears.
- [ ] File 19 notification delivery/deep links are deduplicated and privacy-safe.
- [ ] File 20 owns global shell, Safe Mode and platform rollback.
- [ ] File 24 receives sanitized assurance evidence only.
- [ ] File 25 owns public visual/profile/timeline destinations.
- [ ] Optional providers 03, 05, 06, 08–12, 15–18 show valid or explicit unavailable/stale states without crashing other sections.

## Functional journeys

- [ ] Federated inventory counts, pagination, search, filters and saved views are accurate.
- [ ] Item inspector uses immutable provider/object reference and current native version.
- [ ] Review operations require nonce, capability, native authorization, object version, idempotency and audit reason.
- [ ] Calendar validates timezone/DST/conflict and re-reads native state before success.
- [ ] Failed scheduling produces visible alert and retry path.
- [ ] Collections/campaigns store pointers only and survive provider reindex/reconciliation.
- [ ] Knowledge links route to canonical owners.
- [ ] Sources/evidence, media usage, interactions, revisions and notifications are projections only.
- [ ] Analytics are aggregate and privacy-thresholded; no raw-event warehouse exists.
- [ ] Tasks, delegations, automation, exports, jobs and local repair pass negative and failure-path tests.
- [ ] No dead button, fake success, generic unregistered action or silent failure remains.

## Accessibility, responsive and weak connection

- [ ] Widths 320, 360, 390, 768, 1024, 1440 and 1920 px have no blocking overflow.
- [ ] Urdu/Arabic RTL layout and logical navigation are correct.
- [ ] Keyboard-only completion, visible focus and skip/navigation behavior pass.
- [ ] Screen reader names, landmarks, live messages and error association pass.
- [ ] 200%/400% zoom, contrast, forced colors and reduced motion pass.
- [ ] Slow/unstable connection shows loading, retry, stale and partial-provider states without duplicate mutation.

## Performance and resilience

- [ ] Real database with at least 10,000 representative objects is measured.
- [ ] Overview, inventory, filters, analytics and exports meet recorded response-time/query budgets.
- [ ] Provider timeout, exception and partial outage are isolated.
- [ ] Cron failure, retry, dead-letter and reconciliation are exercised.
- [ ] Database/filesystem failure does not report false success.
- [ ] Backup restore reproduces File 23 metadata and audit integrity.
- [ ] Package rollback leaves native data intact and restores service within the approved window.
- [ ] Cache purge and post-deployment smoke tests pass.

## Acceptance

- [ ] All defects are fixed and affected suites rerun.
- [ ] Two fresh review/fix rounds after the last code change are recorded.
- [ ] Zero unresolved blocker/critical defects; residual risks documented and approved.
- [ ] Founder completes mobile and desktop critical journeys with real representative data.
- [ ] Release sign-off form is completed and dated.
- [ ] PR merge and production deployment are explicitly authorized.
