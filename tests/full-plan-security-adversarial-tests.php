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
$destination = $read( 'includes/class-spdb-safe-destination.php' );
$repository = $read( 'includes/class-spdb-operations-repository.php' );
$guard      = $read( 'includes/class-spdb-operational-mutation-guard.php' );
$client     = $read( 'assets/js/operations.js' );

foreach ( array( 'patient', 'diagnos', 'prescription', 'potency', 'dosage', 'auto[_-]?publish', 'delete', 'impersonat', 'mass[_-]?publish', 'cure[_-]?claim', 'change[_-]?author', 'export[_-]?patient' ) as $pattern ) {
	$assert( str_contains( $governance, $pattern ), "Automation guard must reject {$pattern}." );
}
$assert( str_contains( $governance, "'can_publish'             => false" ) && str_contains( $governance, "'can_export'              => false" ) && str_contains( $governance, "'can_access_patient_data' => false" ), 'Delegations must never silently grant publish, export, or patient access.' );
$assert( str_contains( $governance, 'session_two_factor' ) && str_contains( $governance, '90 * DAY_IN_SECONDS' ), 'Delegation creation must require current MFA and bounded expiry.' );
$assert( str_contains( $automation, 'human_confirmation' ) && str_contains( $automation, 'idempotent' ) && str_contains( $automation, 'spdb/automation_action_result' ), 'Automation execution must be human-governed, idempotent, and native-provider mediated.' );
$assert( preg_match( '/patient\|message\|email\|phone\|address\|token\|secret\|password\|consent\|document\|ip/', $automation ) === 1, 'Automation event payload must suppress sensitive fields.' );
$assert( str_contains( $activation, 'is_user_founder' ) && str_contains( $activation, 'session_two_factor' ) && str_contains( $activation, "'production' === \$environment" ), 'Staging acceptance must require Founder identity, MFA, and a non-production environment.' );
$assert( str_contains( $activation, "preg_match( '/\\A[a-f0-9]{40}\\z/'" ) && str_contains( $activation, "preg_match( '/\\A[a-f0-9]{64}\\z/'" ), 'Staging acceptance must bind evidence to an exact source commit and package SHA-256.' );
foreach ( array( 'role_matrix_evidence', 'provider_contract_evidence', 'cache_privacy_evidence', 'accessibility_evidence', 'backup_restore_evidence', 'rollback_evidence' ) as $evidence_key ) {
	$assert( str_contains( $activation, "'{$evidence_key}'" ), "Staging acceptance must require {$evidence_key}." );
}
$assert( str_contains( $activation, 'acceptance_version_valid' ) && str_contains( $activation, 'hash_equals( SPDB_VERSION' ), 'Acceptance must expire when the installed plugin version changes.' );
$assert( str_contains( $activation, 'spdb_activation_audit_failed' ) && str_contains( $activation, 'is_wp_error( $audit )' ) && str_contains( $activation, 'update_option( self::OPTION, $previous, false )' ), 'High-risk staging acceptance must roll back when its audit evidence cannot be persisted.' );
$assert( str_contains( $router, 'DONOTCACHEPAGE' ) && str_contains( $router, 'private, no-store' ) && str_contains( $router, 'noindex, nofollow, noarchive' ), 'Private dashboard route must be cache-excluded and non-indexable.' );
$assert( str_contains( $export, 'hash_equals' ) && str_contains( $export, 'hash_hmac' ) && str_contains( $export, 'get_current_user_id' ), 'Export downloads must verify owner-bound HMAC signatures.' );
$assert( str_contains( $export, 'spreadsheet_safe' ) && str_contains( $export, "'/^[=+\\-@]/'" ), 'CSV export must contain spreadsheet-formula neutralization.' );
$assert( str_contains( $validator, 'sensitive_key' ) && str_contains( $validator, 'SPDB_Safe_Destination::normalize' ) && str_contains( $destination, 'home_url' ) && str_contains( $destination, 'key_is_sensitive' ), 'Operational projections must filter sensitive metadata and use the centralized exact-origin, secret-free destination validator.' );
$assert( str_contains( $repository, 'previous_hash' ) && str_contains( $repository, 'event_hash' ) && str_contains( $repository, 'hash_equals' ), 'Dashboard audit must use an append-only integrity chain.' );
$assert( str_contains( $repository, 'idempotency_key' ) && str_contains( $repository, 'lock_token' ) && str_contains( $repository, 'dead_letter' ), 'Background jobs must use idempotency, locking, and dead-letter state.' );

$assert( str_contains( $guard, 'X-WP-Nonce' ) && str_contains( $guard, "wp_verify_nonce( \$nonce, 'wp_rest' )" ), 'Operational mutation endpoints must not rely on UI visibility or cookie authentication without an explicit REST nonce.' );
$assert( str_contains( $guard, "get_header( 'Origin' )" ) && str_contains( $guard, "get_header( 'Referer' )" ) && str_contains( $guard, 'same_origin_value' ), 'Operational mutations must reject cross-origin browser submissions.' );
$assert( str_contains( $guard, 'Idempotency-Key' ) && str_contains( $guard, 'spdb_mutation_idempotency_conflict' ) && str_contains( $guard, 'payload_hash' ), 'Reused idempotency keys with altered payloads must fail closed.' );
$assert( str_contains( $guard, 'START TRANSACTION' ) && str_contains( $guard, 'COMMIT' ) && str_contains( $guard, 'ROLLBACK' ), 'Local mutation, receipt, and canonical audit evidence must commit or roll back together.' );
$assert( str_contains( $guard, 'SPDB_Operations_Repository' ) && str_contains( $guard, 'mutation_requested' ) && str_contains( $guard, "'mutation_' . \$outcome" ), 'Request and outcome evidence must use the canonical hash-chained audit repository.' );
$assert( str_contains( $guard, 'sanitize_response_data' ) && preg_match( '/password\|secret\|token\|nonce\|cookie\|authorization\|otp\|cvv\|card\|patient\|clinical\|message_body/', $guard ) === 1, 'Replay receipts must remove secret, clinical, patient, and credential-shaped fields.' );
$assert( str_contains( $guard, "private, no-store, max-age=0" ) && str_contains( $guard, 'X-SPDB-Idempotent' ), 'Recorded and replayed mutation responses must remain private and explicitly marked.' );
$assert( ! str_contains( $guard, 'CREATE TABLE' ), 'The mutation guard must not create a shadow backend or alternate audit table.' );
$assert( str_contains( $client, "headers: { 'Idempotency-Key': requestKey }" ) && str_contains( $client, 'data-spdb-idempotency-key' ), 'The browser must retain a stable idempotency key for one logical submission.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} adversarial tests failed.\n" ); exit( 1 ); }
echo "All {$tests} full-plan adversarial tests passed.\n";
