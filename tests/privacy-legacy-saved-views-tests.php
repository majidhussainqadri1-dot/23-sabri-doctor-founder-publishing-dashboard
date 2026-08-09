<?php
/** Legacy File 23 saved-view metadata must remain exportable/erasable after GET migration was removed. */
$source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-privacy-integration.php' );
$tests = 0; $failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void { ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } };
foreach ( array( "LEGACY_SAVED_VIEWS_META = 'spdb_saved_views_v1'", "data['legacy_saved_views']", 'delete_user_meta', '$legacy_removed' ) as $marker ) {
	$assert( false !== strpos( $source, $marker ), "Privacy integration is missing legacy saved-view marker: {$marker}." );
}
$assert( false !== strpos( $source, 'array_slice( $legacy_saved_views, 0, 25 )' ), 'Legacy privacy export must remain bounded.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} legacy saved-view privacy tests failed.\n" ); exit( 1 ); }
echo "All {$tests} legacy saved-view privacy tests passed.\n";
