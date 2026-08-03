<?php
/**
 * Executable tests for isolated provider registration dispatch.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';

$registry = new SPDB_Adapter_Registry();
$hook     = new stdClass();
$hook->callbacks = array(
	10 => array(
		'failing' => array(
			'function'      => static function ( SPDB_Adapter_Registry $ignored ): void {
				throw new RuntimeException( 'Provider secret that must not escape.' );
			},
			'accepted_args' => 1,
		),
	),
	20 => array(
		'healthy' => array(
			'function'      => static function ( SPDB_Adapter_Registry $target ): void {
				$target->register( new SPDB_Test_Adapter() );
			},
			'accepted_args' => 1,
		),
	),
);
$GLOBALS['wp_filter'][ SPDB_Provider_Registration::HOOK ] = $hook;

SPDB_Provider_Registration::dispatch( $registry );

$failed = 0;
function spdb_provider_assert( bool $condition, string $message ): void {
	global $failed;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

spdb_provider_assert( $registry->has( 'provider_one' ), 'A later healthy provider must register after an earlier callback throws.' );
spdb_provider_assert( ! empty( $registry->registration_errors()['system'] ), 'The isolated provider failure must be recorded for diagnostics.' );
spdb_provider_assert( ! isset( $GLOBALS['wp_filter'][ SPDB_Provider_Registration::HOOK ] ), 'Registration callbacks must not remain available for accidental duplicate dispatch.' );

if ( $failed > 0 ) {
	exit( 1 );
}

echo 'All File 23 provider-registration tests passed.' . PHP_EOL;
