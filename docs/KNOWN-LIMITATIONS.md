# File 23 Known Limitations and Residual-Risk Register — Version 1.3.0

## Source scope

Version 1.3.0 is the amended-plan repository candidate. It adds current File 00–26 ownership, Sabri Green token consumption, Founder/Doctor/Teacher/Admin studios and current-plan traceability. No staging/live/operational claim is made from source presence alone. Any source defect found by the 1.3.0 exact-head CI or fresh reviews must be fixed before repository release closure.

| ID | Limitation / residual risk | Current control | Closure evidence |
|---|---|---|---|
| F23-LIM-001 | Hostinger/LiteSpeed behavior has not been accepted on the exact 1.3.0 package. | Production writes fail closed; private/no-store markers in source. | Fresh install plus cross-user cache isolation logs/screenshots. |
| F23-LIM-002 | Final real-role/studio matrix is not yet executed on Hostinger. | File 00 assertions, explicit Teacher/Admin studio capabilities and runtime gates. | Founder/Doctor/Teacher/Admin/Reviewer/Pending/Suspended test record for roles actually present. |
| F23-LIM-003 | Companion plugins may drift from pinned contract versions, including File 26 availability. | Explicit semantic compatibility, provider acceptance and complete File 00–26 discovery manifest. | Exact installed versions and real integration suite. |
| F23-LIM-004 | Optional providers may be absent or partially implemented. | Per-provider isolation and explicit unavailable/stale states. | Provider-by-provider acceptance or approved unavailable status. |
| F23-LIM-005 | Real browser/device/assistive-technology/low-bandwidth behavior is not proven by static tests. | Semantic templates, File 25 token consumption, focus/RTL/reduced-motion/reduced-data/forced-colors CSS. | Browser, mobile, keyboard, screen-reader, 200% zoom, contrast and constrained-network matrix. |
| F23-LIM-006 | Modeled 10,000-operation integrity is not a real 10,000+ object database benchmark. | Bounded pagination/query/provider budgets in code. | Real dataset timings, query counts and SLO approval. |
| F23-LIM-007 | Real backup/restore and rollback timings are unknown. | Non-destructive schema/install and documented runbook. | Successful rehearsal with integrity reconciliation, rights/deletion state and recovery time. |
| F23-LIM-008 | Operational secrets, incident escalation, vulnerability response and on-call readiness depend on hosting/operations procedures. | No secrets in client/log/assurance contracts; current-plan operational requirements are traceable. | Named operators, key management, incident/vulnerability drills and runbook evidence. |
| F23-LIM-009 | Automated CI does not constitute Founder visual/functional staging acceptance. | Separate release states and pending release sign-off. | Dated Founder sign-off on representative mobile/desktop/RTL role journeys. |
| F23-LIM-010 | The current 1.3.0 branch/PR candidate is not evidence of deployment to Live. | Deterministic artifacts and exact-head evidence are separate from deploy evidence. | Approved merge commit, exact deployed package/checksum parity, DB/migration state and live smoke/monitoring evidence. |

## Intentional canonical boundaries, not missing features

- File 23 does not own publication, draft, review, source, media, comment, learning, notification, profile, clinical, payment, search/ranking or raw-analytics truth.
- File 22 remains the sole universal create/edit/draft/autosave/preview/submit Composer.
- File 21 remains canonical social/news publication and review owner.
- File 20 owns the application shell, PWA-level behavior, global Safe Mode and platform rollback.
- File 24 owns cross-platform security/privacy/compliance/resilience assurance while native controls remain native.
- File 25 owns public visual/profile/timeline presentation and the primary design-token registry; File 23 only consumes it with approved Sabri Green fallback.
- File 26 owns Search/Discovery/Ranking; File 23 only consumes typed projections/destinations.
- A missing provider degrades its section rather than being replaced by a duplicate File 23 backend.
- Donor status or paid tiers do not grant File 23 capability, reach, ranking or support advantage.

## Release rule

Any newly discovered blocker/critical defect reopens coding and invalidates release acceptance until corrected and freshly re-reviewed. External limitations close only with attached evidence in `docs/RELEASE-SIGNOFF.md`. Exact deployed code remains unverified until deployment-parity evidence exists.
