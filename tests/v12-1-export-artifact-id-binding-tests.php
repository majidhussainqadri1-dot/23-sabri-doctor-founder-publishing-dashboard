<?php
/**
 * File23 V12.1-STO-09: a retention/erasure row may delete only its OWN
 * export_ID.spdb. A syntactically valid reference to another job's artifact
 * must stop before unlink, including legacy and >20k paged code paths.
 *
 * Synthetic filesystem and mock DB only: no Hostinger or private user data.
 */
require_once __DIR__ . '/bootstrap.php';

$GLOBALS['spdb_exact_ref_root'] = sys_get_temp_dir() . '/spdb-exact-ref-' . bin2hex( random_bytes( 8 ) );
function wp_upload_dir( $time = null, $create_dir = true ): array { return array( 'basedir' => $GLOBALS['spdb_exact_ref_root'], 'error' => false ); }
function wp_mkdir_p( string $path ): bool { return is_dir( $path ) || mkdir( $path, 0700, true ); }
function trailingslashit( string $path ): string { return rtrim( $path, '/\\' ) . '/'; }

class SPDB_Exact_Ref_Mock_DB {
	public array $row = array();
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_results( string $query, $format = null ) { return array( $this->row ); }
}
$GLOBALS['wpdb'] = new SPDB_Exact_Ref_Mock_DB();
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$filename_a = 'export_' . str_repeat( 'a', 32 ) . '.spdb';
$filename_b = 'export_' . str_repeat( 'b', 32 ) . '.spdb';
$directory = $GLOBALS['spdb_exact_ref_root'] . '/spdb-private-exports';
$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $msg ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$msg}\n" ); }
};

try {
	$init = new ReflectionMethod( SPDB_Export_Service::class, 'private_directory' );
	$init->setAccessible( true );
	$assert( is_string( $init->invoke( $service ) ), 'Synthetic private storage created and guarded.' );

	$GLOBALS['wpdb']->row = array(
		'id' => 1,
		'owner_user_id' => 7,
		'export_id' => substr( $filename_a, 0, -5 ),
		'storage_ref' => $filename_b,
		'status' => 'ready',
		'expires_at_gmt' => '2026-07-30 00:00:00',
	);
	$paths = array(
		array( 'name' => 'bounded expiry cleanup', 'call' => static fn() => $service->cleanup_expired_files_page( '2026-07-30 00:00:00', 0, 200, false ), 'error' => 'spdb_export_cleanup_reference_invalid' ),
		array( 'name' => 'bounded owner erasure', 'call' => static fn() => $service->erase_user_files_page( 7, 0, 200, false ), 'error' => 'spdb_export_erase_reference_invalid' ),
		array( 'name' => 'legacy expiry cleanup', 'call' => static fn() => $service->cleanup_expired_files(), 'error' => 'spdb_export_cleanup_reference_invalid' ),
		array( 'name' => 'legacy owner erasure', 'call' => static fn() => $service->erase_user_files( 7 ), 'error' => 'spdb_export_erase_reference_invalid' ),
	);
	foreach ( $paths as $case ) {
		file_put_contents( $directory . '/' . $filename_b, 'OTHER JOB PRIVATE CIPHERTEXT' );
		$result = $case['call']();
		$assert( is_wp_error( $result ) && $case['error'] === $result->get_error_code(), $case['name'] . ' rejects another job file reference before deletion.' );
		$assert( 'OTHER JOB PRIVATE CIPHERTEXT' === file_get_contents( $directory . '/' . $filename_b ), $case['name'] . ' never deletes another job ciphertext.' );
	}

	$GLOBALS['wpdb']->row['storage_ref'] = $filename_a;
	foreach ( $paths as $case ) {
		file_put_contents( $directory . '/' . $filename_a, 'MATCHED CIPHERTEXT' );
		$result = $case['call']();
		$assert( is_array( $result ) || is_int( $result ), $case['name'] . ' accepts exactly matched job/file identity.' );
		$assert( ! file_exists( $directory . '/' . $filename_a ), $case['name'] . ' deletes its own matched ciphertext.' );
		$assert( is_file( $directory . '/' . $filename_b ), $case['name'] . ' leaves another job ciphertext untouched.' );
	}

	$GLOBALS['wpdb']->row['export_id'] = substr( $filename_a, 0, -5 );
	$GLOBALS['wpdb']->row['status'] = 'processing';
	$GLOBALS['wpdb']->row['storage_ref'] = $filename_a;
	file_put_contents( $directory . '/' . $filename_a, 'ACTIVE JOB DATA' );
	$active = $service->cleanup_expired_files();
	$assert( is_wp_error( $active ) && 'spdb_export_cleanup_active_job' === $active->get_error_code(), 'Legacy cleanup refuses an expired job still marked processing.' );
	$assert( 'ACTIVE JOB DATA' === file_get_contents( $directory . '/' . $filename_a ), 'An in-progress export cannot be deleted by legacy retention cleanup.' );
	$GLOBALS['wpdb']->row['status'] = 'ready';
	@unlink( $directory . '/' . $filename_a );
	$source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-export-service.php' );
	$assert( false === strpos( $source, 'unlink( $real )' ), 'Legacy erasure never unlinks a previously resolved path after a symlink race.' );

	$GLOBALS['wpdb']->row['export_id'] = 'export_' . str_repeat( 'c', 32 );
	$GLOBALS['wpdb']->row['storage_ref'] = $filename_a;
	$result = $service->erase_user_files_page( 7, 0, 200, true );
	$assert( is_wp_error( $result ) && 'spdb_export_erase_reference_invalid' === $result->get_error_code(), 'Verification-only pass never accepts identity mismatch.' );
} finally {
	if ( is_dir( $directory ) ) {
		foreach ( scandir( $directory ) ?: array() as $file ) {
			if ( '.' !== $file && '..' !== $file ) { @unlink( $directory . '/' . $file ); }
		}
		@rmdir( $directory );
	}
	@rmdir( $GLOBALS['spdb_exact_ref_root'] );
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 exact export artifact identity tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 exact export artifact identity tests passed.\n";
