<?php
/**
 * File23 V12.1 WordPress personal eraser: durable per-user HMAC checkpoint,
 * 200-row bounded turns, paused downloads/generation and independent verify.
 *
 * Source simulation only: no actual patient data, WordPress or Hostinger DB.
 */
require_once __DIR__ . '/bootstrap.php';
function wp_salt( string $scheme = 'auth' ): string { return 'fixture-salt-' . $scheme; }
function add_option( string $key, $value, string $deprecated = '', $autoload = null ): bool {
	if ( isset( $GLOBALS['spdb_test_options'][ $key ] ) ) { return false; }
	$GLOBALS['spdb_test_options'][ $key ] = $value;
	return true;
}
class WP_User { public int $ID = 7; }
function get_user_by( string $field, string $value ) {
	return 'email' === $field && 'privacy@test.invalid' === $value ? new WP_User() : false;
}

$GLOBALS['spdb_privacy_progress_root'] = sys_get_temp_dir() . '/spdb-privacy-progress-' . bin2hex( random_bytes( 8 ) );
function wp_upload_dir( $time = null, $create_dir = true ): array {
	return array( 'basedir' => $GLOBALS['spdb_privacy_progress_root'], 'error' => false );
}
function wp_mkdir_p( string $path ): bool { return is_dir( $path ) || mkdir( $path, 0700, true ); }
function trailingslashit( string $path ): string { return rtrim( $path, '/\\' ) . '/'; }

class SPDB_Privacy_Progress_DB {
	public array $rows = array();
	public bool $lock_busy = false;
	public int $lock_depth = 0;
	public function get_var( string $sql ) {
		if ( str_contains( $sql, 'GET_LOCK' ) ) {
			if ( $this->lock_busy || $this->lock_depth > 0 ) { return '0'; }
			++$this->lock_depth;
			return '1';
		}
		if ( str_contains( $sql, 'RELEASE_LOCK' ) ) { --$this->lock_depth; return '1'; }
		return null;
	}
	public int $current_owner = 0;
	public int $cursor = 0;
	public int $limit = 0;
	public function prepare( string $sql, ...$args ): string {
		$this->current_owner = (int) ( $args[0] ?? 0 );
		$this->cursor = (int) ( $args[1] ?? 0 );
		$this->limit = (int) ( $args[2] ?? 200 );
		return $sql;
	}
	public function get_results( string $sql, $mode = null ) {
		$out = array();
		foreach ( $this->rows as $row ) {
			if ( $row['owner_user_id'] === $this->current_owner && $row['id'] > $this->cursor ) {
				$out[] = array( 'id' => $row['id'], 'storage_ref' => $row['storage_ref'] );
			}
			if ( count( $out ) >= $this->limit ) { break; }
		}
		return $out;
	}
}
$GLOBALS['wpdb'] = new SPDB_Privacy_Progress_DB();
for ( $id = 1; $id <= 401; ++$id ) {
	$GLOBALS['wpdb']->rows[] = array( 'id' => $id, 'owner_user_id' => 7, 'storage_ref' => '' );
}
$GLOBALS['wpdb']->rows[] = array( 'id' => 402, 'owner_user_id' => 8, 'storage_ref' => '' );

$export = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$repository = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$privacy = new SPDB_Privacy_Integration( $repository, $export );
$key = SPDB_Export_Service::privacy_erasure_checkpoint_key( 7 );
$tests = 0; $failed = 0;
$assert = static function ( bool $pass, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $pass ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

try {
	$assert( ! SPDB_Export_Service::privacy_erasure_pending( 7 ), 'Initially owner is not under erasure.' );
	$not_started = $privacy->erase_personal_data( 'privacy@test.invalid', 2 );
	$assert( true === $not_started['done'] && ! SPDB_Export_Service::privacy_erasure_pending( 7 ), 'A stray later page cannot start an unauthorized new checkpoint.' );
	$page1 = $privacy->erase_personal_data( 'privacy@test.invalid', 1 );
	$state1 = get_option( $key, false );
	$assert( false === $page1['done'] && is_array( $state1 ) && 200 === $state1['cursor'] && 'remove' === $state1['stage'], 'First call persists exact first-page cursor.' );
	$assert( SPDB_Export_Service::privacy_erasure_pending( 7 ), 'Checkpoint locks new exports for owner 7.' );
	$assert( ! SPDB_Export_Service::privacy_erasure_pending( 8 ), 'Foreign owner remains unaffected.' );

	// Changing the database checkpoint without its MAC cannot skip the scan.
	$tampered = $state1;
	$tampered['cursor'] = 999999;
	update_option( $key, $tampered, false );
	$failure = $privacy->erase_personal_data( 'privacy@test.invalid', 2 );
	$assert( true === $failure['done'] && true === $failure['items_retained'] && false === $failure['items_removed'], 'Tampered progress fails closed and retains metadata.' );
	$assert( is_array( get_option( $key, false ) ), 'Malformed checkpoint is retained for audit rather than silently bypassed.' );
	update_option( $key, $state1, false );

	$page2 = $privacy->erase_personal_data( 'privacy@test.invalid', 2 );
	$state2 = get_option( $key, false );
	$assert( false === $page2['done'] && 400 === $state2['cursor'], 'Second call resumes from persisted owner cursor.' );
	$page3 = $privacy->erase_personal_data( 'privacy@test.invalid', 3 );
	$state3 = get_option( $key, false );
	$assert( false === $page3['done'] && 'verify' === $state3['stage'] && 0 === $state3['cursor'], 'After final deletion page a full independent verification pass starts at zero.' );
	$verify1 = $privacy->erase_personal_data( 'privacy@test.invalid', 4 );
	$state4 = get_option( $key, false );
	$assert( false === $verify1['done'] && 'verify' === $state4['stage'] && 200 === $state4['cursor'], 'Verification continues across WordPress requests without removing DB rows.' );
	$assert( count( $GLOBALS['wpdb']->rows ) === 402, 'No metadata was deleted before complete verification.' );

	// An unexpected re-created ciphertext in the remaining verification
	// window must interrupt release rather than silently erasing its ref.
	$filename = 'export_' . str_repeat( 'a', 32 ) . '.spdb';
	$directory = $GLOBALS['spdb_privacy_progress_root'] . '/spdb-private-exports';
	file_put_contents( $directory . '/' . $filename, 'unexpected');
	$GLOBALS['wpdb']->rows[200]['storage_ref'] = $filename;
	$blocked = $privacy->erase_personal_data( 'privacy@test.invalid', 5 );
	$assert( true === $blocked['items_retained'] && true === $blocked['done'] && count( $GLOBALS['wpdb']->rows ) === 402, 'Reappeared ciphertext blocks metadata erasure.' );
	$assert( file_exists( $directory . '/' . $filename ), 'Verification does not hide the reappeared artifact.' );
	$assert( SPDB_Export_Service::privacy_erasure_pending( 7 ), 'Failure retains protective owner lock for operator retry.' );
} finally {
	$directory = $GLOBALS['spdb_privacy_progress_root'] . '/spdb-private-exports';
	if ( is_dir( $directory ) ) {
		foreach ( scandir( $directory ) ?: array() as $name ) {
			if ( '.' !== $name && '..' !== $name ) { @unlink( $directory . '/' . $name ); }
		}
		@rmdir( $directory );
	}
	if ( is_dir( $GLOBALS['spdb_privacy_progress_root'] ) ) { @rmdir( $GLOBALS['spdb_privacy_progress_root'] ); }
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 privacy progress tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 privacy progress tests passed.\n";
