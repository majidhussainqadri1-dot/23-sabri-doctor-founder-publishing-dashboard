# Analytics Aggregate Contract

## Principle

File 23 is not the raw cross-platform event collector. Native providers or an approved analytics service own raw events. File 23 reads privacy-filtered aggregates and may keep bounded summary snapshots for dashboard performance.

## Required Metric Definition

Every metric exposed by an adapter must declare:

- canonical metric key;
- human-readable label;
- exact event/aggregation definition;
- native provider;
- unit;
- deduplication rule;
- bot/internal-traffic filtering rule;
- aggregation interval;
- timezone;
- freshness/latency;
- retention period;
- privacy threshold;
- deletion/erasure behavior;
- late-event handling;
- sampling status;
- supported dimensions;
- schema version.

## Initial Metric Families

- content impressions and views;
- reading or playback completion where genuinely measured;
- saves, shares, reactions, and comments;
- search appearances;
- profile visits;
- phone, WhatsApp, Message, clinic, and appointment-request clicks;
- workflow duration, revisions, corrections, schedule success, and unanswered-interaction age;
- knowledge-link, reference, copyright, and accessibility completeness.

## Privacy Rules

- no patient identity;
- no private message content;
- no appointment or clinical details;
- no raw IP in the dashboard;
- small cohorts suppressed below the configured privacy threshold;
- doctor users see only authorized own-content aggregates;
- Founder/global views remain aggregated and capability-controlled.

## Quality Rule

Analytics indicate reach, interaction, and operational performance; they do not prove medical truth, clinical efficacy, scholarly quality, or treatment success.

## Cache Rule

File 23 snapshot records must contain provider, metric key, object reference, interval, aggregate value, generated time, freshness, privacy status, and schema version. A stale or unavailable provider must be labeled; the dashboard must not fabricate zero or success.
