<?php
/**
 * V12.1-STO-09: File 23 personal-data erasure must not delete metadata
 * when encrypted export artifacts cannot be inspected/deleted safely.
 *
 * Isolated source-unit fixture: no real Hostinger/WordPress data.
 */
require_once __DIR__ . '/bootstrap.php';

class SPDB_Erasure_Test_User {
	public int $ID;
	public function __construct( int $id ) { $this->ID = $id; }
}
if ( ! class_exists( 'WP_User' ) ) {
	class WP_User extends SPDB_Erasure_Test_User {}
}
function get_user_by( string $field, string $value ) {
	return 'email' === $field && 'example@test.invalid' === $value ? new WP_User( 7 ) : false;
}
$GLOBALS['spdb_erase_test_root'] = sys_get_temp_dir() . '/spdb-erase-v121-' . bin2hex( random_bytes( 7 ) );
$GLOBALS['spdb_erase_test_upload_fails'] = false;
function wp_upload_dir( $time = null, $create_dir = true ): array {
	return $GLOBALS['spdb_erase_test_upload_fails']
		? array( 'basedir' => '', 'error' => 'storage unavailable' )
		: array( 'basedir' => $GLOBALS['spdb_erase_test_root'], 'error' => false );
}
function wp_mkdir_p( string $path ): bool { return is_dir( $path ) || mkdir( $path, 0700, true ); }
function trailingslashit( string $value ): string { return rtrim( $value, '/\\' ) . '/'; }

class SPDB_Erasure_Test_DB {
	public array $rows = array();
	public function get_var( string $sql ) { return '1'; }
	public bool $fail_query = false;
	public int $reads = 0;
	public bool $endless_full_batches = false;
	public function prepare( string $query, ...$values ): string { return $query; }
	public function get_results( string $query, $output = null ) {
		++$this->reads;
		if ( $this->fail_query ) { return false; }
		if ( $this->endless_full_batches ) {
			$start = ( $this->reads - 1 ) * 200 + 1;
			return array_map( static fn( int $id ): array => array( 'id' => $id, 'storage_ref' => '' ), range( $start, $start + 199 ) );
		}
		return array_map( static function ( array $row ): array {
			$row['export_id'] = '' !== (string) ( $row['storage_ref'] ?? '' ) ? substr( (string) $row['storage_ref'], 0, -5 ) : 'export_' . str_repeat( '0', 32 );
			return $row;
		}, $this->rows );
	}
}
$GLOBALS['wpdb'] = new SPDB_Erasure_Test_DB();

$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$method = new ReflectionMethod( SPDB_Export_Service::class, 'erase_user_files' );
$method->setAccessible( true );
$repository = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$privacy = new SPDB_Privacy_Integration( $repository, $service );
$tests = 0; $failed = 0;
$assert = static function ( bool $pass, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $pass ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$check_error = static function ( $result, string $code ) use ( $assert ): void {
	$assert( is_wp_error( $result ) && $code === $result->get_error_code(), 'Expected error ' . $code );
};
$root = $GLOBALS['spdb_erase_test_root'];
$directory = $root . '/spdb-private-exports';

try {
	$GLOBALS['spdb_erase_test_upload_fails'] = true;
	$check_error( $service->erase_user_files( 7 ), 'spdb_export_erase_storage_unavailable' );
	$privacy_failure = $privacy->erase_personal_data( 'example@test.invalid' );
	$assert( ! empty( $privacy_failure['items_retained'] ) && false === ( $privacy_failure['items_removed'] ?? null ), 'Private file failure must prevent irreversible metadata erase.' );
	$GLOBALS['spdb_erase_test_upload_fails'] = false;

	$GLOBALS['wpdb']->fail_query = true;
	$check_error( $service->erase_user_files( 7 ), 'spdb_export_erase_read_failed' );
	$GLOBALS['wpdb']->fail_query = false;

	$GLOBALS['wpdb']->rows = array( array( 'id' => 1, 'storage_ref' => 'illegal/path/export_' . str_repeat( 'a', 32 ) . '.spdb' ) );
	$check_error( $service->erase_user_files( 7 ), 'spdb_export_erase_reference_invalid' );

	$valid = 'export_' . str_repeat( 'b', 32 ) . '.spdb';
	$GLOBALS['wpdb']->rows = array( array( 'id' => 2, 'storage_ref' => $valid ) );
	if ( ! is_dir( $directory ) ) { mkdir( $directory, 0700, true ); }
	file_put_contents( $directory . '/' . $valid, 'encrypted-fixture' );
	$result = $service->erase_user_files( 7 );
	$assert( 1 === $result && ! file_exists( $directory . '/' . $valid ), 'Valid private ciphertext must be deleted before metadata.' );

	$target = $root . '/outside-test.txt';
	file_put_contents( $target, 'outside' );
	$link = $directory . '/' . $valid;
	if ( function_exists( 'symlink' ) && @symlink( $target, $link ) ) {
		$check_error( $service->erase_user_files( 7 ), 'spdb_export_erase_path_invalid' );
		$assert( file_exists( $target ), 'Symlink target must never be deleted.' );
		unlink( $link );
	}

	$GLOBALS['wpdb']->rows = array( array( 'id' => 3, 'storage_ref' => '' ) );
	$assert( 0 === $service->erase_user_files( 7 ), 'Queued export without ciphertext may erase metadata safely.' );

	$GLOBALS['wpdb']->rows = array( array( 'id' => 3, 'storage_ref' => 'export_' . str_repeat( 'c', 32 ) . '.spdb' ) );
	$assert( 0 === $service->erase_user_files( 7 ), 'Already removed ciphertext is idempotently safe to retry.' );

	$GLOBALS['wpdb']->rows = array( array( 'id' => 3, 'storage_ref' => $valid ), array( 'id' => 2, 'storage_ref' => $valid ) );
	$check_error( $service->erase_user_files( 7 ), 'spdb_export_erase_cursor_invalid' );

	$GLOBALS['wpdb']->rows = array();
	$GLOBALS['wpdb']->endless_full_batches = true;
	$GLOBALS['wpdb']->reads = 0;
	$check_error( $service->erase_user_files( 7 ), 'spdb_export_erase_batch_incomplete' );
	$GLOBALS['wpdb']->endless_full_batches = false;

	$GLOBALS['wpdb']->rows = array();
	$assert( 0 === $service->erase_user_files( 7 ), 'Zero existing private export jobs must return zero deletions.' );
} finally {
	if ( is_dir( $directory ) ) {
		foreach ( scandir( $directory ) ?: array() as $entry ) {
			if ( '.' !== $entry && '..' !== $entry ) { @unlink( $directory . '/' . $entry ); }
		}
		@rmdir( $directory );
	}
	if ( file_exists( $root . '/outside-test.txt' ) ) { unlink( $root . '/outside-test.txt' ); }
	if ( is_dir( $root ) ) { @rmdir( $root ); }
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1-STO-09 erasure tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1-STO-09 erasure tests passed.\n";
