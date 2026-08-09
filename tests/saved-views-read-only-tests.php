<?php
/** Ensure GET/list saved-view resolution never migrates or deletes persistent data. */
$source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-saved-views.php' );
$tests = 0; $failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void { ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } };
$start = strpos( $source, 'public function get_for_user' );
$end   = strpos( $source, 'private function normalize_stored_views', false === $start ? 0 : $start );
$body  = false !== $start && false !== $end ? substr( $source, $start, $end - $start ) : '';
$assert( '' !== $body, 'Saved-view read resolver must be discoverable.' );
$assert( false !== strpos( $body, 'list_saved_views' ), 'Saved-view reads may query the File 23 repository.' );
$assert( false !== strpos( $body, 'normalize_stored_views' ), 'Legacy saved views may remain read-compatible.' );
foreach ( array( 'create_saved_view', 'delete_user_meta', 'update_user_meta', 'delete_saved_view' ) as $forbidden ) {
	$assert( false === strpos( $body, $forbidden ), "GET saved-view resolution must not call {$forbidden}." );
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} saved-view read-only tests failed.\n" ); exit( 1 ); }
echo "All {$tests} saved-view read-only tests passed.\n";
