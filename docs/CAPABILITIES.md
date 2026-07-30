# Capability Contract

## Principle

Roles are labels; capabilities are authority. Every privileged action must be authorized server-side against the current authenticated user, current Membership Core approval/suspension state, current object ownership, provider policy, native state, and object version.

A WordPress capability alone is insufficient. File 23 fails closed when the compatible File 00 contract is unavailable. Pending, rejected, expired-document, appeal-review, and suspended accounts may receive only the restricted read-only capabilities required for status, appeal, and owned-content visibility; every mutable or institution-wide capability requires an `approved` or `verified` account.

## Core Capabilities

| Capability | Purpose |
|---|---|
| `spdb_view_dashboard` | Open the private or status-restricted dashboard |
| `spdb_view_own_content` | View federated content owned by the current user |
| `spdb_manage_own_content` | Invoke permitted native actions on owned content |
| `spdb_view_review_queue` | View assigned or authorized review projections |
| `spdb_review_assigned_content` | Invoke registered provider review actions |
| `spdb_manage_schedule` | Request native schedule changes through provider validation |
| `spdb_manage_campaigns` | Create and manage File 23-owned cross-module campaigns |
| `spdb_view_own_analytics` | View private aggregates for owned content |
| `spdb_view_global_analytics` | View institution-wide aggregates |
| `spdb_manage_interactions` | Act on provider-owned interactions where authorized |
| `spdb_export_reports` | Generate privacy-filtered exports |
| `spdb_manage_delegations` | Create, revoke, or inspect scoped delegations |
| `spdb_manage_dashboard_settings` | Configure File 23-owned settings |
| `spdb_run_system_check` | Run non-destructive diagnostics |
| `spdb_repair_owned_data` | Repair File 23-owned data only |
| `spdb_manage_safe_mode` | Enable or disable File 23 read-only Safe Mode |

File 23 defines these keys but does not create editorial WordPress roles. Plugin activation is an administrator-approved process that attaches File 23 keys to existing roles. Runtime Membership Core status checks remain mandatory and can only reduce authority.

## Existing-Role Provisioning

The `SPDB_Capability_Installer` reconciles only roles that already exist:

| Existing role | Provisioned File 23 authority |
|---|---|
| `administrator` | All File 23 capabilities, still subject to File 00 account state and native provider policy |
| `sabri_pending` | `spdb_view_dashboard`, `spdb_view_own_content` only |
| `sabri_doctor` | Restricted view keys plus own-content management and own analytics |
| `sabri_verified_doctor` | Restricted view keys plus own-content management and own analytics |
| `sabri_medical_reviewer` | Dashboard, own-content view, review queue, assigned review |
| `sabri_moderator` | Dashboard and interaction moderation |

No role is created, renamed, deleted, or made authoritative merely by this mapping. Missing roles are skipped. The versioned installer reruns when the available role inventory changes.

## Restricted Read-Only Capabilities

Only these capabilities may pass for a non-approved File 00 status:

- `spdb_view_dashboard`
- `spdb_view_own_content`

They provide no submit, edit, review, schedule, interaction, analytics, export, delegation, repair, or Safe Mode authority. The UI must show only status/appeal and policy-permitted owned-content read-only routes.

## Membership Core Compatibility

File 23 currently accepts Membership Core `>= 1.0.1` and `< 2.0.0` together with the canonical functions:

- `smc_user_status()`
- `smc_is_founder()`
- `smc_is_trusted_publisher()`

A new major File 00 version requires explicit compatibility review rather than optimistic acceptance.

## Executable Authorization Order

For a mutable provider operation, File 23 must verify:

1. current logged-in user;
2. compatible File 00 contract availability;
3. approved/verified and non-suspended Membership Core status;
4. canonical File 23 capability;
5. provider institutional acceptance for the server environment;
6. supported object type and registered operation;
7. native provider ownership and policy decision;
8. native current-state guard;
9. version/ETag, idempotency, rate-limit, and audit requirements.

A passing environment gate must never be described as full authorization.

## Founder Policy

Founder publications may receive direct-publish authority only through explicit native provider policy. Founder identity does not bypass malware, upload, privacy, consent, copyright, object-version, audit, or security controls.

## Doctor Policy

- Verified Doctor: submit for review by default.
- Trusted Verified Doctor: direct publishing only for explicit content types and surfaces.
- Pending Doctor: restricted status/appeal and policy-permitted owned-content read-only view.
- Rejected or expired-document Doctor: restricted status/appeal view only where policy permits.
- Suspended Doctor: every privileged/mutable action denied; restricted status/appeal and read-only routes may remain where policy permits.

## Delegation

Every delegation must record principal, delegate, providers, object scope, operations, start/expiry, MFA requirement, revocation state, maximum session policy, and audit reason.

A delegate may not publish as the Founder, change canonical authorship, view patient evidence, or export data unless a separate explicit capability and delegation scope permits it. Delegation never replaces the delegate's current File 00 account-state check.

## Denial Rules

A visible button, profile title, badge, URL parameter, hidden field, JavaScript state, local storage value, client-supplied role/status, provider-supplied acceptance state, or caller-supplied environment value is never authorization.
