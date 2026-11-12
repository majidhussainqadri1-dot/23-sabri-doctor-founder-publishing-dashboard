<?php
/**
 * V12.1 File 00 privileged export assertion consumer regression.
 * Source-unit test only. The true File 00 provider contract needs Hostinger staging.
 */
require_once __DIR__ . '/bootstrap.php';

$GLOBALS['spdb_test_v121_members'] = array();
class SPDB_Membership_Guard {
	public static function assertions( int $user_id ): array {
		return $GLOBALS['spdb_test_v121_members'][ $user_id ] ?? array();
	}
}
class SPDB_Capabilities {
	public static function current_user_can( string $capability ): bool {
		return current_user_can( $capability );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-spdb-export-service.php';
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$check = new ReflectionMethod( SPDB_Export_Service::class, 'user_can_export' );
$check->setAccessible( true );
$current = new ReflectionMethod( SPDB_Export_Service::class, 'current_user_can_export' );
$current->setAccessible( true );
$tests = 0;
$failed = 0;
$assert = static function ( bool $ok, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
};

$actor = 7;
$GLOBALS['spdb_test_user_id'] = $actor;
$GLOBALS['spdb_test_user_capabilities'][ $actor ]['spdb_export_reports'] = true;
$GLOBALS['spdb_test_v121_members'][ $actor ] = array(
	'approved' => true,
	'eligible' => true,
	'suspended' => false,
	'session_two_factor' => true,
);

$assert( true === $check->invoke( $service, $actor, true ), 'Approved eligible actor with live 2FA and export capability may export.' );
$assert( true === $current->invoke( $service ), 'Interactive export requires current File 00 assertions.' );

$GLOBALS['spdb_test_v121_members'][ $actor ]['session_two_factor'] = false;
$assert( false === $check->invoke( $service, $actor, true ), 'Missing current 2FA must deny interactive export.' );
$assert( false === $current->invoke( $service ), 'Current session 2FA revocation must immediately deny interactive export.' );
$assert( true === $check->invoke( $service, $actor, false ), 'A background owner check uses account/capability without assuming interactive session state.' );

$GLOBALS['spdb_test_v121_members'][ $actor ]['suspended'] = true;
$assert( false === $check->invoke( $service, $actor, false ), 'Suspended actor must be denied even in a background job.' );
$GLOBALS['spdb_test_v121_members'][ $actor ]['suspended'] = false;
$GLOBALS['spdb_test_v121_members'][ $actor ]['eligible'] = false;
$assert( false === $check->invoke( $service, $actor, false ), 'Ineligible actor must be denied.' );
$GLOBALS['spdb_test_v121_members'][ $actor ]['eligible'] = true;
$GLOBALS['spdb_test_v121_members'][ $actor ]['approved'] = false;
$assert( false === $check->invoke( $service, $actor, false ), 'Unapproved actor must be denied.' );
$GLOBALS['spdb_test_v121_members'][ $actor ]['approved'] = true;

$GLOBALS['spdb_test_user_capabilities'][ $actor ]['spdb_export_reports'] = false;
$assert( false === $check->invoke( $service, $actor, false ), 'Current export capability revocation must deny.' );
$assert( false === $check->invoke( $service, 0, true ), 'Anonymous actor cannot export.' );
$assert( false === $check->invoke( $service, 999, false ), 'Unknown user must be denied.' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} V12.1 export assertion consumer tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} V12.1 export assertion consumer tests passed.\n";
