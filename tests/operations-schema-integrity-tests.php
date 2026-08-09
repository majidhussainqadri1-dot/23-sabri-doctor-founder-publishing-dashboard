<?php
/** Static regression checks for complete File 23 operational schema verification. */
$root = dirname( __DIR__ );
$source = (string) file_get_contents( $root . '/includes/class-spdb-operations-schema-integrity.php' );
$bootstrap = (string) file_get_contents( $root . '/sabri-publishing-dashboard.php' );
$tests = 0; $failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void { ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } };
foreach ( array( 'SHOW COLUMNS FROM', 'SHOW INDEX FROM', 'information_schema.TABLES', 'spdb_operations_integrity_column_missing', 'spdb_operations_integrity_index_missing', 'rest_pre_dispatch' ) as $marker ) {
	$assert( false !== strpos( $source, $marker ), "Operational schema integrity verifier is missing {$marker}." );
}
foreach ( array( "'preferences'", "'saved_views'", "'tasks'", "'delegations'", "'automation_rules'", "'metric_snapshots'", "'export_jobs'", "'adapter_health'", "'background_jobs'", "'dashboard_audit'" ) as $suffix ) {
	$assert( false !== strpos( $source, $suffix ), "Operational schema integrity requirements are missing {$suffix}." );
}
foreach ( array( 'class-spdb-operations-schema-integrity.php', 'SPDB_Operations_Schema_Integrity::register()', "array( 'SPDB_Operations_Schema_Integrity', 'activate' )" ) as $marker ) {
	$assert( false !== strpos( $bootstrap, $marker ), "Plugin bootstrap is missing operational integrity wiring {$marker}." );
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} operations schema integrity tests failed.\n" ); exit( 1 ); }
echo "All {$tests} operations schema integrity tests passed.\n";
