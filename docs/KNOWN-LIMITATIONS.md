# File 23 Known Limitations and Residual-Risk Register — Version 1.1.0

## Source scope

No known unresolved blocker or critical source defect is intentionally accepted in the Version 1.1.0 candidate after the documented review/fix rounds. The following items are release/operational limitations that require external evidence; they are not treated as completed by source inspection.

| ID | Limitation / residual risk | Current control | Closure evidence |
|---|---|---|---|
| F23-LIM-001 | Hostinger/LiteSpeed behavior has not been accepted on the final package. | Production writes fail closed; private/no-store markers in source. | Fresh install plus cross-user cache isolation logs/screenshots. |
| F23-LIM-002 | Final real-role matrix is not yet executed. | File 00 assertions and capability gates. | Founder/Doctor/Reviewer/Pending/Suspended test record. |
| F23-LIM-003 | Companion plugins may drift from pinned contract versions. | Explicit semantic compatibility and provider acceptance states. | Exact installed versions and real integration suite. |
| F23-LIM-004 | Optional providers may be absent or partially implemented. | Per-provider isolation and explicit unavailable/stale states. | Provider-by-provider acceptance or approved unavailable status. |
| F23-LIM-005 | Real browser/device/assistive-technology differences are not proven by static tests. | Semantic templates, focus/RTL/reduced-motion/forced-colors CSS. | Browser, mobile, keyboard, screen-reader, zoom and contrast matrix. |
| F23-LIM-006 | Modeled 10,000-operation integrity is not a real 10,000+ object database benchmark. | Bounded pagination/query/provider budgets in code. | Real dataset timings, query counts and SLO approval. |
| F23-LIM-007 | Real backup/restore and rollback timings are unknown. | Non-destructive schema/install and documented runbook. | Successful rehearsal with integrity reconciliation and recovery time. |
| F23-LIM-008 | Operational secrets, incident escalation and on-call readiness depend on hosting procedures. | No secrets in client/log/assurance contracts. | Named operators, key management and incident drill evidence. |
| F23-LIM-009 | Automated CI does not constitute Founder visual/functional acceptance. | Draft PR and fail-closed staging acceptance record. | Dated Founder sign-off on representative mobile and desktop journeys. |
| F23-LIM-010 | Current PR is not canonical `main` or Live. | Protected Draft PR; deterministic artifacts. | Explicit merge authorization, merge commit, deployment and monitoring evidence. |

## Non-limitations / intentional boundaries

The following are deliberate architecture decisions, not missing features:

- File 23 does not own publication, review, source, media, comment, notification, profile, clinical, payment or raw-analytics truth.
- File 22 remains the sole create/edit Composer.
- File 20 owns global Safe Mode and platform rollback.
- File 24 receives sanitized assurance evidence only.
- File 25 owns public visual/profile/timeline presentation.
- A missing provider degrades its section rather than being replaced by a duplicate File 23 backend.

## Release rule

Any newly discovered blocker/critical defect reopens coding and invalidates release acceptance until corrected and independently re-reviewed. External limitations close only with attached evidence in `docs/RELEASE-SIGNOFF.md`.
