<?php
/** Executable 1.3.0 studio-selection and institution-scope boundary tests. */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-workspace-adapter.php';

$tests = 0;
$failed = 0;
function spdb_studio_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) $GLOBALS['spdb_test_member_status']; } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return (bool) $GLOBALS['spdb_test_trusted']; } }

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_trusted'] = false;
$GLOBALS['spdb_test_environment'] = 'production';
$GLOBALS['spdb_test_capabilities'] = array(
	'spdb_view_dashboard' => true,
	'spdb_view_own_content' => true,
	'spdb_manage_own_content' => true,
);

$workspace_projection = array(
	'cards' => array(
		array(
			'key' => 'drafts', 'label' => 'Drafts', 'value' => 2, 'note' => 'Native measured count',
			'priority' => 'information', 'data_status' => 'measured', 'source_timestamp' => '2026-08-10T04:00:00Z',
			'scope' => 'own', 'owner_user_id' => 7,
		),
	),
	'actions' => array(
		array(
			'key' => 'create_article', 'label' => 'Create Article', 'description' => 'Open native composer.',
			'action_type' => 'professional_create', 'destination' => 'https://example.test/create/?type=article',
			'required_capability' => 'spdb_manage_own_content', 'mutating' => true, 'founder_only' => false,
			'scope' => 'own', 'owner_user_id' => 7,
		),
		array(
			'key' => 'official_news', 'label' => 'Official News', 'description' => 'Open native official composer.',
			'action_type' => 'official_create', 'destination' => 'https://example.test/create/?type=official_news',
			'required_capability' => 'spdb_manage_own_content', 'mutating' => true, 'founder_only' => true,
			'scope' => 'own', 'owner_user_id' => 7,
		),
	),
	'activity' => array(),
	'alerts' => array(),
	'profile' => null,
	'knowledge' => null,
);

$registry = new SPDB_Adapter_Registry( array( 'workspace_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
spdb_studio_assert( true === $registry->register( new SPDB_Test_Workspace_Adapter( array( 'workspace' => $workspace_projection ) ) ), 'Studio test workspace adapter must register.' );
$service = new SPDB_Role_Workspace_Service( $registry );
$resolver = new SPDB_Workspace_Resolver();
$input = array( 'key' => 'doctor', 'label' => 'Untrusted client label', 'read_only' => false, 'account_status' => 'approved', 'user_id' => 7 );

// Explicit Admin Studio capability selects an admin operational view, but never Founder scope.
$GLOBALS['spdb_test_capabilities']['spdb_view_admin_studio'] = true;
$resolved = $resolver->resolve( 7 );
spdb_studio_assert( 'admin' === $resolved['key'], 'Explicit Admin Studio capability must select the Admin Studio for an approved non-Founder.' );
$admin = $service->build( $input );
spdb_studio_assert( 'admin' === $admin['workspace_key'], 'Service must derive Admin Studio server-side and ignore the client workspace label.' );
spdb_studio_assert( 'admin_federated' === $admin['publishing_policy']['mode'], 'Admin Studio must use the federated admin policy.' );
spdb_studio_assert( array( 'own' ) === $GLOBALS['spdb_test_workspace_context']['allowed_scopes'], 'Admin Studio must not inherit Founder-only institution scope.' );
spdb_studio_assert( 1 === count( $admin['actions'] ) && 'professional_create' === $admin['actions'][0]['action_type'], 'Admin Studio may receive capability-allowed own action but not Founder-only action.' );
spdb_studio_assert( 1 === $admin['blocked_action_count'], 'Founder-only action must be explicitly gated for Admin Studio.' );

// Teacher Studio is explicit, bounded and also own-scope only.
$GLOBALS['spdb_test_capabilities']['spdb_view_admin_studio'] = false;
$GLOBALS['spdb_test_capabilities']['spdb_view_teacher_studio'] = true;
$resolved = $resolver->resolve( 7 );
spdb_studio_assert( 'teacher' === $resolved['key'], 'Explicit Teacher Studio capability must select the Teacher Studio for an approved non-Founder.' );
$teacher = $service->build( $input );
spdb_studio_assert( 'teacher' === $teacher['workspace_key'], 'Service must derive Teacher Studio server-side.' );
spdb_studio_assert( 'teacher_educational' === $teacher['publishing_policy']['mode'], 'Teacher Studio must use the bounded educational policy.' );
spdb_studio_assert( array( 'own' ) === $GLOBALS['spdb_test_workspace_context']['allowed_scopes'], 'Teacher Studio must remain own-scope unless a native owner independently authorizes a specific action.' );
spdb_studio_assert( 1 === $teacher['blocked_action_count'], 'Teacher Studio must not expose Founder-only action.' );

// Founder identity has precedence over both studio selector capabilities.
$GLOBALS['spdb_test_capabilities']['spdb_view_admin_studio'] = true;
$GLOBALS['spdb_test_founder'] = true;
$resolved = $resolver->resolve( 7 );
spdb_studio_assert( 'founder' === $resolved['key'], 'File 00 Founder identity must take precedence over Admin/Teacher selector capabilities.' );
$founder = $service->build( $input );
spdb_studio_assert( 'founder' === $founder['workspace_key'], 'Server-verified Founder must receive Founder Studio.' );
spdb_studio_assert( 'founder_official' === $founder['publishing_policy']['mode'], 'Founder must receive official publishing policy.' );
spdb_studio_assert( array( 'own', 'institution' ) === $GLOBALS['spdb_test_workspace_context']['allowed_scopes'], 'Institution scope must remain Founder-only.' );
spdb_studio_assert( 2 === count( $founder['actions'] ) && 0 === $founder['blocked_action_count'], 'Founder may receive both professional and Founder-only actions when provider/capability gates pass.' );

// A provider trying to project institution scope to Admin must fail closed as a provider error.
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_capabilities']['spdb_view_teacher_studio'] = false;
$GLOBALS['spdb_test_capabilities']['spdb_view_admin_studio'] = true;
$institution_projection = $workspace_projection;
$institution_projection['cards'][0]['scope'] = 'institution';
$institution_projection['cards'][0]['owner_user_id'] = 0;
$institution_registry = new SPDB_Adapter_Registry( array( 'institution_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
spdb_studio_assert( true === $institution_registry->register( new SPDB_Test_Workspace_Adapter( array( 'provider_key' => 'institution_provider', 'provider_name' => 'Institution Provider', 'workspace' => $institution_projection ) ) ), 'Institution projection test adapter must register.' );
$admin_institution = ( new SPDB_Role_Workspace_Service( $institution_registry ) )->build( $input );
spdb_studio_assert( 'admin' === $admin_institution['workspace_key'], 'Institution-scope negative test must still be an Admin Studio request.' );
spdb_studio_assert( 1 === $admin_institution['provider_errors'] && array() === $admin_institution['cards'], 'Admin institution projection must fail closed instead of leaking Founder-only scope.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} File 23 studio-boundary tests failed.\n" );
	exit( 1 );
}

echo "All {$tests} File 23 Founder/Admin/Teacher/Doctor studio-boundary tests passed.\n";
