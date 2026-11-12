<?php
/**
 * V12.1-STO-09 bounded expired export retention:
 * no symlink target deletion and no metadata-cleanup success when truncated.
 */
require_once __DIR__ . '/bootstrap.php';

$GLOBALS['spdb_expired_test_root'] = sys_get_temp_dir() . '/spdb-expired-v121-' . bin2hex( random_bytes( 7 ) );
function wp_upload_dir( $time = null, $create_dir = true ): array { return array( 'basedir' => $GLOBALS['spdb_expired_test_root'], 'error' => false ); }
function wp_mkdir_p( string $path ): bool { return is_dir( $path ) || mkdir( $path, 0700, true ); }
function trailingslashit( string $path ): string { return rtrim( $path, '/\\' ) . '/'; }

class SPDB_Expired_Test_DB {
	public array $rows = array();
	public bool $fail_read = false;
	public bool $full_pages = false;
	public int $reads = 0;
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_results( string $sql, $output = null ) {
		++$this->reads;
		if ( $this->fail_read ) { return false; }
		if ( $this->full_pages ) {
			$first = 200 * ( $this->reads - 1 ) + 1;
			return array_map( static fn( int $i ): array => array( 'id' => $i, 'storage_ref' => '' ), range( $first, $first + 199 ) );
		}
		return $this->rows;
	}
}
$GLOBALS['wpdb'] = new SPDB_Expired_Test_DB();
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$tests = 0; $failed = 0;
$assert = static function ( bool $pass, string $message ) use ( &$tests, &$failed ): void {
	++$tests; if ( ! $pass ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$check_error = static function ( $result, string $code ) use ( $assert ): void {
	$assert( is_wp_error( $result ) && $code === $result->get_error_code(), 'Expected fail-closed code: ' . $code );
};
$directory = $GLOBALS['spdb_expired_test_root'] . '/spdb-private-exports';
$expired = 'export_' . str_repeat( 'a', 32 ) . '.spdb';
$other = 'export_' . str_repeat( 'b', 32 ) . '.spdb';
try {
	$assert( 0 === $service->cleanup_expired_files(), 'No expired jobs means zero deletions.' );

	$GLOBALS['wpdb']->rows = array( array( 'id' => 1, 'storage_ref' => $expired ) );
	file_put_contents( $directory . '/' . $other, 'OTHER USER CIPHERTEXT' );
	if ( function_exists( 'symlink' ) && @symlink( $directory . '/' . $other, $directory . '/' . $expired ) ) {
		$check_error( $service->cleanup_expired_files(), 'spdb_export_cleanup_path_invalid' );
		$assert( 'OTHER USER CIPHERTEXT' === file_get_contents( $directory . '/' . $other ), 'A symlink must never cause deletion of another artifact.' );
		unlink( $directory . '/' . $expired );
	}
	$GLOBALS['wpdb']->rows = array( array( 'id' => 2, 'storage_ref' => '../' . $expired ) );
	$check_error( $service->cleanup_expired_files(), 'spdb_export_cleanup_reference_invalid' );

	$GLOBALS['wpdb']->rows = array( array( 'id' => 3, 'storage_ref' => $expired ) );
	file_put_contents( $directory . '/' . $expired, 'EXPIRED CIPHERTEXT' );
	$assert( 1 === $service->cleanup_expired_files(), 'Verified expired ciphertext is removed.' );
	$assert( ! file_exists( $directory . '/' . $expired ) && file_exists( $directory . '/' . $other ), 'Other artifact remains intact.' );

	$GLOBALS['wpdb']->fail_read = true;
	$check_error( $service->cleanup_expired_files(), 'spdb_export_cleanup_read_failed' );
	$GLOBALS['wpdb']->fail_read = false;
	$GLOBALS['wpdb']->rows = array( array( 'id' => 3, 'storage_ref' => '' ), array( 'id' => 2, 'storage_ref' => '' ) );
	$check_error( $service->cleanup_expired_files(), 'spdb_export_cleanup_cursor_invalid' );
	$GLOBALS['wpdb']->rows = array();
	$GLOBALS['wpdb']->reads = 0;
	$GLOBALS['wpdb']->full_pages = true;
	$check_error( $service->cleanup_expired_files(), 'spdb_export_cleanup_batch_incomplete' );
	$GLOBALS['wpdb']->full_pages = false;
} finally {
	if ( is_dir( $directory ) ) {
		foreach ( scandir( $directory ) ?: array() as $entry ) {
			if ( '.' !== $entry && '..' !== $entry ) { @unlink( $directory . '/' . $entry ); }
		}
		@rmdir( $directory );
	}
	if ( is_dir( $GLOBALS['spdb_expired_test_root'] ) ) { @rmdir( $GLOBALS['spdb_expired_test_root'] ); }
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} expired retention tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 expired-export retention tests passed.\n";
