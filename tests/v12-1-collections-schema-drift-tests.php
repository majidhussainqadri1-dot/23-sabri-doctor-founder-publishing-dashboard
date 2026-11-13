<?php
/**
 * V12.1 WP-12: same-version File23 Collections schema drift must NOT
 * automatically invoke dbDelta/ALTER during an incidental admin_init
 * request. Preserve exact deployed database evidence for operator review.
 */
require_once __DIR__ . '/bootstrap.php';

class SPDB_Collections_Drift_DB {
	public string $prefix = 'wp_test_';
	public int $queries = 0;
	public function prepare( string $query, ...$args ): string { return $query; }
	public function esc_like( string $name ): string { return $name; }
	public function get_var( string $query ) { return null; } // Missing table is a drift.
	public function get_results( string $query, $type = null ) { return array(); }
	public function get_row( string $query, $type = null ) { return array( 'Engine' => 'InnoDB' ); }
	public function query( string $query ) { ++$this->queries; return 1; }
}
$GLOBALS['wpdb'] = new SPDB_Collections_Drift_DB();
$GLOBALS['spdb_test_options']['spdb_collections_schema_version'] = SPDB_Collections_Schema::VERSION;
$tests = 0; $failures = 0;
$assert = static function ( bool $ok, string $label ) use ( &$tests, &$failures ): void {
	++$tests; if ( ! $ok ) { ++$failures; fwrite( STDERR, "FAIL: {$label}\n" ); }
};
$original = SPDB_Collections_Schema::verify();
$assert( is_wp_error( $original ) && 'spdb_collections_schema_table_missing' === $original->get_error_code(), 'Missing table is provably reported by collections verify.' );
$drift = SPDB_Collections_Schema::maybe_upgrade();
$assert( is_wp_error( $drift ) && 'spdb_collections_schema_drift' === $drift->get_error_code(), 'Recorded current version plus failed structural check blocks automatic migration.' );
$assert( 0 === $GLOBALS['wpdb']->queries, 'No automatic schema write occurred when current version has drift.' );
$assert( SPDB_Collections_Schema::VERSION === get_option( 'spdb_collections_schema_version' ), 'Version option retained for forensics.' );

if ( $failures ) { fwrite( STDERR, "{$failures} of {$tests} V12.1 collections schema drift tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 collections schema drift tests passed.\n";
