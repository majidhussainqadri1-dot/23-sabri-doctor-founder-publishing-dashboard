<?php
/**
 * V12.1: schema version + ten table names/InnoDB alone does NOT prove a
 * complete operations migration. All required columns and indexes must be
 * measured from MySQL on acceptance, without repairing automatically.
 */
require_once __DIR__ . '/bootstrap.php';
$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $msg ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$msg}\n" ); }
};
$assert( method_exists( SPDB_Operations_Schema::class, 'requirements' ), 'Operational schema structural manifest is required.' );
if ( ! method_exists( SPDB_Operations_Schema::class, 'requirements' ) ) { exit( 1 ); }
// Derive the manifest under the same synthetic WordPress table prefix
// that the fake database uses; otherwise the fixture tests wrong names.
$GLOBALS['wpdb'] = (object) array( 'prefix' => 'wp_test_' );
$manifest = SPDB_Operations_Schema::requirements();

class SPDB_Operations_Shape_Test_DB {
	public string $prefix = 'wp_test_';
	public array $manifest;
	public string $query_table = '';
	public string $missing_column = '';
	public string $missing_index = '';
	public string $missing_table = '';
	public string $bad_engine = '';
	public bool $fail_describe = false;
	public function __construct( array $manifest ) { $this->manifest = $manifest; }
	public function prepare( string $sql, ...$args ): string {
		$this->query_table = (string) ( $args[0] ?? '' );
		return $sql;
	}
	public function esc_like( string $value ): string { return $value; }
	public function get_var( string $sql ) {
		return $this->query_table === $this->missing_table ? null : $this->query_table;
	}
	public function get_row( string $sql, $output = null ): array {
		return array( 'Engine' => $this->query_table === $this->bad_engine ? 'MyISAM' : 'InnoDB' );
	}
	public function get_results( string $sql, $output = null ) {
		if ( $this->fail_describe ) { return false; }
		preg_match( '/SHOW (?:COLUMNS|INDEX(?:ES)?) FROM `([A-Za-z0-9_]+)`/i', $sql, $match );
		$table = (string) ( $match[1] ?? '' );
		$details = $this->manifest[ $table ] ?? null;
		if ( ! is_array( $details ) ) { return false; }
		if ( stripos( $sql, 'SHOW COLUMNS FROM' ) === 0 ) {
			$columns = array_values( array_filter( $details['columns'], fn( $column ) => $column !== $this->missing_column ) );
			return array_map( fn( $name ) => array( 'Field' => $name ), $columns );
		}
		$indexes = array_values( array_filter( $details['indexes'], fn( $index ) => $index !== $this->missing_index ) );
		return array_map( fn( $name ) => array( 'Key_name' => $name ), $indexes );
	}
}
$GLOBALS['wpdb'] = new SPDB_Operations_Shape_Test_DB( $manifest );
$assert( count( $manifest ) === 10, 'Exactly ten File23-owned operational schema tables must be structurally verified.' );

// The manifest must match the current CREATE TABLE DDL (not just itself).
$ddl = (string) file_get_contents( __DIR__ . '/../includes/class-spdb-operations-schema.php' );
preg_match_all( '/CREATE TABLE \\{\\$tables\\[\x27([^\x27]+)\x27\\]\\} \\(([\\s\\S]*?)\\) ENGINE=InnoDB/', $ddl, $matches, PREG_SET_ORDER );
$assert( count( $matches ) === 10, 'Current schema source still declares ten CREATE TABLE statements.' );
foreach ( $matches as $match ) {
	$key = $match[1];
	$table = 'wp_test_spdb_' . $key;
	$spec = $manifest[ $table ] ?? null;
	$assert( is_array( $spec ), "Manifest includes table {$key}." );
	if ( ! is_array( $spec ) ) { continue; }
	$columns = array(); $indexes = array();
	foreach ( explode( "\n", $match[2] ) as $line ) {
		$line = trim( $line );
		if ( preg_match( '/^PRIMARY KEY/', $line ) ) { $indexes[] = 'PRIMARY'; }
		elseif ( preg_match( '/^(?:UNIQUE )?KEY ([A-Za-z0-9_]+)/', $line, $names ) ) { $indexes[] = $names[1]; }
		elseif ( preg_match( '/^([A-Za-z0-9_]+) (?:bigint|varchar|longtext|text|datetime|int|char|double|decimal|float|tinyint|mediumint|timestamp)/i', $line, $names ) ) {
			$columns[] = $names[1];
		}
	}
	$assert( $spec['columns'] === $columns, "Every column in {$key} is covered exactly as declared in native install DDL." );
	$assert( $spec['indexes'] === $indexes, "Every index in {$key} is covered exactly as declared in native install DDL." );
}
$check_code = static function ( $actual, string $expected ) use ( $assert ): void {
	$assert( is_wp_error( $actual ) && $actual->get_error_code() === $expected, 'Expected structural failure ' . $expected );
};
$assert( true === SPDB_Operations_Schema::verify(), 'Complete ten-table mock schema verifies.' );
$GLOBALS['wpdb']->missing_column = 'storage_ref';
$check_code( SPDB_Operations_Schema::verify(), 'spdb_operations_schema_column_missing' );
$GLOBALS['wpdb']->missing_column = '';
$GLOBALS['wpdb']->missing_index = 'expires_at_gmt';
$check_code( SPDB_Operations_Schema::verify(), 'spdb_operations_schema_index_missing' );
$GLOBALS['wpdb']->missing_index = '';
$GLOBALS['wpdb']->fail_describe = true;
$check_code( SPDB_Operations_Schema::verify(), 'spdb_operations_schema_inspection_failed' );
$GLOBALS['wpdb']->fail_describe = false;
$GLOBALS['wpdb']->bad_engine = 'wp_test_spdb_export_jobs';
$check_code( SPDB_Operations_Schema::verify(), 'spdb_operations_table_not_transactional' );
$GLOBALS['wpdb']->bad_engine = '';
$GLOBALS['wpdb']->missing_table = 'wp_test_spdb_export_jobs';
$check_code( SPDB_Operations_Schema::verify(), 'spdb_operations_table_missing' );
// A same-version partial migration is drift, not permission to run an
// automatic invasive dbDelta repair on an unrelated admin_init request.
$GLOBALS['spdb_test_options']['spdb_operations_schema_version'] = SPDB_Operations_Schema::VERSION;
$GLOBALS['wpdb']->missing_table = '';
$GLOBALS['wpdb']->missing_column = 'storage_ref';
$check_code( SPDB_Operations_Schema::maybe_upgrade(), 'spdb_operations_schema_drift' );
$GLOBALS['wpdb']->missing_column = '';
$assert( true === SPDB_Operations_Schema::maybe_upgrade(), 'Same-version verified schema needs no automatic migration.' );
unset( $GLOBALS['spdb_test_options']['spdb_operations_schema_version'] );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 structural schema tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 structural schema tests passed.\n";
