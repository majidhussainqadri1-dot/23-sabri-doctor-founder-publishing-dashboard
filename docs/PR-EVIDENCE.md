# Draft PR #1 Corrective Evidence Pointer

The authoritative exact-head evidence is maintained in the GitHub Draft PR #1 description because updating a tracked evidence file after a workflow run would create a new branch head and invalidate the SHA it records.

The PR description must record:

- the current PR head SHA;
- the Baseline Integrity workflow run number;
- PHP 8.0, 8.1, 8.2, and 8.3 success;
- PHP syntax-check success;
- executable contract-test success;
- architecture-boundary-guard success;
- version and review-gate success;
- explicit Draft and unmerged status;
- Founder review and acceptance status.

No merge may rely on evidence from an earlier head. Any later branch commit requires review of the affected scope and a complete current-head workflow rerun.

Current authoritative evidence is intentionally not duplicated in this tracked file; see Draft PR #1 description.
