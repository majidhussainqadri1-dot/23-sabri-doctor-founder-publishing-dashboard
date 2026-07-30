<?php
/** Build a non-sensitive dashboard system-state snapshot. */
defined( 'ABSPATH' ) || exit;

final class SPDB_System_State {
	private SPDB_Adapter_Registry $registry;
	private ?SPDB_Collections_Service $collections_service;
	public function __construct( SPDB_Adapter_Registry $registry, ?SPDB_Collections_Service $collections_service = null ) { $this->registry = $registry; $this->collections_service = $collections_service; }
	/** @param array<string,mixed> $workspace @return array<string,mixed> */
	public function snapshot( array $workspace ): array {
		$providers = array();
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			$metadata = $this->registry->metadata( $provider_key ); if ( ! is_array( $metadata ) ) { continue; }
			$providers[] = array( 'provider_key' => $provider_key, 'provider_name' => (string) $metadata['provider_name'], 'provider_version' => (string) $metadata['provider_version'], 'declared_capability' => (string) $metadata['declared_capability'], 'acceptance_state' => (string) $metadata['acceptance_state'], 'effective_state' => $this->registry->get_effective_state( $provider_key ) );
		}
		$errors = 0; foreach ( $this->registry->registration_errors() as $provider_errors ) { $errors += is_array( $provider_errors ) ? count( $provider_errors ) : 0; }
		$membership = SPDB_Membership_Guard::health_snapshot();
		$collections = null !== $this->collections_service ? $this->collections_service->health() : array( 'repository_available' => false, 'resolver_available' => false, 'read_ready' => false, 'write_configured' => false, 'collection_write_ready' => false, 'knowledge_write_ready' => false, 'any_write_ready' => false, 'write_enabled' => false, 'repository_health' => array( 'healthy' => false, 'schema_ready' => false, 'code' => 'service_unavailable' ) );
		$degraded = ! $membership['available'] || $errors > 0 || empty( $collections['read_ready'] );
		return array(
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'plugin_version' => SPDB_VERSION, 'contract_version' => SPDB_CONTRACT_VERSION,
			'workspace' => array( 'key' => (string) $workspace['key'], 'label' => (string) $workspace['label'], 'read_only' => (bool) $workspace['read_only'], 'account_status' => (string) $workspace['account_status'] ),
			'membership' => $membership, 'provider_count' => count( $providers ), 'provider_errors' => $errors, 'providers' => $providers,
			'collections' => $collections, 'degraded' => $degraded, 'generated_at_gmt' => gmdate( 'c' ), 'production_writes' => false, 'phase' => '23F',
		);
	}
}
