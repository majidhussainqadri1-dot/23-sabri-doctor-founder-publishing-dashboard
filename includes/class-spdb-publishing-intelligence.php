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
	private const MIN_PRIVACY_THRESHOLD = 20;
	private const MAX_SIGNAL_ROWS = 250;
	private const MAX_DEPTH = 4;
	private const MAX_QUESTION_LENGTH = 500;
	private const DUE_SOON_SECONDS = 21600;

	/** @return array<string,array<string,mixed>> */
	public static function catalog(): array {
		$catalog = array(
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
			'permission-simulator' => self::feature( 'F23-FPI-12', 'Role and Permission Simulator', 'P0', 'Founder-only read-only presentation of native authorization outcomes for representative roles/states; no impersonation.', 'authorization_projection' ),
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

		foreach ( $catalog as $key => &$feature ) {
			$feature['key'] = $key;
		}
		unset( $feature );
		return $catalog;
	}

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		}
	}

	public static function register_rest_routes(): void {
		register_rest_route( self::API_NAMESPACE, '/intelligence/catalog', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_catalog' ), 'permission_callback' => array( __CLASS__, 'rest_can_view' ) ) );
		register_rest_route( self::API_NAMESPACE, '/intelligence/ask', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_ask' ), 'permission_callback' => array( __CLASS__, 'rest_can_view' ) ) );
		register_rest_route( self::API_NAMESPACE, '/intelligence/simulate', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_simulate' ), 'permission_callback' => array( __CLASS__, 'rest_can_view' ) ) );
		register_rest_route( self::API_NAMESPACE, '/intelligence/(?P<feature>[a-z0-9-]+)', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_snapshot' ), 'permission_callback' => array( __CLASS__, 'rest_can_view' ) ) );
	}

	/**
	 * Read permission for private intelligence routes.
	 *
	 * Institution scope is never inferred from a client-supplied label alone.
	 * Permission simulation is Founder-only and never performs impersonation.
	 *
	 * @param mixed $request Optional REST request.
	 */
	public static function rest_can_view( $request = null ): bool {
		if ( ! function_exists( 'get_current_user_id' ) || ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
			return false;
		}
		$user_id = (int) get_current_user_id();
		if ( $user_id < 1 || ! class_exists( 'SPDB_Membership_Guard' ) || ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id ) ) {
			return false;
		}
		if ( ! class_exists( 'SPDB_Capabilities' ) || ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' ) ) {
			return false;
		}

		$can_own = SPDB_Capabilities::current_user_can( 'spdb_view_own_analytics' );
		$can_global = SPDB_Capabilities::current_user_can( 'spdb_view_global_analytics' );
		if ( ! $can_own && ! $can_global ) {
			return false;
		}

		$scope = self::request_scalar( $request, 'scope', 20 );
		if ( '' === $scope ) {
			$scope = 'own';
		}
		$scope = self::key( $scope );
		if ( ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			return false;
		}
		if ( 'institution' === $scope && ( ! $can_global || ! self::is_institutional( $user_id ) ) ) {
			return false;
		}
		if ( 'own' === $scope && ! $can_own && ! $can_global ) {
			return false;
		}

		$feature = self::requested_feature( $request );
		if ( 'permission-simulator' === $feature && ! SPDB_Membership_Guard::is_user_founder( $user_id ) ) {
			return false;
		}
		if (
			'what-if-planner' === $feature
			&& ! SPDB_Membership_Guard::is_user_founder( $user_id )
			&& ( ! $can_global || ! self::is_institutional( $user_id ) )
		) {
			return false;
		}
		return true;
	}

	public static function rest_catalog() {
		$catalog = self::catalog();
		return rest_ensure_response(
			array(
				'version'           => defined( 'SPDB_VERSION' ) ? SPDB_VERSION : '',
				'features'          => array_values( $catalog ),
				'count'             => count( $catalog ),
				'privacy_threshold' => self::privacy_threshold(),
				'law'               => 'Federated advisory/projection only; canonical owners retain source truth and write authority.',
			)
		);
	}

	public static function rest_snapshot( $request ) {
		$feature = self::key( self::request_scalar( $request, 'feature', 80 ) );
		$snapshot = self::snapshot( $feature, self::request_context( $request ) );
		if ( null === $snapshot ) {
			return new WP_Error( 'spdb_intelligence_unknown_feature', __( 'Unknown publishing-intelligence feature.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $snapshot );
	}

	public static function rest_ask( $request ) {
		$question = self::text( self::request_scalar( $request, 'question', self::MAX_QUESTION_LENGTH ), self::MAX_QUESTION_LENGTH );
		if ( '' === $question ) {
			return new WP_Error( 'spdb_intelligence_question_required', __( 'A question is required.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}
		if ( self::contains_sensitive_text( $question ) ) {
			return new WP_Error( 'spdb_intelligence_sensitive_question', __( 'Remove personal, clinical, credential or other sensitive data before using Ask Publishing Dashboard.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( self::ask( $question, self::request_context( $request ) ) );
	}

	public static function rest_simulate( $request ) {
		$scenario = self::request_value( $request, 'scenario' );
		$scenario = is_array( $scenario ) ? self::sanitize_array( $scenario ) : array();
		return rest_ensure_response( self::simulate( $scenario ) );
	}

	/** @return array<string,mixed>|null */
	public static function snapshot( string $feature, array $context = array() ): ?array {
		$catalog = self::catalog();
		if ( ! isset( $catalog[ $feature ] ) ) {
			return null;
		}
		$context = self::normalize_context( $context );
		$signals = self::signals( $feature, $context );
		$data = self::evaluate( $feature, $signals, $context );
		return array(
			'feature'                   => $catalog[ $feature ],
			'advisory_only'             => true,
			'canonical_write_authority' => false,
			'privacy_threshold'         => self::privacy_threshold(),
			'generated_at'              => gmdate( 'c' ),
			'data'                      => $data,
		);
	}

	/** @return array<string,mixed> */
	public static function ask( string $question, array $context = array() ): array {
		$question = self::text( $question, self::MAX_QUESTION_LENGTH );
		$question_hash = hash( 'sha256', $question );
		if ( '' === $question || self::contains_sensitive_text( $question ) ) {
			return array(
				'status'        => 'rejected_sensitive_input',
				'answer'        => '',
				'question_hash' => $question_hash,
				'advisory_only' => true,
				'auto_action'    => false,
				'message'        => 'Sensitive or empty prompts are not sent to conversational providers.',
			);
		}

		$context = self::normalize_context( $context );
		$response = null;
		if ( function_exists( 'apply_filters' ) ) {
			$response = apply_filters( 'spdb/publishing_intelligence_ask', null, $question, self::provider_context( $context ) );
		}
		if ( ! is_array( $response ) ) {
			return array(
				'status'        => 'provider_unavailable',
				'answer'        => '',
				'question_hash' => $question_hash,
				'advisory_only' => true,
				'auto_action'    => false,
				'message'        => 'No approved conversational intelligence provider supplied an answer.',
			);
		}

		$answer = self::text( (string) ( $response['answer'] ?? '' ), 4000 );
		if ( self::contains_sensitive_text( $answer ) ) {
			return array(
				'status'        => 'provider_response_rejected',
				'answer'        => '',
				'question_hash' => $question_hash,
				'advisory_only' => true,
				'auto_action'    => false,
				'message'        => 'The provider response was suppressed because it appeared to contain sensitive data.',
			);
		}

		return array(
			'status'          => 'answered',
			'answer'          => $answer,
			'question_hash'   => $question_hash,
			'sources'         => self::source_rows( is_array( $response['sources'] ?? null ) ? $response['sources'] : array() ),
			'advisory_only'   => true,
			'auto_action'      => false,
			'audit_safe_only' => true,
		);
	}

	/** @return array<string,mixed> */
	public static function simulate( array $scenario ): array {
		$scenario = self::sanitize_array( $scenario );
		$items = is_array( $scenario['items'] ?? null ) ? $scenario['items'] : array();
		$capacity = max( 1, min( 100, (int) ( $scenario['reviewer_daily_capacity'] ?? 5 ) ) );
		$timezone = self::normalize_timezone( (string) ( $scenario['timezone'] ?? 'UTC' ) );
		$day_counts = array();
		$reviewer_day_counts = array();
		$campaigns = array();
		$processed = 0;

		foreach ( array_slice( $items, 0, self::MAX_SIGNAL_ROWS ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$row = self::sanitize_provider_row( $row );
			++$processed;
			$date = self::date_key( (string) ( $row['scheduled_at'] ?? '' ), $timezone );
			if ( '' !== $date ) {
				$day_counts[ $date ] = ( $day_counts[ $date ] ?? 0 ) + 1;
			}
			$reviewer = self::key( (string) ( $row['reviewer'] ?? '' ) );
			if ( '' !== $reviewer && '' !== $date ) {
				$reviewer_day_key = $date . '|' . $reviewer;
				$reviewer_day_counts[ $reviewer_day_key ] = ( $reviewer_day_counts[ $reviewer_day_key ] ?? 0 ) + 1;
			}
			$campaign = self::key( (string) ( $row['campaign'] ?? '' ) );
			if ( '' !== $campaign ) {
				$campaigns[ $campaign ] = ( $campaigns[ $campaign ] ?? 0 ) + 1;
			}
		}

		$collisions = array_filter( $day_counts, static fn( int $count ): bool => $count > 3 );
		$overload = array_filter( $reviewer_day_counts, static fn( int $count ): bool => $count > $capacity );
		$canonical = self::stable_json( array( 'timezone' => $timezone, 'capacity' => $capacity, 'items' => $items ) );
		return array(
			'advisory_only'          => true,
			'simulation'             => true,
			'scenario_labeled'       => true,
			'auto_schedule'          => false,
			'write_authority'        => false,
			'timezone'               => $timezone,
			'item_count'             => count( $items ),
			'processed_item_count'   => $processed,
			'calendar_collisions'    => $collisions,
			'reviewer_overload'      => $overload,
			'campaign_distribution'  => $campaigns,
			'risk_level'             => ! empty( $collisions ) || ! empty( $overload ) ? 'attention' : 'normal',
			'scenario_hash'          => hash( 'sha256', $canonical ),
		);
	}

	/** @return array<int,array<string,mixed>> */
	private static function signals( string $feature, array $context ): array {
		$signals = array();
		if ( function_exists( 'apply_filters' ) ) {
			$signals = apply_filters( 'spdb/publishing_intelligence_signals', array(), $feature, self::provider_context( $context ) );
		}
		return self::sanitize_rows( is_array( $signals ) ? $signals : array(), self::MAX_SIGNAL_ROWS );
	}

	/** @return array<string,mixed> */
	private static function evaluate( string $feature, array $signals, array $context ): array {
		switch ( $feature ) {
			case 'mission-control': return self::mission_control( $signals );
			case 'experiment-lab': return self::experiments( $signals );
			case 'best-time': return self::best_time( $signals, $context );
			case 'opportunity-radar': return self::opportunities( $signals );
			case 'bottleneck-detector': return self::bottlenecks( $signals );
			case 'review-sla': return self::sla( $signals, $context );
			case 'change-impact-map': return self::impact_map( $signals );
			case 'evidence-freshness': return self::evidence_freshness( $signals );
			case 'medical-preflight': return self::advisory_flags( $signals, 'native_owner_or_human_reviewer', false );
			case 'privacy-leak-guard': return self::privacy_guard( $signals );
			case 'permission-simulator': return self::permission_matrix( $signals );
			case 'semantic-diff': return self::semantic_diff( $signals );
			case 'editorial-playbooks': return array( 'playbooks' => self::playbooks(), 'provider_signals' => self::governance_signal_rows( $signals ), 'bypass_requires_authorized_reason' => true, 'audit_required' => true );
			case 'repurposing-studio': return self::repurposing( $signals );
			case 'audience-board': return self::audience( $signals );
			case 'internal-benchmark': return self::internal_benchmark( $signals );
			case 'external-benchmark': return self::external_benchmark( $signals );
			case 'comment-intelligence': return self::comment_intelligence( $signals );
			case 'evergreen-health': return self::evergreen( $signals, $context );
			case 'localization-center': return self::localization( $signals );
			case 'accessibility-lab': return self::accessibility( $signals );
			case 'provenance-ledger': return self::provenance( $signals );
			case 'what-if-planner': return self::simulate( is_array( $context['scenario'] ?? null ) ? $context['scenario'] : array( 'items' => $signals, 'timezone' => (string) ( $context['timezone'] ?? 'UTC' ) ) );
			case 'ask-dashboard': return array( 'status' => 'use_post_endpoint', 'endpoint' => '/spdb/v1/intelligence/ask', 'auto_action' => false, 'sensitive_input_forbidden' => true );
			default: return array( 'items' => self::safe_default_rows( $signals ), 'count' => count( $signals ), 'read_only' => true );
		}
	}

	/** @return array<string,mixed> */
	private static function mission_control( array $signals ): array {
		$weight = array( 'critical' => 4, 'high' => 3, 'warning' => 2, 'normal' => 1, 'info' => 0 );
		$items = array();
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'label', 'title', 'severity', 'due_at', 'status', 'queue', 'reason_code', 'age_hours' ) );
			$items[] = self::redact_sensitive_text_fields( $item, array( 'label', 'title' ) );
		}
		usort( $items, static function ( array $a, array $b ) use ( $weight ): int {
			$aw = $weight[ self::key( (string) ( $a['severity'] ?? 'normal' ) ) ] ?? 1;
			$bw = $weight[ self::key( (string) ( $b['severity'] ?? 'normal' ) ) ] ?? 1;
			if ( $aw !== $bw ) {
				return $bw <=> $aw;
			}
			return strcmp( (string) ( $a['due_at'] ?? '9999' ), (string) ( $b['due_at'] ?? '9999' ) );
		} );
		return array( 'items' => array_slice( $items, 0, 50 ), 'count' => count( $items ), 'click_time_reauthorization' => true, 'unauthorized_object_details' => false );
	}

	/** @return array<string,mixed> */
	private static function experiments( array $signals ): array {
		$rows = array();
		$threshold = self::privacy_threshold();
		foreach ( $signals as $row ) {
			$cohort = max( 0, (int) ( $row['cohort_count'] ?? 0 ) );
			$base = self::allowlist_row( $row, array( 'experiment_id', 'status', 'variant_ids', 'cohort_count', 'started_at', 'ended_at', 'metric' ) );
			$base['cohort_count'] = $cohort;
			$base['suppressed'] = $cohort < $threshold;
			$base['winner_advisory_only'] = true;
			$base['manual_acceptance_required'] = true;
			if ( ! $base['suppressed'] ) {
				if ( array_key_exists( 'winner', $row ) && is_scalar( $row['winner'] ) ) {
					$winner = self::text( (string) $row['winner'], 120 );
					if ( '' !== $winner && ! self::contains_sensitive_text( $winner ) ) {
						$base['winner'] = $winner;
					} else {
						$base['winner_suppressed'] = true;
					}
				}
				if ( isset( $row['uplift'] ) && is_numeric( $row['uplift'] ) ) {
					$base['uplift'] = (float) $row['uplift'];
				}
				if ( isset( $row['variant_metrics'] ) && is_array( $row['variant_metrics'] ) ) {
					$base['variant_metrics'] = self::privacy_safe_metric_tree( $row['variant_metrics'] );
				}
			}
			$rows[] = $base;
		}
		return array( 'experiments' => $rows, 'auto_apply_winner' => false, 'privacy_threshold' => $threshold );
	}

	/** @return array<string,mixed> */
	private static function best_time( array $signals, array $context ): array {
		$windows = array();
		$threshold = self::privacy_threshold();
		$timezone = self::normalize_timezone( (string) ( $context['timezone'] ?? 'UTC' ) );
		foreach ( $signals as $row ) {
			$observations = max( 0, (int) ( $row['observations'] ?? $row['cohort_count'] ?? 0 ) );
			if ( $observations < $threshold ) {
				continue;
			}
			$window = self::text( (string) ( $row['window'] ?? '' ), 80 );
			if ( '' === $window ) {
				continue;
			}
			$windows[] = array( 'window' => $window, 'score' => round( (float) ( $row['score'] ?? 0 ), 4 ), 'observations' => $observations, 'timezone' => self::normalize_timezone( (string) ( $row['timezone'] ?? $timezone ) ) );
		}
		usort( $windows, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );
		return array( 'status' => empty( $windows ) ? 'unavailable' : 'ready', 'recommended_windows' => array_slice( $windows, 0, 10 ), 'timezone' => $timezone, 'auto_schedule' => false, 'individual_profiling' => false );
	}

	/** @return array<string,mixed> */
	private static function opportunities( array $signals ): array {
		$rows = array();
		$suppressed = 0;
		foreach ( $signals as $row ) {
			$markers = array(
				self::key( (string) ( $row['demand_provider'] ?? '' ) ),
				self::key( (string) ( $row['provider'] ?? '' ) ),
				self::key( (string) ( $row['provenance'] ?? '' ) ),
			);
			if ( ! in_array( 'file26', $markers, true ) ) {
				++$suppressed;
				continue;
			}
			$item = self::allowlist_row( $row, array( 'topic', 'language', 'demand', 'coverage', 'provider', 'demand_provider', 'source', 'source_date', 'provenance', 'generated_at', 'stale' ) );
			$item = self::redact_sensitive_text_fields( $item, array( 'topic', 'source' ) );
			$item['demand'] = round( (float) ( $row['demand'] ?? 0 ), 4 );
			$item['coverage'] = round( (float) ( $row['coverage'] ?? 0 ), 4 );
			$item['opportunity_score'] = round( max( 0.0, $item['demand'] - $item['coverage'] ), 4 );
			$item['demand_owner_verified'] = true;
			$rows[] = $item;
		}
		usort( $rows, static fn( array $a, array $b ): int => ( $b['opportunity_score'] ?? 0 ) <=> ( $a['opportunity_score'] ?? 0 ) );
		return array( 'opportunities' => array_slice( $rows, 0, 50 ), 'untrusted_demand_rows_suppressed' => $suppressed, 'ranking_owner' => 'file26', 'ranking_write_authority' => false, 'paid_or_donor_influence' => false, 'stale_signals_must_be_labeled' => true );
	}

	/** @return array<string,mixed> */
	private static function bottlenecks( array $signals ): array {
		$rows = array();
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'stage', 'queue', 'wait_hours', 'median_wait_hours', 'oldest_wait_hours', 'item_count', 'responsible_queue', 'provider' ) );
			$item['wait_hours'] = round( max( 0, (float) ( $row['wait_hours'] ?? $row['median_wait_hours'] ?? 0 ) ), 2 );
			$rows[] = $item;
		}
		usort( $rows, static fn( array $a, array $b ): int => (float) ( $b['wait_hours'] ?? 0 ) <=> (float) ( $a['wait_hours'] ?? 0 ) );
		return array( 'stages' => $rows, 'worst_stage' => $rows[0]['stage'] ?? '', 'private_reviewer_notes_included' => false, 'purpose' => 'process_improvement' );
	}

	/** @return array<string,mixed> */
	private static function sla( array $signals, array $context ): array {
		$timezone = self::normalize_timezone( (string) ( $context['timezone'] ?? 'UTC' ) );
		$now = self::context_now( $context );
		$counts = array( 'overdue' => 0, 'due_soon' => 0, 'on_track' => 0, 'resolved' => 0, 'invalid' => 0 );
		$items = array();
		foreach ( $signals as $row ) {
			$state = self::key( (string) ( $row['state'] ?? $row['status'] ?? '' ) );
			$is_resolved = in_array( $state, array( 'resolved', 'completed', 'closed', 'published', 'retracted' ), true );
			$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'queue', 'priority', 'due_at', 'status', 'state', 'policy', 'review_type' ) );
			if ( $is_resolved ) {
				$status = 'resolved';
				$due = null;
			} else {
				$due = self::parse_timestamp( (string) ( $row['due_at'] ?? '' ), $timezone );
				if ( null === $due ) {
					$status = 'invalid';
				} elseif ( $due < $now ) {
					$status = 'overdue';
				} elseif ( $due <= $now + self::DUE_SOON_SECONDS ) {
					$status = 'due_soon';
				} else {
					$status = 'on_track';
				}
			}
			++$counts[ $status ];
			$item['sla_status'] = $status;
			$item['timezone'] = $timezone;
			$item['escalation_candidate'] = in_array( $status, array( 'overdue', 'due_soon' ), true );
			$item['realert_allowed'] = ! $is_resolved;
			$item['escalation_key'] = $item['escalation_candidate'] ? hash( 'sha256', (string) ( $item['provider'] ?? '' ) . '|' . (string) ( $item['object_type'] ?? '' ) . '|' . (string) ( $item['object_id'] ?? '' ) . '|' . (string) ( $item['due_at'] ?? '' ) . '|' . (string) ( $item['review_type'] ?? '' ) . '|' . (string) ( $item['policy'] ?? '' ) ) : '';
			$items[] = $item;
		}
		return array( 'counts' => $counts, 'items' => $items, 'timezone' => $timezone, 'notification_owner' => 'file19', 'duplicate_escalation_requires_idempotency_key' => true );
	}

	/** @return array<string,mixed> */
	private static function impact_map( array $signals ): array {
		$items = array();
		foreach ( $signals as $row ) {
			$items[] = self::allowlist_row( $row, array( 'source_provider', 'source_type', 'source_id', 'affected_provider', 'affected_type', 'affected_id', 'relationship', 'status', 'reason_code' ) );
		}
		return array( 'items' => $items, 'count' => count( $items ), 'read_only' => true, 'destructive_cascade' => false, 'native_owner_confirmation_required' => true );
	}

	/** @return array<string,mixed> */
	private static function evidence_freshness( array $signals ): array {
		$allowed = array( 'fresh', 'review_due', 'broken', 'withdrawn', 'superseded', 'expiring', 'unknown' );
		$items = array();
		$counts = array_fill_keys( $allowed, 0 );
		foreach ( $signals as $row ) {
			$status = self::normalize_evidence_status( (string) ( $row['status'] ?? 'unknown' ) );
			$item = self::allowlist_row( $row, array( 'provider', 'source_id', 'source_type', 'title', 'status', 'revision', 'expected_revision', 'expires_at', 'review_due_at', 'replacement_source_id', 'reason_code' ) );
			$item = self::redact_sensitive_text_fields( $item, array( 'title' ) );
			$item['freshness_status'] = $status;
			$item['revision_mismatch'] = isset( $item['revision'], $item['expected_revision'] ) && (string) $item['revision'] !== (string) $item['expected_revision'];
			++$counts[ $status ];
			$items[] = $item;
		}
		return array( 'items' => $items, 'counts' => $counts, 'source_registry_owner' => 'native_owner', 'replacement_suggestion_advisory_only' => true );
	}

	/** @return array<string,mixed> */
	private static function advisory_flags( array $signals, string $decision_authority, bool $auto_block ): array {
		$flags = array();
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'code', 'category', 'severity', 'field', 'location', 'message', 'reason_code', 'remediation', 'remediation_owner', 'provider' ) );
			$item = self::redact_sensitive_text_fields( $item, array( 'message', 'remediation' ) );
			$flags[] = $item;
		}
		return array( 'decision_authority' => $decision_authority, 'auto_block' => $auto_block, 'flags' => $flags, 'raw_sensitive_samples_included' => false );
	}

	/** @return array<string,mixed> */
	private static function privacy_guard( array $signals ): array {
		$data = self::advisory_flags( $signals, 'native_privacy_owner_or_authorized_human', false );
		foreach ( $data['flags'] as &$flag ) {
			unset( $flag['message'], $flag['remediation'] );
		}
		unset( $flag );
		$data['raw_patient_documents_owned'] = false;
		$data['raw_identifiers_returned'] = false;
		$data['free_text_details_returned'] = false;
		$data['privacy_minimized'] = true;
		return $data;
	}

	/** @return array<string,mixed> */
	private static function permission_matrix( array $signals ): array {
		$matrix = array();
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'role', 'state', 'action', 'allowed', 'reason_code', 'authorization_source', 'scope' ) );
			if ( empty( $item['allowed'] ) ) {
				unset( $item['object_id'], $item['object_title'], $item['destination'] );
			}
			$matrix[] = $item;
		}
		return array( 'founder_only' => true, 'impersonation' => false, 'session_swap' => false, 'capability_grant' => false, 'write_authority' => false, 'native_authorization_source' => true, 'matrix' => $matrix );
	}

	/** @return array<string,mixed> */
	private static function semantic_diff( array $signals ): array {
		$classes = array( 'claim', 'evidence', 'medical', 'rights', 'privacy', 'headline', 'factual', 'clinical', 'legal', 'ethical', 'safety', 'citation', 'tone', 'translation', 'structural', 'unknown' );
		$items = array();
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'from_version', 'to_version', 'classification', 'uncertainty', 'risk', 'summary', 'reason_code' ) );
			$class = self::key( (string) ( $item['classification'] ?? 'unknown' ) );
			$item['classification'] = in_array( $class, $classes, true ) ? $class : 'unknown';
			$item['uncertainty'] = max( 0.0, min( 1.0, (float) ( $item['uncertainty'] ?? 1.0 ) ) );
			$item = self::redact_sensitive_text_fields( $item, array( 'summary' ) );
			$items[] = $item;
		}
		return array( 'items' => $items, 'silent_rewrite' => false, 'human_review_required_for_material_change' => true );
	}

	/** @return array<string,mixed> */
	private static function repurposing( array $signals ): array {
		$items = array();
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'source_provider', 'source_type', 'source_id', 'source_version', 'target_format', 'outline', 'summary', 'ai_assisted', 'disclosure_required', 'language' ) );
			foreach ( array( 'outline', 'summary' ) as $text_field ) {
				if ( isset( $item[ $text_field ] ) && self::contains_sensitive_text( (string) $item[ $text_field ] ) ) {
					unset( $item[ $text_field ] );
					$item[ $text_field . '_suppressed' ] = true;
				}
			}
			$items[] = $item;
		}
		return array( 'draft_only' => true, 'auto_publish' => false, 'final_creation_owner' => 'file22_or_native_owner', 'human_acceptance_required' => true, 'clinical_authority' => false, 'signals' => $items );
	}

	/** @return array<string,mixed> */
	private static function audience( array $signals ): array {
		$allowed_dimensions = array( 'aggregate', 'language', 'country', 'region', 'device_class', 'returning_new', 'active_period', 'time_bucket' );
		$rows = array();
		$suppressed = 0;
		$threshold = self::privacy_threshold();
		foreach ( $signals as $row ) {
			$cohort = max( 0, (int) ( $row['cohort_count'] ?? 0 ) );
			if ( $cohort < $threshold ) {
				++$suppressed;
				continue;
			}
			$dimension = self::key( (string) ( $row['dimension'] ?? 'aggregate' ) );
			if ( ! in_array( $dimension, $allowed_dimensions, true ) ) {
				++$suppressed;
				continue;
			}
			$item = self::allowlist_row( $row, array( 'dimension', 'label', 'value', 'cohort_count', 'interval', 'provider' ) );
			$item = self::redact_sensitive_text_fields( $item, array( 'label', 'value' ) );
			$item['dimension'] = $dimension;
			$item['cohort_count'] = $cohort;
			$rows[] = $item;
		}
		return array( 'items' => $rows, 'suppressed_count' => $suppressed, 'privacy_threshold' => $threshold, 'raw_user_list' => false, 'sensitive_profile_dimensions' => false );
	}

	/** @return array<string,mixed> */
	private static function internal_benchmark( array $signals ): array {
		$items = array();
		$suppressed = 0;
		$incomplete = 0;
		$threshold = self::privacy_threshold();
		foreach ( $signals as $row ) {
			$cohort = max( 0, (int) ( $row['cohort_count'] ?? 0 ) );
			if ( $cohort < $threshold ) {
				++$suppressed;
				continue;
			}
			$item = self::allowlist_row( $row, array( 'metric', 'definition', 'label', 'value', 'cohort_count', 'interval', 'benchmark', 'provider' ) );
			if ( '' === trim( (string) ( $item['metric'] ?? '' ) ) || '' === trim( (string) ( $item['definition'] ?? '' ) ) ) {
				++$incomplete;
				continue;
			}
			$item = self::redact_sensitive_text_fields( $item, array( 'definition', 'label', 'value', 'benchmark' ) );
			$item['cohort_count'] = $cohort;
			$items[] = $item;
		}
		return array(
			'items' => $items,
			'suppressed_count' => $suppressed,
			'incomplete_metric_rows_suppressed' => $incomplete,
			'privacy_threshold' => $threshold,
			'metric_definition_required' => true,
			'anonymous_cohorts_only' => true,
			'public_shaming_or_ranking' => false,
			'paid_or_donor_influence' => false,
		);
	}

	/** @return array<string,mixed> */
	private static function external_benchmark( array $signals ): array {
		$items = array();
		$suppressed_incomplete = 0;
		$suppressed_compliance = 0;
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'source', 'source_url', 'source_date', 'retrieved_at', 'metric', 'label', 'value', 'trend', 'provenance', 'terms_status', 'robots_status', 'law_status', 'provider' ) );
			$source = trim( (string) ( $item['source'] ?? '' ) );
			$provenance = trim( (string) ( $item['provenance'] ?? '' ) );
			$source_date = trim( (string) ( $item['source_date'] ?? '' ) );
			if ( '' === $source || '' === $provenance || ! self::valid_public_source_date( $source_date ) ) {
				++$suppressed_incomplete;
				continue;
			}
			if (
				! self::public_source_compliance_allows( (string) ( $item['terms_status'] ?? '' ) )
				|| ! self::public_source_compliance_allows( (string) ( $item['robots_status'] ?? '' ) )
				|| ! self::public_source_compliance_allows( (string) ( $item['law_status'] ?? '' ) )
			) {
				++$suppressed_compliance;
				continue;
			}
			if ( isset( $item['source_url'] ) ) {
				$item['source_url'] = self::safe_public_url( (string) $item['source_url'] );
				if ( '' === $item['source_url'] ) {
					unset( $item['source_url'] );
				}
			}
			$item = self::redact_sensitive_text_fields( $item, array( 'label', 'value', 'trend' ) );
			$items[] = $item;
		}
		return array(
			'items' => $items,
			'incomplete_source_rows_suppressed' => $suppressed_incomplete,
			'compliance_rows_suppressed' => $suppressed_compliance,
			'knowledge_opportunity_only' => true,
			'ranking_manipulation' => false,
			'source_date_and_provenance_required' => true,
			'robots_terms_law_required' => true,
			'provider_disable_path_required' => true,
		);
	}

	/** @return array<string,mixed> */
	private static function comment_intelligence( array $signals ): array {
		$threshold = self::privacy_threshold();
		$items = array();
		$suppressed = 0;
		foreach ( $signals as $row ) {
			$count = max( 0, (int) ( $row['cohort_count'] ?? $row['count'] ?? 0 ) );
			if ( $count < $threshold ) {
				++$suppressed;
				continue;
			}
			$item = self::allowlist_row( $row, array( 'cluster', 'category', 'label', 'cohort_count', 'count', 'trend', 'provider', 'faq_candidate', 'correction_candidate' ) );
			$item = self::redact_sensitive_text_fields( $item, array( 'cluster', 'label' ) );
			$item['cohort_count'] = $count;
			$items[] = $item;
		}
		return array( 'items' => $items, 'suppressed_count' => $suppressed, 'privacy_threshold' => $threshold, 'raw_private_comments' => false, 'raw_user_identity' => false );
	}

	/** @return array<string,mixed> */
	private static function evergreen( array $signals, array $context ): array {
		$timezone = self::normalize_timezone( (string) ( $context['timezone'] ?? 'UTC' ) );
		$today = self::context_now( $context );
		$counts = array( 'fresh' => 0, 'review_soon' => 0, 'outdated' => 0, 'broken_evidence' => 0, 'superseded' => 0 );
		$items = array();
		foreach ( $signals as $row ) {
			$evidence_status = self::normalize_evidence_status( (string) ( $row['evidence_status'] ?? '' ) );
			if ( in_array( $evidence_status, array( 'broken', 'withdrawn' ), true ) ) {
				$status = 'broken_evidence';
			} elseif ( 'superseded' === $evidence_status ) {
				$status = 'superseded';
			} else {
				$status = self::normalize_evergreen_status( (string) ( $row['status'] ?? '' ) );
				if ( '' === $status ) {
					$last = self::parse_timestamp( (string) ( $row['last_reviewed_at'] ?? '' ), $timezone );
					$interval = max( 30, min( 1095, (int) ( $row['review_interval_days'] ?? 365 ) ) );
					$due = null === $last ? 0 : $last + $interval * self::day_seconds();
					if ( 0 === $due || $due < $today ) {
						$status = 'outdated';
					} elseif ( $due <= $today + 30 * self::day_seconds() ) {
						$status = 'review_soon';
					} else {
						$status = 'fresh';
					}
				}
			}
			++$counts[ $status ];
			$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'title', 'status', 'last_reviewed_at', 'review_interval_days', 'evidence_status', 'source_revision' ) );
			$item['health_status'] = $status;
			$items[] = $item;
		}
		return array( 'counts' => $counts, 'items' => $items, 'auto_delete' => false, 'broken_or_superseded_evidence_degrades_immediately' => true );
	}

	/** @return array<string,mixed> */
	private static function localization( array $signals ): array {
		$items = array();
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'language', 'direction', 'translation_status', 'source_version', 'source_hash', 'translation_source_version', 'terminology_status', 'updated_at' ) );
			$direction = self::key( (string) ( $item['direction'] ?? '' ) );
			$item['direction'] = in_array( $direction, array( 'rtl', 'ltr' ), true ) ? $direction : 'unknown';
			$item['source_version_mismatch'] = isset( $item['source_version'], $item['translation_source_version'] ) && (string) $item['source_version'] !== (string) $item['translation_source_version'];
			$items[] = $item;
		}
		return array( 'items' => $items, 'source_version_link_required' => true, 'rtl_ltr_metadata_required' => true );
	}

	/** @return array<string,mixed> */
	private static function accessibility( array $signals ): array {
		$data = self::advisory_flags( $signals, 'authorized_human_or_native_owner', false );
		$motion = self::signal_has_token( $signals, array( 'reduced-motion', 'prefers-reduced-motion', 'motion-reduction' ) );
		$reduced_data = self::signal_has_token( $signals, array( 'reduced-data', 'prefers-reduced-data', 'data-saver', 'low-bandwidth' ) );
		$data['readiness_only'] = true;
		$data['certification_claim'] = false;
		$data['wcag_readiness_traceable'] = ! empty( $data['flags'] );
		$data['reduced_motion_considered'] = $motion;
		$data['reduced_data_considered'] = $reduced_data;
		$data['reduced_motion_and_data_considered'] = $motion && $reduced_data;
		$data['readiness_status'] = empty( $data['flags'] ) ? 'unavailable' : 'evidence_present';
		return $data;
	}

	/** @return array<string,mixed> */
	private static function provenance( array $signals ): array {
		$items = array();
		$allowed_statuses = array( 'unknown', 'unverified', 'verified', 'invalid', 'unavailable', 'not_applicable' );
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'origin', 'author_type', 'ai_assisted', 'translated', 'imported', 'source_id', 'source_version', 'authenticity_status', 'signature_status', 'tamper_evidence', 'recorded_at' ) );
			$status = str_replace( '-', '_', self::key( (string) ( $item['authenticity_status'] ?? 'unknown' ) ) );
			$signature = str_replace( '-', '_', self::key( (string) ( $item['signature_status'] ?? 'unknown' ) ) );
			$item['authenticity_status'] = in_array( $status, $allowed_statuses, true ) ? $status : 'unknown';
			$item['signature_status'] = in_array( $signature, $allowed_statuses, true ) ? $signature : 'unknown';
			$item['badge_eligible'] = 'verified' === $item['authenticity_status']
				&& 'verified' === $item['signature_status']
				&& '' !== trim( (string) ( $item['provider'] ?? '' ) )
				&& '' !== trim( (string) ( $item['tamper_evidence'] ?? '' ) );
			$item['fabricated_badge'] = false;
			$item['claim_level'] = 'provider_asserted_evidence';
			$items[] = $item;
		}
		return array( 'items' => $items, 'missing_provenance_status' => 'unknown', 'native_truth_preserved' => true, 'fabricated_authenticity_badges' => false );
	}

	/** @return array<string,mixed> */
	private static function privacy_thresholded( array $signals, array $fields ): array {
		$visible = array();
		$suppressed = 0;
		$threshold = self::privacy_threshold();
		foreach ( $signals as $row ) {
			if ( max( 0, (int) ( $row['cohort_count'] ?? 0 ) ) < $threshold ) {
				++$suppressed;
				continue;
			}
			$visible[] = self::allowlist_row( $row, $fields );
		}
		return array( 'items' => $visible, 'suppressed_count' => $suppressed, 'privacy_threshold' => $threshold );
	}

	/** @return array<string,array<string,mixed>> */
	private static function playbooks(): array {
		return array(
			'successful_case' => array( 'label' => 'Successful Case', 'checks' => array( 'patient-consent', 'de-identification', 'clinical-claim-review', 'sources', 'image-metadata', 'correction-path' ), 'bypass_requires_reason' => true, 'audit_required' => true ),
			'research' => array( 'label' => 'Research Publication', 'checks' => array( 'sources', 'method-disclosure', 'conflict-disclosure', 'rights', 'accessibility', 'review' ), 'bypass_requires_reason' => true, 'audit_required' => true ),
			'lesson' => array( 'label' => 'Educational Lesson', 'checks' => array( 'learning-objective', 'sources', 'medical-safety', 'accessibility', 'translation-status', 'review' ), 'bypass_requires_reason' => true, 'audit_required' => true ),
			'official_announcement' => array( 'label' => 'Official Announcement', 'checks' => array( 'founder-or-authorized-role', 'fact-check', 'effective-date', 'distribution-plan', 'correction-path' ), 'bypass_requires_reason' => true, 'audit_required' => true ),
			'correction' => array( 'label' => 'Correction / Retraction', 'checks' => array( 'reason', 'evidence', 'impact-map', 'native-owner-action', 'notification-plan', 'audit' ), 'bypass_requires_reason' => true, 'audit_required' => true ),
			'media' => array( 'label' => 'Video / Reel / PDF Publication', 'checks' => array( 'rights', 'captions-or-transcript', 'alt-or-description', 'source-links', 'privacy-scan', 'destination' ), 'bypass_requires_reason' => true, 'audit_required' => true ),
		);
	}

	/** @return array<string,mixed> */
	private static function request_context( $request ): array {
		$context = array();
		foreach ( array( 'scope', 'provider', 'object_type', 'object_id', 'language', 'date_from', 'date_to', 'timezone' ) as $key ) {
			$value = self::request_value( $request, $key );
			if ( is_scalar( $value ) ) {
				$context[ $key ] = self::text( (string) $value, 120 );
			}
		}
		if ( function_exists( 'get_current_user_id' ) ) {
			$context['viewer_user_id'] = (int) get_current_user_id();
		}
		return self::normalize_context( $context );
	}

	/** @return array<string,mixed> */
	private static function normalize_context( array $context ): array {
		$context = self::sanitize_array( $context );
		$scope = self::key( (string) ( $context['scope'] ?? 'own' ) );
		$context['scope'] = in_array( $scope, array( 'own', 'institution' ), true ) ? $scope : 'own';
		$context['timezone'] = self::normalize_timezone( (string) ( $context['timezone'] ?? 'UTC' ) );
		if ( isset( $context['viewer_user_id'] ) ) {
			$context['viewer_user_id'] = max( 0, (int) $context['viewer_user_id'] );
		}
		return $context;
	}

	/** @return array<string,mixed> */
	private static function provider_context( array $context ): array {
		$context = self::normalize_context( $context );
		$context['authorized_scope'] = (string) $context['scope'];
		$context['canonical_write_authority'] = false;
		return $context;
	}

	/** @return array<string,mixed> */
	private static function feature( string $id, string $label, string $priority, string $purpose, string $ownership ): array {
		return array( 'id' => $id, 'key' => '', 'label' => $label, 'priority' => $priority, 'purpose' => $purpose, 'ownership' => $ownership, 'canonical_write_authority' => false, 'advisory_or_projection' => true );
	}

	/** @return array<int,array<string,mixed>> */
	private static function sanitize_rows( array $rows, int $limit ): array {
		$out = array();
		foreach ( array_slice( $rows, 0, $limit ) as $row ) {
			if ( is_array( $row ) ) {
				$out[] = self::sanitize_provider_row( $row );
			}
		}
		return $out;
	}

	/** @return array<string,mixed> */
	private static function sanitize_provider_row( array $row ): array {
		$clean = self::sanitize_array( $row );
		return self::remove_sensitive_keys( $clean );
	}

	/** @return array<string,mixed> */
	private static function remove_sensitive_keys( array $value, int $depth = 0 ): array {
		if ( $depth >= self::MAX_DEPTH ) {
			return array();
		}
		$out = array();
		foreach ( $value as $key => $item ) {
			if ( self::is_sensitive_key( (string) $key ) ) {
				continue;
			}
			if ( is_array( $item ) ) {
				$out[ $key ] = self::remove_sensitive_keys( $item, $depth + 1 );
			} else {
				$out[ $key ] = $item;
			}
		}
		return $out;
	}

	/** @return array<string,mixed> */
	private static function sanitize_array( array $value, int $depth = 0 ): array {
		if ( $depth >= self::MAX_DEPTH ) {
			return array();
		}
		$out = array();
		foreach ( array_slice( $value, 0, 100, true ) as $key => $item ) {
			$clean_key = self::key( (string) $key );
			if ( '' === $clean_key ) {
				continue;
			}
			if ( is_array( $item ) ) {
				$out[ $clean_key ] = self::sanitize_array( $item, $depth + 1 );
			} elseif ( is_bool( $item ) || is_int( $item ) || is_float( $item ) ) {
				$out[ $clean_key ] = $item;
			} elseif ( is_scalar( $item ) ) {
				$out[ $clean_key ] = self::text( (string) $item, 1000 );
			}
		}
		return $out;
	}

	/** @param array<int,string> $fields @return array<string,mixed> */
	private static function allowlist_row( array $row, array $fields ): array {
		$row = self::sanitize_provider_row( $row );
		$out = array();
		foreach ( $fields as $field ) {
			$key = self::key( $field );
			if ( array_key_exists( $key, $row ) ) {
				$out[ $key ] = $row[ $key ];
			}
		}
		return $out;
	}

	/** @return array<int,array<string,mixed>> */
	private static function safe_default_rows( array $signals ): array {
		$rows = array();
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'label', 'title', 'status', 'state', 'severity', 'reason_code', 'updated_at' ) );
			$rows[] = self::redact_sensitive_text_fields( $item, array( 'label', 'title' ) );
		}
		return $rows;
	}

	/** @return array<int,array<string,mixed>> */
	private static function governance_signal_rows( array $signals ): array {
		$rows = array();
		foreach ( $signals as $row ) {
			$rows[] = self::allowlist_row( $row, array( 'provider', 'playbook', 'status', 'check', 'reason_code', 'updated_at' ) );
		}
		return $rows;
	}

	/** @return array<int,array<string,mixed>> */
	private static function source_rows( array $rows ): array {
		$out = array();
		foreach ( array_slice( $rows, 0, 50 ) as $row ) {
			if ( is_array( $row ) ) {
				$item = self::allowlist_row( $row, array( 'provider', 'source_id', 'title', 'revision', 'url', 'retrieved_at', 'published_at' ) );
				$item = self::redact_sensitive_text_fields( $item, array( 'title' ) );
				if ( isset( $item['url'] ) ) {
					$item['url'] = self::safe_public_url( (string) $item['url'] );
					if ( '' === $item['url'] ) {
						unset( $item['url'] );
					}
				}
				$out[] = $item;
			}
		}
		return $out;
	}

	/** @return mixed */
	private static function privacy_safe_metric_tree( $value, int $depth = 0 ) {
		if ( $depth >= self::MAX_DEPTH ) {
			return array();
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( array_slice( $value, 0, 100, true ) as $key => $item ) {
				$clean_key = self::key( (string) $key );
				if ( '' === $clean_key || self::is_sensitive_key( $clean_key ) ) {
					continue;
				}
				$clean = self::privacy_safe_metric_tree( $item, $depth + 1 );
				if ( null !== $clean ) {
					$out[ $clean_key ] = $clean;
				}
			}
			return $out;
		}
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}
		if ( is_scalar( $value ) ) {
			$text = self::text( (string) $value, 160 );
			return '' !== $text && ! self::contains_sensitive_text( $text ) ? $text : null;
		}
		return null;
	}

	/** @param array<string,mixed> $item @param string[] $fields @return array<string,mixed> */
	private static function redact_sensitive_text_fields( array $item, array $fields ): array {
		foreach ( $fields as $field ) {
			if ( isset( $item[ $field ] ) && is_scalar( $item[ $field ] ) && self::contains_sensitive_text( (string) $item[ $field ] ) ) {
				unset( $item[ $field ] );
				$item[ $field . '_suppressed' ] = true;
			}
		}
		return $item;
	}

	private static function privacy_threshold(): int {
		$threshold = self::MIN_PRIVACY_THRESHOLD;
		if ( function_exists( 'apply_filters' ) ) {
			$threshold = (int) apply_filters( 'spdb/publishing_intelligence_privacy_threshold', $threshold );
		}
		return max( self::MIN_PRIVACY_THRESHOLD, min( 10000, $threshold ) );
	}

	private static function is_sensitive_key( string $key ): bool {
		$key = self::key( $key );
		return 1 === preg_match( '/(?:^|_)(patient(?:_id|_name)?|phone|mobile|email|address|cnic|national_id|passport|id_document|identity_document|raw_patient|raw_comment|private_comment|private_message|message_body|reviewer_note|clinical_note|diagnosis|prescription|consent_document|consent_evidence|secret|token|password|otp|recovery_code|api_key|authorization)(?:$|_)/', $key );
	}

	private static function contains_sensitive_text( string $value ): bool {
		$value = trim( $value );
		if ( '' === $value ) {
			return false;
		}
		if ( 1 === preg_match( '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $value ) ) {
			return true;
		}
		if ( 1 === preg_match( '/\b\d{5}-\d{7}-\d\b/', $value ) ) {
			return true;
		}
		if ( 1 === preg_match( '/\b(?:passport|cnic|national\s+id|phone|mobile|patient\s+id|password|otp|recovery\s+code)\s*[:#-]?\s*[A-Z0-9+()-]{5,}\b/i', $value ) ) {
			return true;
		}
		if ( 1 === preg_match( '/\b(?:patient\s+name|home\s+address|postal\s+address|street\s+address|address)\s*[:#-]\s*.{3,120}/iu', $value ) ) {
			return true;
		}
		if ( preg_match_all( '/(?<![A-Za-z0-9])\+?[0-9][0-9 ()-]{7,}[0-9](?![A-Za-z0-9])/', $value, $matches ) ) {
			foreach ( $matches[0] as $candidate ) {
				$digits = preg_replace( '/\D+/', '', (string) $candidate );
				if ( is_string( $digits ) && strlen( $digits ) >= 9 && strlen( $digits ) <= 15 ) {
					return true;
				}
			}
		}
		return false;
	}

	private static function requested_feature( $request ): string {
		$feature = self::key( self::request_scalar( $request, 'feature', 80 ) );
		if ( '' !== $feature ) {
			return $feature;
		}
		$route = '';
		if ( is_object( $request ) && method_exists( $request, 'get_route' ) ) {
			$route = (string) $request->get_route();
		}
		if ( false !== strpos( $route, '/intelligence/ask' ) ) {
			return 'ask-dashboard';
		}
		if ( false !== strpos( $route, '/intelligence/simulate' ) ) {
			return 'what-if-planner';
		}
		return '';
	}

	private static function request_scalar( $request, string $key, int $max ): string {
		$value = self::request_value( $request, $key );
		return is_scalar( $value ) ? self::text( (string) $value, $max ) : '';
	}

	/** @return mixed */
	private static function request_value( $request, string $key ) {
		if ( is_object( $request ) && method_exists( $request, 'get_param' ) ) {
			return $request->get_param( $key );
		}
		if ( is_array( $request ) && array_key_exists( $key, $request ) ) {
			return $request[ $key ];
		}
		if ( $request instanceof ArrayAccess && isset( $request[ $key ] ) ) {
			return $request[ $key ];
		}
		return null;
	}

	private static function is_institutional( int $user_id ): bool {
		if ( ! class_exists( 'SPDB_Membership_Guard' ) ) {
			return false;
		}
		if ( method_exists( 'SPDB_Membership_Guard', 'assertions' ) ) {
			$assertions = SPDB_Membership_Guard::assertions( $user_id );
			return is_array( $assertions )
				&& true === ( $assertions['institutional_account'] ?? false )
				&& true === ( $assertions['approved'] ?? false )
				&& true === ( $assertions['eligible'] ?? false )
				&& false === ( $assertions['suspended'] ?? true );
		}
		return method_exists( 'SPDB_Membership_Guard', 'is_user_founder' ) && SPDB_Membership_Guard::is_user_founder( $user_id );
	}

	private static function safe_public_url( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		$parts = parse_url( $value );
		if ( ! is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) ) {
			return '';
		}
		$scheme = strtolower( (string) $parts['scheme'] );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}
		$host = strtolower( rtrim( (string) $parts['host'], '.' ) );
		$ip_host = trim( $host, '[]' );
		if ( '' === $host || 'localhost' === $host || false !== strpos( $host, '.localhost' ) || false !== strpos( $host, '.local' ) || false !== strpos( $host, '.internal' ) || false !== strpos( $host, '.test' ) || false !== strpos( $host, '.invalid' ) ) {
			return '';
		}
		if ( false !== filter_var( $ip_host, FILTER_VALIDATE_IP ) ) {
			if ( false === filter_var( $ip_host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return '';
			}
		} elseif ( false === strpos( $host, '.' ) ) {
			return '';
		}
		return self::text( $value, 1000 );
	}

	private static function valid_public_source_date( string $value ): bool {
		$value = trim( $value );
		if ( '' === $value || 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})(?:[T ]([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)?)?$/', $value, $matches ) ) {
			return false;
		}
		if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return false;
		}
		$timestamp = self::parse_timestamp( $value, 'UTC' );
		return null !== $timestamp && $timestamp <= time() + 300;
	}

	private static function public_source_compliance_allows( string $value ): bool {
		$value = str_replace( '-', '_', self::key( $value ) );
		return in_array( $value, array( 'allowed', 'approved', 'compliant', 'permitted', 'not_applicable' ), true );
	}

	/** @param array<int,array<string,mixed>> $signals @param string[] $tokens */
	private static function signal_has_token( array $signals, array $tokens ): bool {
		foreach ( $signals as $row ) {
			foreach ( array( 'code', 'category', 'field', 'reason_code', 'check' ) as $field ) {
				if ( ! isset( $row[ $field ] ) || ! is_scalar( $row[ $field ] ) ) {
					continue;
				}
				$value = str_replace( '_', '-', self::key( (string) $row[ $field ] ) );
				foreach ( $tokens as $token ) {
					$needle = str_replace( '_', '-', self::key( $token ) );
					if ( '' !== $needle && false !== strpos( $value, $needle ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	private static function normalize_timezone( string $value ): string {
		$value = self::text( $value, 80 );
		if ( '' === $value ) {
			return 'UTC';
		}
		try {
			new DateTimeZone( $value );
			return $value;
		} catch ( Exception $exception ) {
			return 'UTC';
		}
	}

	private static function parse_timestamp( string $value, string $timezone ): ?int {
		$value = trim( $value );
		if ( '' === $value || 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})(?:[ T]([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?(Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)?)?$/', $value, $matches ) ) {
			return null;
		}
		if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return null;
		}
		try {
			$has_offset = isset( $matches[7] ) && '' !== (string) $matches[7];
			$zone = new DateTimeZone( $has_offset ? 'UTC' : self::normalize_timezone( $timezone ) );
			$date = new DateTimeImmutable( $value, $zone );
			return $date->getTimestamp();
		} catch ( Exception $exception ) {
			return null;
		}
	}

	private static function context_now( array $context ): int {
		if ( isset( $context['now'] ) && is_numeric( $context['now'] ) ) {
			return (int) $context['now'];
		}
		return time();
	}

	private static function normalize_evidence_status( string $value ): string {
		$value = str_replace( '-', '_', self::key( $value ) );
		$map = array(
			'fresh' => 'fresh',
			'review_due' => 'review_due',
			'review_d' => 'review_due',
			'broken' => 'broken',
			'broken_evidence' => 'broken',
			'withdrawn' => 'withdrawn',
			'superseded' => 'superseded',
			'expiring' => 'expiring',
			'expired' => 'expiring',
			'unknown' => 'unknown',
		);
		return $map[ $value ] ?? 'unknown';
	}

	private static function normalize_evergreen_status( string $value ): string {
		$value = str_replace( '-', '_', self::key( $value ) );
		$map = array(
			'fresh' => 'fresh',
			'review_soon' => 'review_soon',
			'reviewsoon' => 'review_soon',
			'outdated' => 'outdated',
			'broken_evidence' => 'broken_evidence',
			'brokenevidence' => 'broken_evidence',
			'superseded' => 'superseded',
		);
		return $map[ $value ] ?? '';
	}

	private static function stable_json( array $value ): string {
		$value = self::sort_recursive( $value );
		$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $value ) : json_encode( $value );
		return is_string( $encoded ) ? $encoded : '';
	}

	/** @return array<mixed> */
	private static function sort_recursive( array $value ): array {
		foreach ( $value as &$item ) {
			if ( is_array( $item ) ) {
				$item = self::sort_recursive( $item );
			}
		}
		unset( $item );
		if ( self::is_assoc( $value ) ) {
			ksort( $value );
		}
		return $value;
	}

	private static function is_assoc( array $value ): bool {
		if ( array() === $value ) {
			return false;
		}
		return array_keys( $value ) !== range( 0, count( $value ) - 1 );
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

	private static function date_key( string $value, string $timezone ): string {
		$time = self::parse_timestamp( $value, $timezone );
		if ( null === $time ) {
			return '';
		}
		try {
			$date = ( new DateTimeImmutable( '@' . $time ) )->setTimezone( new DateTimeZone( self::normalize_timezone( $timezone ) ) );
			return $date->format( 'Y-m-d' );
		} catch ( Exception $exception ) {
			return '';
		}
	}

	private static function day_seconds(): int {
		return defined( 'DAY_IN_SECONDS' ) ? (int) DAY_IN_SECONDS : 86400;
	}
}
