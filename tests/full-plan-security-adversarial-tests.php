<?php
/** Static adversarial security/privacy gate for the completed File 23 scope. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$read = static function ( string $path ) use ( $root ): string { return (string) file_get_contents( $root . '/' . $path ); };

$governance = $read( 'includes/class-spdb-governance-service.php' );
$automation = $read( 'includes/class-spdb-automation-engine.php' );
$activation = $read( 'includes/class-spdb-activation-wizard.php' );
$router     = $read( 'includes/class-spdb-dashboard-router.php' );
$export     = $read( 'includes/class-spdb-export-service.php' );
$validator  = $read( 'includes/class-spdb-operational-projection-validator.php' );
$repository = $read( 'includes/class-spdb-operations-repository.php' );

foreach ( array( 'patient', 'diagnos', 'prescription', 'potency', 'dosage', 'auto[_-]?publish', 'delete', 'impersonat', 'mass[_-]?publish', 'cure[_-]?claim', 'change[_-]?author', 'export[_-]?patient' ) as $pattern ) {
	$assert( str_contains( $governance, $pattern ), "Automation guard must reject {$pattern}." );
}
$assert( str_contains( $governance, "'can_publish'             => false" ) && str_contains( $governance, "'can_export'              => false" ) && str_contains( $governance, "'can_access_patient_data' => false" ), 'Delegations must never silently grant publish, export, or patient access.' );
$assert( str_contains( $governance, 'session_two_factor' ) && str_contains( $governance, '90 * DAY_IN_SECONDS' ), 'Delegation creation must require current MFA and bounded expiry.' );
$assert( str_contains( $automation, 'human_confirmation' ) && str_contains( $automation, 'idempotent' ) && str_contains( $automation, 'spdb/automation_action_result' ), 'Automation execution must be human-governed, idempotent, and native-provider mediated.' );
$assert( preg_match( '/patient\|message\|email\|phone\|address\|token\|secret\|password\|consent\|document\|ip/', $automation ) === 1, 'Automation event payload must suppress sensitive fields.' );
$assert( str_contains( $activation, 'is_user_founder' ) && str_contains( $activation, 'session_two_factor' ) && str_contains( $activation, "'production' === \$environment" ), 'Staging acceptance must require Founder identity, MFA, and a non-production environment.' );
$assert( str_contains( $router, 'DONOTCACHEPAGE' ) && str_contains( $router, 'private, no-store' ) && str_contains( $router, 'noindex, nofollow, noarchive' ), 'Private dashboard route must be cache-excluded and non-indexable.' );
$assert( str_contains( $export, 'hash_equals' ) && str_contains( $export, 'hash_hmac' ) && str_contains( $export, 'get_current_user_id' ), 'Export downloads must verify owner-bound HMAC signatures.' );
$assert( str_contains( $export, 'spreadsheet_safe' ) && str_contains( $export, "'/^[=+\\-@]/'" ), 'CSV export must contain spreadsheet-formula neutralization.' );
$assert( str_contains( $validator, 'sensitive_key' ) && str_contains( $validator, 'same-origin' ) === false && str_contains( $validator, 'home_url' ), 'Operational projections must filter sensitive metadata and validate destinations against the site origin.' );
$assert( str_contains( $repository, 'previous_hash' ) && str_contains( $repository, 'event_hash' ) && str_contains( $repository, 'hash_equals' ), 'Dashboard audit must use an append-only integrity chain.' );
$assert( str_contains( $repository, 'idempotency_key' ) && str_contains( $repository, 'lock_token' ) && str_contains( $repository, 'dead_letter' ), 'Background jobs must use idempotency, locking, and dead-letter state.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} adversarial tests failed.\n" ); exit( 1 ); }
echo "All {$tests} full-plan adversarial tests passed.\n";
