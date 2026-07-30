# Phase 23F — Collections, Campaigns, and Knowledge Links

## Governing Law

**File 23 may own cross-module organizational metadata, but every native content object remains owned by its canonical module.**

A collection, campaign, or knowledge link stores canonical references only:

- provider key;
- native object type;
- native object identifier;
- relationship type;
- bounded ordering and scope metadata;
- safe native destination;
- current metadata version;
- audit and idempotency evidence.

It must never copy publication bodies, drafts, review decisions, schedules, sources, media binaries, comments, corrections, retractions, profiles, clinical records, prescriptions, patient identifiers, or raw analytics.

## File 23-Owned Metadata

Phase 23F introduces three narrowly bounded metadata domains:

1. `spdb_collections` — cross-module collections and Founder-governed campaigns;
2. `spdb_collection_items` — canonical references that place native objects inside a collection;
3. `spdb_knowledge_links` — typed relationships between native knowledge objects.

These tables are organizational indexes, not replacement content repositories.

## Collections

A collection may organize related native objects across News, Learning, Encyclopedia, Research, Video, Reels, PDF Library, Clinical Cases, Remedy Archive, Disease Archive, Profiles, and Search.

An own-scope collection belongs to the current approved user. An institution-scope collection is Founder-governed. Contributors do not obtain authority over the referenced native objects merely because they are listed in collection metadata.

Archiving a collection or removing an item changes only File 23 metadata. It never deletes, unpublishes, edits, or reclassifies the native object.

## Campaigns

A campaign is institution-scoped and Founder-governed. It requires:

- a clear objective;
- an ethical declaration;
- canonical start and end times in UTC;
- explicit target surfaces;
- current audit reason and idempotency key.

Campaign metadata must not use fear, false urgency, fake scarcity, fabricated metrics, cure guarantees, dark patterns, or misleading medical promises.

## Knowledge Links

A knowledge link connects two distinct native objects through a canonical relation such as:

- Encyclopedia;
- Learning;
- Research;
- Founder Book;
- Doctor Knowledge;
- Clinical Case;
- Remedy Archive;
- Disease Archive;
- Video Series;
- PDF Series;
- Timeline;
- Topic Archive;
- Search Index.

Self-links, malformed provider keys, invalid native identifiers, duplicated canonical relations, and sensitive free text are rejected.

## Authorization

All authority is re-derived server-side from File 00 and current capabilities. Caller-supplied roles, Founder flags, account status, ownership, scope, environment, or provider acceptance are never trusted.

The initial Phase 23F foundation defines schema and validation contracts only. Runtime mutation remains disabled until repository, service, explicit REST routes, object-level revalidation, nonce enforcement, optimistic concurrency, idempotency, audit persistence, source review, corrective re-review, exact-head QA, WordPress staging, and Founder acceptance are complete.

## Privacy and Safety

Free-text metadata rejects markup, URLs, email addresses, Pakistani mobile numbers, CNIC-like identifiers, control characters, and excessive lengths. Patient-identifying or clinical content belongs to its native protected owner and must never enter File 23 collection metadata.

## Failure Semantics

Missing or incompatible providers must produce an unavailable/degraded state. File 23 must never fabricate native titles, counts, destinations, success states, or relationships. A visual collection entry is not proof that the referenced native object still exists; the native owner must be re-resolved before any actionable operation.
