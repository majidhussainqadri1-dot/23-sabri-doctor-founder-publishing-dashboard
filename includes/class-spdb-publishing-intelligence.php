<?php
/**
 * File 23 Future Publishing Intelligence — 24 governed advisory facilities.
 *
 * This layer is projection/advisory only. It never owns canonical content,
 * search/ranking, notification delivery, visual presentation, identity,
 * authorization, patient data, or native publication state.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Publishing_Intelligence {
	public const DEFAULT_PRIVACY_THRESHOLD = 20;
	private const CATALOG_VERSION = '2026-08-10';

	/** @return array<int,array<string,mixed>> */
	public static function catalog(): array {
		$features = array(
			array( 'id' => 'F23-FPI-01', 'key' => 'publishing_mission_control', 'label' => 'Publishing Mission Control', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-02', 'key' => 'experiment_lab', 'label' => 'Experiment Lab', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-03', 'key' => 'best_time_to_publish', 'label' => 'Best-Time-to-Publish Intelligence', 'priority' => 'P0', 'privacy_thresholded' => true ),
			array( 'id' => 'F23-FPI-04', 'key' => 'ask_publishing_dashboard', 'label' => 'Ask Publishing Dashboard', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-05', 'key' => 'content_opportunity_radar', 'label' => 'Content Opportunity Radar', 'priority' => 'P0', 'canonical_owner' => 'file26' ),
			array( 'id' => 'F23-FPI-06', 'key' => 'editorial_bottleneck_detector', 'label' => 'Editorial Bottleneck Detector', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-07', 'key' => 'review_sla_escalation', 'label' => 'Review SLA & Escalation Engine', 'priority' => 'P0', 'delivery_owner' => 'file19' ),
			array( 'id' => 'F23-FPI-08', 'key' => 'semantic_change_impact_map', 'label' => 'Semantic Change Impact Map', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-09', 'key' => 'evidence_freshness_monitor', 'label' => 'Evidence Freshness Monitor', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-10', 'key' => 'medical_ethical_preflight', 'label' => 'Medical & Ethical Preflight', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-11', 'key' => 'privacy_leak_guard', 'label' => 'Privacy Leak Guard', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-12', 'key' => 'role_permission_simulator', 'label' => 'Role & Permission Simulator', 'priority' => 'P0' ),
			array( 'id' => 'F23-FPI-13', 'key' => 'semantic_version_diff', 'label' => 'Semantic Version Diff', 'priority' => 'P1' ),
			array( 'id' => 'F23-FPI-14', 'key' => 'editorial_playbooks', 'label' => 'Editorial Playbooks', 'priority' => 'P1' ),
			array( 'id' => 'F23-FPI-15', 'key' => 'cross_format_repurposing', 'label' => 'Cross-Format Repurposing Studio', 'priority' => 'P1', 'final_creation_owner' => 'file22' ),
			array( 'id' => 'F23-FPI-16', 'key' => 'audience_intelligence', 'label' => 'Audience Intelligence Board', 'priority' => 'P1', 'privacy_thresholded' => true ),
			array( 'id' => 'F23-FPI-17', 'key' => 'internal_benchmarking', 'label' => 'Internal Benchmarking', 'priority' => 'P1', 'privacy_thresholded' => true ),
			array( 'id' => 'F23-FPI-18', 'key' => 'external_public_benchmark_watch', 'label' => 'External Public Benchmark Watch', 'priority' => 'P1' ),
			array( 'id' => 'F23-FPI-19', 'key' => 'comment_question_intelligence', 'label' => 'Comment & Question Intelligence', 'priority' => 'P1', 'privacy_thresholded' => true ),
			array( 'id' => 'F23-FPI-20', 'key' => 'evergreen_content_health', 'label' => 'Evergreen Content Health', 'priority' => 'P1' ),
			array( 'id' => 'F23-FPI-21', 'key' => 'localization_command_center', 'label' => 'Localization Command Center', 'priority' => 'P1' ),
			array( 'id' => 'F23-FPI-22', 'key' => 'accessibility_publishing_lab', 'label' => 'Accessibility Publishing Lab', 'priority' => 'P1', 'public_visual_owner' => 'file25' ),
			array( 'id' => 'F23-FPI-23', 'key' => 'content_provenance_authenticity', 'label' => 'Content Provenance & Authenticity Ledger', 'priority' => 'P2' ),
			array( 'id' => 'F23-FPI-24', 'key' => 'editorial_digital_twin', 'label' => 'Editorial Digital Twin / What-if Planner', 'priority' => 'P2' ),
		);

		foreach ( $features as &$feature ) {
			$feature['catalog_version']             = self::CATALOG_VERSION;
			$feature['canonical_write_authority']   = false;
			$feature['advisory_or_projection_only'] = true;
			$feature['authorization_owner']         = 'file00';
			$feature['publication_owner']           = 'file21';
			$feature['notification_owner']          = 'file19';
			$feature['assurance_owner']             = 'file24';
			$feature['search_ranking_owner']        = 'file26';
			$feature['donor_payment_influence']     = false;
			$feature['auto_publish']                = false;
			$feature['auto_schedule']               = false;
		}
		unset( $feature );

		return $features;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			'spdb/v1',
			'/intelligence/catalog',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_catalog' ),
				'permission_callback' => array( $this, 'can_read' ),
			)
		);
		register_rest_route(
			'spdb/v1',
			'/intelligence/(?P<feature>F23-FPI-\d{2})',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_feature' ),
				'permission_callback' => array( $this, 'can_read' ),
			)
		);
		register_rest_route(
			'spdb/v1',
			'/intelligence/ask',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_ask' ),
				'permission_callback' => array( $this, 'can_read' ),
			)
		);
		register_rest_route(
			'spdb/v1',
			'/intelligence/simulate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_simulate' ),
				'permission_callback' => array( $this, 'can_read' ),
			)
		);
	}

	public function can_read(): bool {
		$user_id = get_current_user_id();
		return is_user_logged_in()
			&& $user_id > 0
			&& SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id )
			&& SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' );
	}

	public function rest_catalog() {
		return rest_ensure_response(
			array(
				'catalog_version' => self::CATALOG_VERSION,
				'feature_count'   => 24,
				'features'        => self::catalog(),
				'privacy_threshold' => self::privacy_threshold(),
				'canonical_write_authority' => false,
			)
		);
	}

	public function rest_feature( WP_REST_Request $request ) {
		$id = strtoupper( sanitize_text_field( (string) $request['feature'] ) );
		$feature = self::find_feature( $id );
		if ( null === $feature ) {
			return new WP_Error( 'spdb_intelligence_feature_unknown', __( 'The requested publishing-intelligence feature is not registered.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $this->snapshot( $feature ) );
	}

	public function rest_ask( WP_REST_Request $request ) {
		$params = $request->get_params();
		$question = isset( $params['question'] ) && is_scalar( $params['question'] ) ? trim( sanitize_text_field( (string) $params['question'] ) ) : '';
		if ( '' === $question || strlen( $question ) > 1000 || self::contains_sensitive_text( $question ) ) {
			return new WP_Error( 'spdb_intelligence_question_invalid', __( 'The question is empty, too long, or contains prohibited sensitive data.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}

		$context = array(
			'user_id'                   => get_current_user_id(),
			'catalog_version'           => self::CATALOG_VERSION,
			'canonical_write_authority' => false,
			'execution_allowed'         => false,
			'allowed_source'             => 'authorized_projections_only',
		);
		$result = apply_filters( 'spdb/publishing_intelligence_ask', null, $question, $context );
		if ( ! is_array( $result ) || empty( $result['answer'] ) || ! is_scalar( $result['answer'] ) ) {
			return new WP_Error( 'spdb_intelligence_ask_unavailable', __( 'No approved publishing-intelligence answer provider is available. No action was taken.', 'sabri-publishing-dashboard' ), array( 'status' => 503 ) );
		}
		$answer = trim( wp_strip_all_tags( (string) $result['answer'] ) );
		if ( '' === $answer || self::contains_sensitive_text( $answer ) ) {
			return new WP_Error( 'spdb_intelligence_ask_unsafe', __( 'The provider response did not pass the publishing-intelligence privacy boundary.', 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
		}
		$sources = array();
		foreach ( array_slice( is_array( $result['sources'] ?? null ) ? $result['sources'] : array(), 0, 20 ) as $source ) {
			if ( is_scalar( $source ) ) {
				$safe = substr( sanitize_text_field( (string) $source ), 0, 240 );
				if ( '' !== $safe && ! self::contains_sensitive_text( $safe ) ) {
					$sources[] = $safe;
				}
			}
		}
		return rest_ensure_response(
			array(
				'answer'                    => substr( $answer, 0, 8000 ),
				'sources'                   => $sources,
				'advisory'                  => true,
				'execution_allowed'         => false,
				'canonical_write_authority' => false,
			)
		);
	}

	public function rest_simulate( WP_REST_Request $request ) {
		$params = $request->get_params();
		$scenario = self::sanitize_projection_value( $params['scenario'] ?? array(), 0 );
		if ( ! is_array( $scenario ) ) {
			return new WP_Error( 'spdb_intelligence_simulation_invalid', __( 'The what-if scenario is invalid.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}
		$encoded = wp_json_encode( $scenario );
		$encoded = is_string( $encoded ) ? $encoded : '{}';
		return rest_ensure_response(
			array(
				'feature_id'                => 'F23-FPI-24',
				'scenario_fingerprint'      => hash( 'sha256', $encoded ),
				'input'                     => $scenario,
				'result'                    => array(
					'status' => empty( $scenario ) ? 'insufficient_input' : 'simulated_read_only',
					'note'   => __( 'This result is a read-only scenario projection. It does not change calendars, assignments, campaigns, publications, or native provider state.', 'sabri-publishing-dashboard' ),
				),
				'side_effects'              => false,
				'auto_schedule'             => false,
				'canonical_write_authority' => false,
			)
		);
	}

	/** @param array<string,mixed> $feature @return array<string,mixed> */
	private function snapshot( array $feature ): array {
		$signals = apply_filters(
			'spdb/publishing_intelligence_signals',
			array(),
			array(
				'feature_id' => $feature['id'],
				'feature_key' => $feature['key'],
				'user_id'     => get_current_user_id(),
			)
		);
		$signals = self::sanitize_projection_value( is_array( $signals ) ? $signals : array(), 0 );
		$signals = is_array( $signals ) ? $signals : array();
		$threshold = self::privacy_threshold();
		$cohort = isset( $signals['cohort_count'] ) && is_numeric( $signals['cohort_count'] ) ? max( 0, (int) $signals['cohort_count'] ) : null;
		$suppressed = ! empty( $feature['privacy_thresholded'] ) && null !== $cohort && $cohort < $threshold;
		if ( $suppressed ) {
			$signals = array(
				'cohort_count' => $cohort,
				'suppressed'   => true,
				'reason'       => 'minimum_privacy_threshold',
			);
		}

		return array(
			'feature'                   => $feature,
			'available'                 => ! empty( $signals ),
			'signals'                   => $signals,
			'privacy_threshold'         => $threshold,
			'suppressed'                => $suppressed,
			'advisory'                  => true,
			'canonical_write_authority' => false,
			'generated_at_gmt'          => gmdate( 'c' ),
		);
	}

	private static function privacy_threshold(): int {
		$configured = self::DEFAULT_PRIVACY_THRESHOLD;
		if ( class_exists( 'SPDB_Admin_Settings' ) ) {
			$settings = SPDB_Admin_Settings::get();
			$configured = (int) ( $settings['analytics_min_cohort'] ?? self::DEFAULT_PRIVACY_THRESHOLD );
		}
		$filtered = apply_filters( 'spdb/publishing_intelligence_privacy_threshold', max( self::DEFAULT_PRIVACY_THRESHOLD, $configured ) );
		return min( 10000, max( self::DEFAULT_PRIVACY_THRESHOLD, (int) $filtered ) );
	}

	/** @return array<string,mixed>|null */
	private static function find_feature( string $id ): ?array {
		foreach ( self::catalog() as $feature ) {
			if ( $id === $feature['id'] ) {
				return $feature;
			}
		}
		return null;
	}

	private static function contains_sensitive_text( string $value ): bool {
		return 1 === preg_match(
			'/(?:\b\d{5}-\d{7}-\d\b|\b(?:\+?92|0)3\d{9}\b|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|password|one[- ]?time password|patient[ _-]?id|passport|national[ _-]?id|cnic|private[ _-]?message|appointment[ _-]?detail)/i',
			$value
		);
	}

	/**
	 * Recursively sanitize bounded advisory projection data.
	 *
	 * @return mixed
	 */
	private static function sanitize_projection_value( $value, int $depth ) {
		if ( $depth > 4 ) {
			return null;
		}
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			$value = substr( sanitize_text_field( $value ), 0, 500 );
			return self::contains_sensitive_text( $value ) ? null : $value;
		}
		if ( ! is_array( $value ) ) {
			return null;
		}
		$out = array();
		$count = 0;
		foreach ( $value as $key => $item ) {
			if ( ++$count > 100 ) {
				break;
			}
			$safe_key = is_string( $key ) ? sanitize_key( $key ) : (string) (int) $key;
			if ( preg_match( '/(?:patient|phone|email|address|cnic|passport|secret|token|password|message_body|appointment_detail|donor|donation|payment|paid|rank_boost|visibility_boost)/i', $safe_key ) ) {
				continue;
			}
			$safe_value = self::sanitize_projection_value( $item, $depth + 1 );
			if ( null !== $safe_value ) {
				$out[ $safe_key ] = $safe_value;
			}
		}
		return $out;
	}
}
