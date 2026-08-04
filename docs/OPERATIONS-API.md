# File 23 Operations API — `spdb/v1`

All routes are private, cookie-authenticated, capability checked, no-store and noindex through the File 23 privacy layer.

## Read surfaces

Read routes include native operational projections, analytics, tasks/delegations/rules/exports, preferences, settings, legacy migration diagnostics, system check and activation evidence. Provider projections must implement strict versioned interfaces and return bounded fields, same-origin destinations and no patient/message/credential payloads.

## Explicit mutation surfaces

Mutation routes are limited to task creation/update, delegation create/revoke, automation rule create/status, export request, optional AI assistance request, preferences/settings update, local repair and staging acceptance evidence. There is no unrestricted generic action endpoint.

Every File 23-owned operational mutation is additionally protected by `SPDB_Operational_Mutation_Guard`:

- valid authenticated account and route-level capability checks;
- explicit `X-WP-Nonce`/`wp_rest` verification;
- Origin/Referer same-origin enforcement;
- a 16–128 character `Idempotency-Key`;
- deterministic payload fingerprinting and altered-payload conflict denial;
- bounded private replay receipts stored as non-autoload temporary options;
- transaction commit/rollback for local File 23 writes;
- request and outcome evidence in the canonical hash-chained dashboard audit;
- private/no-store replay responses with secret, credential, patient, clinical and message-shaped fields removed.

The dashboard JavaScript generates one high-entropy idempotency key for a logical form submission, retains it during the same retry and clears it after confirmed success or a subsequent user edit.

Native review, reviewer-assignment and calendar operations continue to use their dedicated operation broker, object version, idempotency, audit reason and provider confirmation contract. The cross-cutting guard does not replace or acquire those native workflows.

## Provider maturity acceptance

`POST /wp-json/spdb/v1/provider-acceptance/{provider_key}` records `staging_accepted`, `production_accepted` or `revoked` for an exact registered provider version. It requires Founder authority, current File 00 MFA, `spdb_manage_dashboard_settings`, REST nonce, same-origin context, a payload-bound idempotency key, privacy-safe reason and evidence identifier. Production acceptance additionally requires the File 23 staging/Founder release record. Persistence is reverted when canonical audit evidence cannot be appended.
