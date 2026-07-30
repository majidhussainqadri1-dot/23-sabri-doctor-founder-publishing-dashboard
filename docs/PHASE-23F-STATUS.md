# Phase 23F Status

## Current State

- Phase: 23F — Collections, Campaigns, and Knowledge Links
- Branch: `phase/23f-collections-knowledge`
- Parent head: corrected Phase 23E commit `196b2b58e2b5b1da734fd8b19797058891151756`
- Nature: stacked Draft candidate
- Production readiness: not ready
- Staging readiness: not ready
- Merge readiness: blocked

## Foundation Implemented

- [x] File 23-owned repository contract for collection/campaign/knowledge-link metadata
- [x] Narrow WordPress schema for three metadata tables
- [x] Explicit ownership boundary against native content duplication
- [x] Collection type, scope, status, contributor, target-surface, timestamp, idempotency, and audit validation
- [x] Founder-only institution scope
- [x] Founder-governed campaigns
- [x] Campaign anti-fear, anti-false-urgency, anti-fake-scarcity, anti-fabricated-metric, and anti-cure-guarantee policy
- [x] Canonical cross-module knowledge relation allowlist
- [x] Self-link rejection
- [x] Sensitive-text rejection
- [x] Executable policy test candidate
- [x] Architecture and review-gate documentation

## Not Yet Implemented

- [ ] WordPress repository implementation
- [ ] Collection/campaign/knowledge service layer
- [ ] Explicit REST read and mutation routes
- [ ] Nonce and capability callbacks
- [ ] Native object re-resolution
- [ ] Object-level authorization and IDOR defenses
- [ ] Optimistic concurrency
- [ ] Idempotent write persistence
- [ ] Audit persistence
- [ ] Dashboard projection and accessible UI
- [ ] Provider outage and stale-reference handling
- [ ] Migration rollback
- [ ] Independent source review and defect correction
- [ ] Corrective re-review
- [ ] Exact-head PHP 8.0–8.3 QA
- [ ] WordPress staging and real-provider acceptance
- [ ] Founder review completed
- [ ] Founder acceptance recorded

## Governing Restriction

No production mutation is enabled by this foundation. No phase or pull request may be merged before the complete gate in `docs/PHASE-23F-REVIEW-GATE.md` passes and merge is explicitly authorized.
