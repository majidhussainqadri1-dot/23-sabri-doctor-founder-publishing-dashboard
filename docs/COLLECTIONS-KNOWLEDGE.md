# Phase 23F — Collections, Campaigns, and Knowledge Links

## Governing Law

**File 23 may own bounded cross-module organizational metadata, but every native content object and destination remains owned by its canonical module.**

Persistent collection and knowledge metadata may contain only:

- provider key;
- native object type;
- native object identifier;
- relationship type;
- bounded ordering, scope, contributor, target, and schedule metadata;
- current File 23 metadata version;
- actor-scoped idempotency hash and canonical-reference hash;
- created, updated, and archived timestamps.

File 23 does **not** persist native edit, preview, public, signed, expiring, or patient-document destinations. A destination is freshly re-resolved from the native owner and safety-validated only when an authorized current request needs it.

It must never copy publication bodies, drafts, review decisions, schedules, sources, media binaries, comments, corrections, retractions, profiles, clinical records, prescriptions, patient identifiers, campaign-result reports, or raw analytics.

## File 23-Owned Metadata

Phase 23F declares exactly three narrowly bounded metadata domains:

1. `spdb_collections` — cross-module collections and Founder-governed campaign metadata;
2. `spdb_collection_items` — canonical references that place native objects inside a collection;
3. `spdb_knowledge_links` — typed relationships between native objects.

These tables are organizational indexes, not replacement content, reporting, workflow, or analytics repositories. Schema version `2` removes the initial candidate’s progress, results, report URL, and persisted destination fields. Installation is accepted only after WordPress verifies that all three tables exist.

## Collections

An own-scope collection belongs to the current approved user and requires `spdb_manage_own_content`. It cannot delegate contributors. An institution-scope collection is Founder-governed and requires `spdb_manage_campaigns`.

A collection may group canonical references from News, Learning, Encyclopedia, Research, Video, Reels, PDF Library, Clinical Cases, Remedy Archive, Disease Archive, Profiles, and Search. Archiving a collection or item changes File 23 metadata only; it never deletes, edits, unpublishes, or reclassifies a native object.

## Campaigns

A campaign is institution-scoped and Founder-governed. It requires:

- a clear objective;
- an ethical declaration;
- canonical UTC start and end times;
- explicit allowlisted target surfaces;
- current audit reason and idempotency key;
- approved and authorized contributors when contributors are supplied.

The automated phrase screen rejects several fear, urgency, scarcity, fabricated-success, miracle, and cure-guarantee patterns. This is **defense in depth only**. It does not replace Founder responsibility, human moderation, medical policy, native-module authorization, or later compliance review.

Campaign-only ethics, target, and schedule fields are forbidden on an ordinary collection.

## Knowledge Links

A knowledge link connects two distinct canonical native objects through an allowlisted relation such as Encyclopedia, Learning, Research, Founder Book, Doctor Knowledge, Clinical Case, Remedy Archive, Disease Archive, Video Series, PDF Series, Timeline, Topic Archive, or Search Index.

Own-scope links require current approved-account and own-content management authority. Institution-scope links require current Founder identity and campaign-management capability. Self-links, malformed references, unknown relations, duplicate canonical values, and sensitive free text fail closed.

A native-reference resolver must freshly confirm exact provider/type/ID, existence, visibility, current permission to reference, current owner, and native version. An optional destination may be returned for the current request only after exact-origin safety validation; it is not persisted.

## Runtime State

The next coding stage has begun with:

- `SPDB_Native_Reference_Resolver`;
- `SPDB_Collections_Service`;
- truthful repository/resolver health;
- bounded read-query normalization;
- current-user visibility checks;
- contributor eligibility rechecks;
- development/staging-only write enablement.

The default plugin runtime injects neither a concrete repository nor a concrete resolver. `SPDB_PHASE23F_WRITES_ENABLED` is false unless explicitly defined, and production is denied even when the constant is defined. No Phase 23F mutation REST route exists.

## Privacy and Failure Semantics

Free-text metadata rejects markup, URLs, email addresses, Pakistani mobile numbers, CNIC-like identifiers, control characters, and excessive Unicode character length. Patient-identifying and clinical content remains with its protected native owner.

Missing repositories, resolvers, providers, permissions, or native objects produce explicit unavailable, forbidden, or not-found states. File 23 must never fabricate native titles, counts, destinations, relationships, persistence success, or authorization.
