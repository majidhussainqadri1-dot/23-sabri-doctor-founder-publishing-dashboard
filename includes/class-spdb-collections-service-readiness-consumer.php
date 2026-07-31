<?php
/**
 * Map reviewed Collections readiness into the stable service health/gate contract.
 *
 * This consumer builds its own full and collection-only probes from one exact
 * repository/resolver pair. It validates the complete internal snapshot before
 * projecting public health or authorizing a readiness requirement. It exposes no
 * raw dependency, performs no metadata operation or native resolution, persists
 * no data, registers no route, and does not wire the plugin container.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Service_Readiness_Consumer {
	private const MAX_CODE_LENGTH = 64;

	private const HEALTH_KEYS = array(
		'repository_available', 'resolver_available', 'read_ready', 'write_configured',
		'collection_write_ready', 'knowledge_write_ready', 'any_write_ready',
		'write_enabled', 'repository_health',
	);
	private const REPOSITORY_HEALTH_KEYS = array(
		'healthy', 'database_ready', 'schema_ready', 'schema_version', 'code', 'cached_for_request',
	);
	private const SNAPSHOT_KEYS = array(
		'inputs_valid', 'integration_code', 'repository_available', 'repository_ready',
		'repository_code', 'repository_schema_version', 'repository_cached_for_request',
		'read_ready', 'write_configured', 'resolver_available',
		'resolver_readiness_available', 'resolver_ready', 'resolver_code',
		'collection_write_ready', 'knowledge_write_ready', 'any_write_ready', 'write_enabled',
	);
	private const INTEGRATION_CODES = array(
		'integration_input_invalid', 'writes_disabled', 'repository_unavailable',
		'repository_health_invalid', 'repository_not_ready', 'gate_state_invalid',
		'collection_ready', 'knowledge_ready',
	);
	private const REPOSITORY_CODES = array(
		'repository_not_evaluated', 'repository_unavailable',
		'repository_health_invalid', 'repository_not_ready', 'ready',
	);
	private const RESOLVER_CODES = array(
		'resolver_absent', 'resolver_readiness_missing', 'resolver_unavailable',
		'resolver_not_ready', 'resolver_readiness_invalid',
		'resolver_readiness_exception', 'resolver_readiness_reentrant',
		'resolver_not_evaluated', 'resolver_state_invalid', 'ready',
	);

	private SPDB_Collections_Repository_Readiness_Probe $probe;
	private SPDB_Collections_Repository_Readiness_Probe $collection_probe;
	private bool $resolver_available;
	private bool $resolver_readiness_available;

	private function __construct(
		SPDB_Collections_Repository_Readiness_Probe $probe,
		SPDB_Collections_Repository_Readiness_Probe $collection_probe,
		bool $resolver_available,
		bool $resolver_readiness_available
	) {
		$this->probe                        = $probe;
		$this->collection_probe             = $collection_probe;
		$this->resolver_available           = $resolver_available;
		$this->resolver_readiness_available = $resolver_readiness_available;
	}

	private function __clone() {}
	/** @return never */
	public function __serialize(): array { throw new LogicException( 'Collections service readiness consumers cannot be serialized.' ); }
	/** @param array<string,mixed> $data @return never */
	public function __unserialize( array $data ): void { throw new LogicException( 'Collections service readiness consumers cannot be unserialized.' ); }
	/** @return never */
	public function __wakeup(): void { throw new LogicException( 'Collections service readiness consumers cannot be awakened.' ); }

	public static function create(
		?SPDB_Collections_Repository $repository,
		?SPDB_Native_Reference_Resolver $resolver
	): self {
		$gate                   = new SPDB_Collections_Service_Readiness_Gate( $resolver );
		$integration            = new SPDB_Collections_Service_Readiness_Integration( $gate );
		$probe                  = new SPDB_Collections_Repository_Readiness_Probe( $repository, $integration );
		$collection_gate        = new SPDB_Collections_Service_Readiness_Gate();
		$collection_integration = new SPDB_Collections_Service_Readiness_Integration( $collection_gate );
		$collection_probe       = new SPDB_Collections_Repository_Readiness_Probe( $repository, $collection_integration );

		return new self(
			$probe,
			$collection_probe,
			null !== $resolver,
			$resolver instanceof SPDB_Native_Reference_Readiness
		);
	}

	/** @param mixed $writes_configured @return array<string,mixed> */
	public function health( $writes_configured ): array {
		$snapshot = $this->validated_snapshot( $writes_configured, false );
		if ( null === $snapshot ) { return $this->invalid_health(); }

		$repository_ready = $snapshot['repository_ready'];
		$health = array(
			'repository_available'   => $snapshot['repository_available'],
			'resolver_available'     => $this->resolver_available,
			'read_ready'             => $snapshot['read_ready'],
			'write_configured'       => $snapshot['write_configured'],
			'collection_write_ready' => $snapshot['collection_write_ready'],
			'knowledge_write_ready'  => $snapshot['knowledge_write_ready'],
			'any_write_ready'        => $snapshot['any_write_ready'],
			'write_enabled'          => $snapshot['write_enabled'],
			'repository_health'      => array(
				'healthy' => $repository_ready,
				'database_ready' => $repository_ready,
				'schema_ready' => $repository_ready,
				'schema_version' => $snapshot['repository_schema_version'],
				'code' => $snapshot['repository_code'],
				'cached_for_request' => $snapshot['repository_cached_for_request'],
			),
		);
		return $this->valid_health_projection( $health ) ? $health : $this->invalid_health();
	}

	/** @return true|WP_Error */
	public function require_read_ready() {
		$snapshot = $this->validated_snapshot( false, true );
		return null === $snapshot ? $this->projection_error() : $this->require_repository_ready( $snapshot );
	}

	/** @param mixed $requires_resolver @param mixed $writes_configured @return true|WP_Error */
	public function require_write_ready( $requires_resolver, $writes_configured ) {
		if ( ! is_bool( $requires_resolver ) || ! is_bool( $writes_configured ) ) {
			return $this->error( 'spdb_collections_readiness_input_invalid', 'The collection service readiness requirement is invalid.' );
		}
		if ( ! $writes_configured ) {
			return $this->error( 'spdb_collections_writes_disabled', 'Collection metadata writes remain disabled until reviewed staging acceptance.' );
		}

		$snapshot = $this->validated_snapshot( true, ! $requires_resolver );
		if ( null === $snapshot ) { return $this->projection_error(); }
		$repository = $this->require_repository_ready( $snapshot );
		if ( is_wp_error( $repository ) ) { return $repository; }

		if ( ! $requires_resolver ) {
			return $snapshot['collection_write_ready'] ? true : $this->projection_error();
		}
		if ( $snapshot['knowledge_write_ready'] ) { return true; }
		return $this->resolver_error( $snapshot['resolver_code'] );
	}

	/** @param mixed $writes_configured @return array<string,mixed>|null */
	private function validated_snapshot( $writes_configured, bool $collection_only ): ?array {
		$probe = $collection_only ? $this->collection_probe : $this->probe;
		try { $snapshot = $probe->snapshot( $writes_configured ); }
		catch ( Throwable $throwable ) { return null; }
		return $this->valid_snapshot( $snapshot, $collection_only ) ? $snapshot : null;
	}

	/** @param mixed $snapshot */
	private function valid_snapshot( $snapshot, bool $collection_only = false ): bool {
		if ( ! is_array( $snapshot )
			|| array_diff( array_keys( $snapshot ), self::SNAPSHOT_KEYS )
			|| array_diff( self::SNAPSHOT_KEYS, array_keys( $snapshot ) ) ) { return false; }

		foreach ( array(
			'inputs_valid', 'repository_available', 'repository_ready',
			'repository_cached_for_request', 'read_ready', 'write_configured',
			'resolver_available', 'resolver_readiness_available', 'resolver_ready',
			'collection_write_ready', 'knowledge_write_ready', 'any_write_ready', 'write_enabled',
		) as $key ) { if ( ! is_bool( $snapshot[ $key ] ) ) { return false; } }
		foreach ( array( 'integration_code', 'repository_code', 'repository_schema_version', 'resolver_code' ) as $key ) {
			if ( ! is_string( $snapshot[ $key ] ) ) { return false; }
		}

		if ( ! in_array( $snapshot['integration_code'], self::INTEGRATION_CODES, true )
			|| ! in_array( $snapshot['repository_code'], self::REPOSITORY_CODES, true )
			|| ! in_array( $snapshot['resolver_code'], self::RESOLVER_CODES, true )
			|| $snapshot['read_ready'] !== $snapshot['repository_ready']
			|| $snapshot['any_write_ready'] !== ( $snapshot['collection_write_ready'] || $snapshot['knowledge_write_ready'] )
			|| $snapshot['write_enabled'] !== $snapshot['any_write_ready']
			|| ( $snapshot['knowledge_write_ready'] && ! $snapshot['collection_write_ready'] )
			|| ( $snapshot['collection_write_ready'] && ( ! $snapshot['write_configured'] || ! $snapshot['repository_ready'] ) )
			|| ( $snapshot['knowledge_write_ready'] && ! $snapshot['resolver_ready'] )
			|| $snapshot['resolver_ready'] !== ( 'ready' === $snapshot['resolver_code'] )
			|| ! $this->valid_repository_snapshot_state( $snapshot )
			|| ! $this->valid_resolver_snapshot_state( $snapshot ) ) { return false; }

		switch ( $snapshot['integration_code'] ) {
			case 'integration_input_invalid':
				return ! $snapshot['inputs_valid'] && ! $snapshot['write_configured']
					&& 'repository_not_evaluated' === $snapshot['repository_code']
					&& $this->no_write_state( $snapshot ) && $this->resolver_not_evaluated( $snapshot );
			case 'writes_disabled':
				return $snapshot['inputs_valid'] && ! $snapshot['write_configured']
					&& 'repository_not_evaluated' !== $snapshot['repository_code']
					&& $this->no_write_state( $snapshot ) && $this->resolver_suppressed( $snapshot );
			case 'repository_unavailable':
				return $snapshot['inputs_valid'] && $snapshot['write_configured']
					&& 'repository_unavailable' === $snapshot['repository_code']
					&& $this->no_write_state( $snapshot ) && $this->resolver_suppressed( $snapshot );
			case 'repository_health_invalid':
				return $snapshot['inputs_valid'] && $snapshot['write_configured']
					&& 'repository_health_invalid' === $snapshot['repository_code']
					&& $this->no_write_state( $snapshot ) && $this->resolver_suppressed( $snapshot );
			case 'repository_not_ready':
				return $snapshot['inputs_valid'] && $snapshot['write_configured']
					&& 'repository_not_ready' === $snapshot['repository_code']
					&& $this->no_write_state( $snapshot ) && $this->resolver_suppressed( $snapshot );
			case 'gate_state_invalid':
				return ! $collection_only && $snapshot['inputs_valid'] && $snapshot['write_configured']
					&& 'ready' === $snapshot['repository_code']
					&& 'resolver_state_invalid' === $snapshot['resolver_code'] && $this->no_write_state( $snapshot );
			case 'collection_ready':
				$base = $snapshot['inputs_valid'] && $snapshot['write_configured']
					&& 'ready' === $snapshot['repository_code']
					&& $snapshot['collection_write_ready'] && ! $snapshot['knowledge_write_ready']
					&& $snapshot['any_write_ready'];
				return $collection_only
					? $base && $this->resolver_suppressed( $snapshot )
					: $base
						&& $snapshot['resolver_available'] === $this->resolver_available
						&& $snapshot['resolver_readiness_available'] === $this->resolver_readiness_available;
			case 'knowledge_ready':
				return ! $collection_only && $snapshot['inputs_valid'] && $snapshot['write_configured']
					&& 'ready' === $snapshot['repository_code']
					&& $snapshot['collection_write_ready'] && $snapshot['knowledge_write_ready']
					&& $snapshot['any_write_ready']
					&& $snapshot['resolver_available'] === $this->resolver_available
					&& $snapshot['resolver_readiness_available'] === $this->resolver_readiness_available
					&& $this->resolver_available && $this->resolver_readiness_available;
			default:
				return false;
		}
	}

	/** @param array<string,mixed> $snapshot */
	private function valid_repository_snapshot_state( array $snapshot ): bool {
		switch ( $snapshot['repository_code'] ) {
			case 'repository_not_evaluated':
			case 'repository_unavailable':
				return ! $snapshot['repository_available'] && ! $snapshot['repository_ready']
					&& '' === $snapshot['repository_schema_version'] && ! $snapshot['repository_cached_for_request'];
			case 'repository_health_invalid':
				return $snapshot['repository_available'] && ! $snapshot['repository_ready']
					&& '' === $snapshot['repository_schema_version'] && ! $snapshot['repository_cached_for_request'];
			case 'repository_not_ready':
				return $snapshot['repository_available'] && ! $snapshot['repository_ready']
					&& $this->current_schema_version( $snapshot['repository_schema_version'] );
			case 'ready':
				return $snapshot['repository_available'] && $snapshot['repository_ready']
					&& $this->current_schema_version( $snapshot['repository_schema_version'] );
			default: return false;
		}
	}

	/** @param array<string,mixed> $snapshot */
	private function valid_resolver_snapshot_state( array $snapshot ): bool {
		switch ( $snapshot['resolver_code'] ) {
			case 'resolver_not_evaluated':
			case 'resolver_absent':
			case 'resolver_state_invalid':
				return ! $snapshot['resolver_available'] && ! $snapshot['resolver_readiness_available'] && ! $snapshot['resolver_ready'];
			case 'resolver_readiness_missing':
				return $snapshot['resolver_available'] && ! $snapshot['resolver_readiness_available'] && ! $snapshot['resolver_ready'];
			case 'resolver_unavailable':
			case 'resolver_not_ready':
			case 'resolver_readiness_invalid':
			case 'resolver_readiness_exception':
			case 'resolver_readiness_reentrant':
				return $snapshot['resolver_available'] && $snapshot['resolver_readiness_available'] && ! $snapshot['resolver_ready'];
			case 'ready':
				return $snapshot['resolver_available'] && $snapshot['resolver_readiness_available'] && $snapshot['resolver_ready'];
			default: return false;
		}
	}

	/** @param array<string,mixed> $snapshot */
	private function no_write_state( array $snapshot ): bool {
		return ! $snapshot['collection_write_ready'] && ! $snapshot['knowledge_write_ready']
			&& ! $snapshot['any_write_ready'] && ! $snapshot['write_enabled'];
	}
	/** @param array<string,mixed> $snapshot */
	private function resolver_not_evaluated( array $snapshot ): bool {
		return 'resolver_not_evaluated' === $snapshot['resolver_code']
			&& ! $snapshot['resolver_available'] && ! $snapshot['resolver_readiness_available'] && ! $snapshot['resolver_ready'];
	}
	/** @param array<string,mixed> $snapshot */
	private function resolver_suppressed( array $snapshot ): bool {
		return 'resolver_absent' === $snapshot['resolver_code']
			&& ! $snapshot['resolver_available'] && ! $snapshot['resolver_readiness_available'] && ! $snapshot['resolver_ready'];
	}

	/** @param array<string,mixed> $health */
	private function valid_health_projection( array $health ): bool {
		if ( array_diff( array_keys( $health ), self::HEALTH_KEYS )
			|| array_diff( self::HEALTH_KEYS, array_keys( $health ) )
			|| ! is_array( $health['repository_health'] ?? null ) ) { return false; }
		foreach ( array( 'repository_available', 'resolver_available', 'read_ready', 'write_configured',
			'collection_write_ready', 'knowledge_write_ready', 'any_write_ready', 'write_enabled' ) as $key ) {
			if ( ! is_bool( $health[ $key ] ) ) { return false; }
		}

		$repository = $health['repository_health'];
		if ( array_diff( array_keys( $repository ), self::REPOSITORY_HEALTH_KEYS )
			|| array_diff( self::REPOSITORY_HEALTH_KEYS, array_keys( $repository ) )
			|| ! is_bool( $repository['healthy'] ?? null )
			|| ! is_bool( $repository['database_ready'] ?? null )
			|| ! is_bool( $repository['schema_ready'] ?? null )
			|| ! is_string( $repository['schema_version'] ?? null )
			|| ! is_string( $repository['code'] ?? null )
			|| ! in_array( $repository['code'], self::REPOSITORY_CODES, true )
			|| ! is_bool( $repository['cached_for_request'] ?? null )
			|| $health['resolver_available'] !== $this->resolver_available ) { return false; }

		$state = array(
			'repository_available' => $health['repository_available'],
			'repository_ready' => $repository['healthy'],
			'repository_code' => $repository['code'],
			'repository_schema_version' => $repository['schema_version'],
			'repository_cached_for_request' => $repository['cached_for_request'],
		);
		if ( ! $this->valid_repository_snapshot_state( $state ) ) { return false; }

		return $health['read_ready'] === $repository['healthy']
			&& $repository['healthy'] === $repository['database_ready']
			&& $repository['healthy'] === $repository['schema_ready']
			&& $health['any_write_ready'] === ( $health['collection_write_ready'] || $health['knowledge_write_ready'] )
			&& $health['write_enabled'] === $health['any_write_ready']
			&& ( $health['write_configured'] || ! $health['any_write_ready'] )
			&& ( ! $health['collection_write_ready'] || ( $health['write_configured'] && $health['read_ready'] ) )
			&& ( ! $health['knowledge_write_ready'] || ( $health['collection_write_ready']
				&& $this->resolver_available && $this->resolver_readiness_available ) );
	}

	/** @param array<string,mixed> $snapshot @return true|WP_Error */
	private function require_repository_ready( array $snapshot ) {
		if ( ! $snapshot['repository_available'] ) {
			return $this->error( 'spdb_collections_repository_unavailable', 'The collection metadata repository is unavailable.' );
		}
		if ( 'repository_health_invalid' === $snapshot['repository_code'] ) {
			return $this->error( 'spdb_collections_repository_health_invalid', 'The collection metadata repository returned an invalid health response.' );
		}
		if ( ! $snapshot['repository_ready'] ) {
			return $this->error( 'spdb_collections_repository_not_ready', 'The collection metadata repository is not ready.' );
		}
		return true;
	}

	private function resolver_error( string $code ): WP_Error {
		switch ( $code ) {
			case 'resolver_absent':
			case 'resolver_unavailable': return $this->error( 'spdb_native_reference_resolver_unavailable', 'The native-reference resolver is unavailable.' );
			case 'resolver_readiness_missing': return $this->error( 'spdb_native_reference_readiness_missing', 'The native-reference resolver does not provide the required readiness contract.' );
			case 'resolver_not_ready': return $this->error( 'spdb_native_reference_resolver_not_ready', 'The native-reference resolver is not operationally ready.' );
			case 'resolver_readiness_invalid': return $this->error( 'spdb_native_reference_readiness_invalid', 'The native-reference readiness response is invalid.' );
			case 'resolver_readiness_exception': return $this->error( 'spdb_native_reference_readiness_failed', 'The native-reference readiness check failed and was isolated.' );
			case 'resolver_readiness_reentrant': return $this->error( 'spdb_native_reference_readiness_reentrant', 'The native-reference readiness check was re-entered and was denied.' );
			default: return $this->projection_error();
		}
	}

	private function current_schema_version( string $version ): bool {
		return class_exists( 'SPDB_Collections_Schema', false )
			&& 1 === preg_match( '/^(?:0|[1-9][0-9]*)$/', $version )
			&& SPDB_Collections_Schema::VERSION === $version;
	}

	/** @return array<string,mixed> */
	private function invalid_health(): array {
		return array(
			'repository_available' => false, 'resolver_available' => $this->resolver_available,
			'read_ready' => false, 'write_configured' => false,
			'collection_write_ready' => false, 'knowledge_write_ready' => false,
			'any_write_ready' => false, 'write_enabled' => false,
			'repository_health' => array(
				'healthy' => false, 'database_ready' => false, 'schema_ready' => false,
				'schema_version' => '', 'code' => 'service_readiness_projection_invalid',
				'cached_for_request' => false,
			),
		);
	}
	private function projection_error(): WP_Error {
		return $this->error( 'spdb_collections_readiness_projection_invalid', 'The collection service readiness projection is invalid.' );
	}
	private function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 503 ) );
	}
}
