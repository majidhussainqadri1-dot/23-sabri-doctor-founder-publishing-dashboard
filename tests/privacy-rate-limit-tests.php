<?php
/** Rate-limit counter privacy export/erasure must be complete and privacy-minimized. */
$root = dirname( __DIR__ );
$bridge = (string) file_get_contents( $root . '/includes/class-spdb-rate-limit-privacy.php' );
$privacy = (string) file_get_contents( $root . '/includes/class-spdb-privacy-integration.php' );
$bootstrap = (string) file_get_contents( $root . '/sabri-publishing-dashboard.php' );
$tests = 0; $failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void { ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } };
foreach ( array( 'export_for_user', 'erase_for_user', 'actor_user_id', 'LIMIT 25' ) as $marker ) {
	$assert( false !== strpos( $bridge, $marker ), "Rate-limit privacy bridge is missing {$marker}." );
}
$export_start = strpos( $bridge, 'public static function export_for_user' );
$erase_start  = strpos( $bridge, 'public static function erase_for_user' );
$export_body  = false !== $export_start && false !== $erase_start ? substr( $bridge, $export_start, $erase_start - $export_start ) : '';
$assert( '' !== $export_body, 'Rate-limit privacy exporter must be discoverable.' );
$assert( false === strpos( $export_body, 'bucket_hash' ), 'Privacy export must never disclose the pseudonymous rate-limit bucket hash.' );
foreach ( array( 'SPDB_Rate_Limit_Privacy::export_for_user', "data['rate_limit_counters']", 'SPDB_Rate_Limit_Privacy::erase_for_user', '$rate_deleted > 0' ) as $marker ) {
	$assert( false !== strpos( $privacy, $marker ), "WordPress privacy integration is missing rate-limit lifecycle marker {$marker}." );
}
$assert( false !== strpos( $bootstrap, 'class-spdb-rate-limit-privacy.php' ), 'Plugin bootstrap must load the rate-limit privacy bridge.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} rate-limit privacy tests failed.\n" ); exit( 1 ); }
echo "All {$tests} rate-limit privacy tests passed.\n";
