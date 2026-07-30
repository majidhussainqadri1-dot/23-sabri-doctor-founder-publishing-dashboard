<?php
/** Configurable Phase 23E provider fixture. */
final class SPDB_Test_Review_Calendar_Adapter implements SPDB_Provider_Adapter, SPDB_Review_Calendar_Provider_Adapter {
	private array $config;

	public function __construct( array $config = array() ) {
		$this->config = array_merge(
			array(
				'provider_key'       => 'review_calendar_provider',
				'provider_name'      => 'Review Calendar Provider',
				'provider_version'   => '1.0.0',
				'capability_state'   => SPDB_Adapter_Registry::CAPABILITY_REVIEW_CAPABLE,
				'review'             => array( 'items' => array(), 'total' => 0, 'has_more' => false ),
				'calendar'           => array( 'items' => array(), 'total' => 0, 'has_more' => false ),
				'allowed_operations' => array(),
				'throw_review'       => false,
				'throw_calendar'     => false,
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
	public function get_privacy_classifications(): array { return array( 'public_content', 'restricted_content' ); }
	public function get_supported_capabilities(): array {
		return array( 'spdb_view_own_content', 'spdb_view_review_queue', 'spdb_review_assigned_content', 'spdb_manage_schedule' );
	}
	public function get_operation_definitions(): array {
		$definitions = array();
		foreach ( array(
			'approve_review'  => 'spdb_review_assigned_content',
			'request_changes' => 'spdb_review_assigned_content',
			'reject_review'   => 'spdb_review_assigned_content',
			'assign_reviewer' => 'spdb_review_assigned_content',
			'schedule'        => 'spdb_manage_schedule',
			'reschedule'      => 'spdb_manage_schedule',
			'unschedule'      => 'spdb_manage_schedule',
		) as $key => $capability ) {
			$definitions[ $key ] = array(
				'required_capability'      => $capability,
				'requires_ownership'       => false,
				'requires_verified_account'=> true,
				'requires_state_guard'     => true,
				'requires_object_version'  => true,
				'requires_idempotency_key' => true,
				'requires_audit_reason'    => true,
				'payload_schema'            => array(),
				'rate_limit'                => array(),
				'success_schema'            => array(),
				'error_schema'              => array(),
			);
		}
		return $definitions;
	}
	public function health_check(): array { return array( 'healthy' => true ); }
	public function list_items( array $query ) { return array( 'items' => array(), 'total' => 0 ); }
	public function get_item( string $object_type, string $object_id ) { return array( 'object_type' => $object_type, 'object_id' => $object_id, 'confirmed' => true ); }
	public function get_allowed_operations( string $object_type, string $object_id ): array { return (array) $this->config['allowed_operations']; }
	public function execute_operation( string $operation_key, string $object_type, string $object_id, array $payload ) { return array( 'operation' => $operation_key, 'object_id' => $object_id ); }
	public function get_review_queue( array $context, array $query ) {
		$GLOBALS['spdb_test_review_context'] = $context;
		if ( ! empty( $this->config['throw_review'] ) ) { throw new RuntimeException( 'Synthetic review failure.' ); }
		return $this->config['review'];
	}
	public function get_calendar_entries( array $context, array $query ) {
		$GLOBALS['spdb_test_calendar_context'] = $context;
		if ( ! empty( $this->config['throw_calendar'] ) ) { throw new RuntimeException( 'Synthetic calendar failure.' ); }
		return $this->config['calendar'];
	}
}
