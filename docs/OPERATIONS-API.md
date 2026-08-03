# File 23 Operations API — `spdb/v1`

All routes are private, cookie-authenticated, nonce-protected by WordPress REST authentication, capability checked, no-store and noindex through the File 23 privacy layer.

Read routes include native operational projections, analytics, tasks/delegations/rules/exports, preferences, settings, legacy migration diagnostics, system check and activation evidence.

Mutation routes are explicit: task creation/update, delegation create/revoke, automation rule create/status, export request, AI assistance request, preferences/settings update, local repair and staging acceptance evidence. There is no unrestricted generic action endpoint.

Provider projections must implement strict versioned interfaces and return same-origin destinations, bounded fields and no patient/message/credential payloads.
