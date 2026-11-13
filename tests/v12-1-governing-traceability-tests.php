<?php
/**
 * V12.1 cross-file acceptance/ownership traceability gate.
 *
 * Validates that File 23 documents all 17 Chapter-12 amendment requirements
 * without claiming live implementation by the canonical domain owners.
 */
$root = dirname( __DIR__ );
$raw = file_get_contents( $root . '/docs/GOVERNING-PLAN-2026-TRACEABILITY.json' );
$plan = is_string( $raw ) ? json_decode( $raw, true ) : null;
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
	}
};
$assert( is_array( $plan ), 'The V12.1 traceability document must be valid JSON.' );
if ( ! is_array( $plan ) ) { exit( 1 ); }
$assert( '2026-08-10' === ( $plan['plan_revision'] ?? null ), 'Original Version 1.3.0 plan baseline must remain accurate.' );
$assert( 70 === ( $plan['requirement_count'] ?? null ), 'Retain the 70 inherited CV requirements.' );
$amendment = $plan['v12_1_amendment'] ?? null;
$assert( is_array( $amendment ), 'V12.1 must have a separate additive amendment block.' );
if ( ! is_array( $amendment ) ) { exit( 1 ); }
$assert( 'unverified' === ( $amendment['runtime_status'] ?? null ), 'Source evidence cannot certify runtime.' );
foreach ( array( 'deployed_version', 'deployed_checksum', 'db_schema_version', 'migration_state' ) as $key ) {
	$assert( 'unverified' === ( $amendment[ $key ] ?? null ), 'Live reality marker must remain unverified: ' . $key );
}
$rows = $amendment['requirements'] ?? null;
$assert( is_array( $rows ) && 17 === count( $rows ), 'All ten STO and seven ACT requirements must be mapped.' );
$rows = is_array( $rows ) ? $rows : array();
$by_id = array();
foreach ( $rows as $row ) {
	if ( ! is_array( $row ) ) { continue; }
	$id = (string) ( $row['id'] ?? '' );
	$assert( ! isset( $by_id[ $id ] ), 'Duplicate requirement ID: ' . $id );
	$by_id[ $id ] = $row;
	$assert( 'pending' === ( $row['staging_acceptance'] ?? null ), $id . ' must not claim staging acceptance.' );
	$assert( 'unverified' === ( $row['live_verification'] ?? null ), $id . ' must not claim live verification.' );
	$assert( '' !== ( $row['canonical_owner'] ?? '' ) && '' !== ( $row['file23_boundary'] ?? '' ), $id . ' needs an owner and a consumer boundary.' );
	foreach ( $row['source_evidence'] ?? array() as $path ) {
		$assert( is_string( $path ) && is_file( $root . '/' . $path ), $id . ' source evidence path missing: ' . (string) $path );
	}
}
for ( $i = 1; $i <= 10; $i++ ) {
	$id = sprintf( 'V12.1-STO-%02d', $i );
	$assert( isset( $by_id[ $id ] ), 'Missing storage requirement ' . $id );
}
for ( $i = 1; $i <= 7; $i++ ) {
	$id = sprintf( 'V12.1-ACT-%02d', $i );
	$assert( isset( $by_id[ $id ] ), 'Missing account requirement ' . $id );
	$assert( ! isset( $by_id[ $id ] ) || false === stripos( (string) $by_id[ $id ]['canonical_owner'], 'File23' ), 'File 23 cannot own File 00/02 account authority: ' . $id );
}
$guard = file_get_contents( $root . '/includes/class-spdb-export-service.php' );
$assert( is_string( $guard ) && false !== strpos( $guard, 'spdb_export_storage_guard_invalid' ), 'File 23 must fail closed on tampered private export guards.' );
$assert( is_string( $guard ) && false !== strpos( $guard, 'hash_equals( $content, $actual )' ), 'Private export guards must be content-checked on every use.' );
if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} V12.1 mapping tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} V12.1 mapping and safety-boundary tests passed.\n";
