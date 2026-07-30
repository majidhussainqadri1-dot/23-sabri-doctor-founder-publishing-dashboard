# Phase 23B Review and Merge Gate

## Governing Rule

**DO NOT MERGE before review is complete.**

No File 23 branch or pull request may be merged merely because code exists, syntax passes, or automated checks are green. The complete affected scope must be reviewed, all discovered defects must be corrected, corrections must be re-reviewed, and the current exact head must pass the complete applicable QA suite.

## Required Review Scope

- protected-route authentication and authorization;
- File 00 status and capability handling;
- Founder, trusted-doctor, doctor, restricted, denied, and dependency-failure workspaces;
- private response headers and cache/index exclusion;
- route activation and upgrade rewrite behavior;
- truthful overview and no fabricated metrics;
- adapter failure isolation;
- saved-view REST permissions, bounds, sanitization, storage, deletion, and read-time revalidation;
- patient-data exclusion;
- output escaping and XSS resistance;
- responsive behavior at 320–1920 pixels;
- keyboard, focus, semantics, reduced motion, and screen-reader status behavior;
- JavaScript failure and weak-connection behavior;
- PHP 8.0–8.3 compatibility;
- WordPress staging activation, deactivation, route loading, and cache purge;
- no duplicate native data ownership.

## Exact-Head Evidence

The pull request description must record the final reviewed commit SHA and the successful workflow run that tested that exact SHA. A later commit invalidates earlier evidence for the affected scope.

## Required Acceptance State

- [ ] Independent technical review complete
- [ ] Defects documented
- [ ] All defects corrected
- [ ] Corrective re-review complete
- [ ] Exact-head automated QA green
- [ ] Staging route acceptance complete
- [ ] Founder review complete
- [ ] Founder acceptance recorded
- [ ] Pull request ready for merge

Until every applicable item is complete, the pull request remains Draft and unmerged.
