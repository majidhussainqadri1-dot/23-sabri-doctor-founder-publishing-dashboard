# File 23 Operations API — `spdb/v1`

All routes are private, cookie-authenticated where authorization is required, capability checked, rate limited, no-store and noindex through the File 23 security/privacy layers.

## Read surfaces

Read routes include native operational projections, analytics, tasks/delegations/rules/exports, preferences, settings, legacy migration diagnostics, system check and activation evidence. Provider projections must implement strict versioned interfaces and return bounded fields, same-origin destinations and no patient/message/credential payloads.

## Mandatory REST rate-limit gate

Every request whose route is `/spdb/v1` or begins `/spdb/v1/` passes `SPDB_REST_Rate_Limiter` before the mutation and sensitive-session guards. The control is not a documentation-only policy:

- fixed actor-level buckets prevent dynamic provider/object IDs from evading limits;
- ordinary reads: 240 requests/minute;
- ordinary writes: 60 requests/minute;
- native review/calendar writes: 30 requests/minute;
- privileged settings/activation/provider-acceptance/local-repair writes: 20 requests/minute;
- optional AI assistance: 20 requests/minute;
- export-generation requests: 10 requests/minute;
- an exceeded bucket fails with HTTP 429 semantics and a bounded retry interval;
- database/schema/persistence/readback failure is fail-closed with 503 rather than silently allowing traffic;
- counters are claimed atomically in the File 23-owned InnoDB `spdb_rest_rate_limits` table;
- authenticated subjects use current user ID; unauthenticated request pressure uses a keyed hash of the server-observed `REMOTE_ADDR`; forwarding headers are not trusted for identity;
- no raw IP, nonce, cookie, token, query string or request body is stored in the rate-limit table;
- expired counters are removed in bounded cleanup batches.

Rate limiting is a defense-in-depth control; it never substitutes for authentication, File 00 authority, capability, ownership/IDOR, current native version, privacy state, nonce/CSRF, idempotency or provider authorization.

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

Native review, reviewer-assignment and calendar operations continue to use their dedicated operation broker, current object version, idempotency, audit reason and provider confirmation contract. They are also subject to the cross-cutting strong-session/same-origin and REST rate-limit gates; those controls do not replace or acquire the native workflows.

## Provider maturity acceptance

`POST /wp-json/spdb/v1/provider-acceptance/{provider_key}` records `staging_accepted`, `production_accepted` or `revoked` for an exact registered provider version. It requires Founder authority, current File 00 MFA/strong-session evidence, `spdb_manage_dashboard_settings`, REST nonce, same-origin context, rate-limit admission, a payload-bound idempotency key, privacy-safe reason and evidence identifier. Production acceptance additionally requires the File 23 staging/Founder release record. Persistence is reverted when canonical audit evidence cannot be appended.
