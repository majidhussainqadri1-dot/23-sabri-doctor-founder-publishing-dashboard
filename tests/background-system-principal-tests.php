<?php
/** Ensure owner-0 background jobs cannot inherit the user who triggered WP-Cron. */
$source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-background-jobs.php' );
$tests = 0; $failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void { ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } };
$start = strpos( $source, 'private function execute' );
$end   = strpos( $source, 'private function aggregate_analytics', false === $start ? 0 : $start );
$body  = false !== $start && false !== $end ? substr( $source, $start, $end - $start ) : '';
$assert( '' !== $body, 'Background execute boundary must be present.' );
$assert( false !== strpos( $body, '$previous_user = get_current_user_id()' ), 'Background jobs must remember the triggering principal.' );
$assert( false !== strpos( $body, 'wp_set_current_user( $owner_user )' ), 'Every job must explicitly switch to its recorded principal, including owner 0.' );
$assert( false === strpos( $body, '$owner_user > 0 && function_exists' ), 'System jobs must not skip the user switch merely because owner is 0.' );
$assert( false !== strpos( $body, 'wp_set_current_user( $previous_user )' ), 'Background execution must restore the previous user in finally.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} background principal-isolation tests failed.\n" ); exit( 1 ); }
echo "All {$tests} background principal-isolation tests passed.\n";
