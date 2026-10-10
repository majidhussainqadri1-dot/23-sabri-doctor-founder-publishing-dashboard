# File 23 source audit — 2026-10-10

Frozen main HEAD: dcae138e6073f4d0ff596623deb05b9940b8271b.

Findings before fixes:
1. File 25 companion CI pin is 19 commits behind current File 25 main (347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e). Latest File 25 CI succeeded and its design-system contract remains 1.4.0.
2. Privacy-thresholded intelligence snapshots expose signals when cohort_count is missing or nonnumeric; require fail-closed suppression.
3. Workflow shell script contains a literal backslash-n between two grep assertions; separate them.
4. Release signoff remains pending and has a stale PR reference; requirements matrix mentions 00–25 rather than 00–26.

Twenty thematic lenses were scoped (identity, shell, publication, composer, assurance, visual, search, FPI, privacy, REST, router, mutation guard, schemas, exports, retention, QA, release). These are not twenty complete independent end-to-end audit/retest rounds.

Staging, deployed version, database, migration and live status remain unverified. No production change is authorized.
