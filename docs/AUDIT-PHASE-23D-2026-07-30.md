# Phase 23D Independent Corrective Audit — 2026-07-30

## Verdict

The initial Phase 23D candidate was not acceptable for merge. Independent source review found **sixteen defects** affecting role authority, action semantics, provider declarations, URL safety, direct-link bypasses, bounded aggregation, timestamps, localization, and executable assurance. All listed source defects were corrected on `phase/23d-role-workspaces`; exact-head QA and staging acceptance remain separate gates.

**DO NOT MERGE.**

## Findings and Corrections

1. **Resolver-output trust:** the workspace service trusted caller-provided `key`, `read_only`, and `account_status`. It now re-derives all authority from File 00 and the current authenticated user.
2. **Founder policy exposure:** a forged Founder key could expose Founder policy even when official actions were hidden. Founder policy now requires server-verified `smc_is_founder()`.
3. **Read-only bypass:** a forged writable workspace could contradict pending or suspended membership state. File 00 status now forces restricted read-only mode.
4. **Provider-controlled mutability:** a create or edit action could claim `mutating=false`. A canonical action-type matrix now fixes mutability.
5. **Capability downgrade:** a provider could assign a view capability to a create action. Each action type now has one mandatory capability.
6. **Founder-flag mismatch:** provider-supplied Founder flags were authoritative. They are now checked against the canonical action contract.
7. **Undeclared capability:** workspace actions were not checked against provider-declared supported capabilities. They now fail closed when undeclared.
8. **Profile-link bypass:** profile edit/public destinations were rendered outside the centralized action gate. They are now synthesized as canonical actions and removed from display projections.
9. **Knowledge-link bypass:** knowledge management destination bypassed account and acceptance gates. It is now a canonical mutating action.
10. **Unbounded federation:** per-provider limits did not prevent a multi-provider UI/DoS flood. Global provider, card, action, activity, alert, profile, and knowledge limits were added.
11. **Duplicate launch ambiguity:** identical native actions could be repeated across providers. Action destinations are now deduplicated before rendering.
12. **Ambiguous query parsing:** `parse_str()` normalized duplicate and bracketed parameters. Raw query parsing now rejects duplicates, arrays, malformed encoding, and ambiguous separators.
13. **Encoded redirect bypass:** nested URLs could survive one decoding pass. Destination values are recursively decoded and rejected when they contain nested schemes or protocol-relative targets.
14. **Scheme omission:** destination validation did not explicitly require HTTP or HTTPS. Exact-origin HTTP(S) is now mandatory.
15. **Weak source-time contract:** profile and knowledge projections could omit source time, and workspace generation used MySQL format. Native source times are now mandatory RFC 3339 and generated time uses RFC 3339 UTC.
16. **Non-localized CSS text and incomplete tests:** user-facing text was generated in CSS and semantic bypasses were not tested. The text is now localized PHP markup; tests and architecture guards cover forged authority, semantic mismatches, direct-link bypasses, encoded URLs, duplicate parameters, and global bounds.

## Native Ownership Confirmation

No publication body, draft, review record, schedule, source, media, profile, knowledge record, successful-case record, correction, retraction, interaction, notification, or raw analytics event is owned by File 23. The corrected workspace remains a bounded operational projection over native owners.

## Corrective Re-review Scope

The re-review must confirm:

- role and account state are derived only from File 00;
- every launch action matches the canonical semantic matrix;
- all mutating destinations require approved account, capability, ownership, and File 23-controlled adapter acceptance;
- profile and knowledge panels contain no direct destination fields;
- global bounds and truncation notices are deterministic;
- destination parsing rejects encoded redirects and duplicate parameters;
- no duplicate Composer, profile, knowledge, review, or publication backend exists.

## Remaining Acceptance Gates

- corrected exact-head PHP 8.0–8.3 workflow;
- retained source checksums and artifacts;
- real File 21, File 22, File 03, and knowledge-owner adapters on WordPress staging;
- Founder, trusted Doctor, Doctor, pending, and suspended accounts;
- cross-doctor privacy and IDOR tests against native records;
- LiteSpeed/hosting cache, accessibility, responsive, RTL, backup/restore, and rollback acceptance;
- Founder review and explicit acceptance.

Until every applicable gate is complete, PR #4 remains Draft and unmerged.
