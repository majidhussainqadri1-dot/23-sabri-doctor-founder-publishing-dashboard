# Phase 23B — Dashboard Core

## Purpose

Phase 23B implements the first usable private dashboard surface without creating any duplicate publication, Composer, Newsroom, schedule, source, media, interaction, correction, retraction, or raw analytics backend.

## Protected Route

- Canonical route: `/publishing-dashboard/`
- Query variable: `spdb_dashboard`
- Shortcode fallback: `[sabri_publishing_dashboard]`
- Authentication: required
- File 00 status: required
- Capability: `spdb_view_dashboard`
- Search indexing: prohibited
- Shared/public caching: prohibited

The virtual route and shortcode fallback emit private `no-store`, `noindex`, `noarchive`, `nosnippet`, same-origin referrer, and content-type protection headers.

## Implemented Workspaces

| Workspace | Source | Mode |
|---|---|---|
| Founder | `smc_is_founder()` | Operational shell; publishing writes still disabled in Phase 23B |
| Trusted Doctor | `smc_is_trusted_publisher()` | Operational shell; publishing writes still disabled in Phase 23B |
| Doctor | Approved/verified File 00 account | Operational shell; publishing writes still disabled in Phase 23B |
| Restricted | Pending, rejected, expired-document, appeal-review, or suspended account with explicit dashboard capability | Read-only |
| Denied | Missing explicit capability or invalid account | No dashboard access |
| Dependency unavailable | Missing/incompatible File 00 | Fail-closed |

Workspace resolution is bound to the current authenticated user and cannot be used to project another user's authority or membership status.

## Implemented Views

1. **Overview** — truthful workspace, account-state, adapter-readiness, and safety information.
2. **Saved Views** — personal, File 23-owned, non-clinical filter preferences.
3. **System Status** — capability-protected, non-sensitive environment and adapter diagnostics.

No navigation item is emitted for a page or action that does not yet exist.

## Truthful Overview Rule

Phase 23B does not have accepted native content providers. It therefore reports zero registered providers and does not invent draft, publication, reader, review, or analytics counts.

## Saved-View Contract

- Storage: current user's WordPress user meta under `spdb_saved_views_v1`.
- Maximum: 25 views per user.
- Label: 1–80 characters.
- Allowed filter keys: `status`, `type`, `provider`, `language`, `sort`, `direction`, `date_from`, and `date_to`.
- Maximum array values: 20.
- Maximum filter value: 100 characters.
- Patient, prescription, appointment, message, identity-document, and consent fields are not accepted.
- Data is revalidated on both write and read projection.
- Approved/verified accounts may create and delete their own saved views.
- Restricted accounts may list existing saved views but cannot create or delete them.
- Saved views never grant publishing authority.

## Graceful Degradation

- No provider: dashboard remains usable and reports zero providers.
- Provider registration error: affected provider is isolated; bounded error count is shown.
- File 00 unavailable: private actions fail closed.
- Restricted account: dashboard remains read-only, including saved-view mutation.
- JavaScript unavailable: overview and system-status views remain readable; saved-view mutation is unavailable rather than silently simulated.

## Accessibility and Responsive Baseline

- Semantic header, navigation, main region, sections, tables, labels, and live region.
- Skip link and visible focus.
- Keyboard-operable navigation, forms, and delete actions.
- Minimum 44-pixel-equivalent interactive height.
- Responsive desktop, tablet, and mobile layouts.
- Reduced-motion support.
- Horizontal overflow confined to explicitly labeled data-table regions.

## Phase Boundary

Phase 23B does not enable:

- native content listing;
- Composer launch;
- review actions;
- scheduling;
- analytics events;
- comments or interactions;
- provider acceptance persistence;
- production publishing writes.

Those capabilities require later reviewed phases and accepted native provider adapters.
