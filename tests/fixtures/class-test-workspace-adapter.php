<?php
/**
 * Configurable provider implementing the optional Phase 23D workspace contract.
 */

final class SPDB_Test_Workspace_Adapter implements SPDB_Provider_Adapter, SPDB_Workspace_Provider_Adapter {
	/** @var array<string,mixed> */
	private array $config;

	/** @param array<string,mixed> $config */
	public function __construct( array $config = array() ) {
		$this->config = array_merge(
			array(
				'provider_key'       => 'workspace_provider',
				'provider_name'      => 'Workspace Provider',
				'provider_version'   => '1.0.0',
				'capability_state'   => SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE,
				'throw_on_workspace' => false,
				'workspace'          => array(),
			),
			$config
		);
	}

	public function get_provider_key(): string { return (string) $this->config['provider_key']; }
	public function get_provider_name(): string { return (string) $this->config['provider_name']; }
	public function get_provider_version(): string { return (string) $this->config['provider_version']; }
	public function get_minimum_contract_version(): string { return '2.0.0'; }
	public function get_maximum_contract_version(): string { return '2.9.9'; }
	public function get_declared_capability_state(): string { return (string) $this->config['capability_state']; }
	public function get_object_types(): array { return array( 'publication' ); }
	public function get_privacy_classifications(): array { return array( 'public_content' ); }
	public function get_supported_capabilities(): array { return array( 'spdb_view_own_content', 'spdb_manage_own_content' ); }
	public function get_operation_definitions(): array { return array(); }
	public function health_check(): array { return array( 'healthy' => true ); }
	public function list_items( array $query ) { return array( 'items' => array(), 'total' => 0 ); }
	public function get_item( string $object_type, string $object_id ) { return new WP_Error( 'not_used', 'Not used.' ); }
	public function get_allowed_operations( string $object_type, string $object_id ): array { return array(); }
	public function execute_operation( string $operation_key, string $object_type, string $object_id, array $payload ) { return new WP_Error( 'disabled', 'Disabled.' ); }

	public function get_workspace_projection( array $context ) {
		$GLOBALS['spdb_test_workspace_context'] = $context;
		if ( ! empty( $this->config['throw_on_workspace'] ) ) {
			throw new RuntimeException( 'Synthetic workspace provider failure.' );
		}
		return $this->config['workspace'];
	}
}
