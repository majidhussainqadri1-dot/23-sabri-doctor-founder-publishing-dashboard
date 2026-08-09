<?php
/** Permanent gate for the second fresh 2026-08-09 eighty-round File 23 review. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $ok, string $message ) use ( &$tests, &$failed ): void {
    ++$tests;
    if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$read = static function ( string $path ) use ( $root ): string { return (string) file_get_contents( $root . '/' . $path ); };
$main = $read( 'sabri-publishing-dashboard.php' );
$audit = $read( 'docs/AUDIT-SECOND-EIGHTY-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md' );
$baseline = $read( '.github/workflows/baseline-integrity.yml' );
$full = $read( '.github/workflows/file23-full-plan-completion.yml' );
$final = $read( '.github/workflows/file23-final-release-candidate.yml' );
$harm = $read( '.github/workflows/file23-three-plan-harmonization.yml' );
$build = $read( 'tools/build-final-release.sh' );
$readme = $read( 'readme.txt' );
$status = $read( 'STATUS.md' );
$signoff = $read( 'docs/RELEASE-SIGNOFF.md' );

$assert( 80 === preg_match_all( '/^\| (?:[1-9]|[1-7][0-9]|80) \|/m', $audit ), 'Second eighty-round audit must contain exactly 80 ledger rows.' );
$assert( str_contains( $audit, 'Defect-bearing rounds: **10**' ), 'Second audit must retain exact defect-bearing count.' );
$assert( str_contains( $audit, '**1, 2, 3, 4, 5, 6, 7, 8, 9, 10**' ), 'Second audit must retain exact defect-bearing rounds.' );
$assert( str_contains( $main, "define( 'SPDB_VERSION', '1.2.7' )" ), 'Runtime identity must be Version 1.2.7.' );
$assert( str_contains( $readme, 'Stable tag: 1.2.7' ), 'Stable tag must be Version 1.2.7.' );
$assert( str_contains( $build, 'version="1.2.7"' ), 'Deterministic build must target Version 1.2.7.' );
$assert( str_contains( $status, 'Current corrected candidate: `1.2.7`' ), 'STATUS must report the current 1.2.7 candidate.' );
$assert( str_contains( $signoff, 'Current candidate version: `1.2.7`' ), 'Release sign-off must target 1.2.7.' );
$assert( str_contains( $baseline, 'second-eighty-round-review-regression-test.php' ), 'Baseline workflow must enforce the second eighty-round gate.' );
$assert( ! str_contains( $baseline, 'Version:     1.2.5' ) && ! str_contains( $baseline, "SPDB_VERSION', '1.2.5" ), 'Baseline workflow must not freeze Version 1.2.5.' );
$assert( str_contains( $full, 'FILE21_SHA: 449b5faef8622ae0866a7faa6f4144cde5451d1b' ), 'Full Plan must pin the reachable reviewed File 21 merged head.' );
$assert( str_contains( $full, 'SPDB_RELEASE_VERSION: 1.2.7' ), 'Full Plan release identity must be 1.2.7.' );
$assert( str_contains( $final, 'FILE21_SHA: 449b5faef8622ae0866a7faa6f4144cde5451d1b' ), 'Final release must pin the reachable reviewed File 21 merged head.' );
$assert( str_contains( $final, 'SPDB_RELEASE_VERSION: 1.2.7' ), 'Final release identity must be 1.2.7.' );
$assert( str_contains( $final, 'second-eighty-round-review-regression-test.php' ), 'Final release must run the second eighty-round gate.' );
$assert( str_contains( $final, 'AUDIT-SECOND-EIGHTY-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md' ), 'Final release must retain the second eighty-round audit artifact.' );
$assert( str_contains( $harm, 'second-eighty-round-review-regression-test.php' ), 'Harmonization workflow must run the second eighty-round gate.' );
$assert( str_contains( $audit, 'File 00 production blocker remains visible' ) || str_contains( $audit, 'File 00 dependency remains separately production-blocking' ), 'Second audit must preserve the File 00 production blocker truth.' );
$assert( str_contains( $audit, 'Hostinger staging acceptance' ), 'Second audit must keep staging distinct from source/CI closure.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} second eighty-round review tests failed.\n" ); exit( 1 ); }
echo "All {$tests} second eighty-round review tests passed.\n";
