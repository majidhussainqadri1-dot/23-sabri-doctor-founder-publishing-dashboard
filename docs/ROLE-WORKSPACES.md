# Phase 23D — Founder and Doctor Role Workspaces

## Status

Initial implementation candidate. Independent review, defect correction, corrective re-review, exact-head QA, WordPress staging, real-account verification, rollback, and Founder acceptance remain mandatory.

**DO NOT MERGE.**

## Purpose

Phase 23D turns the common private dashboard shell into role-specific operational workspaces without creating parallel publishing, profile, or knowledge backends.

- Founder receives official-publishing policy, institution-aware projections, and Founder-only native launch destinations when all gates pass.
- Verified Doctor receives own-content professional publishing, profile, knowledge, compliance, and activity projections.
- Trusted Doctor remains subject to explicit category and native-provider policy.
- Pending, suspended, rejected, expired-document, and other non-approved accounts receive restricted read-only status; no mutating destination is exposed.

## Ownership Boundaries

File 23 owns only the role-workspace presentation and dashboard-specific projection validation. It does not own:

- publication bodies or drafts;
- official or professional publication state;
- profile identity or verification records;
- knowledge entries or successful-case records;
- native activity ledgers;
- review, schedule, source, media, correction, retraction, comment, or analytics-event data.

Every value comes from a registered native adapter, is revalidated by File 23, and remains attributable to its provider.

## Optional Adapter Interface

A provider may implement `SPDB_Workspace_Provider_Adapter` in addition to the base Adapter Contract 2.0.0.

```php
public function get_workspace_projection( array $context );
```

The context is generated server-side and includes the current user, resolved workspace, account state, read-only state, Founder/trusted status, allowed scope, and server-resolved environment. Browser-supplied authority fields are not accepted.

## Projection Contract

### Summary cards

Each card requires:

- canonical key;
- label and optional non-sensitive note;
- `measured` or `unavailable` data status;
- numeric value when measured;
- absolute RFC 3339 source timestamp when measured;
- `own` or Founder-only `institution` scope;
- validated owner user ID;
- information, warning, or critical priority.

Missing counts are displayed as unavailable. File 23 does not invent values.

### Native launch actions

Each action requires:

- canonical key and allowlisted action type;
- native same-origin destination;
- canonical File 23 capability;
- explicit mutating flag;
- optional Founder-only flag;
- validated scope and owner.

A mutating native launch destination is visible only when all current gates pass:

1. current canonical capability;
2. compatible File 00 account state;
3. current owner or server-verified Founder scope;
4. Founder-only policy where applicable;
5. exact same-origin destination safety;
6. server-resolved environment;
7. File 23-controlled staging or production adapter acceptance.

The workspace itself does not execute native mutations and does not call the operation broker.

### Profile projection

Profile data remains owned by File 00/File 03. File 23 may show:

- completion percentage;
- verification state;
- publishing eligibility;
- safe native edit destination;
- safe public-profile destination;
- source timestamp.

### Knowledge projection

Knowledge data remains with Encyclopedia, Learning, Research, Video, Reels, PDF, and other native owners. File 23 may show bounded aggregate counts such as linked items, unlinked items, and successful cases, plus a safe native destination and source timestamp.

### Activity and alerts

Activity and alerts are bounded, non-sensitive provider projections. Patient identifiers, contact information, secrets, URLs in text, and malformed or relative timestamps are rejected.

## Failure Isolation

- A provider exception becomes a bounded generic provider error.
- Invalid provider data does not render.
- A failing provider does not prevent another valid provider from rendering.
- No-provider state shows an explicit unavailable notice instead of fabricated cards or actions.
- Revoked, suspended, detected-only, incompatible, or unavailable providers do not supply workspace projections.

## Founder Publishing Policy

The Founder workspace projects the approved official content classes:

- Founder Update;
- Official Guidance;
- Platform Announcement;
- Platform News and Breaking News;
- Book, Research, and Clinic Announcements;
- Institution-wide Correction;
- Retraction Notice;
- Pinned Official Publication.

Direct native publication remains blocked by malware, patient identifiers, privacy holds, security failure, copyright/legal blockers, missing authority, or unaccepted adapters.

## Doctor Publishing Policy

The Doctor workspace projects professional content classes including Articles, Clinical Education, Patient Education, Successful Cases, Remedy and Disease Notes, Materia Medica, Repertory, Research, Nutrition, Preventive Health, Videos, Reels, PDFs, and Q&A.

Regular Doctor publication uses native Submit for Review by default. Trusted Doctor behavior remains limited by explicit category and provider policy. Patient consent, anonymity, references, medical-claim restrictions, and review feedback remain authoritative.

## Initial Executable Coverage

`tests/workspace-tests.php` covers:

- Doctor and Founder policy separation;
- Founder-only official action denial;
- restricted-account mutation denial;
- provider acceptance gating;
- current-user context and scope;
- cross-doctor owner mismatch;
- institution scope denial for Doctor;
- secret-bearing and cross-origin destination rejection;
- absolute timestamp requirements;
- patient-identifying activity rejection;
- provider failure isolation;
- no-provider truthful empty states;
- protected workspace route.

## Known Initial Limitations

- No real File 21, File 22, File 03, Encyclopedia, Learning, Research, Video, Reels, or PDF workspace adapter is included yet.
- No mutating action is production-enabled by default.
- Profile and knowledge projections require staging integration with their real native modules.
- Manual accessibility, responsive, RTL, cache-stack, upgrade, backup/restore, and rollback evidence is pending.
- This initial candidate has not yet completed its independent source review.
