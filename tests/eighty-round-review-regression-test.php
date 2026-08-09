<?php
/** Permanent gate for the completed 2026-08-09 eighty-round review. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $ok, string $message ) use ( &$tests, &$failed ): void {
    ++$tests;
    if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$read = static function ( string $path ) use ( $root ): string { return (string) file_get_contents( $root . '/' . $path ); };
$main = $read( 'sabri-publishing-dashboard.php' );
$audit = $read( 'docs/AUDIT-EIGHTY-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md' );
$matrix = $read( 'docs/RESPONSIBILITY-MATRIX.md' );
$build = $read( 'tools/build-final-release.sh' );
$readme = $read( 'readme.txt' );

$assert( 80 === preg_match_all( '/^\| (?:[1-9]|[1-7][0-9]|80) \|/m', $audit ), 'Eighty-round audit must contain exactly 80 ledger rows.' );
$assert( str_contains( $audit, 'Defect-bearing rounds: **20**' ), 'Audit must retain the exact defect-bearing count.' );
$assert( str_contains( $audit, '**1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 13, 26, 37, 61, 64, 68, 72, 73, 79**' ), 'Audit must retain exact defect-bearing round numbers.' );
$assert( str_contains( $main, "define( 'SPDB_VERSION', '1.2.6' )" ), 'Runtime identity must be Version 1.2.6.' );
$assert( str_contains( $build, 'version="1.2.6"' ), 'Build identity must match Version 1.2.6.' );
$assert( str_contains( $readme, 'Stable tag: 1.2.6' ), 'WordPress stable tag must match Version 1.2.6.' );
$assert( str_contains( $matrix, 'Security, privacy, compliance and resilience assurance' ), 'File 24 security assurance ownership must remain explicit.' );
$assert( str_contains( $matrix, 'Public timeline and visual presentation' ), 'File 25 public visual ownership must remain explicit.' );
$assert( str_contains( $audit, 'File 19 single notification center' ), 'File 19 notification ownership review must remain recorded.' );
$assert( str_contains( $audit, 'File 20 official shell mounting' ), 'File 20 shell ownership review must remain recorded.' );
$assert( str_contains( $audit, 'Legacy diagnostics are read-only' ), 'File 04 legacy non-mutation boundary must remain recorded.' );
$assert( str_contains( $audit, 'current strong-session + export capability recheck' ), 'Export delivery reauthorization must remain recorded.' );
$assert( str_contains( $audit, 'File 00 dependency has a separate engineering audit' ), 'Upstream File 00 production blocker truth must remain explicit.' );
$assert( str_contains( $audit, 'Hostinger staging acceptance' ), 'Source completion must remain distinct from staging acceptance.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} eighty-round review tests failed.\n" ); exit( 1 ); }
echo "All {$tests} eighty-round review tests passed.\n";
