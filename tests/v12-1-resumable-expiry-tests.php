<?php
/**
 * File23 V12.1 Issue31: >20k expired export artifacts are paginated
 * against one frozen cutoff and independently re-verified before DB purge.
 * Only synthetic local ciphertext/metadata are used.
 */
require_once __DIR__ . '/bootstrap.php';
$GLOBALS['spdb_retention_progress_root'] = sys_get_temp_dir() . '/spdb-retention-progress-' . bin2hex( random_bytes( 7 ) );
function wp_upload_dir( $time = null, $create_dir = true ): array {
	return array( 'basedir' => $GLOBALS['spdb_retention_progress_root'], 'error' => false );
}
function wp_mkdir_p( string $path ): bool { return is_dir( $path ) || mkdir( $path, 0700, true ); }
function trailingslashit( string $path ): string { return rtrim( $path, '/\\' ) . '/'; }
class SPDB_Expiry_Progress_DB {
	public array $rows = array();
	public string $cutoff = '';
	public int $cursor = 0;
	public int $limit = 200;
	public function prepare( string $query, ...$args ): string {
		$this->cutoff = (string) $args[0];
		$this->cursor = (int) $args[1];
		$this->limit = (int) ( $args[3] ?? $args[2] );
		return $query;
	}
	public function get_results( string $query, $mode = null ) {
		$found = array();
		foreach ( $this->rows as $row ) {
			if ( $row['expires_at_gmt'] <= $this->cutoff && $row['id'] > $this->cursor ) {
				$found[] = array( 'id' => $row['id'], 'storage_ref' => $row['storage_ref'], 'export_id' => '' !== $row['storage_ref'] ? substr( $row['storage_ref'], 0, -5 ) : 'export_' . str_repeat( '0', 32 ), 'status' => $row['status'] );
			}
			if ( count( $found ) >= $this->limit ) { break; }
		}
		return $found;
	}
}
$GLOBALS['wpdb'] = new SPDB_Expiry_Progress_DB();
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$cutoff = '2026-07-30 00:00:00';
$valid_earliest = 'export_' . str_repeat( 'a', 32 ) . '.spdb';
$valid_latest = 'export_' . str_repeat( 'b', 32 ) . '.spdb';
$future_file = 'export_' . str_repeat( 'c', 32 ) . '.spdb';
for ( $i = 1; $i <= 20001; ++$i ) {
	$GLOBALS['wpdb']->rows[] = array(
		'id' => $i, 'expires_at_gmt' => $cutoff, 'status' => 'ready',
		'storage_ref' => 1 === $i ? $valid_earliest : ( 20001 === $i ? $valid_latest : '' ),
	);
}
$GLOBALS['wpdb']->rows[] = array( 'id' => 20002, 'expires_at_gmt' => '2026-08-01 00:00:00', 'status' => 'ready', 'storage_ref' => $future_file );
$tests = 0; $failed = 0;
$assert = static function ( bool $pass, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $pass ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$directory = $GLOBALS['spdb_retention_progress_root'] . '/spdb-private-exports';
try {
	$init = new ReflectionMethod( SPDB_Export_Service::class, 'private_directory' );
	$init->setAccessible( true );
	$assert( is_string( $init->invoke( $service ) ), 'Private guards initialized for synthetic expiry data.' );
	file_put_contents( $directory . '/' . $valid_earliest, 'sealed-1' );
	file_put_contents( $directory . '/' . $valid_latest, 'sealed-2' );
	file_put_contents( $directory . '/' . $future_file, 'not-expired' );
	$cursor = 0; $count = 0; $pages = 0; $result = array( 'done' => false );
	do {
		$step = $service->cleanup_expired_files_page( $cutoff, $cursor, 200, false );
		$assert( is_array( $step ), 'A bounded expired-artifact page must be readable.' );
		if ( ! is_array( $step ) ) { break; }
		$assert( $step['next_cursor'] >= $cursor, 'Expiry cursor must move monotonically.' );
		$cursor = $step['next_cursor']; $count += $step['deleted']; ++$pages; $result = $step;
		if ( $pages > 120 ) { break; }
	} while ( ! $result['done'] );
	$assert( $result['done'] && 20001 === $cursor && $pages >= 101, 'Frozen-cutoff expiry scan traverses >20,000 rows without deleting metadata.' );
	$assert( 2 === $count && ! file_exists( $directory . '/' . $valid_earliest ) && ! file_exists( $directory . '/' . $valid_latest ), 'Only the two expired ciphertexts are deleted.' );
	$assert( is_file( $directory . '/' . $future_file ), 'Post-cutoff ciphertext remains intact.' );
	$assert( 20002 === count( $GLOBALS['wpdb']->rows ), 'Metadata remains until independently verified and purge is authorized.' );

	$first_verify = $service->cleanup_expired_files_page( $cutoff, 0, 200, true );
	$assert( is_array( $first_verify ) && 0 === $first_verify['deleted'], 'Verification is read-only and restarts at zero.' );
	file_put_contents( $directory . '/' . $valid_earliest, 'late/corrupt' );
	$invalid = $service->cleanup_expired_files_page( $cutoff, 0, 200, true );
	$assert( is_wp_error( $invalid ) && 'spdb_export_cleanup_verification_failed' === $invalid->get_error_code(), 'Recreated expired artifact aborts verification.' );
	unlink( $directory . '/' . $valid_earliest );

	$GLOBALS['wpdb']->rows[0]['status'] = 'processing';
	$pending = $service->cleanup_expired_files_page( $cutoff, 0, 200, false );
	$assert( is_wp_error( $pending ) && 'spdb_export_cleanup_active_job' === $pending->get_error_code(), 'Expired in-flight export blocks retention deletion.' );
	$GLOBALS['wpdb']->rows[0]['status'] = 'ready';

	$future = $service->cleanup_expired_files_page( '2026-08-01 00:00:00', 0, 200, false );
	$assert( is_wp_error( $future ), 'Future cutoff cannot be supplied by a background job.' );
	$bad = $service->cleanup_expired_files_page( $cutoff, 0, 10000, false );
	$assert( is_wp_error( $bad ), 'Unbounded page sizes are not authorized.' );
} finally {
	if ( is_dir( $directory ) ) {
		foreach ( scandir( $directory ) ?: array() as $name ) {
			if ( '.' !== $name && '..' !== $name ) { @unlink( $directory . '/' . $name ); }
		}
		@rmdir( $directory );
	}
	if ( is_dir( $GLOBALS['spdb_retention_progress_root'] ) ) { @rmdir( $GLOBALS['spdb_retention_progress_root'] ); }
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} bounded retention tests failed.\n" ); exit( 1 ); }
echo "All {$tests} bounded V12.1 retention tests passed.\n";
