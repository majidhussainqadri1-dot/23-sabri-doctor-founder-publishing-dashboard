<?php
/** Static corrective regression checks for the Phase 23F Collections UI. */
$root = dirname( __DIR__ );
$template = file_get_contents( $root . '/templates/collections.php' );
$dashboard = file_get_contents( $root . '/templates/dashboard.php' );
$tests = 0;
$failed = 0;
function spdb_ui_corrective_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

spdb_ui_corrective_assert( is_string( $template ), 'The Collections template must be readable.' );
spdb_ui_corrective_assert( is_string( $template ) && str_contains( $template, '$is_founder' ) && str_contains( $template, '$render_scope_options' ), 'Institution scope rendering must be explicitly Founder-aware.' );
spdb_ui_corrective_assert( is_string( $template ) && ! str_contains( $template, '&larr;' ), 'Back navigation must not hard-code a left-pointing symbol that becomes incorrect in RTL.' );
spdb_ui_corrective_assert( is_string( $template ) && str_contains( $template, "'scope' => \$detail['scope']" ) && str_contains( $template, "'scope' => \$link['scope']" ), 'Collection and knowledge back links must preserve the authorized scope.' );
spdb_ui_corrective_assert( is_string( $template ) && str_contains( $template, 'Collection mutation UI:' ) && str_contains( $template, 'Knowledge mutation UI:' ) && str_contains( $template, 'Not exposed' ), 'Readiness copy must not imply that mutation controls are available.' );
spdb_ui_corrective_assert( is_string( $template ) && ! preg_match( '/name="status"[^>]*type="text"/i', $template ), 'Status filtering must use bounded choices rather than free-text input.' );
spdb_ui_corrective_assert( is_string( $dashboard ) && str_contains( $dashboard, 'Collections reads' ) && str_contains( $dashboard, 'Collection writes' ) && str_contains( $dashboard, 'Knowledge writes' ), 'System Status must expose non-sensitive Phase 23F readiness.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} corrective Collections UI checks failed.\n" );
	exit( 1 );
}
echo "All {$tests} corrective Collections UI checks passed.\n";
