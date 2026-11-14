<?php
/**
 * V12.3 WP-02/WP-13: a provider acceptance made on staging must not become a
 * production write grant simply because a copied option says production_accepted.
 * Source-only negative regression; live attestation remains external to File23.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';
$checks = 0; $failures = 0;
$check = static function ( bool $ok, string $label ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $ok ) { ++$failures; fwrite( STDERR, "FAIL: {$label}\n" ); }
};
$record = static function ( string $state, string $origin ): array {
	return array(
		'state' => $state,
		'provider_version' => '1.0.0',
		'contract_version' => SPDB_CONTRACT_VERSION,
		'plugin_version' => SPDB_VERSION,
		'environment' => $origin,
		'evidence_id' => 'release:synthetic:001',
		'evidence_hash' => str_repeat( 'a', 64 ),
	);
};
$setup = static function ( array $record ): SPDB_Adapter_Registry {
	$r = new SPDB_Adapter_Registry( array( 'provider_one' => $record ) );
	$result = $r->register( new SPDB_Test_Adapter() );
	if ( true !== $result ) {
		throw new RuntimeException( 'Synthetic provider registration failed.' );
	}
	return $r;
};
$GLOBALS['spdb_test_environment'] = 'production';
$staging_claim = $setup( $record( 'production_accepted', 'staging' ) );
$check( ! $staging_claim->is_environment_write_eligible( 'provider_one' ), 'Staging-origin production label must never authorize live native writes.' );
$check( 'unreviewed' === $staging_claim->get_acceptance_state( 'provider_one' ), 'Staging-origin production label must remain unreviewed on binding.' );

$dev_claim = $setup( $record( 'production_accepted', 'development' ) );
$check( ! $dev_claim->is_environment_write_eligible( 'provider_one' ), 'Development-origin acceptance cannot authorize production writes.' );

$missing_origin = $setup( $record( 'production_accepted', '' ) );
$check( ! $missing_origin->is_environment_write_eligible( 'provider_one' ), 'Missing acceptance environment must not authorize live writes.' );

$production = $setup( $record( 'production_accepted', 'production' ) );
$check( $production->is_environment_write_eligible( 'provider_one' ), 'Explicit matching production acceptance remains available at the source gate.' );

$revoked = $setup( $record( 'revoked', 'staging' ) );
$check( ! $revoked->is_environment_write_eligible( 'provider_one' ), 'Revocation remains fail-closed across environments.' );

$staging = $setup( $record( 'staging_accepted', 'staging' ) );
$check( ! $staging->is_environment_write_eligible( 'provider_one' ), 'Staging authorization cannot be promoted on a production runtime.' );

if ( $failures ) { fwrite( STDERR, "{$failures} of {$checks} V12.3 acceptance-environment checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 acceptance-environment checks passed; Hostinger trusted attestation still required.\n";
