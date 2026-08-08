<?php
/** Executable tests for least-privilege capability provisioning. */
require_once __DIR__ . '/bootstrap.php';

$roles = array(
	'administrator',
	'sabri_doctor_pending',
	'sabri_doctor_verified',
	'sabri_membership_reviewer',
	'sabri_membership_senior_reviewer',
	/* Migration aliases remain tested so old installations are reconciled safely. */
	'sabri_pending',
	'sabri_doctor',
	'sabri_verified_doctor',
	'sabri_medical_reviewer',
	'sabri_moderator',
);
foreach ( $roles as $role_key ) {
	$GLOBALS['spdb_test_roles'][ $role_key ] = new SPDB_Test_Role();
	$GLOBALS['spdb_test_roles'][ $role_key ]->add_cap( 'spdb_manage_safe_mode' );
}

$result = SPDB_Capability_Installer::ensure();
$failed = 0;
function spdb_cap_assert( bool $condition, string $message ): void {
	global $failed;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}

$admin = $GLOBALS['spdb_test_roles']['administrator'];
foreach ( SPDB_Capabilities::all() as $capability ) {
	spdb_cap_assert( ! empty( $admin->capabilities[ $capability ] ), "Administrator is missing {$capability}." );
}

foreach ( array( 'sabri_doctor_pending', 'sabri_pending' ) as $pending_key ) {
	$pending = $GLOBALS['spdb_test_roles'][ $pending_key ];
	spdb_cap_assert( ! empty( $pending->capabilities['spdb_view_dashboard'] ), "{$pending_key} needs restricted dashboard access." );
	spdb_cap_assert( ! empty( $pending->capabilities['spdb_view_own_content'] ), "{$pending_key} needs policy-limited owned-content visibility." );
	spdb_cap_assert( empty( $pending->capabilities['spdb_manage_own_content'] ), "{$pending_key} must not receive mutation authority." );
	spdb_cap_assert( empty( $pending->capabilities['spdb_export_reports'] ), "{$pending_key} must not receive report export authority." );
}

foreach ( array( 'sabri_doctor_verified', 'sabri_doctor', 'sabri_verified_doctor' ) as $doctor_key ) {
	$doctor = $GLOBALS['spdb_test_roles'][ $doctor_key ];
	spdb_cap_assert( ! empty( $doctor->capabilities['spdb_view_dashboard'] ), "{$doctor_key} needs dashboard access." );
	spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_own_content'] ), "{$doctor_key} needs own content-management capability." );
	spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_interactions'] ), "{$doctor_key} needs bounded interaction-management capability." );
	spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_tasks'] ), "{$doctor_key} needs bounded task-management capability." );
	spdb_cap_assert( ! empty( $doctor->capabilities['spdb_export_reports'] ), "{$doctor_key} needs privacy-filtered own-report export capability." );
	spdb_cap_assert( empty( $doctor->capabilities['spdb_view_global_analytics'] ), "{$doctor_key} must not receive global analytics authority." );
	spdb_cap_assert( empty( $doctor->capabilities['spdb_manage_automation_rules'] ), "{$doctor_key} must not receive institutional automation-rule authority." );
}

foreach ( array( 'sabri_membership_reviewer', 'sabri_membership_senior_reviewer', 'sabri_medical_reviewer' ) as $reviewer_key ) {
	$reviewer = $GLOBALS['spdb_test_roles'][ $reviewer_key ];
	spdb_cap_assert( ! empty( $reviewer->capabilities['spdb_view_review_queue'] ), "{$reviewer_key} needs the review queue capability." );
	spdb_cap_assert( ! empty( $reviewer->capabilities['spdb_review_assigned_content'] ), "{$reviewer_key} needs assigned-review authority." );
	spdb_cap_assert( empty( $reviewer->capabilities['spdb_manage_safe_mode'] ), "{$reviewer_key} must not receive Safe Mode authority." );
	spdb_cap_assert( empty( $reviewer->capabilities['spdb_manage_delegations'] ), "{$reviewer_key} must not receive delegation administration." );
}

spdb_cap_assert( '6' === get_option( 'spdb_capability_schema_version', '' ), 'Capability schema version 6 must be recorded.' );
spdb_cap_assert( '6' === (string) $result['schema_version'], 'Installer result must identify capability schema version 6.' );
spdb_cap_assert( ! empty( get_option( 'spdb_capability_role_fingerprint', '' ) ), 'Role inventory fingerprint must be recorded.' );
spdb_cap_assert( count( $roles ) === count( $result['roles_seen'] ), 'Every existing tested role must be reconciled.' );
spdb_cap_assert( count( $roles ) === (int) $result['capabilities_removed'], 'The retired File 20 Safe Mode capability must be removed from every managed role.' );
foreach ( $roles as $role_key ) {
	spdb_cap_assert( empty( $GLOBALS['spdb_test_roles'][ $role_key ]->capabilities['spdb_manage_safe_mode'] ), "Retired Safe Mode capability remains on {$role_key}." );
}

$second = SPDB_Capability_Installer::ensure();
spdb_cap_assert( 0 === $second['capabilities_added'], 'Capability reconciliation must be idempotent.' );
spdb_cap_assert( 0 === $second['capabilities_removed'], 'Retired-capability reconciliation must be idempotent.' );

if ( $failed > 0 ) { exit( 1 ); }
echo 'All File 23 capability-installer tests passed.' . PHP_EOL;
