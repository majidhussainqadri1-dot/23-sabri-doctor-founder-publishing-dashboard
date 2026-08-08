<?php
/**
 * Federated, read-oriented operational projections, analytics, reports, and
 * optional File 16 assistance. Native providers remain the source of truth.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Operations_Service {
	private SPDB_Adapter_Registry $registry;
	private SPDB_Operations_Repository $repository;

	public function __construct( SPDB_Adapter_Registry $registry, SPDB_Operations_Repository $repository ) {
		$this->registry   = $registry;
		$this->repository = $repository;
	}

	/**
	 * List native sources, media usage, interactions, content gaps, revision
	 * obligations, or publishing notifications without copying native records.
	 *
	 * @param array<string,mixed> $query Raw bounded query.
	 * @return array<string,mixed>|WP_Error
	 */
	public function projections( string $domain, array $query = array() ) {
		if ( ! SPDB_Operational_Projection_Validator::is_domain( $domain ) ) {
			return self::error( 'spdb_projection_domain_invalid', __( 'The requested operational projection is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			return self::error( 'spdb_projection_forbidden', __( 'You are not authorized to view this operational projection.', 'sabri-publishing-dashboard' ), 403 );
		}
		if ( ! empty( SPDB_Admin_Settings::get()['local_projection_pause'] ) ) {
			return self::error( 'spdb_projection_paused', __( 'File 23 local projection processing is paused. Native records remain unchanged.', 'sabri-publishing-dashboard' ), 503 );
		}

		$context = $this->query_context( $query );
		if ( is_wp_error( $context ) ) {
			return $context;
		}

		$items     = array();
		$providers = array();
		$errors    = array();
		$total     = 0;
		$has_more  = false;

		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			if ( ! $adapter instanceof SPDB_Operational_Projection_Provider || ! $this->provider_readable( $provider_key ) ) {
				continue;
			}

			try {
				$domains = $adapter->get_operational_projection_domains();
			} catch ( Throwable $exception ) {
				$errors[ $provider_key ] = 'provider_exception';
				continue;
			}
			$domains = self::provider_domains( $domains );
			if ( is_wp_error( $domains ) ) {
				$errors[ $provider_key ] = $domains->get_error_code();
				continue;
			}
			if ( ! in_array( $domain, $domains, true ) ) {
				continue;
			}

			try {
				$raw = $adapter->list_operational_projections( $domain, $context );
			} catch ( Throwable $exception ) {
				$errors[ $provider_key ] = 'provider_exception';
				continue;
			}
			if ( is_wp_error( $raw ) ) {
				$errors[ $provider_key ] = sanitize_key( $raw->get_error_code() );
				continue;
			}

			$validated = SPDB_Operational_Projection_Validator::projection_page( $raw, $provider_key, $domain );
			if ( is_wp_error( $validated ) ) {
				$errors[ $provider_key ] = sanitize_key( $validated->get_error_code() );
				continue;
			}

			$providers[] = $provider_key;
			$total      += (int) $validated['total'];
			$has_more    = $has_more || ! empty( $validated['has_more'] );
			foreach ( $validated['items'] as $item ) {
				$items[] = $item;
				if ( count( $items ) >= SPDB_Operational_Projection_Validator::MAX_ITEMS ) {
					$has_more = true;
					break 2;
				}
			}
		}

		return array(
			'domain'           => $domain,
			'items'            => $items,
			'total'            => $total,
			'has_more'         => $has_more,
			'providers'        => $providers,
			'provider_errors'  => $errors,
			'generated_at_gmt' => gmdate( 'c' ),
			'empty_reason'     => empty( $providers ) ? 'no_compatible_provider' : ( empty( $items ) ? 'no_eligible_items' : '' ),
		);
	}

	/**
	 * Return provider-owned privacy-safe aggregates. Raw events are never read or
	 * stored by File 23.
	 *
	 * @param array<string,mixed> $query Raw bounded query.
	 * @return array<string,mixed>|WP_Error
	 */
	public function analytics( array $query = array() ) {
		$scope = sanitize_key( (string) ( $query['scope'] ?? 'own' ) );
		if ( ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			return self::error( 'spdb_analytics_scope_invalid', __( 'The analytics scope is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$required = 'institution' === $scope ? 'spdb_view_global_analytics' : 'spdb_view_own_analytics';
		if ( ! empty( SPDB_Admin_Settings::get()['local_projection_pause'] ) ) {
			return self::error( 'spdb_analytics_paused', __( 'File 23 local analytics projection processing is paused.', 'sabri-publishing-dashboard' ), 503 );
		}
		if ( ! SPDB_Capabilities::current_user_can( $required ) ) {
			return self::error( 'spdb_analytics_forbidden', __( 'You are not authorized to view these analytics.', 'sabri-publishing-dashboard' ), 403 );
		}
		if ( 'institution' === $scope && ! self::is_institutional( get_current_user_id() ) ) {
			return self::error( 'spdb_analytics_scope_forbidden', __( 'Institution-wide analytics are not authorized.', 'sabri-publishing-dashboard' ), 403 );
		}

		$context = $this->query_context( $query );
		if ( is_wp_error( $context ) ) {
			return $context;
		}
		$settings  = SPDB_Admin_Settings::get();
		$threshold = (int) $settings['analytics_min_cohort'];
		$metrics   = array();
		$providers = array();
		$errors    = array();

		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			if ( ! $adapter instanceof SPDB_Analytics_Provider || ! $this->provider_readable( $provider_key ) ) {
				continue;
			}
			try {
				$raw = $adapter->get_spdb_aggregate_metrics( $context );
			} catch ( Throwable $exception ) {
				$errors[ $provider_key ] = 'provider_exception';
				continue;
			}
			if ( is_wp_error( $raw ) ) {
				$errors[ $provider_key ] = sanitize_key( $raw->get_error_code() );
				continue;
			}
			$validated = SPDB_Operational_Projection_Validator::metrics( $raw, $provider_key, $threshold );
			if ( is_wp_error( $validated ) ) {
				$errors[ $provider_key ] = sanitize_key( $validated->get_error_code() );
				continue;
			}
			$providers[] = $provider_key;
			foreach ( $validated['metrics'] as $metric ) {
				$metrics[] = $metric;
			}
		}

		$source = 'live_provider';
		if ( empty( $metrics ) ) {
			$cached = $this->repository->list_metric_snapshots( get_current_user_id(), 'institution' === $scope, 500 );
			if ( ! is_wp_error( $cached ) && ! empty( $cached ) ) {
				$cached_metrics = array();
				foreach ( $cached as $snapshot ) {
					$generated = strtotime( (string) $snapshot['generated_at_gmt'] );
					if ( false === $generated ) {
						continue;
					}
					$cached_metrics[] = array(
						'provider_key'     => (string) $snapshot['provider_key'],
						'metric_key'       => (string) $snapshot['metric_key'],
						'label'            => (string) $snapshot['label'],
						'definition'       => (string) $snapshot['definition'],
						'value'            => $snapshot['value'],
						'unit'             => (string) $snapshot['unit'],
						'interval'         => (string) $snapshot['interval'],
						'cohort_count'     => (int) $snapshot['cohort_count'],
						'privacy_threshold'=> (int) $snapshot['privacy_threshold'],
						'suppressed'       => (int) $snapshot['cohort_count'] < (int) $snapshot['privacy_threshold'],
						'generated_at'     => gmdate( 'c', $generated ),
						'cached'           => true,
					);
				}
				if ( $cached_metrics ) {
					$source = 'bounded_cached_snapshot';
					$metrics = $cached_metrics;
				}
			}
		}

		return array(
			'scope'            => $scope,
			'metrics'          => array_slice( $metrics, 0, 500 ),
			'providers'        => $providers,
			'provider_errors'  => $errors,
			'source'           => $source,
			'generated_at_gmt' => gmdate( 'c' ),
			'privacy_notice'   => __( 'Values below the configured minimum cohort are suppressed. File 23 does not store raw analytics events; cached values are bounded, rebuildable provider aggregates.', 'sabri-publishing-dashboard' ),
		);
	}

	/**
	 * Build a privacy-filtered report model. Export rendering is handled by the
	 * export service so report generation never writes native provider records.
	 *
	 * @param array<string,mixed> $filters Bounded report filters.
	 * @return array<string,mixed>|WP_Error
	 */
	public function report( string $report_key, string $scope, array $filters = array() ) {
		$report_key = sanitize_key( $report_key );
		$scope      = sanitize_key( $scope );
		$allowed    = array(
			'publication_history', 'content_performance', 'review_history', 'knowledge_portfolio',
			'comment_response', 'monthly_summary', 'institution_publishing', 'doctor_contributions',
			'editorial_backlog', 'campaign_results', 'corrections_retractions', 'source_completeness',
			'safety_incidents', 'content_gaps', 'calendar_health',
		);
		if ( ! in_array( $report_key, $allowed, true ) || ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			return self::error( 'spdb_report_invalid', __( 'The requested report is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_export_reports' ) ) {
			return self::error( 'spdb_report_forbidden', __( 'You are not authorized to generate reports.', 'sabri-publishing-dashboard' ), 403 );
		}
		if ( 'institution' === $scope && ! self::is_institutional( get_current_user_id() ) ) {
			return self::error( 'spdb_report_scope_forbidden', __( 'Institution-wide reports are not authorized.', 'sabri-publishing-dashboard' ), 403 );
		}

		$rows    = array();
		$columns = array( 'provider', 'type', 'title', 'status', 'updated_at' );
		$query   = array_merge( $filters, array( 'scope' => $scope, 'per_page' => SPDB_Admin_Settings::get()['max_export_rows'] ) );

		if ( in_array( $report_key, array( 'content_performance', 'monthly_summary', 'institution_publishing', 'doctor_contributions' ), true ) ) {
			$analytics = $this->analytics( $query );
			if ( is_wp_error( $analytics ) ) {
				return $analytics;
			}
			$columns = array( 'provider', 'metric', 'label', 'value', 'unit', 'interval', 'suppressed' );
			foreach ( $analytics['metrics'] as $metric ) {
				$rows[] = array(
					'provider'   => $metric['provider_key'],
					'metric'     => $metric['metric_key'],
					'label'      => $metric['label'],
					'value'      => $metric['suppressed'] ? '' : $metric['value'],
					'unit'       => $metric['unit'],
					'interval'   => $metric['interval'],
					'suppressed' => $metric['suppressed'] ? 'yes' : 'no',
				);
			}
		} else {
			$domain = $this->report_domain( $report_key );
			$projection = $this->projections( $domain, $query );
			if ( is_wp_error( $projection ) ) {
				return $projection;
			}
			foreach ( $projection['items'] as $item ) {
				$rows[] = array(
					'provider'   => $item['provider_key'],
					'type'       => $item['object_type'],
					'title'      => $item['title'],
					'status'     => $item['status'],
					'updated_at' => $item['updated_at'],
				);
			}
		}

		return array(
			'report_key'       => $report_key,
			'scope'            => $scope,
			'columns'          => $columns,
			'rows'             => array_slice( $rows, 0, (int) SPDB_Admin_Settings::get()['max_export_rows'] ),
			'generated_at_gmt' => gmdate( 'c' ),
			'limitations'      => __( 'This report contains only current privacy-filtered provider projections and aggregates. It is not a raw analytics or native-record export.', 'sabri-publishing-dashboard' ),
		);
	}

	/**
	 * Request source-linked File 16 assistance. Suggestions never execute any
	 * publication, review, schedule, or clinical action.
	 *
	 * @param array<string,mixed> $request Raw assistance request.
	 * @return array<string,mixed>|WP_Error
	 */
	public function ai_assistance( array $request ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_request_ai_assistance' ) ) {
			return self::error( 'spdb_ai_forbidden', __( 'You are not authorized to request AI assistance.', 'sabri-publishing-dashboard' ), 403 );
		}
		$settings = SPDB_Admin_Settings::get();
		if ( empty( $settings['ai_assistance_enabled'] ) ) {
			return self::error( 'spdb_ai_disabled', __( 'AI assistance is disabled. The manual publishing workflow remains available.', 'sabri-publishing-dashboard' ), 503 );
		}

		$type = sanitize_key( (string) ( $request['type'] ?? '' ) );
		$allowed_types = array( 'title', 'excerpt', 'outline', 'grammar', 'keywords', 'duplicate_topic', 'source_completeness', 'alt_text', 'summary', 'translation', 'related_knowledge' );
		$text = isset( $request['text'] ) && is_scalar( $request['text'] ) ? trim( (string) $request['text'] ) : '';
		if ( ! in_array( $type, $allowed_types, true ) || '' === $text || strlen( $text ) > 12000 || self::contains_sensitive_data( $text ) ) {
			return self::error( 'spdb_ai_request_invalid', __( 'The AI assistance request is invalid or contains prohibited sensitive data.', 'sabri-publishing-dashboard' ), 400 );
		}
		$provider_key = sanitize_key( (string) ( $request['provider_key'] ?? 'file16_ai' ) );
		$adapter      = $this->registry->get( $provider_key );
		if ( ! $adapter instanceof SPDB_AI_Assistance_Provider || ! $this->provider_readable( $provider_key ) ) {
			return self::error( 'spdb_ai_provider_unavailable', __( 'The approved source-linked AI provider is unavailable. Continue with the manual workflow.', 'sabri-publishing-dashboard' ), 503 );
		}

		$safe_request = array(
			'type'          => $type,
			'text'          => $text,
			'language'      => sanitize_key( (string) ( $request['language'] ?? 'en' ) ),
			'corpus_policy' => 'approved_accessible_sources_only',
			'human_review'  => true,
			'no_execution'  => true,
		);
		try {
			$response = $adapter->request_spdb_assistance( $safe_request );
		} catch ( Throwable $exception ) {
			return self::error( 'spdb_ai_provider_exception', __( 'The AI provider failed. Continue with the manual workflow.', 'sabri-publishing-dashboard' ), 502 );
		}
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! is_array( $response ) || ! isset( $response['suggestion'], $response['citations'] ) || ! is_scalar( $response['suggestion'] ) || ! is_array( $response['citations'] ) ) {
			return self::error( 'spdb_ai_response_invalid', __( 'The AI provider returned an invalid source-linked suggestion.', 'sabri-publishing-dashboard' ), 502 );
		}

		$suggestion = trim( wp_strip_all_tags( (string) $response['suggestion'] ) );
		$citations  = array();
		foreach ( array_slice( $response['citations'], 0, 20 ) as $citation ) {
			if ( ! is_array( $citation ) || empty( $citation['title'] ) || empty( $citation['source_id'] ) ) {
				continue;
			}
			$citations[] = array(
				'source_id' => substr( sanitize_text_field( (string) $citation['source_id'] ), 0, 128 ),
				'title'     => substr( sanitize_text_field( (string) $citation['title'] ), 0, 240 ),
			);
		}
		if ( '' === $suggestion || empty( $citations ) ) {
			return self::error( 'spdb_ai_evidence_insufficient', __( 'The AI provider did not return sufficient source evidence. Continue with the manual workflow.', 'sabri-publishing-dashboard' ), 422 );
		}

		$audit = $this->repository->append_audit( get_current_user_id(), 'ai_assistance_requested', 'type:' . $type, array( 'provider_key' => $provider_key, 'citation_count' => count( $citations ) ) );
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return array(
			'suggestion'       => substr( $suggestion, 0, 12000 ),
			'citations'        => $citations,
			'human_review'     => true,
			'execution_allowed' => false,
			'generated_at_gmt' => gmdate( 'c' ),
		);
	}

	/** File 22 remains the sole create/edit orchestration surface. */
	public function composer_url(): string {
		$url = '';
		$resolver = array( '\\Sabri\\UniversalComposer\\Core\\Page_Resolver', 'url' );
		$ready    = array( '\\Sabri\\UniversalComposer\\Core\\Page_Resolver', 'is_ready' );
		if ( is_callable( $resolver ) && is_callable( $ready ) ) {
			try {
				$url = call_user_func( $ready ) ? (string) call_user_func( $resolver ) : '';
			} catch ( Throwable $exception ) {
				$url = '';
			}
		}
		$url = apply_filters( 'spdb/file22_composer_url', $url );
		if ( ! is_string( $url ) || '' === trim( $url ) ) {
			return '';
		}
		$normalized = SPDB_Safe_Destination::normalize( $url );
		return is_wp_error( $normalized ) ? '' : $normalized;
	}

	/** @param array<string,mixed> $query @return array<string,mixed>|WP_Error */
	private function query_context( array $query ) {
		$scope = sanitize_key( (string) ( $query['scope'] ?? 'own' ) );
		if ( ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			return self::error( 'spdb_query_scope_invalid', __( 'The requested scope is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		if ( 'institution' === $scope && ! self::is_institutional( get_current_user_id() ) ) {
			return self::error( 'spdb_query_scope_forbidden', __( 'Institution-wide scope is not authorized.', 'sabri-publishing-dashboard' ), 403 );
		}
		$page     = min( 1000, max( 1, (int) ( $query['page'] ?? 1 ) ) );
		$per_page = min( 100, max( 1, (int) ( $query['per_page'] ?? 25 ) ) );
		$search   = isset( $query['search'] ) && is_scalar( $query['search'] ) ? trim( sanitize_text_field( (string) $query['search'] ) ) : '';
		if ( strlen( $search ) > 200 || self::contains_sensitive_data( $search ) ) {
			return self::error( 'spdb_query_search_invalid', __( 'The search value is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		return array(
			'viewer_user_id' => get_current_user_id(),
			'scope'          => $scope,
			'page'           => $page,
			'per_page'       => $per_page,
			'search'         => $search,
			'date_from'      => self::safe_date( $query['date_from'] ?? '' ),
			'date_to'        => self::safe_date( $query['date_to'] ?? '' ),
			'provider'       => sanitize_key( (string) ( $query['provider'] ?? '' ) ),
			'locale'         => function_exists( 'determine_locale' ) ? determine_locale() : get_locale(),
		);
	}

	private function provider_readable( string $provider_key ): bool {
		return ! in_array(
			$this->registry->get_effective_state( $provider_key ),
			array(
				SPDB_Adapter_Registry::CAPABILITY_UNAVAILABLE,
				SPDB_Adapter_Registry::CAPABILITY_INCOMPATIBLE,
				SPDB_Adapter_Registry::CAPABILITY_TEMPORARILY_SUSPENDED,
				SPDB_Adapter_Registry::ACCEPTANCE_REVOKED,
			),
			true
		);
	}

	private function report_domain( string $report_key ): string {
		if ( in_array( $report_key, array( 'review_history', 'corrections_retractions' ), true ) ) {
			return 'revisions';
		}
		if ( in_array( $report_key, array( 'comment_response' ), true ) ) {
			return 'interactions';
		}
		if ( in_array( $report_key, array( 'source_completeness', 'safety_incidents' ), true ) ) {
			return 'sources';
		}
		if ( in_array( $report_key, array( 'content_gaps', 'knowledge_portfolio' ), true ) ) {
			return 'gaps';
		}
		return 'notifications';
	}

	private static function safe_date( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	private static function contains_sensitive_data( string $value ): bool {
		return 1 === preg_match(
			'/(?:\b\d{5}-\d{7}-\d\b|\b(?:\+?92|0)3\d{9}\b|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|password|one[- ]?time password|patient id|passport|national id|cnic)/i',
			$value
		);
	}

	/** @param mixed $raw @return string[]|WP_Error */
	private static function provider_domains( $raw ) {
		if ( ! is_array( $raw ) || count( $raw ) > count( SPDB_Operational_Projection_Validator::domains() ) ) {
			return self::error( 'spdb_projection_provider_domains_invalid', __( 'The provider declared invalid operational domains.', 'sabri-publishing-dashboard' ), 502 );
		}
		$domains = array();
		foreach ( $raw as $domain ) {
			if ( ! is_string( $domain ) || ! SPDB_Operational_Projection_Validator::is_domain( $domain ) || in_array( $domain, $domains, true ) ) {
				return self::error( 'spdb_projection_provider_domains_invalid', __( 'The provider declared invalid operational domains.', 'sabri-publishing-dashboard' ), 502 );
			}
			$domains[] = $domain;
		}
		return $domains;
	}

	private static function is_institutional( int $user_id ): bool {
		$assertions = SPDB_Membership_Guard::assertions( $user_id );
		return is_array( $assertions )
			&& true === ( $assertions['institutional_account'] ?? false )
			&& true === ( $assertions['approved'] ?? false )
			&& true === ( $assertions['eligible'] ?? false )
			&& false === ( $assertions['suspended'] ?? true );
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
