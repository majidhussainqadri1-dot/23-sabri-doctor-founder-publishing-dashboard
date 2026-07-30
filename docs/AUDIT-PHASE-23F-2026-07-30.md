# Phase 23F Independent Source Audit — 2026-07-30

## Verdict

The initial Phase 23F foundation at `77383c80d1e844b069936ab8488c0bc6927362b6` is **not acceptable for merge or runtime activation**. The metadata-only direction is correct, but independent review found eighteen defects across authorization, ownership boundaries, schema integrity, privacy, migration evidence, regression protection, and executable QA.

**DO NOT MERGE.** Every finding below must be corrected, the corrected source must be re-reviewed, and exact-head PHP 8.0–8.3 QA must succeed before this foundation may be treated as source-complete. Staging and Founder acceptance remain separate later gates.

## Findings

1. **Phase 23F behavioral tests were not executed by CI.** The workflow syntax-checked the file indirectly but never ran `tests/phase23f-policy-tests.php`, so the green baseline did not prove Phase 23F behavior.
2. **The architecture guard regressed prior protections.** The Phase 23F edit replaced rather than extended several Phase 23C–23E guards for inventory, workspace, review/calendar semantics, and provider mutation boundaries.
3. **Schema fields exceeded the declared ownership boundary.** `progress`, `results_summary`, and `final_report_url` were not part of the policy contract and could become a parallel campaign-results or reporting backend.
4. **Persistent native destinations violated re-resolution rules.** `native_destination` in collection items and knowledge links could retain signed, expiring, private, stale, or permission-lost URLs.
5. **Knowledge-link scope was missing from validation.** The schema had `scope` and `owner_user_id`, but the input contract could not distinguish own from institution governance.
6. **Own-scope validation did not require an approved current account or an explicit management capability.** Shape validation could succeed for an unauthorized or restricted account.
7. **Institution knowledge links had no Founder and capability gate.** Institution authority was enforced only for collections/campaigns.
8. **Contributor identifiers were only syntactically validated.** The foundation did not clearly distinguish shape validation from later approved-account and capability validation.
9. **Campaign ethics detection was narrower than the documentation claimed.** A short English phrase list cannot be described as comprehensive ethical moderation.
10. **Text limits used byte length.** `strlen()` could reject or mismeasure valid multilingual metadata and did not match the platform’s multilingual design.
11. **Idempotency uniqueness was globally scoped per table.** The same client-generated key used by different actors could collide and disclose the existence of another actor’s request.
12. **Knowledge relation uniqueness ignored owner and scope.** Two users could not independently create the same own-scope relationship even though their metadata authority is separate.
13. **Schema installation reported no verifiable result.** `install()` returned `void`, updated the schema option after `dbDelta()`, and did not verify that all declared tables actually existed.
14. **Schema lifecycle was not wired to activation or upgrade.** The declared tables would not be installed by the plugin bootstrap.
15. **Repository contract was incomplete.** It lacked collection archive, item fetch/update, bounded query contracts, repository health, and schema-readiness reporting needed for safe runtime behavior.
16. **No native-reference resolver contract existed.** The foundation required native re-resolution in prose but supplied no versioned code boundary for existence, visibility, ownership, permission, and safe-destination checks.
17. **Tests were too shallow.** Pending/suspended accounts, missing capabilities, institution knowledge, malformed lists and timestamps, Unicode length, sensitive metadata, duplicate values, and CI regression behavior were untested.
18. **Status and documentation overstated implemented evidence.** The documents described idempotency, audit, safe destinations, and schema validation more strongly than the executable implementation supported.

## Required Corrections

- Restore every prior architecture guard and append Phase 23F-specific checks.
- Remove noncanonical result/report fields and every persisted native destination.
- Add current-account, capability, scope, and Founder fail-closed policy gates.
- Add explicit knowledge-link scope and distinguish shape validation from contributor authorization.
- Use Unicode-aware bounded text validation.
- Scope idempotency and canonical-relation uniqueness to the acting owner/scope.
- Make schema installation verifiable and update the version option only after table verification.
- Expand the repository interface and introduce a native-reference resolver contract.
- Wire schema lifecycle and a fail-closed Phase 23F runtime service without exposing production mutation routes.
- Execute expanded Phase 23F tests in every PHP matrix job and retain exact-head evidence.
- Correct the documentation and status language.

## Merge Gate

This audit does not approve the phase. The Pull Request must remain Draft and unmerged until corrections, corrective re-review, exact-head QA, WordPress staging, real-provider and real-account evidence, accessibility, backup/restore, rollback, Founder review, and explicit merge authorization are complete.