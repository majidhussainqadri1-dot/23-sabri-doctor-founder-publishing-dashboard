<?php
/** Executable read-only File 04 migration evidence tests. */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['spdb_filters'] = array();
function apply_filters( string $tag, $value ) { return $GLOBALS['spdb_filters'][ $tag ] ?? $value; }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ) ?? ''; }
function sanitize_text_field( string $value ): string { return trim( strip_tags( $value ) ); }
require_once dirname( __DIR__ ) . '/includes/class-spdb-legacy-migration-diagnostics.php';
$tests = 0; $failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void { ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } };
$empty = SPDB_Legacy_Migration_Diagnostics::snapshot();
$assert( false === $empty['available'] && false === $empty['mutation_supported'], 'Absent legacy provider must remain unavailable and read-only.' );
$GLOBALS['spdb_filters']['spdb/file04_migration_inventory'] = array( 'total_records' => 10, 'eligible_candidates' => 8 );
$GLOBALS['spdb_filters']['spdb/file04_migration_mapping'] = array( 'migrated_records' => 8, 'mapped_records' => 8, 'duplicate_records' => 0, 'orphaned_records' => 0, 'failed_records' => 0 );
$GLOBALS['spdb_filters']['spdb/file04_migration_state'] = array( 'dry_run_completed' => true, 'legacy_write_state' => 'disabled', 'rollback_evidence_id' => 'rollback-1', 'reconciliation_evidence_id' => 'reconcile-1', 'provider_version' => '1.0.0', 'last_checked_at_gmt' => '2026-08-04T00:00:00Z' );
$ready = SPDB_Legacy_Migration_Diagnostics::snapshot();
$assert( true === $ready['cutover_ready'] && 'file21' === $ready['canonical_owner'], 'Complete evidence must report cutover readiness without changing ownership.' );
$GLOBALS['spdb_filters']['spdb/file04_migration_mapping']['duplicate_records'] = 1;
$blocked = SPDB_Legacy_Migration_Diagnostics::snapshot();
$assert( false === $blocked['cutover_ready'] && 'evidence_incomplete' === $blocked['code'], 'Duplicate legacy mappings must block cutover readiness.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} legacy diagnostics tests failed.\n" ); exit( 1 ); }
echo "All {$tests} legacy migration diagnostics tests passed.\n";
