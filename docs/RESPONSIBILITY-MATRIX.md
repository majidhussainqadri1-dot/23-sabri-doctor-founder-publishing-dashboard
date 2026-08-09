# File 21–25 Responsibility Matrix

## Governing Rule

Each permanent data domain has exactly one native owner. File 23 may query, project, aggregate, route, and invoke authorized provider actions, but it must not silently become the owner of another module's records.

| Domain | Native owner | File 23 responsibility | File 23 prohibited behavior |
|---|---|---|---|
| Identity, verification, suspension | File 00 | Read current authenticated identity and capabilities | Creating parallel users, roles, verification, or suspension states |
| Global application shell | File 20 | Mount private dashboard routes and use shell integration slots | Replacing global navigation or duplicating global Safe Mode |
| Social and News publications | File 21 | List, filter, route, and invoke versioned native actions | Copying publication bodies or storing parallel publication status |
| Newsroom review | File 21 | Display a federated review projection and route authorized decisions | Creating a second review ledger or approval state |
| Sources and evidence for News | File 21 | Show completeness/alerts and native editor links | Creating a competing News source registry |
| Comments, reports, corrections, retractions | File 21 or relevant native provider | Unified inbox/projection and authorized routing | Duplicating comments, reports, correction, or retraction records |
| Composer, drafts, autosave, editing, preview | File 22 | Open correct create/edit/revision destinations | Building another composer or draft store |
| Learning lessons/courses | File 05 or future University module | Project and route through adapter | Copying lesson/course bodies or completion records |
| Encyclopedia entries | File 06 | Project and route through adapter | Duplicating canonical knowledge entries |
| Video publications | File 10 | Project metadata and native actions | Owning video binaries or processing pipeline |
| Reels | File 11 | Project metadata and native actions | Owning Reel binaries or watch history |
| PDF Library | File 12 | Project metadata, compliance, and native routes | Owning encrypted PDF binaries, notes, or reading progress |
| Notifications | File 19 | Consume notification events and show inbox link/status | Creating another delivery queue |
| Profiles and public identity | Files 00/03 | Preview resolved author identity and link to native editor | Writing parallel profile data |
| Security, privacy, compliance and resilience assurance | File 24 | Consume sanitized assurance/health evidence and respect coordinated restrictions | Duplicating native security enforcement, secrets, incident evidence, or security state ownership |
| Public timeline and visual presentation | File 25 | Preview/link to public destinations and consume visual-system contracts | Owning public profile rendering or creating a second shell/visual-system owner |
| Raw analytics events | Native providers/analytics service | Read bounded aggregates and cache summaries | Collecting raw cross-platform events without an approved contract |
| Cross-module tasks | File 23 | Own task metadata and references | Copying native content into tasks |
| Cross-module collections/campaigns | File 23 | Own canonical object references, order, goals, and schedule metadata | Copying content bodies or replacing native series |
| Dashboard preferences/saved views | File 23 | Own private user configuration | Exposing private configuration publicly |
| Adapter health and dashboard audit | File 23 | Own bounded operational metadata | Treating health cache as native source of truth |

## Change-Control Rule

A domain may move to another owner only through an approved migration plan containing old owner, new owner, schema impact, API impact, rollback, tests, and acceptance evidence.
