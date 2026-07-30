<?php
/**
 * Configurable provider adapter used by File 23 executable tests.
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
				'provider_key'           => 'provider_one',
				'provider_name'          => 'Provider One',
				'provider_version'       => '1.0.0',
				'minimum_contract'       => '2.0.0',
				'maximum_contract'       => '2.9.9',
				'capability_state'       => SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE,
				'object_types'           => array( 'publication' ),
				'privacy_classes'        => array( 'public_content' ),
				'supported_capabilities' => array( 'spdb_manage_own_content' ),
				'throw_on_key'           => false,
				'list_exception'         => false,
				'list_error'             => false,
				'get_exception'          => false,
				'get_error'              => false,
				'items'                  => array(),
				'total'                  => 0,
				'item'                   => null,
				'allowed_operations'     => array( 'submit_item' ),
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
		$GLOBALS['spdb_test_last_inventory_query'] = $query;
		if ( $this->config['list_exception'] ) {
			throw new RuntimeException( 'Synthetic list exception.' );
		}
		if ( $this->config['list_error'] ) {
			return new WP_Error( 'synthetic_list_error', 'Synthetic list error.' );
		}
		return array(
			'items' => $this->config['items'],
			'total' => (int) $this->config['total'],
		);
	}

	public function get_item( string $object_type, string $object_id ) {
		if ( $this->config['get_exception'] ) {
			throw new RuntimeException( 'Synthetic item exception.' );
		}
		if ( $this->config['get_error'] ) {
			return new WP_Error( 'synthetic_item_error', 'Synthetic item error.' );
		}
		if ( is_array( $this->config['item'] ) ) {
			return $this->config['item'];
		}
		return self::projection( $object_type, $object_id );
	}

	public function get_allowed_operations( string $object_type, string $object_id ): array {
		return is_array( $this->config['allowed_operations'] ) ? $this->config['allowed_operations'] : array();
	}

	public function execute_operation( string $operation_key, string $object_type, string $object_id, array $payload ) {
		return array( 'executed' => true, 'operation' => $operation_key );
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function projection( string $object_type = 'publication', string $object_id = '42', array $overrides = array() ): array {
		return array_merge(
			array(
				'object_type'       => $object_type,
				'object_id'         => $object_id,
				'object_version'    => 'v2',
				'title'             => 'Validated native publication',
				'summary'           => 'A bounded provider projection.',
				'privacy_class'     => 'public_content',
				'lifecycle_state'   => 'draft',
				'review_state'      => 'awaiting_review',
				'visibility_state'  => 'private',
				'operational_state' => 'healthy',
				'language'          => 'en',
				'topic'             => 'homeopathy',
				'author'            => array( 'id' => 7, 'display_name' => 'Test Doctor' ),
				'created_at'        => '2026-07-30T01:00:00Z',
				'modified_at'       => '2026-07-30T02:00:00Z',
				'canonical_url'     => 'https://example.test/publication/' . rawurlencode( $object_id ) . '/',
				'destinations'      => array(
					'edit'    => 'https://example.test/composer/?object=' . rawurlencode( $object_id ),
					'preview' => 'https://example.test/preview/' . rawurlencode( $object_id ) . '/',
					'public'  => 'https://example.test/publication/' . rawurlencode( $object_id ) . '/',
				),
				'compliance_alerts' => array(),
			),
			$overrides
		);
	}
}
