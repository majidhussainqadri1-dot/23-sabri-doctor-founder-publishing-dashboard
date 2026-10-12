<?php
/** Third fresh 80-round export authorization and retention contract regressions. */
$root = dirname( __DIR__ );
$export = file_get_contents( $root . '/includes/class-spdb-export-service.php' );
$bg = file_get_contents( $root . '/includes/class-spdb-background-jobs.php' );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$assert( is_string( $export ) && false !== strpos( $export, 'private function current_user_can_export(): bool' ), 'Export service must centralize current-state authorization.' );
$assert( substr_count( $export, '! $this->current_user_can_export()' ) >= 3, 'Export request, listing and download must re-check current authorization.' );
$assert( false !== strpos( $export, 'true !== ( $assertions[\'approved\'] ?? false )' ) && false !== strpos( $export, 'true === ( $assertions[\'suspended\'] ?? true )' ), 'Export authorization must consume current File 00 approval/suspension assertions.' );
$assert( false !== strpos( $export, 'user_can( $user_id, \'spdb_export_reports\' )' ), 'Background export authorization must verify the owner capability for the target user.' );
$assert( false !== strpos( $export, 'spdb_export_authorization_revoked' ) && false !== strpos( $export, 'spdb_export_scope_revoked' ), 'Queued export generation must fail closed when owner or institution scope is revoked.' );
$assert( false !== strpos( $export, 'public function cleanup_expired_files()' ), 'Export service must expose bounded expired-artifact cleanup.' );
$assert( false !== strpos( $export, 'WHERE expires_at_gmt <= %s AND id > %d' ) && false !== strpos( $export, 'spdb_export_cleanup_delete_failed' ), 'Expired artifact cleanup must be expiry-bounded and fail closed on file deletion failure.' );
$cleanup_pos = is_string( $bg ) ? strpos( $bg, '$this->exports->cleanup_expired_files()' ) : false;
$metadata_pos = is_string( $bg ) ? strpos( $bg, '$this->repository->cleanup_retention( SPDB_Admin_Settings::get() )' ) : false;
$assert( false !== $cleanup_pos && false !== $metadata_pos && $cleanup_pos < $metadata_pos, 'Retention worker must delete encrypted artifacts before deleting export metadata rows.' );
if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} third fresh export regressions failed.\n" ); exit( 1 ); }
echo "All {$tests} third fresh File 23 export authorization/retention regressions passed.\n";
