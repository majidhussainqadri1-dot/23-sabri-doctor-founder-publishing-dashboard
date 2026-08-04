<?php
/**
 * Idempotent File 23 background-job runner with retries and dead-letter state.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Background_Jobs {
	public const HOOK = 'spdb_process_background_jobs';
	private const SCHEDULE = 'spdb_five_minutes';

	private SPDB_Operations_Repository $repository;
	private SPDB_Operations_Service $operations;
	private SPDB_Export_Service $exports;
	private SPDB_Adapter_Registry $registry;
	private SPDB_Automation_Engine $automation;

	public function __construct(
		SPDB_Operations_Repository $repository,
		SPDB_Operations_Service $operations,
		SPDB_Export_Service $exports,
		SPDB_Adapter_Registry $registry,
		SPDB_Automation_Engine $automation
	) {
		$this->repository = $repository;
		$this->operations = $operations;
		$this->exports    = $exports;
		$this->registry   = $registry;
		$this->automation = $automation;
	}

	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( self::HOOK, array( $this, 'run' ) );
		add_action( 'init', array( $this, 'ensure_schedule' ), 20 );
	}

	/** @param array<string,array<string,mixed>> $schedules @return array<string,array<string,mixed>> */
	public function cron_schedules( array $schedules ): array {
		$schedules[ self::SCHEDULE ] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every five minutes (File 23 fallback)', 'sabri-publishing-dashboard' ),
		);
		return $schedules;
	}

	public function ensure_schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, self::SCHEDULE, self::HOOK );
		}
	}

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
			$timestamp = wp_next_scheduled( self::HOOK );
		}
	}

	/**
	 * Run one bounded batch. A real server cron may invoke the same hook; WP-Cron
	 * remains only the documented fallback.
	 */
	public function run(): void {
		$this->enqueue_maintenance_jobs();
		$settings = SPDB_Admin_Settings::get();
		$jobs     = $this->repository->claim_due_jobs( (int) $settings['jobs_per_run'] );
		if ( is_wp_error( $jobs ) ) {
			do_action( 'spdb/background_job_claim_failed', array( 'error_code' => $jobs->get_error_code() ) );
			return;
		}

		foreach ( $jobs as $job ) {
			$result = $this->execute( $job );
			if ( is_wp_error( $result ) ) {
				$transition = $this->repository->fail_or_retry_job( $job['job_id'], $job['lock_token'], $result->get_error_code() );
				if ( is_wp_error( $transition ) ) {
					do_action( 'spdb/background_job_transition_failed', array( 'job_id' => $job['job_id'], 'error_code' => $transition->get_error_code(), 'phase' => 'retry_or_dead_letter' ) );
					continue;
				}
				$updated = $this->repository->get_job( $job['job_id'] );
				if ( is_array( $updated ) && 'dead_letter' === $updated['status'] ) {
					do_action(
						'spdb/background_job_dead_lettered',
						array(
							'job_id'          => $updated['job_id'],
							'job_type'        => $updated['job_type'],
							'last_error_code' => $updated['last_error_code'],
							'attempts'        => $updated['attempts'],
						)
					);
				}
				continue;
			}
			$completed = $this->repository->complete_job( $job['job_id'], $job['lock_token'] );
			if ( is_wp_error( $completed ) ) {
				do_action( 'spdb/background_job_transition_failed', array( 'job_id' => $job['job_id'], 'error_code' => $completed->get_error_code(), 'phase' => 'complete' ) );
			}
		}
	}

	/**
	 * Queue a job through the same bounded contract used by the REST layer.
	 *
	 * @param array<string,mixed> $payload Bounded payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function enqueue( string $job_type, int $owner_user_id, array $payload, string $idempotency_key ) {
		return $this->repository->enqueue_job(
			$job_type,
			$owner_user_id,
			$payload,
			$idempotency_key,
			(int) SPDB_Admin_Settings::get()['job_max_attempts']
		);
	}

	/** @param array<string,mixed> $job @return true|WP_Error */
	private function execute( array $job ) {
		$previous_user = get_current_user_id();
		$owner_user    = max( 0, (int) $job['owner_user_id'] );
		if ( $owner_user > 0 && function_exists( 'wp_set_current_user' ) ) {
			wp_set_current_user( $owner_user );
		}

		try {
			switch ( $job['job_type'] ) {
				case 'export_generate':
					$export_id = sanitize_key( (string) ( $job['payload']['export_id'] ?? '' ) );
					return '' === $export_id
						? self::error( 'spdb_export_job_invalid', __( 'The export background job is invalid.', 'sabri-publishing-dashboard' ) )
						: $this->exports->process( $export_id );

				case 'analytics_aggregate':
					return $this->aggregate_analytics( $job );

				case 'adapter_health_check':
					return $this->refresh_adapter_health();

				case 'retention_cleanup':
					$result = $this->repository->cleanup_retention( SPDB_Admin_Settings::get() );
					return is_wp_error( $result ) ? $result : true;

				case 'calendar_reconcile':
				case 'failed_schedule_detection':
				case 'stale_task_cleanup':
				case 'broken_source_check':
				case 'campaign_reminder':
					return $this->external_handler( $job );

				case 'automation_rule':
					return $this->automation->execute( $job );
			}
			return self::error( 'spdb_job_type_unregistered', __( 'The background job type is not registered.', 'sabri-publishing-dashboard' ) );
		} finally {
			if ( function_exists( 'wp_set_current_user' ) ) {
				wp_set_current_user( $previous_user );
			}
		}
	}

	/** @param array<string,mixed> $job @return true|WP_Error */
	private function aggregate_analytics( array $job ) {
		$scope = sanitize_key( (string) ( $job['payload']['scope'] ?? 'own' ) );
		$query = is_array( $job['payload']['query'] ?? null ) ? $job['payload']['query'] : array();
		$query['scope'] = $scope;
		$result = $this->operations->analytics( $query );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$start = isset( $query['date_from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $query['date_from'] ) ? $query['date_from'] . ' 00:00:00' : gmdate( 'Y-m-01 00:00:00' );
		$end   = isset( $query['date_to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $query['date_to'] ) ? $query['date_to'] . ' 23:59:59' : gmdate( 'Y-m-d 23:59:59' );
		$owner = 'own' === $scope ? max( 0, (int) $job['owner_user_id'] ) : 0;
		foreach ( $result['metrics'] as $metric ) {
			if ( ! empty( $metric['suppressed'] ) ) {
				continue;
			}
			$generated = strtotime( (string) $metric['generated_at'] );
			$stored = $this->repository->store_metric_snapshot(
				array(
					'provider_key'      => $metric['provider_key'],
					'metric_key'        => $metric['metric_key'],
					'scope'             => $scope,
					'owner_user_id'     => $owner,
					'period_start_gmt'  => $start,
					'period_end_gmt'    => $end,
					'definition'        => $metric['definition'],
					'value'             => $metric['value'],
					'unit'              => $metric['unit'],
					'label'             => $metric['label'],
					'interval'          => $metric['interval'],
					'cohort_count'      => $metric['cohort_count'],
					'privacy_threshold' => $metric['privacy_threshold'],
					'generated_at_gmt'  => false === $generated ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s', $generated ),
					'expires_at_gmt'    => gmdate( 'Y-m-d H:i:s', time() + 25 * MONTH_IN_SECONDS ),
				)
			);
			if ( is_wp_error( $stored ) ) {
				return $stored;
			}
		}
		return true;
	}

	/** @return true|WP_Error */
	private function refresh_adapter_health() {
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			try {
				$raw = $adapter->health_check();
			} catch ( Throwable $exception ) {
				$raw = array( 'healthy' => false, 'code' => 'provider_exception' );
			}
			$health = $this->sanitize_health( $raw );
			$result = $this->repository->store_adapter_health( $provider_key, $this->registry->get_effective_state( $provider_key ), $health );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		return true;
	}

	/** @param array<string,mixed> $job @return true|WP_Error */
	private function external_handler( array $job ) {
		$result = apply_filters( 'spdb/background_job_result', null, $job['job_type'], $job['payload'], $job );
		if ( true === $result ) {
			return true;
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return self::error( 'spdb_job_handler_unavailable', __( 'No compatible provider accepted this background job.', 'sabri-publishing-dashboard' ) );
	}

	private function enqueue_maintenance_jobs(): void {
		$bucket = gmdate( 'YmdHi', (int) floor( time() / ( 5 * MINUTE_IN_SECONDS ) ) * ( 5 * MINUTE_IN_SECONDS ) );
		$health = $this->repository->enqueue_job( 'adapter_health_check', 0, array(), 'health:' . $bucket, 3 );
		if ( is_wp_error( $health ) ) {
			do_action( 'spdb/background_job_enqueue_failed', array( 'job_type' => 'adapter_health_check', 'error_code' => $health->get_error_code() ) );
		}
		$day = gmdate( 'Ymd' );
		$retention = $this->repository->enqueue_job( 'retention_cleanup', 0, array(), 'retention:' . $day, 3 );
		if ( is_wp_error( $retention ) ) {
			do_action( 'spdb/background_job_enqueue_failed', array( 'job_type' => 'retention_cleanup', 'error_code' => $retention->get_error_code() ) );
		}
	}

	/** @param mixed $raw @return array<string,mixed> */
	private function sanitize_health( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array( 'healthy' => false, 'code' => 'invalid_health' );
		}
		$out = array(
			'healthy' => true === ( $raw['healthy'] ?? false ),
			'code'    => sanitize_key( (string) ( $raw['code'] ?? 'unknown' ) ),
		);
		foreach ( array( 'available', 'ready', 'degraded' ) as $key ) {
			if ( array_key_exists( $key, $raw ) && is_bool( $raw[ $key ] ) ) {
				$out[ $key ] = $raw[ $key ];
			}
		}
		return $out;
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => 500 ) );
	}
}
