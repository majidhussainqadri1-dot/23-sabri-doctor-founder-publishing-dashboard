# File 23 Data Ownership Matrix — Version 1.3.0

## Governing principle

File 23 is a private federated publishing-operations interface. Native owners remain authoritative; File 23 stores only bounded operational metadata, immutable pointers, aggregate snapshots, jobs, preferences and audit evidence. The amended 2026 architecture is File 00–26; a dashboard, cache, AI helper, search result or notification is never promoted into a second system of record.

| Domain | Canonical owner | File 23 relationship | File 23 storage permission |
|---|---|---|---|
| Identity, approval, verification, suspension, institutional authority | File 00 | Consume current server-side assertions | No identity-document or credential copy. |
| Authentication/session | File 02 | Consume authenticated current user/session | No password/OAuth-secret storage. |
| Profile and doctor identity | File 03 | Privacy-filtered projection and native destination | No duplicate profile truth. |
| Legacy publishing migration | File 04 / cutover owner File 21 | Read-only diagnostics and migration evidence | No new legacy mutation owner. |
| Learning content | File 05 | Provider projection/native routes, including Teacher Studio educational context | Pointer/aggregate only; no lesson/student truth copy. |
| Encyclopedia/knowledge content | File 06 | Provider projection/native routes | Pointer/relationship only. |
| Doctor directory | File 07 | Public destination context | No directory truth copy. |
| Clinic/appointments | File 08 | Context links and permitted projections | No clinical/appointment truth. |
| Doctor professional verification | File 09 | Eligibility projection | No credential-document copy. |
| Video/live | File 10 | Asset/content projection | No media binary/private delivery URL. |
| Reels | File 11 | Asset/content projection | No media binary/private delivery URL. |
| PDF library | File 12 | Document projection/native destination | No PDF binary/private token. |
| Intro animation | File 13 | Route/health awareness only | None. |
| Clinic USP | File 14 | Destination health only | None. |
| Radar/trends | File 15 | Aggregate trend/knowledge projection | No raw event warehouse. |
| AI assistance | File 16 | Optional citation-bound educational assistance | No autonomous action/diagnosis/prescription; no sensitive prompt retention by File 23. |
| Network/messages | File 17 | Context and native deep links | No message body/attachment copy. |
| Marketplace | File 18 | Listing/deal context projection | No order/payment truth. |
| Notifications | File 19 | Emit privacy-safe event; consume status/deep link | No delivery queue or preferences duplicate. |
| Global shell, route mount, global Safe Mode/rollback | File 20 | Mount private route and consume global state | No second shell/PWA/global repair owner. |
| Publication, review, source, interaction and correction truth | File 21 | Canonical versioned adapter and operation broker | No publication/review/source/comment duplicate. |
| Create/edit/draft/preview/submit orchestration | File 22 | Sole composer deep link and contract | No second composer form or draft truth. |
| Publishing operational metadata and role studios | File 23 | Native owner | Preferences, saved views, tasks, delegations, rules, pointers, bounded metrics, exports/jobs/health/audit only. |
| Security/privacy/compliance/resilience assurance | File 24 | Send sanitized evidence; consume assurance state | No central secret/incident evidence copy; native controls stay native. |
| Public visual/profile/timeline presentation and primary token registry | File 25 | Consume components/tokens/public destinations | No public timeline/profile backend; Sabri Green fallback is not a second token registry. |
| Search, Discovery and Ranking | File 26 | Consume typed search/discovery projections, health and canonical destinations | No search index, ranking model, query history or discovery truth owned by File 23. |

## File 23-owned records

1. Dashboard preferences and saved views.
2. Collaboration tasks.
3. Scoped delegations.
4. Human-governed automation-rule definitions.
5. Cross-module collections/campaign metadata and immutable native pointers.
6. Knowledge relationships between native objects.
7. Privacy-thresholded bounded aggregate snapshots.
8. Export-job metadata and expiring delivery references.
9. Adapter-health cache.
10. Background-job state.
11. Hash-chained dashboard audit evidence.

## F23-CEN-01 enforcement

Founder, Doctor, Teacher and Administrator studios are operational projections. Studio selection changes the least-privilege view and available adapter actions; it does not change the canonical owner of a publication, draft, review, lesson, profile, message, appointment, media asset, search result, moderation decision or security assertion.

## Mandatory prohibitions

- No direct write to companion database tables or File 26 search indexes.
- No duplicate content, draft, review, source, asset, interaction, profile, notification, learning, search/ranking, clinical or payment backend.
- No client-supplied role/provider/status/environment treated as authority.
- No successful provider action reported until native object state is re-read and confirmed.
- One provider outage must degrade only its own section and must never imply approved/published/deleted state.
- No paid tier or donor state may alter File 23 capability, reach, ranking or support priority.
