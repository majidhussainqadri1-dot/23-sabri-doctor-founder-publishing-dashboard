<?php
/** Build a bounded, non-sensitive dashboard system-state snapshot. */
defined( 'ABSPATH' ) || exit;

final class SPDB_System_State {
	private SPDB_Adapter_Registry $registry;
	private ?SPDB_Collections_Service $collections_service;
	private ?SPDB_Native_Reference_Registry $native_reference_registry;
	private ?SPDB_Operations_Repository $operations_repository;
	private ?SPDB_Local_Repair $local_repair;
	private ?SPDB_Activation_Wizard $activation_wizard;

	public function __construct(
		SPDB_Adapter_Registry $registry,
		?SPDB_Collections_Service $collections_service = null,
		?SPDB_Native_Reference_Registry $native_reference_registry = null,
		?SPDB_Operations_Repository $operations_repository = null,
		?SPDB_Local_Repair $local_repair = null,
		?SPDB_Activation_Wizard $activation_wizard = null
	) {
		$this->registry                  = $registry;
		$this->collections_service       = $collections_service;
		$this->native_reference_registry = $native_reference_registry;
		$this->operations_repository     = $operations_repository;
		$this->local_repair              = $local_repair;
		$this->activation_wizard         = $activation_wizard;
	}

	/** @param array<string,mixed> $workspace @return array<string,mixed> */
	public function snapshot( array $workspace ): array {
		$providers = array();
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) ) {
				continue;
			}
			$providers[] = array(
				'provider_key'        => $provider_key,
				'provider_name'       => (string) $metadata['provider_name'],
				'provider_version'    => (string) $metadata['provider_version'],
				'declared_capability' => (string) $metadata['declared_capability'],
				'acceptance_state'    => (string) $metadata['acceptance_state'],
				'effective_state'     => $this->registry->get_effective_state( $provider_key ),
				'object_types'        => is_array( $metadata['object_types'] ?? null ) ? array_values( $metadata['object_types'] ) : array(),
			);
		}

		$errors = 0;
		foreach ( $this->registry->registration_errors() as $provider_errors ) {
			$errors += is_array( $provider_errors ) ? count( $provider_errors ) : 0;
		}
		$membership = SPDB_Membership_Guard::health_snapshot();
		$collections = null !== $this->collections_service
			? $this->collections_service->health()
			: array(
				'repository_available' => false,
				'read_ready'           => false,
				'write_enabled'        => false,
				'repository_health'    => array( 'healthy' => false, 'schema_ready' => false, 'code' => 'service_unavailable' ),
			);
		$native_references = null !== $this->native_reference_registry
			? $this->native_reference_registry->health_snapshot()
			: array( 'available' => false, 'resolver_count' => 0, 'ready_count' => 0, 'registration_errors' => 0, 'ready' => false, 'providers' => array() );
		$operations = null !== $this->operations_repository
			? $this->operations_repository->health_check()
			: array( 'healthy' => false, 'schema_ready' => false, 'code' => 'repository_unavailable' );
		$jobs = null !== $this->operations_repository ? $this->operations_repository->job_counts() : array();
		$audit = null !== $this->operations_repository ? $this->operations_repository->audit_health() : array( 'healthy' => false, 'code' => 'repository_unavailable' );
		$system_check = null !== $this->local_repair ? $this->local_repair->system_check() : array();
		$activation = null !== $this->activation_wizard ? $this->activation_wizard->state() : array( 'staging_accepted' => false, 'live_activation_allowed' => false );
		$module_manifest = SPDB_Module_Manifest::snapshot( $this->registry );
		$legacy_migration = SPDB_Legacy_Migration_Diagnostics::snapshot();
		$assurance_manifest = null !== $this->operations_repository ? SPDB_Module_Manifest::assurance( $this->registry, $this->operations_repository ) : array();

		$degraded = ! (bool) ( $membership['available'] ?? false )
			|| $errors > 0
			|| empty( $collections['read_ready'] )
			|| empty( $operations['healthy'] )
			|| empty( $audit['healthy'] )
			|| (int) ( $native_references['registration_errors'] ?? 0 ) > 0;

		return array(
			'environment'             => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'plugin_version'          => SPDB_VERSION,
			'contract_version'        => SPDB_CONTRACT_VERSION,
			'operations_schema'       => SPDB_Operations_Schema::VERSION,
			'collections_schema'      => SPDB_Collections_Schema::VERSION,
			'workspace'               => array(
				'key'            => (string) ( $workspace['key'] ?? 'unknown' ),
				'label'          => (string) ( $workspace['label'] ?? '' ),
				'read_only'      => (bool) ( $workspace['read_only'] ?? true ),
				'account_status' => (string) ( $workspace['account_status'] ?? 'unknown' ),
			),
			'membership'              => $membership,
			'provider_count'          => count( $providers ),
			'provider_errors'         => $errors,
			'providers'               => $providers,
			'module_manifest'         => $module_manifest,
			'legacy_migration'        => $legacy_migration,
			'assurance_manifest'      => $assurance_manifest,
			'collections'             => $collections,
			'native_references'       => $native_references,
			'operations'              => $operations,
			'background_jobs'         => is_array( $jobs ) ? $jobs : array(),
			'audit'                   => $audit,
			'system_check'            => $system_check,
			'activation'              => $activation,
			'degraded'                => $degraded,
			'generated_at_gmt'        => gmdate( 'c' ),
			'production_writes'       => false,
			'activation_authorized'    => ! empty( $activation['live_activation_allowed'] ),
			'phase'                   => 'full_plan_candidate',
			'global_safe_mode_owner'  => 'file20',
			'file23_local_repairs_only' => true,
			'completion_status'       => array(
				'specified'        => true,
				'coded'            => true,
				'packaged'         => false,
				'automated_qa'     => false,
				'staging_accepted' => ! empty( $activation['staging_accepted'] ),
				'live_deployed'    => false,
				'operational'      => false,
			),
		);
	}
}
