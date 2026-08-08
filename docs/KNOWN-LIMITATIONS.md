# File 23 Known Limitations and Residual-Risk Register

## Source scope

No known unresolved blocker or critical **File 23 source defect** is intentionally accepted after the documented review/fix rounds. This statement is deliberately scoped to File 23 itself: a companion dependency with unresolved blocker/critical defects can still block staging promotion or production acceptance even when File 23 source tests are green. The following items are release/operational limitations that require external evidence; they are not treated as completed by source inspection.

| ID | Limitation / residual risk | Current control | Closure evidence |
|---|---|---|---|
| F23-LIM-001 | Hostinger/LiteSpeed behavior has not been accepted on the final package. | Production writes fail closed; private/no-store markers in source. | Fresh install plus cross-user cache isolation logs/screenshots. |
| F23-LIM-002 | Final real-role matrix is not yet executed. | File 00 assertions and capability gates. | Founder/Doctor/Reviewer/Pending/Suspended/institutional-AI test record. |
| F23-LIM-003 | Companion plugins may drift from pinned contract versions. | Explicit semantic compatibility and provider acceptance states. | Exact installed versions and real integration suite. |
| F23-LIM-004 | Optional providers may be absent or partially implemented. | Per-provider isolation and explicit unavailable/stale states. | Provider-by-provider acceptance or approved unavailable status. |
| F23-LIM-005 | Real browser/device/assistive-technology differences are not proven by static tests. | Semantic templates, focus/RTL/reduced-motion/forced-colors CSS. | Browser, mobile, keyboard, screen-reader, zoom and contrast matrix. |
| F23-LIM-006 | Modeled 10,000-operation integrity is not a real 10,000+ object database benchmark. | Bounded pagination/query/provider budgets in code. | Real dataset timings, query counts and SLO approval. |
| F23-LIM-007 | Real backup/restore and rollback timings are unknown. | Non-destructive schema/install and documented runbook. | Successful rehearsal with integrity reconciliation and recovery time. |
| F23-LIM-008 | Operational secrets, incident escalation and on-call readiness depend on hosting procedures. | No secrets in client/log/assurance contracts. | Named operators, key management and incident drill evidence. |
| F23-LIM-009 | Automated CI does not constitute Founder visual/functional acceptance. | Draft PR and fail-closed staging acceptance record. | Dated Founder sign-off on representative mobile and desktop journeys. |
| F23-LIM-010 | Current corrective work is not canonical `main` or Live. | Draft PR; deterministic artifacts only after exact-head gates pass. | Explicit merge authorization, merge commit, deployment and monitoring evidence. |
| F23-LIM-011 | The currently pinned File 00 head (`3a84c32a6ddad151f2ed09d244fa8aa536a58108`) has a separate current engineering audit that reports unresolved Critical/High defects. File 23 cannot convert File 00 contract compatibility into a claim that the identity system is production-safe. | File 23 consumes File 00 through a fail-closed versioned contract, rechecks current identity/session state, and keeps staging/live/operational status separate. | A later File 00 exact-head corrective release with its own green source gates **and** required real staging/security/restore acceptance; then File 23 must refresh the pin and rerun real-contract and real-role gates. |

## Non-limitations / intentional boundaries

The following are deliberate architecture decisions, not missing features:

- File 23 does not own publication, review, source, media, comment, notification, profile, clinical, payment or raw-analytics truth.
- File 22 remains the sole create/edit Composer.
- File 20 owns global Safe Mode and platform rollback.
- File 24 receives sanitized assurance evidence only.
- File 25 owns public visual/profile/timeline presentation.
- File 26 owns global Search/Discovery/Recommendations/Ranking.
- A missing provider degrades its section rather than being replaced by a duplicate File 23 backend.

## Release rule

Any newly discovered blocker/critical defect in File 23 **or a required production dependency** reopens the applicable correction/acceptance gate. File 23 may remain source-complete while production promotion remains blocked. External limitations close only with attached evidence in `docs/RELEASE-SIGNOFF.md`; green CI alone never closes staging, live or operational gates.
