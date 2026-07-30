<?php
/** Configurable provider-specific native reference resolver for executable tests. */
final class SPDB_Test_Native_Reference_Provider implements SPDB_Native_Reference_Provider {
	/** @var array<string,mixed> */
	private array $config;
	public int $health_calls = 0;
	public int $resolve_calls = 0;
	/** @param array<string,mixed> $config */
	public function __construct( array $config = array() ) {
		$this->config = array_merge( array(
			'provider_key' => 'provider_one',
			'provider_version' => '1.0.0',
			'resolver_version' => '1.0.0',
			'object_types' => array( 'publication' ),
			'healthy' => true,
			'health_code' => 'ready',
			'health_exception' => false,
			'resolve_exception' => false,
			'invalid_response' => false,
			'mismatch_response' => false,
		), $config );
	}
	public function get_provider_key(): string { return (string) $this->config['provider_key']; }
	public function get_provider_version(): string { return (string) $this->config['provider_version']; }
	public function get_resolver_version(): string { return (string) $this->config['resolver_version']; }
	public function get_object_types(): array { return $this->config['object_types']; }
	public function health_check(): array {
		++$this->health_calls;
		if ( $this->config['health_exception'] ) { throw new RuntimeException( 'Synthetic resolver health exception.' ); }
		return array( 'healthy' => (bool) $this->config['healthy'], 'code' => (string) $this->config['health_code'], 'ignored_sensitive_detail' => 'not projected' );
	}
	public function resolve_reference( string $object_type, string $object_id, array $context ) {
		++$this->resolve_calls;
		if ( $this->config['resolve_exception'] ) { throw new RuntimeException( 'Synthetic resolver exception.' ); }
		if ( $this->config['invalid_response'] ) { return 'invalid'; }
		return array(
			'provider_key' => $this->config['mismatch_response'] ? 'provider_two' : (string) $this->config['provider_key'],
			'object_type' => $object_type,
			'object_id' => $object_id,
			'exists' => true,
			'visible' => true,
			'reference_allowed' => true,
			'owner_user_id' => (int) ( $context['user_id'] ?? 0 ),
			'native_version' => 'v1',
			'scope' => (string) ( $context['scope'] ?? 'own' ),
			'destination' => '',
		);
	}
}
