<?php
/**
 * V12.1 WP-12 / STO-09: export-job metadata may never be purged by a
 * direct/default retention call without a previously verified cutoff and ID.
 * This is a source-only regression, not proof of installed Hostinger behavior.
 */
require_once __DIR__ . '/bootstrap.php';

$tests = 0; $failures = 0;
$assert = static function ( bool $ok, string $msg ) use ( &$tests, &$failures ): void {
	++$tests;
	if ( ! $ok ) {
		++$failures;
		fwrite( STDERR, "FAIL: {$msg}\n" );
	}
};

$repository = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$settings = array( 'failed_job_retention_days' => 30, 'task_retention_days' => 365 );

$invalid = array(
	array( 'args' => array( $settings ), 'label' => 'no frozen cutoff and no row fence' ),
	array( 'args' => array( $settings, '2026-01-01 00:00:00' ), 'label' => 'cutoff without a frozen ID fence' ),
	array( 'args' => array( $settings, null, 100 ), 'label' => 'row fence without a cutoff' ),
	array( 'args' => array( $settings, '2026-01-01 00:00:00', -1 ), 'label' => 'negative row fence' ),
	array( 'args' => array( $settings, 'invalid', 100 ), 'label' => 'malformed cutoff' ),
	array( 'args' => array( $settings, gmdate( 'Y-m-d H:i:s', time() + 86400 ), 100 ), 'label' => 'future cutoff' ),
);
foreach ( $invalid as $test ) {
	$result = $repository->cleanup_retention( ...$test['args'] );
	$assert(
		is_wp_error( $result ) && in_array( $result->get_error_code(), array( 'spdb_retention_proof_required', 'spdb_retention_max_id_invalid', 'spdb_retention_cutoff_invalid' ), true ),
		'Retention must fail closed: ' . $test['label']
	);
}
$source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-operations-repository.php' );
$assert( false === strpos( $source, "'exports' => null === \$export_max_id" ), 'No unrestricted expired export row DELETE branch remains.' );
$assert( false !== strpos( $source, 'AND id <= %d' ), 'Export metadata DELETE requires the verified ID upper fence.' );
$assert( false !== strpos( $source, 'spdb_retention_proof_required' ), 'Missing retention proof returns explicit typed error.' );

if ( $failures ) { fwrite( STDERR, "{$failures} of {$tests} V12.1 retention-boundary tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 retention-boundary tests passed.\n";
