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
	'sabri_teacher',
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
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$admin = $GLOBALS['spdb_test_roles']['administrator'];
foreach ( SPDB_Capabilities::all() as $capability ) {
	spdb_cap_assert( ! empty( $admin->capabilities[ $capability ] ), "Administrator is missing {$capability}." );
}
spdb_cap_assert( ! empty( $admin->capabilities['spdb_view_admin_studio'] ), 'Administrator must receive the explicit Admin Studio selector.' );

$pending = $GLOBALS['spdb_test_roles']['sabri_pending'];
spdb_cap_assert( ! empty( $pending->capabilities['spdb_view_dashboard'] ), 'Pending members need restricted dashboard access.' );
spdb_cap_assert( ! empty( $pending->capabilities['spdb_view_own_content'] ), 'Pending members need policy-limited owned-content visibility.' );
spdb_cap_assert( empty( $pending->capabilities['spdb_manage_own_content'] ), 'Pending members must not receive mutation authority.' );
spdb_cap_assert( empty( $pending->capabilities['spdb_export_reports'] ), 'Pending members must not receive report export authority.' );
spdb_cap_assert( empty( $pending->capabilities['spdb_view_teacher_studio'] ), 'Pending members must not receive Teacher Studio authority.' );
spdb_cap_assert( empty( $pending->capabilities['spdb_view_admin_studio'] ), 'Pending members must not receive Admin Studio authority.' );

$doctor = $GLOBALS['spdb_test_roles']['sabri_doctor'];
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_view_dashboard'] ), 'Doctors need dashboard access.' );
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_own_content'] ), 'Approved doctors need their own content-management capability.' );
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_interactions'] ), 'Approved doctors need bounded interaction-management capability.' );
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_manage_tasks'] ), 'Approved doctors need bounded task-management capability.' );
spdb_cap_assert( ! empty( $doctor->capabilities['spdb_export_reports'] ), 'Approved doctors need privacy-filtered own-report export capability.' );
spdb_cap_assert( empty( $doctor->capabilities['spdb_view_global_analytics'] ), 'Doctors must not receive global analytics authority.' );
spdb_cap_assert( empty( $doctor->capabilities['spdb_manage_automation_rules'] ), 'Doctors must not receive institutional automation-rule authority.' );
spdb_cap_assert( empty( $doctor->capabilities['spdb_view_teacher_studio'] ), 'Doctors must not receive Teacher Studio selector by default.' );
spdb_cap_assert( empty( $doctor->capabilities['spdb_view_admin_studio'] ), 'Doctors must not receive Admin Studio selector.' );

$teacher = $GLOBALS['spdb_test_roles']['sabri_teacher'];
spdb_cap_assert( ! empty( $teacher->capabilities['spdb_view_dashboard'] ), 'Teachers need the private dashboard when their existing role is present.' );
spdb_cap_assert( ! empty( $teacher->capabilities['spdb_view_teacher_studio'] ), 'Existing teachers must receive the bounded Teacher Studio selector.' );
spdb_cap_assert( ! empty( $teacher->capabilities['spdb_manage_own_content'] ), 'Teachers need authorized own educational content operations.' );
spdb_cap_assert( ! empty( $teacher->capabilities['spdb_view_own_analytics'] ), 'Teachers need privacy-filtered own educational analytics.' );
spdb_cap_assert( empty( $teacher->capabilities['spdb_view_admin_studio'] ), 'Teachers must not receive Admin Studio authority.' );
spdb_cap_assert( empty( $teacher->capabilities['spdb_view_global_analytics'] ), 'Teachers must not receive global analytics authority.' );
spdb_cap_assert( empty( $teacher->capabilities['spdb_manage_delegations'] ), 'Teachers must not receive delegation administration by default.' );

$reviewer = $GLOBALS['spdb_test_roles']['sabri_medical_reviewer'];
spdb_cap_assert( ! empty( $reviewer->capabilities['spdb_view_review_queue'] ), 'Medical reviewers need the review queue capability.' );
spdb_cap_assert( ! empty( $reviewer->capabilities['spdb_review_assigned_content'] ), 'Medical reviewers need assigned-review authority.' );
spdb_cap_assert( empty( $reviewer->capabilities['spdb_manage_safe_mode'] ), 'Reviewers must not receive Safe Mode authority.' );
spdb_cap_assert( empty( $reviewer->capabilities['spdb_manage_delegations'] ), 'Reviewers must not receive delegation administration.' );
spdb_cap_assert( empty( $reviewer->capabilities['spdb_view_admin_studio'] ), 'Reviewers must not receive Admin Studio authority.' );

spdb_cap_assert( '5' === get_option( 'spdb_capability_schema_version', '' ), 'Capability schema version 5 must be recorded.' );
spdb_cap_assert( '5' === (string) $result['schema_version'], 'Installer result must identify capability schema version 5.' );
spdb_cap_assert( ! empty( get_option( 'spdb_capability_role_fingerprint', '' ) ), 'Role inventory fingerprint must be recorded.' );
spdb_cap_assert( count( $roles ) === count( $result['roles_seen'] ), 'Every existing tested role must be reconciled.' );
spdb_cap_assert( count( $roles ) === (int) $result['capabilities_removed'], 'The retired File 20 Safe Mode capability must be removed from every managed role.' );
foreach ( $roles as $role_key ) { spdb_cap_assert( empty( $GLOBALS['spdb_test_roles'][ $role_key ]->capabilities['spdb_manage_safe_mode'] ), "Retired Safe Mode capability remains on {$role_key}." ); }

$second = SPDB_Capability_Installer::ensure();
spdb_cap_assert( 0 === $second['capabilities_added'], 'Capability reconciliation must be idempotent.' );
spdb_cap_assert( 0 === $second['capabilities_removed'], 'Retired-capability reconciliation must be idempotent.' );

if ( $failed > 0 ) {
	exit( 1 );
}

echo 'All File 23 capability-installer tests passed.' . PHP_EOL;
