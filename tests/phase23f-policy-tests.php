<?php
/** Executable Phase 23F collection and knowledge policy tests. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-policy.php';

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

if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) $GLOBALS['spdb_test_member_status']; } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_founder'] = false;

$own = SPDB_Collections_Policy::validate_collection(
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
	)
);
spdb_23f_assert( is_array( $own ) && 'own' === $own['scope'], 'An approved user may validate an own-scope collection.' );

$forbidden = SPDB_Collections_Policy::validate_collection(
	array(
		'record_type'         => 'collection',
		'scope'               => 'institution',
		'title'               => 'Institution Set',
		'objective'           => '',
		'ethical_declaration' => '',
		'contributors'        => array(),
		'target_surfaces'     => array(),
		'status'              => 'draft',
		'start_at_gmt'        => '',
		'end_at_gmt'          => '',
		'idempotency_key'     => 'collection-create-0002',
		'audit_reason'        => 'Attempt an institution collection without Founder authority.',
	)
);
spdb_23f_assert( 'spdb_collection_institution_forbidden' === spdb_23f_code( $forbidden ), 'A non-Founder must not obtain institution scope.' );

$GLOBALS['spdb_test_founder'] = true;
$campaign = SPDB_Collections_Policy::validate_collection(
	array(
		'record_type'         => 'campaign',
		'scope'               => 'institution',
		'title'               => 'Source Citation Improvement',
		'objective'           => 'Improve source completeness across approved educational publications.',
		'ethical_declaration' => 'Use truthful information without pressure, scarcity, or medical guarantees.',
		'contributors'        => array( 7, 9 ),
		'target_surfaces'     => array( 'news', 'learning', 'encyclopedia' ),
		'status'              => 'draft',
		'start_at_gmt'        => '2026-08-01T00:00:00Z',
		'end_at_gmt'          => '2026-08-31T23:59:59Z',
		'idempotency_key'     => 'campaign-create-0001',
		'audit_reason'        => 'Create a governed source-quality campaign for approved content.',
	)
);
spdb_23f_assert( is_array( $campaign ) && 'campaign' === $campaign['record_type'], 'A valid Founder campaign must normalize.' );

$unethical = SPDB_Collections_Policy::validate_collection(
	array(
		'record_type'         => 'campaign',
		'scope'               => 'institution',
		'title'               => 'Invalid Campaign',
		'objective'           => 'Act now for a guaranteed cure.',
		'ethical_declaration' => 'Use educational surfaces.',
		'contributors'        => array(),
		'target_surfaces'     => array( 'news' ),
		'status'              => 'draft',
		'start_at_gmt'        => '2026-08-01T00:00:00Z',
		'end_at_gmt'          => '2026-08-02T00:00:00Z',
		'idempotency_key'     => 'campaign-create-0002',
		'audit_reason'        => 'Test rejection of prohibited campaign language.',
	)
);
spdb_23f_assert( 'spdb_campaign_ethics_invalid' === spdb_23f_code( $unethical ), 'Fear, urgency, or cure guarantees must be rejected.' );

$link = SPDB_Collections_Policy::validate_knowledge_link(
	array(
		'source_provider_key' => 'file21',
		'source_object_type'  => 'publication',
		'source_object_id'    => 'post-101',
		'target_provider_key' => 'file06',
		'target_object_type'  => 'remedy',
		'target_object_id'    => 'remedy-22',
		'relation_type'       => 'encyclopedia',
		'idempotency_key'     => 'knowledge-link-0001',
		'audit_reason'        => 'Connect the publication to its canonical encyclopedia remedy entry.',
	)
);
spdb_23f_assert( is_array( $link ) && 'encyclopedia' === $link['relation_type'], 'A valid cross-module knowledge link must normalize.' );

$self_link = SPDB_Collections_Policy::validate_knowledge_link(
	array(
		'source_provider_key' => 'file21',
		'source_object_type'  => 'publication',
		'source_object_id'    => 'post-101',
		'target_provider_key' => 'file21',
		'target_object_type'  => 'publication',
		'target_object_id'    => 'post-101',
		'relation_type'       => 'timeline',
		'idempotency_key'     => 'knowledge-link-0002',
		'audit_reason'        => 'Test rejection of a self-referential relationship.',
	)
);
spdb_23f_assert( 'spdb_knowledge_self_link_invalid' === spdb_23f_code( $self_link ), 'A native object must not link to itself.' );

$native_body = SPDB_Collections_Policy::validate_collection(
	array(
		'record_type'     => 'collection',
		'scope'           => 'own',
		'title'           => 'Invalid Native Copy',
		'content_body'    => 'File 23 must not own this field.',
		'idempotency_key' => 'collection-create-0003',
		'audit_reason'    => 'Test the native content ownership boundary.',
	)
);
spdb_23f_assert( 'spdb_collection_field_forbidden' === spdb_23f_code( $native_body ), 'Native content fields must fail closed.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23F policy tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Phase 23F policy tests passed.\n";
