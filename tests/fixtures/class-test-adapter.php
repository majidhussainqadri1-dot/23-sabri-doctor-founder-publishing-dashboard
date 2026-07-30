<?php
/**
 * Configurable provider adapter used by Phase 23A contract tests.
 */

final class SPDB_Test_Adapter implements SPDB_Provider_Adapter {
	/** @var array<string,mixed> */
	private array $config;

	/**
	 * @param array<string,mixed> $config Test configuration.
	 */
	public function __construct( array $config = array() ) {
		$this->config = array_merge(
			array(
				'provider_key'          => 'provider_one',
				'provider_name'         => 'Provider One',
				'provider_version'      => '1.0.0',
				'minimum_contract'      => '2.0.0',
				'maximum_contract'      => '2.9.9',
				'capability_state'      => SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE,
				'object_types'          => array( 'publication' ),
				'privacy_classes'       => array( 'public_content' ),
				'supported_capabilities'=> array( 'spdb_manage_own_content' ),
				'throw_on_key'          => false,
			),
			$config
		);
	}

	public function get_provider_key(): string {
		if ( $this->config['throw_on_key'] ) {
			throw new RuntimeException( 'Synthetic provider exception.' );
		}
		return (string) $this->config['provider_key'];
	}

	public function get_provider_name(): string {
		return (string) $this->config['provider_name'];
	}

	public function get_provider_version(): string {
		return (string) $this->config['provider_version'];
	}

	public function get_minimum_contract_version(): string {
		return (string) $this->config['minimum_contract'];
	}

	public function get_maximum_contract_version(): string {
		return (string) $this->config['maximum_contract'];
	}

	public function get_declared_capability_state(): string {
		return (string) $this->config['capability_state'];
	}

	public function get_object_types(): array {
		return $this->config['object_types'];
	}

	public function get_privacy_classifications(): array {
		return $this->config['privacy_classes'];
	}

	public function get_supported_capabilities(): array {
		return $this->config['supported_capabilities'];
	}

	public function get_operation_definitions(): array {
		return array(
			'submit_item' => array(
				'required_capability'       => 'spdb_manage_own_content',
				'requires_ownership'         => true,
				'requires_verified_account'  => true,
				'requires_state_guard'       => true,
				'requires_object_version'    => true,
				'requires_idempotency_key'   => true,
				'requires_audit_reason'      => false,
				'payload_schema'             => array( 'type' => 'object' ),
				'rate_limit'                 => array( 'requests' => 10, 'window' => 60 ),
				'success_schema'             => array( 'type' => 'object' ),
				'error_schema'               => array( 'type' => 'object' ),
			),
		);
	}

	public function health_check(): array {
		return array( 'healthy' => true );
	}

	public function list_items( array $query ) {
		return array( 'items' => array(), 'total' => 0 );
	}

	public function get_item( string $object_type, string $object_id ) {
		return array(
			'object_type'    => $object_type,
			'object_id'      => $object_id,
			'object_version' => 'v2',
		);
	}

	public function get_allowed_operations( string $object_type, string $object_id ): array {
		return array( 'submit_item' );
	}

	public function execute_operation( string $operation_key, string $object_type, string $object_id, array $payload ) {
		return array( 'executed' => true, 'operation' => $operation_key );
	}
}
