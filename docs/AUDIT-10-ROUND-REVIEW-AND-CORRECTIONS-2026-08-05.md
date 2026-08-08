# File 23 — Ten-Round Code Review and Corrective Record

Date: 2026-08-05

Candidate: Version 1.2.2

Scope: GitHub source, executable tests, release workflows, and the File 23 plan boundaries.

Status: Code-level corrective candidate; Hostinger staging acceptance and production acceptance remain pending.

This review combines the open three-plan harmonization branch with the previously reviewed File 21/File 22 live-provider integration before applying further corrections. File 23 remains a private federated operational dashboard; it does not acquire native publishing, composer, appointment, messaging, review, support, learning, clinical, payment, or media ownership.

## Round 1 — Branch convergence

Defect: the harmonization branch did not contain the open Version 1.2.1 File 21/File 22 provider-consumption corrections, so merging it alone would regress live provider discovery, the exact File 22 composer route, and corrected visual integration.

Correction: merged the complete provider-consumption branch into this candidate before any new edits, retaining its pinned companion repository heads and regression evidence.

## Round 2 — REST least privilege

Defect: tasks, delegations, automation rules, exports, and AI mutations used broad dashboard read/write permission callbacks at route dispatch. Restricted accounts or users without the resource capability could reach handlers and rely on deeper service checks.

Correction: added approved-account, resource-specific REST permission callbacks for `spdb_manage_tasks`, `spdb_manage_delegations`, `spdb_manage_automation_rules`, `spdb_export_reports`, and `spdb_request_ai_assistance`.

## Round 3 — Provider error isolation

Defect: provider and native-resolver registration error arrays could grow without limit; the adapter registry also retained provider-controlled `WP_Error` messages and data.

Correction: bounded both registries globally, per provider, and by provider count; retained only validated error codes and generic privacy-safe messages.

## Round 4 — Exact safe destinations

Defect: professional-workspace links and operational projection links used separate weaker same-origin checks. One accepted secret-bearing query strings; the other did not compare scheme and effective port.

Correction: both paths now reuse `SPDB_Safe_Destination`, which enforces exact scheme/host/port, rejects credentials/fragments, ambiguous queries, secrets, signatures, expiry values, and nested redirect targets.

## Round 5 — Projection truth and privacy

Defect: invalid projection items were silently omitted while provider totals remained visible, invalid timestamps were replaced with the current time, native identifiers accepted control characters, and corrupt cached analytics timestamps were presented as current.

Correction: reject an invalid provider page as a unit, validate canonical IDs/text/timestamps, require real values for unsuppressed metrics, normalize multibyte text safely, and skip corrupt cached timestamps instead of fabricating data.

## Round 6 — Mutation and replay integrity

Defect: conflicting header/body idempotency keys could split a mutation between guard and service identities, while saved-view create/delete routes bypassed the central nonce, origin, idempotency, transaction, replay-receipt, and audit guard.

Correction: reject conflicting transports before claiming a receipt; added both saved-view mutations to the guard and supplied cryptographically generated client idempotency keys.

## Round 7 — Client and bootstrap reliability

Defect: operational forms could be re-entered programmatically while a request was active, an insecure `Math.random` fallback generated request identities, and the dashboard renderer received an extra constructor dependency.

Correction: added in-flight submission locks, fail-closed Web Crypto requirements, idempotent saved-view clients, and exact service-container constructor wiring.

## Round 8 — Lifecycle cleanup

Defect: mutation-guard deactivation removed only one scheduled cleanup occurrence; both cleanup paths could loop indefinitely if unscheduling failed.

Correction: prefer `wp_clear_scheduled_hook()` to clear all occurrences and retain a progress-checked compatibility loop.

## Round 9 — Cross-plan operational integration

Defect: the validator recognized appointments, messages, reviews, followers, downloads, support, and learning, but the REST route could not address them. Provider domain declarations were silently normalized, allowing malformed contract values to appear valid.

Correction: exposed every recognized domain through the bounded REST route and reject malformed, duplicate, non-canonical, or unsupported provider domain declarations.

## Round 10 — Release identity and evidence

Defect: after branch convergence and corrective edits, Version 1.2.1 package hashes/manifests no longer represented the candidate source.

Correction: advanced the combined candidate to Version 1.2.2, removed stale committed generated checksums, updated deterministic package/workflow identities, and added this executable ten-round regression gate. New checksums and exact-head evidence must be generated only by GitHub Actions from the final commit.

## Verification boundary

- Local static checks: whitespace/diff validation, JavaScript syntax, shell syntax, version and source assertions.
- GitHub Actions: PHP lint, every standalone test, architecture guard, companion-repository contract tests, deterministic two-build comparison, package manifests, and checksums.
- Still pending by design: Hostinger staging backup/restore/migration/rollback drill, staging acceptance, Founder sign-off, production provider acceptance, and production deployment.
