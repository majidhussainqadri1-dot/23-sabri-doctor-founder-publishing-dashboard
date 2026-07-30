# Phase 23F — Collections, Campaigns, and Knowledge Links

## Governing Law

**File 23 may own bounded cross-module organizational metadata, but every native content object and destination remains owned by its canonical module.**

Persistent collection and knowledge metadata may contain only:

- provider key;
- native object type;
- native object identifier;
- observed native version;
- relationship type;
- bounded ordering, scope, contributor, target, and schedule metadata;
- current File 23 metadata version;
- actor-scoped idempotency hash and canonical request fingerprint;
- canonical-reference or relation hash;
- bounded created/last audit reason;
- created, updated, and archived timestamps.

File 23 does **not** persist native edit, preview, public, signed, expiring, or patient-document destinations. A destination is freshly re-resolved from the native owner and safety-validated only when an authorized current request needs it.

It must never copy publication bodies, drafts, review decisions, schedules, sources, media binaries, comments, corrections, retractions, profiles, clinical records, prescriptions, patient identifiers, campaign-result reports, or raw analytics.

## File 23-Owned Metadata

Phase 23F declares exactly three narrowly bounded metadata domains:

1. `spdb_collections` — cross-module collections and Founder-governed campaign metadata;
2. `spdb_collection_items` — canonical references that place native objects inside a collection;
3. `spdb_knowledge_links` — typed relationships between native objects.

These tables are organizational indexes, not replacement content, reporting, workflow, or analytics repositories. Schema Version `3` verifies all required tables, columns, and indexes before the repository reports readiness. It persists request fingerprints, bounded audit reasons, and observed native versions while continuing to exclude native destinations and parallel result/report fields.

## Collections

An own-scope collection belongs to the current approved user and requires `spdb_manage_own_content`. It cannot delegate contributors. An institution-scope collection is Founder-governed and requires `spdb_manage_campaigns`.

A collection may group canonical references from News, Learning, Encyclopedia, Research, Video, Reels, PDF Library, Clinical Cases, Remedy Archive, Disease Archive, Profiles, and Search. Archiving a collection or item changes File 23 metadata only; it never deletes, edits, unpublishes, or reclassifies a native object.

The current concrete repository implements verified collection reads and idempotent collection creation. Collection update, archive, item creation, reorder, update, and archive remain explicitly disabled until their separate review gates are complete.

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

A native-reference resolver must freshly confirm exact provider/type/ID, exact authorized scope, existence, visibility, current permission to reference, current owner, and native version. An optional destination may be returned for the current request only after exact-origin safety validation; it is not persisted.

The concrete repository implements verified knowledge-link reads and idempotent knowledge-link persistence, but runtime creation remains unavailable until a reviewed native resolver is injected. Knowledge-link update and archive remain disabled.

## Runtime State

The corrected runtime now includes:

- `SPDB_Native_Reference_Resolver` contract;
- `SPDB_Collections_Service`;
- `SPDB_WP_Collections_Repository`;
- separate read, collection-write, and knowledge-write readiness;
- verified tables, columns, and indexes;
- bounded read-query normalization;
- approved-current-account read authority;
- fail-closed institution scope;
- repository-envelope and projected-row IDOR validation;
- exact replay and same-key/different-payload conflict handling;
- contributor eligibility rechecks;
- native owner, version, visibility, and permission validation;
- development/staging-only write configuration.

The default plugin runtime injects the concrete repository for verified server-side reads but does not inject a native resolver. `SPDB_PHASE23F_WRITES_ENABLED` is false unless explicitly defined, and production is denied even when the constant is defined. No Phase 23F mutation REST route exists.

## Privacy and Failure Semantics

Free-text metadata rejects markup, URLs, email addresses, Pakistani mobile numbers, CNIC-like identifiers, control characters, and excessive Unicode character length. Patient-identifying and clinical content remains with its protected native owner.

Missing or unhealthy schemas, repositories, resolvers, providers, permissions, native objects, malformed repository envelopes, cross-user records, and idempotency payload conflicts produce explicit unavailable, forbidden, not-found, or conflict states. File 23 must never fabricate native titles, counts, destinations, relationships, persistence success, or authorization.
