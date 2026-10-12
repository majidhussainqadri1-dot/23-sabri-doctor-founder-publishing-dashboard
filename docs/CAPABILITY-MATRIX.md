# File 23 Capability Matrix — Version 1.3.0

## Authority model

File 23 defines capabilities but does not create identity truth or autonomous institutional roles. File 00 supplies current account state, verification, suspension and Founder/institutional authority. Every privileged operation is revalidated server-side. Teacher and Admin studios are federated operational views selected by explicit capabilities; neither studio becomes a source of native domain truth.

| Capability | Founder/Admin | Teacher | Approved Doctor | Medical Reviewer | Pending/Suspended | Purpose and boundary |
|---|---:|---:|---:|---:|---:|---|
| `spdb_view_dashboard` | Yes | Yes when existing role/cap grants it | Yes | Yes | Restricted only | Open the private no-store dashboard. |
| `spdb_view_own_content` | Yes | Yes | Yes | Assigned scope | Restricted only | Read privacy-filtered native projections. |
| `spdb_manage_own_content` | Yes | Authorized educational scope | Yes | No | No | Route to native owner operations; File 23 never writes publication/learning truth directly. |
| `spdb_view_teacher_studio` | Admin may inspect by policy | Yes | No by default | No | No | Select the bounded educational operational studio; no student-private/domain-truth ownership. |
| `spdb_view_admin_studio` | Administrator only by explicit grant; Founder resolves first | No | No | No | No | Select administrative operational view; Founder-only actions remain Founder-only. |
| `spdb_view_review_queue` | Yes | Policy-bound | Optional | Yes | No | View authorized review projections. |
| `spdb_review_assigned_content` | Yes | Policy-bound if separately granted | No | Yes | No | Execute only registered native review operations. |
| `spdb_manage_schedule` | Yes | Authorized educational scope | Policy-bound | Assigned scope | No | Native calendar operation with version/idempotency/audit controls. |
| `spdb_manage_campaigns` | Yes | No by default | Own scope | No | No | File 23-owned collection/campaign pointer metadata only. |
| `spdb_manage_tasks` | Yes | Own/team scope | Own/team scope | Assigned scope | No | File 23-owned collaboration tasks. |
| `spdb_manage_automation_rules` | Yes | No by default | No by default | No | No | Human-governed reversible rules; no autonomous medical/publication action. |
| `spdb_manage_interactions` | Yes | No by default | Own scope | Assigned scope | No | Native interaction projections/actions only. |
| `spdb_manage_delegations` | Yes | No by default | Own bounded scope | No | No | Expiring, revocable, MFA-backed delegation grants. |
| `spdb_view_own_analytics` | Yes | Yes | Yes | Assigned scope | No | Aggregate privacy-thresholded metrics only. |
| `spdb_view_global_analytics` | Yes | No | No | No | No | Institutional aggregate analytics. |
| `spdb_view_assurance_status` | Yes | No | No | Optional | No | Sanitized File 24 assurance state. |
| `spdb_export_reports` | Yes | Own scope | Own scope | Assigned scope | No | Field-filtered signed expiring exports. |
| `spdb_request_ai_assistance` | Yes | Policy-bound educational assistance | Policy-bound | Policy-bound | No | File 16 assistance only; source-linked/AI-labeled confirmation required; no diagnosis/prescription authority. |
| `spdb_reconcile_projections` | Yes | No | No | No | No | Rebuild File 23-owned projections without foreign mutation. |
| `spdb_manage_dashboard_settings` | Yes | No | No | No | No | File 23 settings and staging evidence. |
| `spdb_run_system_check` | Yes | No | No | Optional read | No | Read-first diagnostics. |
| `spdb_repair_owned_data` | Yes | No | No | No | No | Reversible File 23-owned repair only. |

## Studio precedence and least privilege

1. Server-verified File 00 Founder identity resolves to Founder Studio first.
2. A non-Founder requires explicit `spdb_view_admin_studio` for Admin Studio.
3. A non-Founder/non-Admin requires explicit `spdb_view_teacher_studio` for Teacher Studio.
4. Trusted/approved Doctor resolution follows after the explicit studio checks.
5. A WordPress role label alone never grants a studio or a native domain action.
6. File 23 does not create the `sabri_teacher` role; capability schema 5 only reconciles a bounded matrix if that existing role is supplied by File 00 or an approved integration.
7. File 00 current account state, object ownership/state, native provider policy and adapter acceptance are rechecked at protected action time.

## Canonical ownership boundaries

- File 20 owns the application shell and global Safe Mode.
- File 21 owns canonical social/news publication and review truth.
- File 22 owns universal create/draft/autosave/preview/submit orchestration.
- File 24 owns cross-platform security/privacy/compliance/resilience assurance.
- File 25 owns canonical visual/design tokens; File 23 consumes the Sabri Green token with `#087A4E` fallback.
- File 26 owns Search/Discovery/Ranking; File 23 may consume typed projections and destinations only.

## Retired migration key

`spdb_manage_safe_mode` is not a canonical File 23 capability. The installer removes it from managed roles during schema reconciliation. File 20 remains the sole global Safe Mode owner.

## Mandatory enforcement

1. Capability key must be canonical and present in `SPDB_Capabilities::all()`.
2. WordPress capability alone is insufficient; current File 00 state must permit the operation.
3. Pending, rejected, expired-document, appeal-review and suspended states receive only explicitly assigned restricted reads.
4. Mutations require nonce, same-origin context, idempotency, privacy validation and canonical audit evidence.
5. Native provider authorization, ownership, object version and state guards remain authoritative.
6. Donor status and paid tiers do not grant a File 23 capability, ranking advantage or support priority.

## Evidence

Canonical implementation: `includes/class-spdb-capabilities.php`, `includes/class-spdb-capability-installer.php`, `includes/class-spdb-membership-guard.php`, `includes/class-spdb-workspace-resolver.php`, `includes/class-spdb-role-workspace-service.php`, `includes/class-spdb-operational-mutation-guard.php` and the provider operation broker. Current governing mapping: `includes/class-spdb-governing-plan.php` and `docs/GOVERNING-PLAN-2026-TRACEABILITY.json`.
