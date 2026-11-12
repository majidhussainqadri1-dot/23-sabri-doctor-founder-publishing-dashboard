<?php
/**
 * V12.1 WP-12 / Issue #31: bounded owner-bound, replay-safe private
 * ciphertext erasure + full verification across >20k metadata rows.
 * Source-only harness; no real hosting files/WordPress DB.
 */
require_once __DIR__ . '/bootstrap.php';

$GLOBALS['spdb_resumable_root'] = sys_get_temp_dir() . '/spdb-resumable-v121-' . bin2hex( random_bytes( 8 ) );
function wp_upload_dir( $time = null, $create_dir = true ): array {
	return array( 'basedir' => $GLOBALS['spdb_resumable_root'], 'error' => false );
}
function wp_mkdir_p( string $path ): bool { return is_dir( $path ) || mkdir( $path, 0700, true ); }
function trailingslashit( string $path ): string { return rtrim( $path, '/\\' ) . '/'; }

class SPDB_Resumable_Erase_DB {
	public array $rows = array();
	public int $user_id = 0;
	public int $after_id = 0;
	public int $limit = 0;
	public bool $read_failure = false;
	public int $calls = 0;
	public function prepare( string $query, ...$args ): string {
		$this->user_id = (int) ( $args[0] ?? 0 );
		$this->after_id = (int) ( $args[1] ?? 0 );
		$this->limit = (int) ( $args[2] ?? 200 );
		return $query;
	}
	public function get_results( string $query, $mode = null ) {
		++$this->calls;
		if ( $this->read_failure ) { return false; }
		$found = array();
		foreach ( $this->rows as $row ) {
			if ( (int) $row['owner_user_id'] === $this->user_id && (int) $row['id'] > $this->after_id ) {
				$found[] = array( 'id' => (int) $row['id'], 'storage_ref' => (string) $row['storage_ref'] );
			}
			if ( count( $found ) >= $this->limit ) { break; }
		}
		return $found;
	}
}
$GLOBALS['wpdb'] = new SPDB_Resumable_Erase_DB();
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$tests = 0; $failed = 0;
$assert = static function ( bool $pass, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $pass ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$assert( method_exists( SPDB_Export_Service::class, 'erase_user_files_page' ), 'Bounded resumable owner-erasure method must exist.' );
if ( ! method_exists( SPDB_Export_Service::class, 'erase_user_files_page' ) ) { exit( 1 ); }
$directory = $GLOBALS['spdb_resumable_root'] . '/spdb-private-exports';
try {
	$filename_first = 'export_' . str_repeat( 'a', 32 ) . '.spdb';
	$filename_last = 'export_' . str_repeat( 'b', 32 ) . '.spdb';
	$rows = array();
	for ( $i = 1; $i <= 20001; ++$i ) {
		$rows[] = array( 'id' => $i, 'owner_user_id' => 7, 'storage_ref' => 1 === $i ? $filename_first : ( 20001 === $i ? $filename_last : '' ) );
	}
	$rows[] = array( 'id' => 20002, 'owner_user_id' => 8, 'storage_ref' => 'export_' . str_repeat( 'c', 32 ) . '.spdb' );
	$GLOBALS['wpdb']->rows = $rows;
	// Establish private directory with the actual guard generation routine.
	$directory_method = new ReflectionMethod( SPDB_Export_Service::class, 'private_directory' );
	$directory_method->setAccessible( true );
	$assert( is_string( $directory_method->invoke( $service ) ), 'Private directory guards created.' );
	file_put_contents( $directory . '/' . $filename_first, 'private-first' );
	file_put_contents( $directory . '/' . $filename_last, 'private-last' );
	$cursor = 0; $deleted = 0; $pages = 0;
	do {
		$result = $service->erase_user_files_page( 7, $cursor, 200, false );
		$assert( is_array( $result ), 'Owner erasure page must return a bounded result, not a hard 20k refusal.' );
		if ( ! is_array( $result ) ) { break; }
		$assert( (int) $result['next_cursor'] >= $cursor, 'Monotonic server cursor.' );
		$cursor = (int) $result['next_cursor'];
		$deleted += (int) $result['deleted'];
		++$pages;
		if ( $pages > 120 ) { break; }
	} while ( empty( $result['done'] ) );
	$assert( 20001 === $cursor && ! empty( $result['done'] ) && $pages >= 101, 'Owner erasure spans more than 20k rows and finishes with a bounded cursor.' );
	$assert( 2 === $deleted && ! file_exists( $directory . '/' . $filename_first ) && ! file_exists( $directory . '/' . $filename_last ), 'All two owned ciphertexts deleted.' );
	$assert( count( $GLOBALS['wpdb']->rows ) === 20002, 'Owner metadata remains intact throughout file deletion.' );
	$assert( ! in_array( 20002, array( $cursor ), true ), 'Foreign-owner cursor never entered the owner-specific scan.' );

	$verified = $service->erase_user_files_page( 7, 0, 200, true );
	$assert( is_array( $verified ) && 0 === (int) $verified['deleted'], 'Verification pass checks rather than deletes.' );
	file_put_contents( $directory . '/' . $filename_first, 're-created encrypted artifact' );
	$broken = $service->erase_user_files_page( 7, 0, 200, true );
	$assert( is_wp_error( $broken ) && 'spdb_export_erase_verification_failed' === $broken->get_error_code(), 'Re-created ciphertext during verification fails closed.' );
	$assert( file_exists( $directory . '/' . $filename_first ), 'Verification never silently deletes an unexpected artifact.' );
	unlink( $directory . '/' . $filename_first );

	$bad = $service->erase_user_files_page( 0, 0, 200, false );
	$assert( is_wp_error( $bad ), 'Invalid account denied.' );
	$bad = $service->erase_user_files_page( 7, -1, 200, false );
	$assert( is_wp_error( $bad ), 'Negative cursor denied.' );
	$bad = $service->erase_user_files_page( 7, 0, 10000, false );
	$assert( is_wp_error( $bad ), 'Unbounded batch size denied.' );
	$GLOBALS['wpdb']->read_failure = true;
	$bad = $service->erase_user_files_page( 7, 0, 200, false );
	$assert( is_wp_error( $bad ) && 'spdb_export_erase_read_failed' === $bad->get_error_code(), 'DB failure propagates without advancing cursor.' );
} finally {
	if ( is_dir( $directory ) ) {
		foreach ( scandir( $directory ) ?: array() as $entry ) {
			if ( '.' !== $entry && '..' !== $entry ) { @unlink( $directory . '/' . $entry ); }
		}
		@rmdir( $directory );
	}
	if ( is_dir( $GLOBALS['spdb_resumable_root'] ) ) { @rmdir( $GLOBALS['spdb_resumable_root'] ); }
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 resumable erasure tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 resumable erasure tests passed.\n";
