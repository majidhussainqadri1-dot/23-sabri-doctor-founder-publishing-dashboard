# Phase 23B Second Corrective Review — 2026-07-30

## Scope

This review re-examined the complete Phase 23B stacked change set after the first corrective audit. It covered runtime access with the supplied File 00 Membership Core 1.0.1 package, private route and REST behavior, saved-view persistence, provider isolation, accessibility, multiple-render behavior, CI evidence, and the permanent review-before-merge rule.

## Decision

**DO NOT MERGE.** Source corrections and automated tests do not replace WordPress staging, real-account, cache-stack, responsive, accessibility, and Founder acceptance.

## New Findings and Corrections

| ID | Severity | Finding | Correction |
|---|---|---|---|
| 23B-R2-01 | Critical | The supplied File 00 1.0.1 roles did not contain any `spdb_*` capabilities, so Founder and doctor accounts could not actually enter the dashboard despite passing Membership Core status checks. | Added an activation- and upgrade-reconciled capability installer. It creates no roles, grants only File 23 keys to existing approved roles, and leaves runtime Membership Core checks mandatory. |
| 23B-R2-02 | Critical | Pull-request CI checked GitHub's synthetic merge ref while the PR claimed exact-head evidence. | Checkout now pins the PR head SHA, verifies `git rev-parse HEAD`, and fails on mismatch. |
| 23B-R2-03 | High | A throwing provider registration callback was caught only around the entire action, preventing later healthy providers from registering. | Added per-callback isolated registration dispatch and regression tests proving later providers still register. |
| 23B-R2-04 | High | File 23 REST error responses could reach the serving stage without the private header policy. | Added `rest_pre_serve_request` protection for every `/spdb/v1` response, including converted errors, while preserving response objects. |
| 23B-R2-05 | High | The shortcode could execute from an indirect widget/template context that was not detected early enough for cache and indexing protection. | Shortcode rendering now fails closed unless it is the virtual route or a directly detected protected singular page. |
| 23B-R2-06 | High | Private dashboard requests did not set the cache-plugin interoperability constant used by common WordPress cache stacks. | Added early `DONOTCACHEPAGE` marking for virtual and shortcode routes, while retaining explicit no-store headers. |
| 23B-R2-07 | Medium | The saved-view deletion route sanitized its ID but had no explicit REST validation callback. | Added exact canonical saved-view ID validation. |
| 23B-R2-08 | High | Nested or object filter values could be cast to strings, causing warnings or accepting malformed values. | Added strict scalar/shape validation for labels, filter objects, scalar filters, and multi-value entries. |
| 23B-R2-09 | High | Concurrent saved-view creates/deletes could silently overwrite another request's changes. | Added compare-and-store persistence using the exact previous user-meta value and a 409 conflict response on concurrent modification. |
| 23B-R2-10 | Medium | The public saved-view projection method accepted arbitrary user IDs. | Projection is now bound to the current authenticated user. |
| 23B-R2-11 | Medium | Deleting the last initially rendered saved view left no empty-state message. | The template now always renders a toggleable empty state and list container. |
| 23B-R2-12 | Medium | Multiple shortcode instances created duplicate IDs and the JavaScript initialized only the first dashboard. | Every render now receives unique IDs, and JavaScript initializes each dashboard shell independently. |
| 23B-R2-13 | Medium | CSS removed the visible focus outline from the skip-link target. | Added an accessibility correction layer restoring visible main-region focus. |
| 23B-R2-14 | High | CI produced transient console output but no retained exact-head evidence package. | Every PHP matrix job now records context, test output, source checksums, and uploads a retained QA artifact. |

## Supplied File 00 Verification

The supplied `00 sabri-membership-core-1.0.1.zip` was inspected during this review. It defines:

- `SMC_VERSION` 1.0.1;
- `smc_user_status()`;
- `smc_is_founder()`;
- `smc_is_trusted_publisher()`;
- existing roles including `administrator`, `sabri_pending`, `sabri_doctor`, `sabri_verified_doctor`, `sabri_medical_reviewer`, and `sabri_moderator`.

It does not define File 23 `spdb_*` capabilities. The new installer closes that actual integration gap without creating or renaming File 00 roles.

## Required Re-Review Evidence

After the final corrective commit:

1. PHP 8.0, 8.1, 8.2, and 8.3 must pass.
2. The checked SHA must equal the pull-request head SHA.
3. Contract, dashboard-core, REST privacy, capability installer, provider registration, and architecture tests must pass.
4. JavaScript syntax and version alignment must pass.
5. QA artifacts and source checksums must be retained.
6. Any later source commit invalidates the evidence and requires another affected-scope review and rerun.

## Remaining Non-Automated Gates

- Hostinger WordPress staging activation;
- private headers through LiteSpeed and hosting cache layers;
- real Founder account;
- real verified and non-verified doctor accounts;
- pending and suspended read-only accounts;
- mobile, keyboard, screen-reader, contrast, and responsive acceptance;
- rollback and reactivation;
- Founder review and explicit acceptance.

Until every applicable gate is complete, PR #2 remains Draft, open, and unmerged.
