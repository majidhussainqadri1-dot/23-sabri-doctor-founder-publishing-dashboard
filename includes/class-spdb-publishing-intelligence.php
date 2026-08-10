<?php
/**
 * File 23 Future Publishing Intelligence Superset (24 enhancements).
 *
 * File 23 remains a federated orchestration/projection layer. This service does
 * not own canonical content, comments, analytics events, patient data, search
 * indexes, notification truth, or AI diagnosis/prescription decisions.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Publishing_Intelligence {
	private const API_NAMESPACE = 'spdb/v1';
	private const PRIVACY_THRESHOLD = 20;
	private const MAX_SIGNAL_ROWS = 250;
	private const MAX_DEPTH = 4;

	/** @return array<string,array<string,mixed>> */
	public static function catalog(): array {
		return array(
			'mission-control' => self::feature( 'F23-FPI-01', 'Publishing Mission Control', 'P0', 'Prioritized action board for overdue reviews, failed schedules, corrections, source failures and adapter incidents.', 'federated_projection' ),
			'experiment-lab' => self::feature( 'F23-FPI-02', 'Experiment Lab', 'P0', 'Governed title, excerpt, cover and CTA experiments using provider-owned metrics and privacy thresholds.', 'analytics_orchestration' ),
			'best-time' => self::feature( 'F23-FPI-03', 'Best-Time-to-Publish Intelligence', 'P0', 'Advisory publishing windows derived from privacy-safe aggregate audience activity.', 'analytics_advisory' ),
			'ask-dashboard' => self::feature( 'F23-FPI-04', 'Ask Publishing Dashboard', 'P0', 'Conversational read-only answers over authorized dashboard projections; never grants authority or executes writes.', 'ai_assistance' ),
			'opportunity-radar' => self::feature( 'F23-FPI-05', 'Content Opportunity Radar', 'P0', 'Demand-versus-coverage opportunities from File 26 and native knowledge providers.', 'discovery_projection' ),
			'bottleneck-detector' => self::feature( 'F23-FPI-06', 'Editorial Bottleneck Detector', 'P0', 'Queue-stage delay and blockage analysis across draft, review, approval and scheduling projections.', 'workflow_projection' ),
			'review-sla' => self::feature( 'F23-FPI-07', 'Review SLA and Escalation Engine', 'P0', 'Due, near-due and overdue review projections with escalation events delegated to File 19.', 'governance_projection' ),
			'change-impact-map' => self::feature( 'F23-FPI-08', 'Semantic Change Impact Map', 'P0', 'Shows downstream translations, media, lessons, PDFs, campaigns and citations affected by a correction.', 'reference_graph_projection' ),
			'evidence-freshness' => self::feature( 'F23-FPI-09', 'Evidence Freshness Monitor', 'P0', 'Flags broken, withdrawn, superseded, expiring or review-due evidence without becoming the source registry.', 'evidence_projection' ),
			'medical-preflight' => self::feature( 'F23-FPI-10', 'Medical and Ethical Preflight', 'P0', 'Advisory safety flags supplied by approved policy/clinical providers; no diagnosis, prescription or dosage authority.', 'safety_advisory' ),
			'privacy-leak-guard' => self::feature( 'F23-FPI-11', 'Privacy Leak Guard', 'P0', 'Advisory detection of sensitive-data exposure signals supplied by approved privacy/security providers.', 'privacy_advisory' ),
			'permission-simulator' => self::feature( 'F23-FPI-12', 'Role and Permission Simulator', 'P0', 'Read-only presentation of native authorization outcomes for representative roles/states; no impersonation.', 'authorization_projection' ),
			'semantic-diff' => self::feature( 'F23-FPI-13', 'Semantic Version Diff', 'P1', 'Classifies meaning-level revision changes supplied by native revision/evidence providers.', 'revision_projection' ),
			'editorial-playbooks' => self::feature( 'F23-FPI-14', 'Editorial Playbooks', 'P1', 'Governed checklists for successful cases, research, lessons, announcements, corrections and media publication.', 'file23_metadata' ),
			'repurposing-studio' => self::feature( 'F23-FPI-15', 'Cross-Format Repurposing Studio', 'P1', 'AI-assisted draft suggestions for approved content; final creation remains File 22/native-owner controlled.', 'ai_assistance' ),
			'audience-board' => self::feature( 'F23-FPI-16', 'Audience Intelligence Board', 'P1', 'Privacy-safe cohort aggregates for language, geography, device class, return behavior and active periods.', 'analytics_projection' ),
			'internal-benchmark' => self::feature( 'F23-FPI-17', 'Internal Benchmarking', 'P1', 'Anonymous cohort comparison without donor, payment or visibility influence.', 'analytics_projection' ),
			'external-benchmark' => self::feature( 'F23-FPI-18', 'External Public Benchmark Watch', 'P1', 'Lawful public-source comparison signals for knowledge opportunities, not popularity copying or ranking manipulation.', 'public_signal_projection' ),
			'comment-intelligence' => self::feature( 'F23-FPI-19', 'Comment and Question Intelligence', 'P1', 'Privacy-safe recurring-question, complaint, correction-request and FAQ candidate clusters from File 21.', 'interaction_projection' ),
			'evergreen-health' => self::feature( 'F23-FPI-20', 'Evergreen Content Health', 'P1', 'Fresh, review-soon, outdated, broken-evidence and superseded health projections for long-lived content.', 'content_health_projection' ),
			'localization-center' => self::feature( 'F23-FPI-21', 'Localization Command Center', 'P1', 'Translation status, terminology, outdated translation and source-version mismatch projections.', 'localization_projection' ),
			'accessibility-lab' => self::feature( 'F23-FPI-22', 'Accessibility Publishing Lab', 'P1', 'Pre-publication readiness for alt text, captions, headings, directionality and other approved accessibility checks.', 'accessibility_advisory' ),
			'provenance-ledger' => self::feature( 'F23-FPI-23', 'Content Provenance and Authenticity Ledger', 'P2', 'Displays human, AI-assisted, translated, imported and available authenticity metadata without duplicating native truth.', 'provenance_projection' ),
			'what-if-planner' => self::feature( 'F23-FPI-24', 'Editorial Digital Twin / What-if Planner', 'P2', 'Read-only scenario simulation for calendar collisions, workload pressure and campaign completeness.', 'simulation' ),
		);
	}

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		}
	}

	public static function register_rest_routes(): void {
		register_rest_route( self::API_NAMESPACE, '/intelligence/catalog', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_catalog' ), 'permission_callback' => array( __CLASS__, 'rest_can_view' ) ) );
		register_rest_route( self::API_NAMESPACE, '/intelligence/(?P<feature>[a-z0-9-]+)', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_snapshot' ), 'permission_callback' => array( __CLASS__, 'rest_can_view' ) ) );
		register_rest_route( self::API_NAMESPACE, '/intelligence/ask', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_ask' ), 'permission_callback' => array( __CLASS__, 'rest_can_view' ) ) );
		register_rest_route( self::API_NAMESPACE, '/intelligence/simulate', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_simulate' ), 'permission_callback' => array( __CLASS__, 'rest_can_view' ) ) );
	}

	public static function rest_can_view(): bool {
		if ( ! function_exists( 'get_current_user_id' ) || ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
			return false;
		}
		$user_id = (int) get_current_user_id();
		if ( class_exists( 'SPDB_Membership_Guard' ) && ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id ) ) {
			return false;
		}
		if ( class_exists( 'SPDB_Capabilities' ) ) {
			return SPDB_Capabilities::current_user_can( 'spdb_view_own_analytics' ) || SPDB_Capabilities::current_user_can( 'spdb_view_global_analytics' );
		}
		return false;
	}

	public static function rest_catalog() {
		return rest_ensure_response( array( 'version' => defined( 'SPDB_VERSION' ) ? SPDB_VERSION : '', 'features' => array_values( self::catalog() ), 'count' => count( self::catalog() ), 'law' => 'Federated advisory/projection only; canonical owners retain source truth and write authority.' ) );
	}

	public static function rest_snapshot( $request ) {
		$feature = self::key( (string) $request['feature'] );
		$snapshot = self::snapshot( $feature, self::request_context( $request ) );
		if ( null === $snapshot ) {
			return new WP_Error( 'spdb_intelligence_unknown_feature', __( 'Unknown publishing-intelligence feature.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $snapshot );
	}

	public static function rest_ask( $request ) {
		$question = self::text( (string) $request->get_param( 'question' ), 500 );
		if ( '' === $question ) {
			return new WP_Error( 'spdb_intelligence_question_required', __( 'A question is required.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( self::ask( $question, self::request_context( $request ) ) );
	}

	public static function rest_simulate( $request ) {
		$scenario = $request->get_param( 'scenario' );
		$scenario = is_array( $scenario ) ? self::sanitize_array( $scenario ) : array();
		return rest_ensure_response( self::simulate( $scenario ) );
	}

	/** @return array<string,mixed>|null */
	public static function snapshot( string $feature, array $context = array() ): ?array {
		$catalog = self::catalog();
		if ( ! isset( $catalog[ $feature ] ) ) {
			return null;
		}
		$signals = self::signals( $feature, $context );
		$data = self::evaluate( $feature, $signals, $context );
		return array( 'feature' => $catalog[ $feature ], 'advisory_only' => true, 'canonical_write_authority' => false, 'privacy_threshold' => self::PRIVACY_THRESHOLD, 'generated_at' => gmdate( 'c' ), 'data' => $data );
	}

	/** @return array<string,mixed> */
	public static function ask( string $question, array $context = array() ): array {
		$question = self::text( $question, 500 );
		$response = null;
		if ( function_exists( 'apply_filters' ) ) {
			$response = apply_filters( 'spdb/publishing_intelligence_ask', null, $question, self::sanitize_array( $context ) );
		}
		if ( ! is_array( $response ) ) {
			return array( 'status' => 'provider_unavailable', 'answer' => '', 'question' => $question, 'advisory_only' => true, 'auto_action' => false, 'message' => 'No approved conversational intelligence provider supplied an answer.' );
		}
		return array( 'status' => 'answered', 'answer' => self::text( (string) ( $response['answer'] ?? '' ), 4000 ), 'question' => $question, 'sources' => self::sanitize_rows( is_array( $response['sources'] ?? null ) ? $response['sources'] : array(), 50 ), 'advisory_only' => true, 'auto_action' => false );
	}

	/** @return array<string,mixed> */
	public static function simulate( array $scenario ): array {
		$items = is_array( $scenario['items'] ?? null ) ? $scenario['items'] : array();
		$capacity = max( 1, min( 100, (int) ( $scenario['reviewer_daily_capacity'] ?? 5 ) ) );
		$day_counts = array();
		$reviewer_counts = array();
		$campaigns = array();
		foreach ( array_slice( $items, 0, self::MAX_SIGNAL_ROWS ) as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$date = self::date_key( (string) ( $row['scheduled_at'] ?? '' ) );
			if ( '' !== $date ) { $day_counts[ $date ] = ( $day_counts[ $date ] ?? 0 ) + 1; }
			$reviewer = self::key( (string) ( $row['reviewer'] ?? '' ) );
			if ( '' !== $reviewer ) { $reviewer_counts[ $reviewer ] = ( $reviewer_counts[ $reviewer ] ?? 0 ) + 1; }
			$campaign = self::key( (string) ( $row['campaign'] ?? '' ) );
			if ( '' !== $campaign ) { $campaigns[ $campaign ] = ( $campaigns[ $campaign ] ?? 0 ) + 1; }
		}
		$collisions = array_filter( $day_counts, static fn( int $count ): bool => $count > 3 );
		$overload = array_filter( $reviewer_counts, static fn( int $count ): bool => $count > $capacity );
		return array( 'advisory_only' => true, 'auto_schedule' => false, 'item_count' => count( $items ), 'calendar_collisions' => $collisions, 'reviewer_overload' => $overload, 'campaign_distribution' => $campaigns, 'risk_level' => ! empty( $collisions ) || ! empty( $overload ) ? 'attention' : 'normal' );
	}

	/** @return array<int,array<string,mixed>> */
	private static function signals( string $feature, array $context ): array {
		$signals = array();
		if ( function_exists( 'apply_filters' ) ) {
			$signals = apply_filters( 'spdb/publishing_intelligence_signals', array(), $feature, self::sanitize_array( $context ) );
		}
		return self::sanitize_rows( is_array( $signals ) ? $signals : array(), self::MAX_SIGNAL_ROWS );
	}

	/** @return array<string,mixed> */
	private static function evaluate( string $feature, array $signals, array $context ): array {
		switch ( $feature ) {
			case 'mission-control': return self::mission_control( $signals );
			case 'experiment-lab': return self::experiments( $signals );
			case 'best-time': return self::best_time( $signals );
			case 'opportunity-radar': return self::opportunities( $signals );
			case 'bottleneck-detector': return self::bottlenecks( $signals );
			case 'review-sla': return self::sla( $signals );
			case 'editorial-playbooks': return array( 'playbooks' => self::playbooks(), 'provider_signals' => $signals );
			case 'audience-board':
			case 'internal-benchmark':
			case 'comment-intelligence': return self::privacy_thresholded( $signals );
			case 'evergreen-health': return self::evergreen( $signals );
			case 'what-if-planner': return self::simulate( is_array( $context['scenario'] ?? null ) ? $context['scenario'] : array( 'items' => $signals ) );
			case 'ask-dashboard': return array( 'status' => 'use_post_endpoint', 'endpoint' => '/spdb/v1/intelligence/ask', 'auto_action' => false );
			case 'repurposing-studio': return array( 'draft_only' => true, 'auto_publish' => false, 'signals' => $signals );
			case 'medical-preflight':
			case 'privacy-leak-guard':
			case 'accessibility-lab': return array( 'decision_authority' => 'native_owner_or_human_reviewer', 'auto_block' => false, 'flags' => $signals );
			case 'permission-simulator': return array( 'impersonation' => false, 'write_authority' => false, 'matrix' => $signals );
			default: return array( 'items' => $signals, 'count' => count( $signals ) );
		}
	}

	/** @return array<string,mixed> */
	private static function mission_control( array $signals ): array {
		$weight = array( 'critical' => 4, 'high' => 3, 'warning' => 2, 'normal' => 1, 'info' => 0 );
		usort( $signals, static function ( array $a, array $b ) use ( $weight ): int {
			$aw = $weight[ self::key( (string) ( $a['severity'] ?? 'normal' ) ) ] ?? 1;
			$bw = $weight[ self::key( (string) ( $b['severity'] ?? 'normal' ) ) ] ?? 1;
			if ( $aw !== $bw ) { return $bw <=> $aw; }
			return strcmp( (string) ( $a['due_at'] ?? '9999' ), (string) ( $b['due_at'] ?? '9999' ) );
		} );
		return array( 'items' => array_slice( $signals, 0, 50 ), 'count' => count( $signals ) );
	}

	/** @return array<string,mixed> */
	private static function experiments( array $signals ): array {
		$rows = array();
		foreach ( $signals as $row ) {
			$cohort = max( 0, (int) ( $row['cohort_count'] ?? 0 ) );
			$row['suppressed'] = $cohort < self::PRIVACY_THRESHOLD;
			$row['winner_advisory_only'] = true;
			if ( $row['suppressed'] ) { unset( $row['winner'], $row['uplift'], $row['variant_metrics'] ); }
			$rows[] = $row;
		}
		return array( 'experiments' => $rows, 'auto_apply_winner' => false );
	}

	/** @return array<string,mixed> */
	private static function best_time( array $signals ): array {
		$windows = array();
		foreach ( $signals as $row ) {
			$observations = max( 0, (int) ( $row['observations'] ?? $row['cohort_count'] ?? 0 ) );
			if ( $observations < self::PRIVACY_THRESHOLD ) { continue; }
			$window = self::text( (string) ( $row['window'] ?? '' ), 80 );
			if ( '' === $window ) { continue; }
			$windows[] = array( 'window' => $window, 'score' => round( (float) ( $row['score'] ?? 0 ), 4 ), 'observations' => $observations );
		}
		usort( $windows, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );
		return array( 'recommended_windows' => array_slice( $windows, 0, 10 ), 'auto_schedule' => false );
	}

	/** @return array<string,mixed> */
	private static function opportunities( array $signals ): array {
		foreach ( $signals as &$row ) {
			$row['opportunity_score'] = round( max( 0.0, (float) ( $row['demand'] ?? 0 ) - (float) ( $row['coverage'] ?? 0 ) ), 4 );
		}
		unset( $row );
		usort( $signals, static fn( array $a, array $b ): int => ( $b['opportunity_score'] ?? 0 ) <=> ( $a['opportunity_score'] ?? 0 ) );
		return array( 'opportunities' => array_slice( $signals, 0, 50 ), 'ranking_owner' => 'file26', 'paid_or_donor_influence' => false );
	}

	/** @return array<string,mixed> */
	private static function bottlenecks( array $signals ): array {
		usort( $signals, static fn( array $a, array $b ): int => (float) ( $b['wait_hours'] ?? 0 ) <=> (float) ( $a['wait_hours'] ?? 0 ) );
		return array( 'stages' => $signals, 'worst_stage' => $signals[0]['stage'] ?? '' );
	}

	/** @return array<string,mixed> */
	private static function sla( array $signals ): array {
		$now = time();
		$counts = array( 'overdue' => 0, 'due_soon' => 0, 'on_track' => 0 );
		$items = array();
		foreach ( $signals as $row ) {
			$due = strtotime( (string) ( $row['due_at'] ?? '' ) );
			$status = 'on_track';
			if ( false !== $due && $due < $now ) { $status = 'overdue'; }
			elseif ( false !== $due && $due <= $now + 6 * HOUR_IN_SECONDS ) { $status = 'due_soon'; }
			++$counts[ $status ];
			$row['sla_status'] = $status;
			$items[] = $row;
		}
		return array( 'counts' => $counts, 'items' => $items, 'notification_owner' => 'file19' );
	}

	/** @return array<string,mixed> */
	private static function privacy_thresholded( array $signals ): array {
		$visible = array(); $suppressed = 0;
		foreach ( $signals as $row ) {
			if ( max( 0, (int) ( $row['cohort_count'] ?? 0 ) ) < self::PRIVACY_THRESHOLD ) { ++$suppressed; continue; }
			$visible[] = $row;
		}
		return array( 'items' => $visible, 'suppressed_count' => $suppressed, 'privacy_threshold' => self::PRIVACY_THRESHOLD );
	}

	/** @return array<string,mixed> */
	private static function evergreen( array $signals ): array {
		$today = time();
		$counts = array( 'fresh' => 0, 'review_soon' => 0, 'outdated' => 0, 'broken_evidence' => 0, 'superseded' => 0 );
		$items = array();
		foreach ( $signals as $row ) {
			$status = self::key( (string) ( $row['status'] ?? '' ) );
			if ( ! isset( $counts[ $status ] ) ) {
				$last = strtotime( (string) ( $row['last_reviewed_at'] ?? '' ) );
				$interval = max( 30, min( 1095, (int) ( $row['review_interval_days'] ?? 365 ) ) );
				$due = false === $last ? 0 : $last + $interval * DAY_IN_SECONDS;
				if ( 0 === $due || $due < $today ) { $status = 'outdated'; }
				elseif ( $due <= $today + 30 * DAY_IN_SECONDS ) { $status = 'review_soon'; }
				else { $status = 'fresh'; }
			}
			++$counts[ $status ];
			$row['health_status'] = $status;
			$items[] = $row;
		}
		return array( 'counts' => $counts, 'items' => $items );
	}

	/** @return array<string,array<string,mixed>> */
	private static function playbooks(): array {
		return array(
			'successful_case' => array( 'label' => 'Successful Case', 'checks' => array( 'patient-consent', 'de-identification', 'clinical-claim-review', 'sources', 'image-metadata', 'correction-path' ) ),
			'research' => array( 'label' => 'Research Publication', 'checks' => array( 'sources', 'method-disclosure', 'conflict-disclosure', 'rights', 'accessibility', 'review' ) ),
			'lesson' => array( 'label' => 'Educational Lesson', 'checks' => array( 'learning-objective', 'sources', 'medical-safety', 'accessibility', 'translation-status', 'review' ) ),
			'official_announcement' => array( 'label' => 'Official Announcement', 'checks' => array( 'founder-or-authorized-role', 'fact-check', 'effective-date', 'distribution-plan', 'correction-path' ) ),
			'correction' => array( 'label' => 'Correction / Retraction', 'checks' => array( 'reason', 'evidence', 'impact-map', 'native-owner-action', 'notification-plan', 'audit' ) ),
			'media' => array( 'label' => 'Video / Reel / PDF Publication', 'checks' => array( 'rights', 'captions-or-transcript', 'alt-or-description', 'source-links', 'privacy-scan', 'destination' ) ),
		);
	}

	/** @return array<string,mixed> */
	private static function request_context( $request ): array {
		$context = array();
		foreach ( array( 'scope', 'provider', 'object_type', 'object_id', 'language', 'date_from', 'date_to', 'timezone' ) as $key ) {
			$value = $request->get_param( $key );
			if ( is_scalar( $value ) ) { $context[ $key ] = self::text( (string) $value, 120 ); }
		}
		return $context;
	}

	/** @return array<string,mixed> */
	private static function feature( string $id, string $label, string $priority, string $purpose, string $ownership ): array {
		return array( 'id' => $id, 'key' => self::key( strtolower( str_replace( ' ', '-', $label ) ) ), 'label' => $label, 'priority' => $priority, 'purpose' => $purpose, 'ownership' => $ownership, 'canonical_write_authority' => false, 'advisory_or_projection' => true );
	}

	/** @return array<int,array<string,mixed>> */
	private static function sanitize_rows( array $rows, int $limit ): array {
		$out = array();
		foreach ( array_slice( $rows, 0, $limit ) as $row ) { if ( is_array( $row ) ) { $out[] = self::sanitize_array( $row ); } }
		return $out;
	}

	/** @return array<string,mixed> */
	private static function sanitize_array( array $value, int $depth = 0 ): array {
		if ( $depth >= self::MAX_DEPTH ) { return array(); }
		$out = array();
		foreach ( array_slice( $value, 0, 100, true ) as $key => $item ) {
			$clean_key = self::key( (string) $key );
			if ( '' === $clean_key ) { continue; }
			if ( is_array( $item ) ) { $out[ $clean_key ] = self::sanitize_array( $item, $depth + 1 ); }
			elseif ( is_bool( $item ) || is_int( $item ) || is_float( $item ) ) { $out[ $clean_key ] = $item; }
			elseif ( is_scalar( $item ) ) { $out[ $clean_key ] = self::text( (string) $item, 1000 ); }
		}
		return $out;
	}

	private static function key( string $value ): string {
		$value = strtolower( trim( $value ) );
		$value = preg_replace( '/[^a-z0-9_-]+/', '-', $value );
		return trim( (string) $value, '-' );
	}

	private static function text( string $value, int $max ): string {
		$value = trim( strip_tags( $value ) );
		return function_exists( 'mb_substr' ) ? (string) mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
	}

	private static function date_key( string $value ): string {
		$time = strtotime( $value );
		return false === $time ? '' : gmdate( 'Y-m-d', $time );
	}
}
