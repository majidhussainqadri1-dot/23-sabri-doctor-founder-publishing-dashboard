<?php
/**
 * Non-destructive File 23 system checks and local reversible repair actions.
 * Global Safe Mode, platform repair, and rollback remain owned by File 20.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Local_Repair {
	private SPDB_Adapter_Registry $registry;
	private SPDB_Operations_Repository $repository;

	public function __construct( SPDB_Adapter_Registry $registry, SPDB_Operations_Repository $repository ) {
		$this->registry   = $registry;
		$this->repository = $repository;
	}

	/** @return array<string,mixed> */
	public function system_check(): array {
		$collection_schema = SPDB_Collections_Schema::verify();
		$operations_schema = SPDB_Operations_Schema::verify();
		$jobs              = $this->repository->job_counts();
		$audit             = $this->repository->audit_health();
		$legacy_migration  = SPDB_Legacy_Migration_Diagnostics::snapshot();
		$providers         = array();
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			$metadata = $this->registry->metadata( $provider_key );
			$providers[] = array(
				'provider_key'     => $provider_key,
				'provider_version' => is_array( $metadata ) ? (string) $metadata['provider_version'] : '',
				'capability'       => is_array( $metadata ) ? (string) $metadata['declared_capability'] : 'unknown',
				'acceptance'       => $this->registry->get_acceptance_state( $provider_key ),
				'effective_state'  => $this->registry->get_effective_state( $provider_key ),
			);
		}
		$registration_errors = 0;
		foreach ( $this->registry->registration_errors() as $errors ) {
			$registration_errors += is_array( $errors ) ? count( $errors ) : 0;
		}

		$checks = array(
			'php' => array(
				'ok'   => version_compare( PHP_VERSION, '8.0.0', '>=' ),
				'code' => version_compare( PHP_VERSION, '8.0.0', '>=' ) ? 'ready' : 'php_incompatible',
			),
			'wordpress' => array(
				'ok'   => isset( $GLOBALS['wp_version'] ) && version_compare( (string) $GLOBALS['wp_version'], '6.5', '>=' ),
				'code' => isset( $GLOBALS['wp_version'] ) && version_compare( (string) $GLOBALS['wp_version'], '6.5', '>=' ) ? 'ready' : 'wordpress_incompatible',
			),
			'https' => array(
				'ok'   => function_exists( 'is_ssl' ) && is_ssl(),
				'code' => function_exists( 'is_ssl' ) && is_ssl() ? 'ready' : 'https_required',
			),
			'membership' => array(
				'ok'   => SPDB_Membership_Guard::is_available(),
				'code' => SPDB_Membership_Guard::is_available() ? 'ready' : 'membership_unavailable',
			),
			'collections_schema' => array(
				'ok'   => true === $collection_schema,
				'code' => true === $collection_schema ? 'ready' : 'schema_unavailable',
			),
			'operations_schema' => array(
				'ok'   => true === $operations_schema,
				'code' => true === $operations_schema ? 'ready' : 'schema_unavailable',
			),
			'cron' => array(
				'ok'   => (bool) wp_next_scheduled( SPDB_Background_Jobs::HOOK ),
				'code' => wp_next_scheduled( SPDB_Background_Jobs::HOOK ) ? 'ready' : 'not_scheduled',
			),
			'audit' => array(
				'ok'   => ! empty( $audit['healthy'] ),
				'code' => (string) ( $audit['code'] ?? 'unknown' ),
			),
			'legacy_file04_migration' => array(
				'ok'   => ! $legacy_migration['available'] || ! empty( $legacy_migration['cutover_ready'] ),
				'code' => (string) $legacy_migration['code'],
			),
			'provider_registry' => array(
				'ok'   => 0 === $registration_errors,
				'code' => 0 === $registration_errors ? 'ready' : 'registration_errors',
			),
		);

		$healthy = true;
		foreach ( $checks as $check ) {
			$healthy = $healthy && ! empty( $check['ok'] );
		}

		return array(
			'healthy'            => $healthy,
			'checks'             => $checks,
			'providers'          => $providers,
			'provider_errors'    => $registration_errors,
			'background_jobs'    => is_array( $jobs ) ? $jobs : array(),
			'audit'              => $audit,
			'legacy_migration'   => $legacy_migration,
			'plugin_version'     => SPDB_VERSION,
			'contract_version'   => SPDB_CONTRACT_VERSION,
			'operations_schema'  => SPDB_Operations_Schema::VERSION,
			'collections_schema' => SPDB_Collections_Schema::VERSION,
			'generated_at_gmt'   => gmdate( 'c' ),
			'global_safe_mode_owner' => 'file20',
			'local_repairs_only' => true,
		);
	}

	/**
	 * @return array<string,mixed>|WP_Error
	 */
	public function repair( string $action, string $reason ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_reconcile_projections' ) || ! SPDB_Capabilities::current_user_can( 'spdb_run_system_check' ) || ! SPDB_Capabilities::current_user_can( 'spdb_repair_owned_data' ) ) {
			return self::error( 'spdb_repair_forbidden', __( 'You are not authorized to run File 23 repairs.', 'sabri-publishing-dashboard' ), 403 );
		}
		$assertions = SPDB_Membership_Guard::assertions( get_current_user_id() );
		if ( ! is_array( $assertions ) || empty( $assertions['approved'] ) || ! empty( $assertions['suspended'] ) || empty( $assertions['session_two_factor'] ) ) {
			return self::error( 'spdb_repair_step_up_required', __( 'An approved two-factor-authenticated account is required for repair.', 'sabri-publishing-dashboard' ), 403 );
		}
		$action = sanitize_key( $action );
		$reason = trim( wp_strip_all_tags( $reason ) );
		if ( self::text_length( $reason ) < 10 || self::text_length( $reason ) > 500 || preg_match( '/[\x00-\x1F\x7F]/', $reason ) || preg_match( '/password|secret|token|nonce|otp|cvv|patient|diagnosis|prescription|message[ _-]?body/i', $reason ) ) {
			return self::error( 'spdb_repair_reason_required', __( 'A bounded repair reason is required.', 'sabri-publishing-dashboard' ), 400 );
		}

		switch ( $action ) {
			case 'reconcile_capabilities':
				$result = SPDB_Capability_Installer::ensure();
				break;
			case 'install_operations_schema':
				$result = SPDB_Operations_Schema::install();
				break;
			case 'install_collections_schema':
				$result = SPDB_Collections_Schema::install();
				break;
			case 'reschedule_jobs':
				if ( ! wp_next_scheduled( SPDB_Background_Jobs::HOOK ) ) {
					wp_schedule_event( time() + MINUTE_IN_SECONDS, 'spdb_five_minutes', SPDB_Background_Jobs::HOOK );
				}
				$result = array( 'scheduled' => (bool) wp_next_scheduled( SPDB_Background_Jobs::HOOK ) );
				break;
			case 'flush_local_routes':
				SPDB_Dashboard_Router::activate();
				$result = array( 'routes_refreshed' => true );
				break;
			case 'retention_cleanup':
				$result = $this->repository->cleanup_retention( SPDB_Admin_Settings::get() );
				break;
			case 'verify_audit':
				$result = $this->repository->audit_health();
				break;
			default:
				return self::error( 'spdb_repair_action_invalid', __( 'The requested repair action is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$audit = $this->repository->append_audit( get_current_user_id(), 'local_repair_completed', 'repair:' . $action, array( 'action' => $action, 'reason_hash' => hash( 'sha256', $reason ) ) );
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return array( 'action' => $action, 'result' => $result, 'system_check' => $this->system_check() );
	}

	private static function text_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
