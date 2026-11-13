<?php
/**
 * V12.1 deployed artifact identity: regression test before corrective patch.
 *
 * This tests the File23 verification boundary, NOT the actual Hostinger
 * attestation provider, package bytes or migration state.
 */
require_once __DIR__ . '/bootstrap.php';

$tests = 0;
$failed = 0;
$assert = static function ( bool $ok, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
};

$assert( method_exists( SPDB_Activation_Wizard::class, 'attestation_matches_record' ), 'Current deployment attestation verifier must exist.' );
if ( ! method_exists( SPDB_Activation_Wizard::class, 'attestation_matches_record' ) ) {
	fwrite( STDERR, "{$failed} of {$tests} V12.1 deployment-attestation tests failed.\n" );
	exit( 1 );
}

$reflect = new ReflectionMethod( SPDB_Activation_Wizard::class, 'attestation_matches_record' );
$reflect->setAccessible( true );
$now = 1794480000;
$source = str_repeat( 'a', 40 );
$package = str_repeat( 'b', 64 );
$accepted = array(
	'plugin_version' => SPDB_VERSION,
	'source_commit' => $source,
	'package_sha256' => $package,
	'environment' => 'staging',
);
$attested = array(
	'contract' => 'file23_deployed_artifact_v1',
	'verified' => true,
	'plugin_version' => SPDB_VERSION,
	'source_commit' => $source,
	'package_sha256' => $package,
	'environment' => 'staging',
	'operations_schema' => SPDB_Operations_Schema::VERSION,
	'collections_schema' => SPDB_Collections_Schema::VERSION,
	'migration_state' => 'complete',
	'observed_at_unix' => $now - 30,
);
$verify = static function ( $evidence, $measured, string $environment = 'staging', int $at = 1794480000 ) use ( $reflect ): bool {
	return true === $reflect->invoke( null, $evidence, $measured, $environment, $at );
};

$assert( $verify( $accepted, $attested ), 'Fresh matching independently supplied staging artifact/schema assertion may pass.' );
$assert( ! $verify( $accepted, null ), 'Missing runtime attestation must fail closed.' );
$assert( ! $verify( $accepted, array() ), 'Empty runtime attestation must fail closed.' );
$t = $attested; $t['verified'] = false;
$assert( ! $verify( $accepted, $t ), 'Unverified attestation must fail closed.' );
$t = $attested; $t['package_sha256'] = str_repeat( 'c', 64 );
$assert( ! $verify( $accepted, $t ), 'Same-version but different installed package hash must be denied.' );
$t = $attested; $t['source_commit'] = str_repeat( 'd', 40 );
$assert( ! $verify( $accepted, $t ), 'Wrong deployed commit must be denied.' );
$t = $attested; $t['plugin_version'] = '1.2.0';
$assert( ! $verify( $accepted, $t ), 'Different installed plugin version must be denied.' );
$t = $attested; $t['environment'] = 'development';
$assert( ! $verify( $accepted, $t ), 'Attested environment mismatch must be denied.' );
$assert( ! $verify( $accepted, $attested, 'production' ), 'Staging attestation cannot authorize a production environment.' );
$t = $attested; $t['operations_schema'] = 'stale';
$assert( ! $verify( $accepted, $t ), 'Outdated operations schema must fail.' );
$t = $attested; $t['collections_schema'] = 'stale';
$assert( ! $verify( $accepted, $t ), 'Outdated collections schema must fail.' );
$t = $attested; $t['migration_state'] = 'pending';
$assert( ! $verify( $accepted, $t ), 'Pending migration must fail closed.' );
$t = $attested; $t['observed_at_unix'] = $now - 3600;
$assert( ! $verify( $accepted, $t ), 'Stale measurement must be denied.' );
$t = $attested; $t['observed_at_unix'] = $now + 3600;
$assert( ! $verify( $accepted, $t ), 'Future-dated measurement must be denied.' );
$t = $attested; $t['contract'] = 'unknown';
$assert( ! $verify( $accepted, $t ), 'Unsupported attestation contract must be denied.' );
$old = $accepted; $old['package_sha256'] = str_repeat( 'e', 64 );
$assert( ! $verify( $old, $attested ), 'Old acceptance record cannot be reused after package replacement.' );
$old = $accepted; unset( $old['environment'] );
$assert( ! $verify( $old, $attested ), 'Acceptance without environment binding must be denied.' );
$old = $accepted; $old['source_commit'] = 'forged';
$assert( ! $verify( $old, $attested ), 'Malformed acceptance commit must be denied.' );
$t = $attested; $t['package_sha256'] = 'forged';
$assert( ! $verify( $accepted, $t ), 'Malformed attestation checksum must be denied.' );

$source_code = file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-activation-wizard.php' );
$assert( is_string( $source_code ) && false !== strpos( $source_code, "'spdb/file23_deployed_artifact_attestation'" ), 'Runtime must use a server-side attestation provider contract.' );
$assert( is_string( $source_code ) && false !== strpos( $source_code, "'spdb_activation_attestation_unverified'" ), 'New staging acceptance must fail closed when current artifact cannot be attested.' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} V12.1 deployment-attestation tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} V12.1 deployment-attestation tests passed.\n";
