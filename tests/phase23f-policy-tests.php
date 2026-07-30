<?php
/** Executable corrective Phase 23F collection and knowledge tests. */
require_once __DIR__ . '/bootstrap.php';

$tests = 0;
$failed = 0;
function spdb_23f_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}
function spdb_23f_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}
function spdb_23f_collection( array $overrides = array() ): array {
	return array_merge(
		array(
			'record_type'         => 'collection',
			'scope'               => 'own',
			'title'               => 'Classical Homeopathy Study Set',
			'objective'           => '',
			'ethical_declaration' => '',
			'contributors'        => array(),
			'target_surfaces'     => array(),
			'status'              => 'draft',
			'start_at_gmt'        => '',
			'end_at_gmt'          => '',
			'idempotency_key'     => 'collection-create-0001',
			'audit_reason'        => 'Create an owned cross-module study collection.',
		),
		$overrides
	);
}
function spdb_23f_link( array $overrides = array() ): array {
	return array_merge(
		array(
			'scope'               => 'own',
			'source_provider_key' => 'file21',
			'source_object_type'  => 'publication',
			'source_object_id'    => 'post-101',
			'target_provider_key' => 'file06',
			'target_object_type'  => 'remedy',
			'target_object_id'    => 'remedy-22',
			'relation_type'       => 'encyclopedia',
			'idempotency_key'     => 'knowledge-link-0001',
			'audit_reason'        => 'Connect the publication to its canonical encyclopedia remedy entry.',
		),
		$overrides
	);
}

if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) ( $user_id === $GLOBALS['spdb_test_user_id'] ? $GLOBALS['spdb_test_member_status'] : ( $GLOBALS['spdb_test_member_statuses'][ $user_id ] ?? 'unknown' ) ); } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_capabilities']['spdb_manage_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_campaigns'] = false;
$GLOBALS['spdb_test_user_capabilities'][7]['spdb_manage_own_content'] = true;
$GLOBALS['spdb_test_user_capabilities'][9]['spdb_manage_own_content'] = true;
$GLOBALS['spdb_test_member_statuses'][7] = 'approved';
$GLOBALS['spdb_test_member_statuses'][9] = 'approved';

$own = SPDB_Collections_Policy::validate_collection( spdb_23f_collection() );
spdb_23f_assert( is_array( $own ) && 'own' === $own['scope'], 'An approved authorized user may validate an own-scope collection.' );

$GLOBALS['spdb_test_capabilities']['spdb_manage_own_content'] = false;
$without_capability = SPDB_Collections_Policy::validate_collection( spdb_23f_collection() );
spdb_23f_assert( 'spdb_collections_own_forbidden' === spdb_23f_code( $without_capability ), 'Own-scope metadata requires the current management capability.' );
$GLOBALS['spdb_test_capabilities']['spdb_manage_own_content'] = true;

$GLOBALS['spdb_test_member_status'] = 'submitted';
$pending = SPDB_Collections_Policy::validate_collection( spdb_23f_collection() );
spdb_23f_assert( 'spdb_collections_account_forbidden' === spdb_23f_code( $pending ), 'Pending accounts must fail closed before metadata validation.' );
$GLOBALS['spdb_test_member_status'] = 'approved';

$institution_forbidden = SPDB_Collections_Policy::validate_collection( spdb_23f_collection( array( 'scope' => 'institution' ) ) );
spdb_23f_assert( 'spdb_collections_institution_forbidden' === spdb_23f_code( $institution_forbidden ), 'A non-Founder must not obtain institution scope.' );

$contributors_forbidden = SPDB_Collections_Policy::validate_collection( spdb_23f_collection( array( 'contributors' => array( 9 ) ) ) );
spdb_23f_assert( 'spdb_collection_contributors_forbidden' === spdb_23f_code( $contributors_forbidden ), 'Own-scope collections must not delegate contributor authority.' );

$campaign_fields = SPDB_Collections_Policy::validate_collection( spdb_23f_collection( array( 'target_surfaces' => array( 'news' ) ) ) );
spdb_23f_assert( 'spdb_collection_campaign_fields_forbidden' === spdb_23f_code( $campaign_fields ), 'Campaign-only targeting must not enter ordinary collection metadata.' );

$bad_date = SPDB_Collections_Policy::validate_collection( spdb_23f_collection( array( 'start_at_gmt' => '2026-02-31T00:00:00Z' ) ) );
spdb_23f_assert( 'spdb_collection_timestamp_invalid' === spdb_23f_code( $bad_date ), 'Impossible UTC timestamps must be rejected.' );

$GLOBALS['spdb_test_founder'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_campaigns'] = true;
$campaign = SPDB_Collections_Policy::validate_collection(
	spdb_23f_collection(
		array(
			'record_type'         => 'campaign',
			'scope'               => 'institution',
			'title'               => 'Source Citation Improvement',
			'objective'           => 'Improve source completeness across approved educational publications.',
			'ethical_declaration' => 'Use truthful information without pressure, scarcity, or medical guarantees.',
			'contributors'        => array( 7, 9 ),
			'target_surfaces'     => array( 'news', 'learning', 'encyclopedia' ),
			'start_at_gmt'        => '2026-08-01T00:00:00Z',
			'end_at_gmt'          => '2026-08-31T23:59:59Z',
			'idempotency_key'     => 'campaign-create-0001',
			'audit_reason'        => 'Create a governed source-quality campaign for approved content.',
		)
	)
);
spdb_23f_assert( is_array( $campaign ) && 'campaign' === $campaign['record_type'], 'A valid Founder campaign must normalize.' );

$duplicate_surface = SPDB_Collections_Policy::validate_collection( spdb_23f_collection( array( 'record_type' => 'campaign', 'scope' => 'institution', 'objective' => 'Improve source quality.', 'ethical_declaration' => 'Use truthful educational information.', 'target_surfaces' => array( 'news', 'news' ), 'start_at_gmt' => '2026-08-01T00:00:00Z', 'end_at_gmt' => '2026-08-31T23:59:59Z' ) ) );
spdb_23f_assert( 'spdb_collection_surfaces_invalid' === spdb_23f_code( $duplicate_surface ), 'Duplicate target surfaces must fail closed.' );

$unethical = SPDB_Collections_Policy::validate_collection( spdb_23f_collection( array( 'record_type' => 'campaign', 'scope' => 'institution', 'title' => 'Miracle cure launch', 'objective' => 'Act now for 100% success.', 'ethical_declaration' => 'Use educational surfaces.', 'target_surfaces' => array( 'news' ), 'start_at_gmt' => '2026-08-01T00:00:00Z', 'end_at_gmt' => '2026-08-02T00:00:00Z', 'idempotency_key' => 'campaign-create-0002' ) ) );
spdb_23f_assert( 'spdb_campaign_ethics_invalid' === spdb_23f_code( $unethical ), 'Miracle, urgency, fabricated-success, or cure-guarantee language must be rejected.' );

$sensitive = SPDB_Collections_Policy::validate_collection( spdb_23f_collection( array( 'title' => 'Contact patient@example.com' ) ) );
spdb_23f_assert( 'spdb_collection_title_invalid' === spdb_23f_code( $sensitive ), 'Sensitive contact data must not enter collection text.' );

$unknown_field = SPDB_Collections_Policy::validate_collection( spdb_23f_collection( array( 'content_body' => 'Forbidden native content.' ) ) );
spdb_23f_assert( 'spdb_collection_field_forbidden' === spdb_23f_code( $unknown_field ), 'Native content fields must fail closed.' );

$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_capabilities']['spdb_manage_campaigns'] = false;
$link = SPDB_Collections_Policy::validate_knowledge_link( spdb_23f_link() );
spdb_23f_assert( is_array( $link ) && 'own' === $link['scope'], 'A valid own-scope knowledge link must normalize.' );

$institution_link = SPDB_Collections_Policy::validate_knowledge_link( spdb_23f_link( array( 'scope' => 'institution' ) ) );
spdb_23f_assert( 'spdb_collections_institution_forbidden' === spdb_23f_code( $institution_link ), 'Institution knowledge links require Founder authority and capability.' );

$self_link = SPDB_Collections_Policy::validate_knowledge_link( spdb_23f_link( array( 'target_provider_key' => 'file21', 'target_object_type' => 'publication', 'target_object_id' => 'post-101', 'relation_type' => 'timeline' ) ) );
spdb_23f_assert( 'spdb_knowledge_self_link_invalid' === spdb_23f_code( $self_link ), 'A native object must not link to itself.' );

$bad_relation = SPDB_Collections_Policy::validate_knowledge_link( spdb_23f_link( array( 'relation_type' => 'unrestricted_relation' ) ) );
spdb_23f_assert( 'spdb_knowledge_relation_invalid' === spdb_23f_code( $bad_relation ), 'Unregistered knowledge relations must fail closed.' );

$service = new SPDB_Collections_Service();
$health = $service->health();
spdb_23f_assert( false === $health['repository_available'] && false === $health['resolver_available'] && false === $health['write_enabled'], 'The default Phase 23F runtime must report unavailable dependencies and disabled writes truthfully.' );
$disabled = $service->prepare_collection_create( spdb_23f_collection() );
spdb_23f_assert( 'spdb_phase23f_writes_disabled' === spdb_23f_code( $disabled ), 'Phase 23F writes must fail closed without an explicit staging-only enablement.' );
$read_unavailable = $service->list_collections( array( 'scope' => 'own', 'page' => 1, 'per_page' => 20 ) );
spdb_23f_assert( 'spdb_collections_repository_unavailable' === spdb_23f_code( $read_unavailable ), 'Runtime reads must report a missing repository instead of fabricating records.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} corrective Phase 23F tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} corrective Phase 23F policy and runtime-foundation tests passed.\n";
