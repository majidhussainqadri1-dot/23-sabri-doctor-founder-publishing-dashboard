<?php
/** Security, privacy and acceptance regressions for File 23 FPI-01..24. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['spdb_test_logged_in'] = true;
$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_caps'] = array(
	'spdb_view_dashboard' => true,
	'spdb_view_own_analytics' => true,
	'spdb_view_global_analytics' => false,
);
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_institutional'] = false;
$GLOBALS['spdb_test_signals'] = array();
$GLOBALS['spdb_test_ask_response'] = null;
$GLOBALS['spdb_test_privacy_threshold'] = 20;
$GLOBALS['spdb_test_ask_calls'] = 0;

function is_user_logged_in() { return (bool) $GLOBALS['spdb_test_logged_in']; }
function get_current_user_id() { return (int) $GLOBALS['spdb_test_user_id']; }
function apply_filters( $hook, $value, ...$args ) {
	if ( 'spdb/publishing_intelligence_signals' === $hook ) {
		return $GLOBALS['spdb_test_signals'][ (string) ( $args[0] ?? '' ) ] ?? array();
	}
	if ( 'spdb/publishing_intelligence_ask' === $hook ) {
		++$GLOBALS['spdb_test_ask_calls'];
		return $GLOBALS['spdb_test_ask_response'];
	}
	if ( 'spdb/publishing_intelligence_privacy_threshold' === $hook ) {
		return (int) $GLOBALS['spdb_test_privacy_threshold'];
	}
	return $value;
}

final class SPDB_Membership_Guard {
	public static function can_user_view_restricted_dashboard( int $user_id ): bool { return $user_id > 0; }
	public static function is_user_founder( int $user_id ): bool { return $user_id > 0 && (bool) $GLOBALS['spdb_test_founder']; }
	public static function assertions( int $user_id ): array {
		return array(
			'institutional_account' => (bool) $GLOBALS['spdb_test_institutional'],
			'approved' => true,
			'eligible' => true,
			'suspended' => false,
		);
	}
}
final class SPDB_Capabilities {
	public static function current_user_can( string $capability ): bool { return ! empty( $GLOBALS['spdb_test_caps'][ $capability ] ); }
}
final class SPDB_Test_Request {
	private array $params;
	private string $route;
	public function __construct( array $params = array(), string $route = '' ) { $this->params = $params; $this->route = $route; }
	public function get_param( string $key ) { return $this->params[ $key ] ?? null; }
	public function get_route(): string { return $this->route; }
}

require_once dirname( __DIR__ ) . '/includes/class-spdb-publishing-intelligence.php';

$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

// Catalog contract and stable keys.
$catalog = SPDB_Publishing_Intelligence::catalog();
$assert( 24 === count( $catalog ), 'FPI catalog must remain exactly 24 features.' );
foreach ( $catalog as $key => $feature ) {
	$assert( $key === $feature['key'], 'Catalog key must match stable REST feature key: ' . $key );
}

// Permission and scope law.
$req = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'mission-control' ) );
$assert( SPDB_Publishing_Intelligence::rest_can_view( $req ), 'Own-scope analytics user with dashboard capability should be allowed.' );
$GLOBALS['spdb_test_caps']['spdb_view_dashboard'] = false;
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $req ), 'FPI routes must require spdb_view_dashboard.' );
$GLOBALS['spdb_test_caps']['spdb_view_dashboard'] = true;
$institution = new SPDB_Test_Request( array( 'scope' => 'institution', 'feature' => 'audience-board' ) );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $institution ), 'Own analytics capability must not escalate to institution scope.' );
$GLOBALS['spdb_test_caps']['spdb_view_global_analytics'] = true;
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $institution ), 'Global capability without current institutional assertion must still fail.' );
$GLOBALS['spdb_test_institutional'] = true;
$assert( SPDB_Publishing_Intelligence::rest_can_view( $institution ), 'Global capability plus current institutional assertion may view institution scope.' );
$simreq = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'permission-simulator' ) );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $simreq ), 'Role/permission simulator must be Founder-only.' );
$GLOBALS['spdb_test_founder'] = true;
$assert( SPDB_Publishing_Intelligence::rest_can_view( $simreq ), 'Founder may access the read-only permission simulator.' );

// Provider signal privacy minimization.
$GLOBALS['spdb_test_signals']['privacy-leak-guard'] = array(
	array( 'code' => 'pii', 'severity' => 'high', 'patient_name' => 'Secret Patient', 'phone' => '+92-300-0000000', 'private_message' => 'secret', 'remediation' => 'remove field' ),
);
$privacy = SPDB_Publishing_Intelligence::snapshot( 'privacy-leak-guard' );
$encoded = json_encode( $privacy );
$assert( false === strpos( $encoded, 'Secret Patient' ) && false === strpos( $encoded, '300-0000000' ), 'Privacy Leak Guard must never echo raw patient identifiers.' );
$assert( true === $privacy['data']['privacy_minimized'] && false === $privacy['data']['raw_identifiers_returned'], 'Privacy Leak Guard must declare minimized output.' );

// Strong small-cohort suppression for experiments.
$GLOBALS['spdb_test_signals']['experiment-lab'] = array(
	array( 'experiment_id' => 'e1', 'cohort_count' => 4, 'winner' => 'B', 'uplift' => 9.2, 'variant_metrics' => array( 'A' => 1, 'B' => 2 ), 'raw_values' => array( 1, 2, 3 ) ),
);
$exp = SPDB_Publishing_Intelligence::snapshot( 'experiment-lab' );
$small = $exp['data']['experiments'][0];
$assert( true === $small['suppressed'], 'Small experiment cohort must be suppressed.' );
$assert( ! isset( $small['winner'], $small['uplift'], $small['variant_metrics'], $small['raw_values'] ), 'Suppressed experiment must expose no winner/uplift/variant/raw metrics.' );
$assert( true === $small['manual_acceptance_required'], 'Experiment winner must require human acceptance.' );

// Best-time: explicit timezone, no profiling, unavailable state.
$GLOBALS['spdb_test_signals']['best-time'] = array( array( 'window' => '09:00-10:00', 'score' => 0.8, 'observations' => 30 ) );
$best = SPDB_Publishing_Intelligence::snapshot( 'best-time', array( 'timezone' => 'Asia/Karachi' ) );
$assert( 'Asia/Karachi' === $best['data']['timezone'] && 'Asia/Karachi' === $best['data']['recommended_windows'][0]['timezone'], 'Best-time result must explicitly carry timezone.' );
$assert( false === $best['data']['individual_profiling'] && false === $best['data']['auto_schedule'], 'Best-time must prohibit profiling and automatic scheduling.' );
$GLOBALS['spdb_test_signals']['best-time'] = array();
$best_empty = SPDB_Publishing_Intelligence::snapshot( 'best-time', array( 'timezone' => 'Asia/Karachi' ) );
$assert( 'unavailable' === $best_empty['data']['status'], 'Missing best-time provider signal must be explicit Unavailable.' );

// Ask Dashboard: sensitive input never reaches provider; sensitive provider output suppressed.
$GLOBALS['spdb_test_ask_calls'] = 0;
$ask_sensitive = SPDB_Publishing_Intelligence::ask( 'Patient email is user@example.com' );
$assert( 'rejected_sensitive_input' === $ask_sensitive['status'] && 0 === $GLOBALS['spdb_test_ask_calls'], 'Sensitive Ask prompt must be rejected before provider invocation.' );
$GLOBALS['spdb_test_ask_response'] = array( 'answer' => 'Call phone: +92-3001234567' );
$ask_bad = SPDB_Publishing_Intelligence::ask( 'What should I review today?' );
$assert( 'provider_response_rejected' === $ask_bad['status'] && '' === $ask_bad['answer'], 'Sensitive provider answer must be suppressed.' );
$GLOBALS['spdb_test_ask_response'] = array(
	'answer' => 'Review the overdue evidence queue.',
	'sources' => array( array( 'source_id' => 's1', 'title' => 'Source', 'patient_name' => 'Hidden', 'private_note' => 'Nope' ) ),
);
$ask_good = SPDB_Publishing_Intelligence::ask( 'What should I review today?' );
$assert( 'answered' === $ask_good['status'] && isset( $ask_good['question_hash'] ) && ! isset( $ask_good['question'] ), 'Ask response must use audit-safe question hash rather than echo prompt.' );
$assert( false === strpos( json_encode( $ask_good ), 'Hidden' ), 'Ask sources must be allowlisted and privacy-minimized.' );

// Mission Control should prioritize safely without leaking reviewer notes.
$GLOBALS['spdb_test_signals']['mission-control'] = array(
	array( 'label' => 'Normal', 'severity' => 'normal', 'reviewer_note' => 'private' ),
	array( 'label' => 'Critical', 'severity' => 'critical', 'due_at' => '2026-08-10T00:00:00Z' ),
);
$mission = SPDB_Publishing_Intelligence::snapshot( 'mission-control' );
$assert( 'Critical' === $mission['data']['items'][0]['label'], 'Mission Control must prioritize critical items.' );
$assert( false === strpos( json_encode( $mission ), 'private' ) && true === $mission['data']['click_time_reauthorization'], 'Mission Control must omit private notes and require click-time reauthorization.' );

// Opportunity Radar remains File 26-owned and carries provenance/stale label capability.
$GLOBALS['spdb_test_signals']['opportunity-radar'] = array( array( 'topic' => 'T', 'demand' => 0.9, 'coverage' => 0.2, 'provenance' => 'file26', 'stale' => true ) );
$op = SPDB_Publishing_Intelligence::snapshot( 'opportunity-radar' );
$assert( 'file26' === $op['data']['ranking_owner'] && false === $op['data']['ranking_write_authority'], 'Opportunity Radar must never take File 26 ranking ownership.' );
$assert( true === $op['data']['opportunities'][0]['stale'] && 'file26' === $op['data']['opportunities'][0]['provenance'], 'Opportunity signal must preserve stale/provenance markers.' );

// Bottleneck detector must not reveal reviewer notes.
$GLOBALS['spdb_test_signals']['bottleneck-detector'] = array( array( 'stage' => 'review', 'wait_hours' => 9, 'reviewer_note' => 'private reviewer note', 'responsible_queue' => 'clinical-review' ) );
$bot = SPDB_Publishing_Intelligence::snapshot( 'bottleneck-detector' );
$assert( false === strpos( json_encode( $bot ), 'private reviewer note' ) && false === $bot['data']['private_reviewer_notes_included'], 'Bottleneck output must not expose private reviewer notes.' );

// SLA: resolved does not re-alert; invalid due date is not silently on-track; timezone explicit.
$GLOBALS['spdb_test_signals']['review-sla'] = array(
	array( 'object_id' => 'r1', 'due_at' => '2026-08-09 09:00:00', 'status' => 'resolved' ),
	array( 'object_id' => 'r2', 'due_at' => 'not-a-date', 'status' => 'pending' ),
);
$sla = SPDB_Publishing_Intelligence::snapshot( 'review-sla', array( 'timezone' => 'Asia/Karachi', 'now' => strtotime( '2026-08-10 10:00:00 Asia/Karachi' ) ) );
$assert( 1 === $sla['data']['counts']['resolved'] && 1 === $sla['data']['counts']['invalid'], 'SLA must distinguish resolved and invalid dates.' );
$assert( false === $sla['data']['items'][0]['realert_allowed'] && false === $sla['data']['items'][0]['escalation_candidate'], 'Resolved review must never be re-alerted.' );
$assert( 'Asia/Karachi' === $sla['data']['timezone'] && 'file19' === $sla['data']['notification_owner'], 'SLA must be timezone-explicit and keep File 19 notification ownership.' );

// Change impact, evidence freshness and medical preflight invariants.
$GLOBALS['spdb_test_signals']['change-impact-map'] = array( array( 'source_id' => 'a', 'affected_id' => 'b', 'relationship' => 'translation' ) );
$impact = SPDB_Publishing_Intelligence::snapshot( 'change-impact-map' );
$assert( true === $impact['data']['read_only'] && false === $impact['data']['destructive_cascade'], 'Change impact map must remain read-only and non-destructive.' );
$GLOBALS['spdb_test_signals']['evidence-freshness'] = array( array( 'source_id' => 's', 'status' => 'broken-evidence', 'revision' => '1', 'expected_revision' => '2' ) );
$freshness = SPDB_Publishing_Intelligence::snapshot( 'evidence-freshness' );
$assert( 'broken' === $freshness['data']['items'][0]['freshness_status'] && true === $freshness['data']['items'][0]['revision_mismatch'], 'Evidence freshness must normalize broken evidence and show revision mismatch.' );
$GLOBALS['spdb_test_signals']['medical-preflight'] = array( array( 'code' => 'guaranteed-cure', 'severity' => 'high', 'patient_name' => 'Hidden' ) );
$medical = SPDB_Publishing_Intelligence::snapshot( 'medical-preflight' );
$assert( false === $medical['data']['auto_block'] && 'native_owner_or_human_reviewer' === $medical['data']['decision_authority'], 'Medical preflight remains advisory with native/human decision authority.' );
$assert( false === strpos( json_encode( $medical ), 'Hidden' ), 'Medical preflight must not leak patient identifiers.' );

// Permission simulator projects only native decisions and does not impersonate or grant.
$GLOBALS['spdb_test_signals']['permission-simulator'] = array( array( 'role' => 'doctor', 'action' => 'publish', 'allowed' => false, 'reason_code' => 'review-required', 'object_id' => 'secret-object' ) );
$perm = SPDB_Publishing_Intelligence::snapshot( 'permission-simulator' );
$assert( true === $perm['data']['founder_only'] && false === $perm['data']['impersonation'] && false === $perm['data']['capability_grant'], 'Permission simulator must be Founder-only and non-impersonating/non-granting.' );
$assert( false === strpos( json_encode( $perm ), 'secret-object' ), 'Denied permission projection must not leak object existence/details.' );

// Semantic diff, playbooks and repurposing.
$GLOBALS['spdb_test_signals']['semantic-diff'] = array( array( 'classification' => 'clinical', 'uncertainty' => 0.25, 'summary' => 'Claim meaning changed.' ) );
$diff = SPDB_Publishing_Intelligence::snapshot( 'semantic-diff' );
$assert( 'clinical' === $diff['data']['items'][0]['classification'] && 0.25 === $diff['data']['items'][0]['uncertainty'], 'Semantic diff must preserve normalized classification and uncertainty.' );
$assert( false === $diff['data']['silent_rewrite'], 'Semantic diff must never silently rewrite canonical content.' );
$play = SPDB_Publishing_Intelligence::snapshot( 'editorial-playbooks' );
$assert( true === $play['data']['bypass_requires_authorized_reason'] && true === $play['data']['audit_required'], 'Editorial playbook bypass must require authorized reason and audit.' );
$GLOBALS['spdb_test_signals']['repurposing-studio'] = array( array( 'source_id' => 'src-1', 'source_version' => 'v2', 'target_format' => 'reel', 'outline' => 'Short outline', 'ai_assisted' => true, 'disclosure_required' => true ) );
$rep = SPDB_Publishing_Intelligence::snapshot( 'repurposing-studio' );
$assert( 'src-1' === $rep['data']['signals'][0]['source_id'] && true === $rep['data']['human_acceptance_required'], 'Repurposing must retain source reference and require human acceptance.' );
$assert( 'file22_or_native_owner' === $rep['data']['final_creation_owner'] && false === $rep['data']['clinical_authority'], 'Final repurposed content creation remains File 22/native and non-clinical.' );

// Audience and benchmarks.
$GLOBALS['spdb_test_signals']['audience-board'] = array(
	array( 'dimension' => 'language', 'label' => 'Urdu', 'cohort_count' => 25, 'value' => 12 ),
	array( 'dimension' => 'religion', 'label' => 'Sensitive', 'cohort_count' => 200, 'value' => 99 ),
);
$audience = SPDB_Publishing_Intelligence::snapshot( 'audience-board' );
$assert( 1 === count( $audience['data']['items'] ) && false === $audience['data']['raw_user_list'], 'Audience board must allow only approved aggregate dimensions and never raw user lists.' );
$GLOBALS['spdb_test_signals']['internal-benchmark'] = array( array( 'metric' => 'engagement', 'cohort_count' => 50, 'value' => 2.3 ) );
$bench = SPDB_Publishing_Intelligence::snapshot( 'internal-benchmark' );
$assert( false === $bench['data']['paid_or_donor_influence'] && false === $bench['data']['public_shaming_or_ranking'], 'Internal benchmark must be donor-neutral and non-shaming.' );
$GLOBALS['spdb_test_signals']['external-benchmark'] = array( array( 'source' => 'Public source', 'source_url' => 'javascript:alert(1)', 'source_date' => '2026-08-01', 'provenance' => 'public', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'patient_name' => 'Nope' ) );
$ext = SPDB_Publishing_Intelligence::snapshot( 'external-benchmark' );
$assert( 'Public source' === $ext['data']['items'][0]['source'] && '2026-08-01' === $ext['data']['items'][0]['source_date'], 'External benchmark must surface source/date provenance.' );
$assert( false === strpos( json_encode( $ext ), 'Nope' ) && ! isset( $ext['data']['items'][0]['source_url'] ) && true === $ext['data']['provider_disable_path_required'], 'External benchmark must minimize sensitive data, reject unsafe source URLs and have a provider-disable path.' );

// Comment intelligence must aggregate only.
$GLOBALS['spdb_test_signals']['comment-intelligence'] = array( array( 'cluster' => 'Need source', 'cohort_count' => 31, 'raw_comment' => 'private text', 'patient_name' => 'Hidden' ) );
$comment = SPDB_Publishing_Intelligence::snapshot( 'comment-intelligence' );
$assert( 1 === count( $comment['data']['items'] ) && false === $comment['data']['raw_private_comments'], 'Comment intelligence must return aggregate clusters only.' );
$assert( false === strpos( json_encode( $comment ), 'private text' ) && false === strpos( json_encode( $comment ), 'Hidden' ), 'Comment intelligence must not expose raw comments or identities.' );

// Evergreen aliases and no automatic deletion.
$GLOBALS['spdb_test_signals']['evergreen-health'] = array(
	array( 'object_id' => 'a', 'status' => 'broken-evidence' ),
	array( 'object_id' => 'b', 'status' => 'review-soon' ),
);
$ever = SPDB_Publishing_Intelligence::snapshot( 'evergreen-health' );
$assert( 1 === $ever['data']['counts']['broken_evidence'] && 1 === $ever['data']['counts']['review_soon'], 'Evergreen health must normalize hyphenated plan statuses.' );
$assert( false === $ever['data']['auto_delete'], 'Evergreen health must never auto-delete content.' );

// Localization, accessibility, provenance.
$GLOBALS['spdb_test_signals']['localization-center'] = array( array( 'language' => 'ur', 'direction' => 'rtl', 'source_version' => '5', 'translation_source_version' => '4' ) );
$loc = SPDB_Publishing_Intelligence::snapshot( 'localization-center' );
$assert( 'rtl' === $loc['data']['items'][0]['direction'] && true === $loc['data']['items'][0]['source_version_mismatch'], 'Localization center must carry direction and source-version mismatch.' );
$GLOBALS['spdb_test_signals']['accessibility-lab'] = array( array( 'code' => 'missing-alt', 'severity' => 'warning' ) );
$a11y = SPDB_Publishing_Intelligence::snapshot( 'accessibility-lab' );
$assert( true === $a11y['data']['readiness_only'] && false === $a11y['data']['certification_claim'], 'Accessibility lab is readiness evidence, never a certification claim.' );
$GLOBALS['spdb_test_signals']['provenance-ledger'] = array( array( 'object_id' => 'p1', 'origin' => 'imported' ) );
$prov = SPDB_Publishing_Intelligence::snapshot( 'provenance-ledger' );
$assert( 'unknown' === $prov['data']['items'][0]['authenticity_status'] && false === $prov['data']['fabricated_authenticity_badges'], 'Missing provenance/authenticity must display Unknown and never fabricate a badge.' );

// What-if planner: reviewer capacity is daily, not global; deterministic and side-effect free.
$scenario = array(
	'timezone' => 'Asia/Karachi',
	'reviewer_daily_capacity' => 2,
	'items' => array(
		array( 'scheduled_at' => '2026-08-11 09:00:00', 'reviewer' => 'r1', 'campaign' => 'c1' ),
		array( 'scheduled_at' => '2026-08-11 10:00:00', 'reviewer' => 'r1', 'campaign' => 'c1' ),
		array( 'scheduled_at' => '2026-08-12 09:00:00', 'reviewer' => 'r1', 'campaign' => 'c2' ),
		array( 'scheduled_at' => '2026-08-12 10:00:00', 'reviewer' => 'r1', 'campaign' => 'c2' ),
	),
);
$sim1 = SPDB_Publishing_Intelligence::simulate( $scenario );
$sim2 = SPDB_Publishing_Intelligence::simulate( $scenario );
$assert( empty( $sim1['reviewer_overload'] ), 'Reviewer daily capacity must reset per calendar date rather than aggregate globally.' );
$assert( $sim1['scenario_hash'] === $sim2['scenario_hash'], 'Identical What-if inputs must yield deterministic scenario identity.' );
$assert( true === $sim1['scenario_labeled'] && false === $sim1['auto_schedule'] && false === $sim1['write_authority'], 'What-if planner must be clearly labeled, read-only and side-effect free.' );
$scenario['items'][] = array( 'scheduled_at' => '2026-08-11 11:00:00', 'reviewer' => 'r1', 'campaign' => 'c1' );
$sim_over = SPDB_Publishing_Intelligence::simulate( $scenario );
$assert( 3 === ( $sim_over['reviewer_overload']['2026-08-11|r1'] ?? 0 ), 'Reviewer overload must be keyed and counted per date+reviewer.' );

// File 24 may make privacy threshold stricter but no provider/filter may weaken below 20.
$GLOBALS['spdb_test_privacy_threshold'] = 5;
$threshold_low = SPDB_Publishing_Intelligence::snapshot( 'audience-board' );
$assert( 20 === $threshold_low['privacy_threshold'], 'Privacy threshold filter must never weaken below the mandatory floor of 20.' );
$GLOBALS['spdb_test_privacy_threshold'] = 30;
$GLOBALS['spdb_test_signals']['audience-board'] = array( array( 'dimension' => 'language', 'label' => 'Urdu', 'cohort_count' => 25, 'value' => 12 ) );
$threshold_high = SPDB_Publishing_Intelligence::snapshot( 'audience-board' );
$assert( 30 === $threshold_high['privacy_threshold'] && 0 === count( $threshold_high['data']['items'] ), 'Stricter approved privacy threshold must suppress cohorts that were previously visible.' );


// Second fresh 80-round pass: regressions discovered after the first 80-round closure.
$GLOBALS['spdb_test_signals']['privacy-leak-guard'] = array(
	array( 'code' => 'pii', 'severity' => 'high', 'message' => 'Patient name: Secret Person', 'remediation' => 'Call +92 300 1234567', 'provider' => 'file24' ),
);
$privacy_free_text = SPDB_Publishing_Intelligence::snapshot( 'privacy-leak-guard' );
$privacy_free_encoded = json_encode( $privacy_free_text );
$assert( false === strpos( $privacy_free_encoded, 'Secret Person' ) && false === strpos( $privacy_free_encoded, '1234567' ), 'Privacy Leak Guard must never return provider free-text samples containing patient identifiers.' );
$assert( false === $privacy_free_text['data']['free_text_details_returned'], 'Privacy Leak Guard must explicitly declare that free-text details are not returned.' );

$GLOBALS['spdb_test_signals']['repurposing-studio'] = array(
	array( 'source_id' => 'src-private', 'target_format' => 'reel', 'outline' => 'Safe outline', 'summary' => 'Contact +92 300 1234567 for the case.' ),
);
$rep_private = SPDB_Publishing_Intelligence::snapshot( 'repurposing-studio' );
$assert( ! isset( $rep_private['data']['signals'][0]['summary'] ) && true === $rep_private['data']['signals'][0]['summary_suppressed'], 'Repurposing summary must be suppressed when sensitive free text is detected.' );

$GLOBALS['spdb_test_signals']['semantic-diff'] = array(
	array( 'classification' => 'clinical', 'summary' => 'Patient email user@example.com changed.' ),
);
$diff_private = SPDB_Publishing_Intelligence::snapshot( 'semantic-diff' );
$assert( ! isset( $diff_private['data']['items'][0]['summary'] ) && true === $diff_private['data']['items'][0]['summary_suppressed'], 'Semantic diff summary must suppress detected sensitive free text.' );

$GLOBALS['spdb_test_signals']['comment-intelligence'] = array(
	array( 'cluster' => 'user@example.com asked for source', 'label' => 'Contact +92 300 1234567', 'cohort_count' => 31, 'provider' => 'file21' ),
);
$comment_private = SPDB_Publishing_Intelligence::snapshot( 'comment-intelligence' );
$comment_private_encoded = json_encode( $comment_private );
$assert( false === strpos( $comment_private_encoded, 'user@example.com' ) && false === strpos( $comment_private_encoded, '1234567' ), 'Comment intelligence cluster labels must suppress detected sensitive free text.' );

$GLOBALS['spdb_test_signals']['external-benchmark'] = array(
	array( 'source' => 'Public source', 'source_date' => '2026-08-01', 'provenance' => 'public', 'source_url' => 'http://127.0.0.1/admin', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
	array( 'source' => 'Incomplete source', 'source_date' => 'not-a-date', 'provider' => 'public-provider' ),
);
$ext_hardened = SPDB_Publishing_Intelligence::snapshot( 'external-benchmark' );
$assert( 1 === count( $ext_hardened['data']['items'] ) && 1 === $ext_hardened['data']['incomplete_source_rows_suppressed'], 'External benchmark must suppress rows without valid source/date/provenance.' );
$assert( ! isset( $ext_hardened['data']['items'][0]['source_url'] ), 'External benchmark must reject localhost/private/reserved source URLs.' );

$GLOBALS['spdb_test_signals']['provenance-ledger'] = array(
	array( 'provider' => 'file21', 'object_id' => 'p2', 'authenticity_status' => 'certified-absolute', 'signature_status' => 'verified', 'tamper_evidence' => 'hash:abc' ),
	array( 'provider' => 'file21', 'object_id' => 'p3', 'authenticity_status' => 'verified', 'signature_status' => 'verified', 'tamper_evidence' => 'hash:def' ),
);
$prov_hardened = SPDB_Publishing_Intelligence::snapshot( 'provenance-ledger' );
$assert( 'unknown' === $prov_hardened['data']['items'][0]['authenticity_status'] && false === $prov_hardened['data']['items'][0]['badge_eligible'], 'Unknown authenticity labels must normalize to Unknown and never become badge-eligible.' );
$assert( true === $prov_hardened['data']['items'][1]['badge_eligible'] && 'provider_asserted_evidence' === $prov_hardened['data']['items'][1]['claim_level'], 'Verified provenance badge eligibility requires provider, verified signature and tamper evidence, and remains provider-asserted evidence.' );

$GLOBALS['spdb_test_ask_calls'] = 0;
$ask_unlabelled_phone = SPDB_Publishing_Intelligence::ask( 'Please contact +92 300 1234567 about this item.' );
$assert( 'rejected_sensitive_input' === $ask_unlabelled_phone['status'] && 0 === $GLOBALS['spdb_test_ask_calls'], 'Unlabelled telephone patterns must be blocked before Ask provider invocation.' );


// Third fresh 80-round pass: privacy, lawful-source, freshness and a11y regressions.
$GLOBALS['spdb_test_privacy_threshold'] = 20;
$GLOBALS['spdb_test_signals']['audience-board'] = array(
	array( 'dimension' => 'language', 'label' => 'user@example.com', 'value' => 'Contact +92 300 1234567', 'cohort_count' => 30, 'provider' => 'analytics' ),
);
$audience_private_value = SPDB_Publishing_Intelligence::snapshot( 'audience-board' );
$audience_private_encoded = json_encode( $audience_private_value );
$assert( false === strpos( $audience_private_encoded, 'user@example.com' ) && false === strpos( $audience_private_encoded, '1234567' ), 'Audience aggregate labels/values must suppress detected identifiers even above the cohort threshold.' );

$GLOBALS['spdb_test_signals']['internal-benchmark'] = array(
	array( 'metric' => 'engagement_rate', 'definition' => 'Approved aggregate interaction rate.', 'label' => 'Contact +92 300 1234567', 'value' => 12.4, 'benchmark' => 11.2, 'cohort_count' => 40, 'provider' => 'analytics' ),
	array( 'metric' => 'undefined_metric', 'cohort_count' => 40, 'value' => 9, 'provider' => 'analytics' ),
);
$internal_explainable = SPDB_Publishing_Intelligence::snapshot( 'internal-benchmark' );
$assert( 1 === count( $internal_explainable['data']['items'] ) && 1 === $internal_explainable['data']['incomplete_metric_rows_suppressed'], 'Internal benchmarking must suppress rows without an explainable metric definition.' );
$assert( true === $internal_explainable['data']['metric_definition_required'] && ! isset( $internal_explainable['data']['items'][0]['label'] ), 'Internal benchmark definitions are mandatory and detected sensitive labels are suppressed.' );

$GLOBALS['spdb_test_signals']['external-benchmark'] = array(
	array( 'source' => 'Lawful source', 'source_date' => '2026-08-01', 'provenance' => 'public', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
	array( 'source' => 'Terms forbidden', 'source_date' => '2026-08-01', 'provenance' => 'public', 'terms_status' => 'forbidden', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
	array( 'source' => 'Relative date', 'source_date' => 'tomorrow', 'provenance' => 'public', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
	array( 'source' => 'Future source', 'source_date' => '2100-01-01', 'provenance' => 'public', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
);
$external_lawful = SPDB_Publishing_Intelligence::snapshot( 'external-benchmark' );
$assert( 1 === count( $external_lawful['data']['items'] ), 'Only an explicitly dated, lawful/robots/terms-compliant external benchmark row may be projected.' );
$assert( 1 === $external_lawful['data']['compliance_rows_suppressed'] && 2 === $external_lawful['data']['incomplete_source_rows_suppressed'], 'External benchmark must separately suppress compliance failures and invalid/relative/future source dates.' );
$assert( true === $external_lawful['data']['robots_terms_law_required'], 'External public benchmark acceptance must explicitly require robots, terms and law status.' );

$GLOBALS['spdb_test_signals']['evergreen-health'] = array(
	array( 'object_id' => 'broken-now', 'status' => 'fresh', 'evidence_status' => 'broken', 'last_reviewed_at' => '2026-08-10', 'review_interval_days' => 365 ),
	array( 'object_id' => 'superseded-now', 'status' => 'fresh', 'evidence_status' => 'superseded', 'last_reviewed_at' => '2026-08-10', 'review_interval_days' => 365 ),
);
$evergreen_evidence = SPDB_Publishing_Intelligence::snapshot( 'evergreen-health', array( 'now' => strtotime( '2026-08-10 10:00:00 UTC' ) ) );
$assert( 'broken_evidence' === $evergreen_evidence['data']['items'][0]['health_status'] && 'superseded' === $evergreen_evidence['data']['items'][1]['health_status'], 'Broken or superseded evidence must immediately degrade evergreen health even when content was recently reviewed.' );
$assert( true === $evergreen_evidence['data']['broken_or_superseded_evidence_degrades_immediately'], 'Evergreen response must expose the immediate evidence-degradation contract.' );

$GLOBALS['spdb_test_signals']['accessibility-lab'] = array();
$a11y_empty = SPDB_Publishing_Intelligence::snapshot( 'accessibility-lab' );
$assert( false === $a11y_empty['data']['wcag_readiness_traceable'] && false === $a11y_empty['data']['reduced_motion_and_data_considered'] && 'unavailable' === $a11y_empty['data']['readiness_status'], 'Accessibility Lab must not claim traceability or reduced-context coverage when no evidence exists.' );
$GLOBALS['spdb_test_signals']['accessibility-lab'] = array(
	array( 'code' => 'heading-order', 'severity' => 'info', 'provider' => 'a11y' ),
	array( 'code' => 'reduced-motion', 'severity' => 'info', 'provider' => 'a11y' ),
	array( 'code' => 'reduced-data', 'severity' => 'info', 'provider' => 'a11y' ),
);
$a11y_evidence = SPDB_Publishing_Intelligence::snapshot( 'accessibility-lab' );
$assert( true === $a11y_evidence['data']['wcag_readiness_traceable'] && true === $a11y_evidence['data']['reduced_motion_considered'] && true === $a11y_evidence['data']['reduced_data_considered'], 'Accessibility readiness flags must be derived from supplied evidence rather than hard-coded true values.' );


// Fourth fresh 80-round pass: authorization, File26 provenance, deterministic time and value privacy.
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_institutional'] = false;
$GLOBALS['spdb_test_caps']['spdb_view_global_analytics'] = false;
$what_if_own = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'what-if-planner' ), '/spdb/v1/intelligence/simulate' );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $what_if_own ), 'What-if Planner must not be available to an ordinary own-analytics user.' );
$GLOBALS['spdb_test_caps']['spdb_view_global_analytics'] = true;
$GLOBALS['spdb_test_institutional'] = true;
$assert( SPDB_Publishing_Intelligence::rest_can_view( $what_if_own ), 'An institutional operator with explicit global analytics authority may use the read-only What-if Planner.' );
$GLOBALS['spdb_test_caps']['spdb_view_global_analytics'] = false;
$GLOBALS['spdb_test_institutional'] = false;

$GLOBALS['spdb_test_signals']['mission-control'] = array( array( 'label' => 'user@example.com', 'title' => 'Call +92 300 1234567', 'severity' => 'high' ) );
$mission_private = SPDB_Publishing_Intelligence::snapshot( 'mission-control' );
$assert( false === strpos( json_encode( $mission_private ), 'user@example.com' ) && false === strpos( json_encode( $mission_private ), '1234567' ), 'Mission Control labels/titles must suppress detected identifiers.' );
$GLOBALS['spdb_test_signals']['evidence-freshness'] = array( array( 'source_id' => 's-private', 'title' => 'Patient email user@example.com', 'status' => 'fresh' ) );
$evidence_private = SPDB_Publishing_Intelligence::snapshot( 'evidence-freshness' );
$assert( ! isset( $evidence_private['data']['items'][0]['title'] ) && true === $evidence_private['data']['items'][0]['title_suppressed'], 'Evidence Freshness titles must be value-screened for identifiers.' );
$GLOBALS['spdb_test_ask_response'] = array( 'answer' => 'Review source.', 'sources' => array( array( 'source_id' => 's1', 'title' => 'Patient email user@example.com' ) ) );
$ask_source_private = SPDB_Publishing_Intelligence::ask( 'Which source needs review?' );
$assert( ! isset( $ask_source_private['sources'][0]['title'] ) && true === $ask_source_private['sources'][0]['title_suppressed'], 'Ask source titles must suppress detected identifiers.' );

$GLOBALS['spdb_test_signals']['experiment-lab'] = array( array( 'experiment_id' => 'e-private', 'cohort_count' => 40, 'winner' => 'user@example.com', 'variant_metrics' => array( 'a' => array( 'value' => 3, 'note' => 'Call +92 300 1234567' ) ) ) );
$experiment_private = SPDB_Publishing_Intelligence::snapshot( 'experiment-lab' );
$experiment_encoded = json_encode( $experiment_private );
$assert( true === $experiment_private['data']['experiments'][0]['winner_suppressed'] && false === strpos( $experiment_encoded, 'user@example.com' ) && false === strpos( $experiment_encoded, '1234567' ), 'Experiment winner/nested metric values must suppress detected identifiers.' );

$GLOBALS['spdb_test_signals']['opportunity-radar'] = array(
	array( 'topic' => 'Trusted', 'demand' => 0.8, 'coverage' => 0.3, 'demand_provider' => 'file26' ),
	array( 'topic' => 'Forged', 'demand' => 1.0, 'coverage' => 0.0, 'provider' => 'unknown-search' ),
);
$op_owner = SPDB_Publishing_Intelligence::snapshot( 'opportunity-radar' );
$assert( 1 === count( $op_owner['data']['opportunities'] ) && 1 === $op_owner['data']['untrusted_demand_rows_suppressed'], 'Opportunity Radar must suppress demand not attributable to File 26.' );
$assert( true === $op_owner['data']['opportunities'][0]['demand_owner_verified'], 'Accepted opportunity demand must carry verified File 26 ownership evidence.' );

$GLOBALS['spdb_test_signals']['review-sla'] = array( array( 'provider' => 'file21', 'object_type' => 'post', 'object_id' => 'same', 'due_at' => '2026-08-10 12:00:00', 'status' => 'pending', 'review_type' => 'clinical', 'policy' => 'standard' ) );
$sla_due = SPDB_Publishing_Intelligence::snapshot( 'review-sla', array( 'timezone' => 'UTC', 'now' => strtotime( '2026-08-10 10:00:00 UTC' ) ) );
$sla_over = SPDB_Publishing_Intelligence::snapshot( 'review-sla', array( 'timezone' => 'UTC', 'now' => strtotime( '2026-08-10 13:00:00 UTC' ) ) );
$assert( $sla_due['data']['items'][0]['escalation_key'] === $sla_over['data']['items'][0]['escalation_key'], 'SLA escalation idempotency key must remain stable as due-soon becomes overdue.' );
$GLOBALS['spdb_test_signals']['review-sla'] = array( array( 'object_id' => 'relative', 'due_at' => 'tomorrow', 'status' => 'pending' ) );
$sla_relative = SPDB_Publishing_Intelligence::snapshot( 'review-sla', array( 'timezone' => 'UTC', 'now' => strtotime( '2026-08-10 10:00:00 UTC' ) ) );
$assert( 1 === $sla_relative['data']['counts']['invalid'], 'SLA must reject natural-language relative timestamps to preserve deterministic behavior.' );
$relative_sim = SPDB_Publishing_Intelligence::simulate( array( 'timezone' => 'UTC', 'items' => array( array( 'scheduled_at' => 'tomorrow', 'reviewer' => 'r1' ) ) ) );
$assert( empty( $relative_sim['reviewer_overload'] ) && empty( $relative_sim['calendar_collisions'] ), 'What-if calendar parsing must reject relative timestamps rather than depend on runtime clock interpretation.' );

foreach ( array( 'claim', 'evidence', 'medical', 'rights', 'privacy', 'headline' ) as $semantic_class ) {
	$GLOBALS['spdb_test_signals']['semantic-diff'] = array( array( 'classification' => $semantic_class, 'uncertainty' => 0.1 ) );
	$semantic = SPDB_Publishing_Intelligence::snapshot( 'semantic-diff' );
	$assert( $semantic_class === $semantic['data']['items'][0]['classification'], 'Semantic diff must support current-plan class: ' . $semantic_class );
}
$GLOBALS['spdb_test_signals']['accessibility-lab'] = array( array( 'code' => 'promotion-banner', 'severity' => 'info' ) );
$a11y_no_false_motion = SPDB_Publishing_Intelligence::snapshot( 'accessibility-lab' );
$assert( false === $a11y_no_false_motion['data']['reduced_motion_considered'], 'Unrelated words containing motion must not satisfy reduced-motion evidence.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} publishing-intelligence security regressions failed.\n" ); exit( 1 ); }
echo "All {$tests} File 23 Publishing Intelligence security/privacy regressions passed.\n";
