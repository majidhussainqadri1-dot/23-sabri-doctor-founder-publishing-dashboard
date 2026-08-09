<?php
/** Executable final-deliverables and release-tooling gate for File 23. */
$root   = dirname( __DIR__ );
$tests  = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

$deliverables = array(
	'docs/SOURCE-MANIFEST.md'                                      => array( 'complete-source', 'SHA-256', 'git archive' ),
	'docs/DATABASE-SCHEMA-MANIFEST.md'                             => array( 'spdb_preferences', 'spdb_collections', 'spdb_dashboard_audit' ),
	'docs/CAPABILITY-MATRIX.md'                                    => array( 'spdb_view_dashboard', 'spdb_manage_tasks', 'File 00' ),
	'docs/ADAPTER-CONTRACT.md'                                     => array( 'Versioned Provider Adapter Contract', 'production_accepted', 'idempotency-key requirement' ),
	'docs/OPERATIONS-API.md'                                       => array( 'REST', 'nonce', 'idempotency' ),
	'docs/DATA-OWNERSHIP-MATRIX.md'                                => array( 'File 21', 'File 22', 'File 25' ),
	'docs/THREAT-MODEL.md'                                         => array( 'CSRF', 'IDOR', 'Audit bypass' ),
	'docs/REQUIREMENTS-TRACEABILITY-MATRIX.md'                     => array( 'F23-R001', 'F23-R035', 'Release-state rule' ),
	'docs/PRIVACY-RETENTION-AND-LOCAL-REPAIR.md'                   => array( 'privacy', 'retention', 'repair' ),
	'docs/BACKGROUND-JOBS-AND-EXPORTS.md'                           => array( 'dead-letter', 'export', 'idempotency' ),
	'docs/STAGING-BACKUP-RESTORE-MIGRATION-ROLLBACK-RUNBOOK.md'    => array( 'staging', 'backup', 'rollback' ),
	'docs/STAGING-CHECKLIST.md'                                    => array( 'Founder', '10,000', 'LiteSpeed' ),
	'docs/FOUNDER-MANUAL.md'                                       => array( 'Founder', 'File 22', 'staging acceptance' ),
	'docs/DOCTOR-MANUAL.md'                                        => array( 'Doctor', 'submits for review', 'idempotency' ),
	'docs/ADMIN-MANUAL.md'                                         => array( 'Administrator', 'Hostinger staging', 'rollback' ),
	'docs/TEST-REPORT.md'                                          => array( 'Automated source gates', 'Hostinger staging', 'Defect policy' ),
	'CHANGELOG.md'                                                  => array( '1.2.7', 'second fresh eighty-round' ),
	'docs/KNOWN-LIMITATIONS.md'                                    => array( 'Residual-Risk', 'LiteSpeed', 'F23-LIM-011' ),
	'docs/RELEASE-SIGNOFF.md'                                      => array( 'Exact Git head', 'PENDING', 'Founder', '1.2.7' ),
	'docs/AUDIT-40-ROUND-REVIEW-AND-CORRECTIONS-2026-08-04.md'     => array( 'چالیس ادوار', 'دور 40', 'Hostinger staging' ),
	'docs/AUDIT-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-05.md'     => array( 'Round 10', 'Version 1.2.2', 'Hostinger staging' ),
	'docs/AUDIT-SECOND-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-08.md' => array( 'Rounds with defects', '1, 2, 5, 6, 9, 10', 'Hostinger staging' ),
	'docs/AUDIT-THIRD-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-08.md' => array( 'Rounds with defects', '1, 2, 3, 4, 9, 10', 'Hostinger' ),
	'docs/AUDIT-FOURTH-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md' => array( 'Rounds with defects', '1, 2, 3, 4, 5, 6, 7, 8, 9, 10', 'File 00' ),
	'docs/AUDIT-EIGHTY-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md' => array( '80 of 80', 'Defect-bearing rounds: **20**', 'Version 1.2.6' ),
	'docs/AUDIT-SECOND-EIGHTY-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md' => array( '80 of 80', 'Defect-bearing rounds: **12**', 'Version 1.2.7' ),
);

foreach ( $deliverables as $relative => $markers ) {
	$path = $root . '/' . $relative;
	$assert( is_file( $path ), "Required deliverable missing: {$relative}." );
	if ( ! is_file( $path ) ) { continue; }
	$content = (string) file_get_contents( $path );
	$assert( strlen( $content ) >= 120, "Required deliverable is unexpectedly empty: {$relative}." );
	foreach ( $markers as $marker ) {
		$assert( false !== stripos( $content, $marker ), "{$relative} is missing marker: {$marker}." );
	}
}

$main = (string) file_get_contents( $root . '/sabri-publishing-dashboard.php' );
$current = '';
if ( preg_match( "/define\( 'SPDB_VERSION', '([^']+)' \)/", $main, $match ) ) { $current = (string) $match[1]; }
$assert( '1.2.7' === $current, 'Current second-eighty-round closure runtime must be Version 1.2.7.' );
$readme = (string) file_get_contents( $root . '/readme.txt' );
$assert( str_contains( $readme, 'Stable tag: ' . $current ), 'Stable tag must match current runtime.' );
$build = (string) file_get_contents( $root . '/tools/build-final-release.sh' );
foreach ( array(
	'23-sabri-doctor-founder-publishing-dashboard-${version}.zip',
	'23-Doctor-Founder-Publishing-Dashboard-Source-',
	'FILE23-${version}-SOURCE-MANIFEST.sha256',
	'git archive',
	'sha256sum "$source_package"',
	'version="1.2.7"',
) as $marker ) {
	$assert( false !== strpos( $build, $marker ), "Release tooling is missing marker: {$marker}." );
}

$workflow = (string) file_get_contents( $root . '/.github/workflows/file23-final-release-candidate.yml' );
foreach ( array(
	'SPDB_RELEASE_VERSION: 1.2.7',
	'FILE21_SHA: 449b5faef8622ae0866a7faa6f4144cde5451d1b',
	'php tests/final-deliverables-tests.php',
	'php tests/forty-round-review-gate-tests.php',
	'php tests/ten-round-corrective-review-tests.php',
	'php tests/second-ten-round-review-tests.php',
	'php tests/third-ten-round-review-tests.php',
	'php tests/fourth-ten-round-review-tests.php',
	'php tests/eighty-round-review-regression-test.php',
	'php tests/second-eighty-round-review-regression-test.php',
	'23-Doctor-Founder-Publishing-Dashboard-Source-1.2.7.zip',
	'FILE23-1.2.7-SOURCE-MANIFEST.sha256',
	'docs/RELEASE-SIGNOFF.md',
	'docs/AUDIT-40-ROUND-REVIEW-AND-CORRECTIONS-2026-08-04.md',
	'docs/AUDIT-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-05.md',
	'docs/AUDIT-SECOND-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-08.md',
	'docs/AUDIT-THIRD-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-08.md',
	'docs/AUDIT-FOURTH-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md',
	'docs/AUDIT-EIGHTY-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md',
	'docs/AUDIT-SECOND-EIGHTY-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md',
) as $marker ) {
	$assert( false !== strpos( $workflow, $marker ), "Final release workflow is missing marker: {$marker}." );
}

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} final-deliverables tests failed.\n" ); exit( 1 ); }
echo "All {$tests} File 23 final-deliverables tests passed.\n";
