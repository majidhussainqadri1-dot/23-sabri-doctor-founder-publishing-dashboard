<?php
/**
 * Executable final-deliverables and release-tooling gate for File 23.
 */

$root   = dirname( __DIR__ );
$tests  = 0;
$failed = 0;

$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
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
	'docs/GOVERNING-PLAN-2026-TRACEABILITY.json'                   => array( 'F23-CEN-01', 'CV-050', 'CV-285', 'AJ-40', '1.3.0' ),
	'docs/PRIVACY-RETENTION-AND-LOCAL-REPAIR.md'                   => array( 'privacy', 'retention', 'repair' ),
	'docs/BACKGROUND-JOBS-AND-EXPORTS.md'                           => array( 'dead-letter', 'export', 'idempotency' ),
	'docs/STAGING-BACKUP-RESTORE-MIGRATION-ROLLBACK-RUNBOOK.md'    => array( 'staging', 'backup', 'rollback' ),
	'docs/STAGING-CHECKLIST.md'                                    => array( 'Founder', '10,000', 'LiteSpeed' ),
	'docs/FOUNDER-MANUAL.md'                                       => array( 'Founder', 'File 22', 'staging acceptance' ),
	'docs/DOCTOR-MANUAL.md'                                        => array( 'Doctor', 'submits for review', 'idempotency' ),
	'docs/ADMIN-MANUAL.md'                                         => array( 'Administrator', 'Hostinger staging', 'rollback' ),
	'docs/TEST-REPORT.md'                                          => array( 'Automated source gates', 'Hostinger staging', 'Defect policy' ),
	'CHANGELOG.md'                                                  => array( '1.3.0', 'File 26', '#087A4E' ),
	'docs/KNOWN-LIMITATIONS.md'                                    => array( 'Residual-risk', 'LiteSpeed', 'F23-LIM-010' ),
	'docs/RELEASE-SIGNOFF.md'                                      => array( 'Exact Git head', 'PENDING', 'Founder' ),
	'docs/AUDIT-40-ROUND-REVIEW-AND-CORRECTIONS-2026-08-04.md'     => array( 'چالیس ادوار', 'دور 40', 'Hostinger staging' ),
);

foreach ( $deliverables as $relative => $markers ) {
	$path = $root . '/' . $relative;
	$assert( is_file( $path ), "Required deliverable missing: {$relative}." );
	if ( ! is_file( $path ) ) {
		continue;
	}
	$content = (string) file_get_contents( $path );
	$assert( strlen( $content ) >= 120, "Required deliverable is unexpectedly empty: {$relative}." );
	foreach ( $markers as $marker ) {
		$assert( false !== stripos( $content, $marker ), "{$relative} is missing marker: {$marker}." );
	}
}

$build = (string) file_get_contents( $root . '/tools/build-final-release.sh' );
foreach ( array(
	'version="1.3.0"',
	'23-sabri-doctor-founder-publishing-dashboard-${version}.zip',
	'23-Doctor-Founder-Publishing-Dashboard-Source-',
	'FILE23-${version}-SOURCE-MANIFEST.sha256',
	'git archive',
	'sha256sum "$source_package"',
) as $marker ) {
	$assert( false !== strpos( $build, $marker ), "Release tooling is missing marker: {$marker}." );
}

$workflow = (string) file_get_contents( $root . '/.github/workflows/file23-final-release-candidate.yml' );
foreach ( array(
	'php tests/governing-plan-2026-tests.php',
	'php tests/final-deliverables-tests.php',
	'php tests/forty-round-review-gate-tests.php',
	'23-Doctor-Founder-Publishing-Dashboard-Source-1.3.0.zip',
	'FILE23-1.3.0-SOURCE-MANIFEST.sha256',
	'docs/GOVERNING-PLAN-2026-TRACEABILITY.json',
	'docs/RELEASE-SIGNOFF.md',
	'docs/AUDIT-40-ROUND-REVIEW-AND-CORRECTIONS-2026-08-04.md',
) as $marker ) {
	$assert( false !== strpos( $workflow, $marker ), "Final release workflow is missing marker: {$marker}." );
}

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} final-deliverables tests failed.\n" );
	exit( 1 );
}

echo "All {$tests} File 23 final-deliverables tests passed.\n";
