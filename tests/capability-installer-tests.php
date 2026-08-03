<?php
/**
 * Executable tests for least-privilege capability provisioning.
 */

require_once __DIR__ . '/bootstrap.php';

$roles = array(
	'administrator',
	'sabri_pending',
	'sabri_doctor',
	'sabri_verified_doctor',
	'sabri_medical_reviewer',
	'sabri_moderator',
);
foreach ( $roles as $role_key ) {
	$GLOBALS['spdb_test_roles'][ $role_key ] = new SPDB_Test_Role();
}

$result = SPDB_Capability_Installer::ensure();
$failed = 0;

function spdb_cap_assert( bool $condition, string $message ): void {
	global $failed;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$admin = $GLOBALS['spdb_test_roles']['administrator'];
foreach ( SPDB_Capabilities::all() as $capability ) {
	spdb_cap_assert( ! empty( $admin->capabilities[ $capability ] ), "Administrator is missing {$capability}." );
}

$pending = $GLOBALS['spdb_test_roles']['sabri_pending'];
spdb_cap_assert( ! empty( $pending->capabilities['spdb_view_dashboard'] ), 'Pending members need restricted dashboard access.' );
spdb_cap_assert( ! empty( $pending->capabilities['spdb_view_own_content'] ), 'Pending members need policy-limited owned-content visibility.' );
spdb_cap_assert( empty( $pending->capabilities['spdb_manage_own_content'] ), 'Pending members must not receive mutation authority.' );
spdb_cap_assert( empty( $pending->capabilities['spdb_export_reports'] ), 'Pending members must not receive report export authority.' );

$doctor = $GLOBALS['spdb_test_roles']['sabri_doctor'];
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_view_dashboard'] ), 'Doctors need dashboard access.' );
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_own_content'] ), 'Approved doctors need their own content-management capability.' );
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_interactions'] ), 'Approved doctors need bounded interaction-management capability.' );
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_tasks'] ), 'Approved doctors need bounded task-management capability.' );
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_export_reports'] ), 'Approved doctors need privacy-filtered own-report export capability.' );
spdb_cap_assert( empty( $doctor->capabilities['spdb_view_global_analytics'] ), 'Doctors must not receive global analytics authority.' );
spdb_cap_assert( empty( $doctor->capabilities['spdb_manage_automation_rules'] ), 'Doctors must not receive institutional automation-rule authority.' );

$reviewer = $GLOBALS['spdb_test_roles']['sabri_medical_reviewer'];
spdb_cap_assert( ! empty( $reviewer->capabilities['spdb_view_review_queue'] ), 'Medical reviewers need the review queue capability.' );
spdb_cap_assert( ! empty( $reviewer->capabilities['spdb_review_assigned_content'] ), 'Medical reviewers need assigned-review authority.' );
spdb_cap_assert( empty( $reviewer->capabilities['spdb_manage_safe_mode'] ), 'Reviewers must not receive Safe Mode authority.' );
spdb_cap_assert( empty( $reviewer->capabilities['spdb_manage_delegations'] ), 'Reviewers must not receive delegation administration.' );

spdb_cap_assert( '2' === get_option( 'spdb_capability_schema_version', '' ), 'Capability schema version 2 must be recorded.' );
spdb_cap_assert( '2' === (string) $result['schema_version'], 'Installer result must identify capability schema version 2.' );
spdb_cap_assert( ! empty( get_option( 'spdb_capability_role_fingerprint', '' ) ), 'Role inventory fingerprint must be recorded.' );
spdb_cap_assert( count( $roles ) === count( $result['roles_seen'] ), 'Every existing tested role must be reconciled.' );

$second = SPDB_Capability_Installer::ensure();
spdb_cap_assert( 0 === $second['capabilities_added'], 'Capability reconciliation must be idempotent.' );

if ( $failed > 0 ) {
	exit( 1 );
}

echo 'All File 23 capability-installer tests passed.' . PHP_EOL;
