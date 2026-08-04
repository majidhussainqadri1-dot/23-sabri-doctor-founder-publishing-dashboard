# File 23 Capability Matrix — Version 1.2.0

## Authority model

File 23 defines capabilities but does not create identity truth or autonomous institutional roles. File 00 supplies current account state, verification, suspension and institutional authority. Every privileged operation is revalidated server-side.

| Capability | Founder/Admin | Approved Doctor | Medical Reviewer | Pending/Suspended | Purpose and boundary |
|---|---:|---:|---:|---:|---|
| `spdb_view_dashboard` | Yes | Yes | Yes | Restricted only | Open the private no-store dashboard. |
| `spdb_view_own_content` | Yes | Yes | Assigned scope | Restricted only | Read privacy-filtered native projections. |
| `spdb_manage_own_content` | Yes | Yes | No | No | Route to native owner operations; File 23 never writes publication truth directly. |
| `spdb_view_review_queue` | Yes | Optional | Yes | No | View authorized review projections. |
| `spdb_review_assigned_content` | Yes | No | Yes | No | Execute only registered native review operations. |
| `spdb_manage_schedule` | Yes | Policy-bound | Assigned scope | No | Native calendar operation with version/idempotency/audit controls. |
| `spdb_manage_campaigns` | Yes | Own scope | No | No | File 23-owned collection/campaign pointer metadata only. |
| `spdb_manage_tasks` | Yes | Own/team scope | Assigned scope | No | File 23-owned collaboration tasks. |
| `spdb_manage_automation_rules` | Yes | No by default | No | No | Human-governed reversible rules; no autonomous medical/publication action. |
| `spdb_manage_interactions` | Yes | Own scope | Assigned scope | No | Native interaction projections/actions only. |
| `spdb_manage_delegations` | Yes | Own bounded scope | No | No | Expiring, revocable, MFA-backed delegation grants. |
| `spdb_view_own_analytics` | Yes | Yes | Assigned scope | No | Aggregate privacy-thresholded metrics. |
| `spdb_view_global_analytics` | Yes | No | No | No | Institutional aggregate analytics. |
| `spdb_view_assurance_status` | Yes | No | Optional | No | Sanitized File 24 assurance state. |
| `spdb_export_reports` | Yes | Own scope | Assigned scope | No | Field-filtered signed expiring exports. |
| `spdb_request_ai_assistance` | Yes | Policy-bound | Policy-bound | No | File 16 assistance only; confirmation required. |
| `spdb_reconcile_projections` | Yes | No | No | No | Rebuild File 23-owned projections without foreign mutation. |
| `spdb_manage_dashboard_settings` | Yes | No | No | No | File 23 settings and staging evidence. |
| `spdb_run_system_check` | Yes | No | Optional read | No | Read-first diagnostics. |
| `spdb_repair_owned_data` | Yes | No | No | No | Reversible File 23-owned repair only. |

## Retired migration key

`spdb_manage_safe_mode` is not a canonical File 23 capability. The installer removes it from managed roles during schema reconciliation. File 20 remains the sole global Safe Mode owner.

## Mandatory enforcement

1. Capability key must be canonical and present in `SPDB_Capabilities::all()`.
2. WordPress capability alone is insufficient; current File 00 state must permit the operation.
3. Pending, rejected, expired-document, appeal-review and suspended states receive only explicitly assigned restricted reads.
4. Mutations require nonce, same-origin context, idempotency, privacy validation and canonical audit evidence.
5. Native provider authorization, ownership, object version and state guards remain authoritative.

## Evidence

Canonical implementation: `includes/class-spdb-capabilities.php`, `includes/class-spdb-capability-installer.php`, `includes/class-spdb-membership-guard.php`, `includes/class-spdb-operational-mutation-guard.php` and the provider operation broker.
