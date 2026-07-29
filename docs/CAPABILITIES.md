# Capability Contract

## Principle

Roles are labels; capabilities are authority. Every write action must be authorized server-side against the current authenticated user, current Membership Core verification/suspension state, current object ownership, provider policy, and object version.

## Core Capabilities

| Capability | Purpose |
|---|---|
| `spdb_view_dashboard` | Open the private dashboard |
| `spdb_view_own_content` | View federated content owned by the current user |
| `spdb_manage_own_content` | Invoke permitted native actions on owned content |
| `spdb_view_review_queue` | View assigned or authorized review projections |
| `spdb_review_assigned_content` | Invoke registered provider review actions |
| `spdb_manage_schedule` | Change native schedules through provider validation |
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

## Founder Policy

Founder publications may receive direct-publish authority only through explicit provider policy. Founder identity does not bypass malware, upload, privacy, consent, copyright, or security controls.

## Doctor Policy

- Verified Doctor: submit for review by default.
- Trusted Verified Doctor: direct publishing only for explicit content types and surfaces.
- Pending Doctor: restricted read-only/private draft view according to Membership Core policy.
- Suspended Doctor: all File 23 write operations denied.

## Delegation

Every delegation must record:

- principal user;
- delegate user;
- allowed providers;
- allowed object types/IDs;
- allowed operations;
- start and expiry times;
- MFA requirement;
- revocation state;
- maximum session policy;
- audit reason.

A delegate may not publish as the Founder, change canonical authorship, view patient evidence, or export data unless a distinct explicit capability and delegation scope permits it.

## Denial Rules

A visible button, profile title, badge, URL parameter, hidden field, JavaScript state, local storage value, or client-supplied role/status is never authorization.
