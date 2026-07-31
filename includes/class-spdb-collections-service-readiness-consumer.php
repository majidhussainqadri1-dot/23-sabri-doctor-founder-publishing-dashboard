<?php
/**
 * Map reviewed Collections readiness into the stable service health/gate contract.
 *
 * This consumer builds its own gate, integration, and probe from one exact
 * repository/resolver pair. It stores only resolver presence, exposes no raw
 * dependency, performs no metadata operation or native resolution, persists no
 * data, registers no route, and does not wire the plugin container.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Service_Readiness_Consumer {
	private const HEALTH_KEYS = array(
		'repository_available',
		'resolver_available',
		'read_ready',
		'write_configured',
		'collection_write_ready',
		'knowledge_write_ready',
		'any_write_ready',
		'write_enabled',
		'repository_health',
	);

	private const REPOSITORY_HEALTH_KEYS = array(
		'healthy',
		'database_ready',
		'schema_ready',
		'schema_version',
		'code',
		'cached_for_request',
	);

	private SPDB_Collections_Repository_Readiness_Probe $probe;
	private bool $resolver_available;

	private function __construct(
		SPDB_Collections_Repository_Readiness_Probe $probe,
		bool $resolver_available
	) {
		$this->probe              = $probe;
		$this->resolver_available = $resolver_available;
	}

	private function __clone() {}

	/** @return never */
	public function __serialize(): array {
		throw new LogicException( 'Collections service readiness consumers cannot be serialized.' );
	}

	/** @param array<string,mixed> $data @return never */
	public function __unserialize( array $data ): void {
		throw new LogicException( 'Collections service readiness consumers cannot be unserialized.' );
	}

	public static function create(
		?SPDB_Collections_Repository $repository,
		?SPDB_Native_Reference_Resolver $resolver
	): self {
		$gate        = new SPDB_Collections_Service_Readiness_Gate( $resolver );
		$integration = new SPDB_Collections_Service_Readiness_Integration( $gate );
		$probe       = new SPDB_Collections_Repository_Readiness_Probe( $repository, $integration );

		return new self( $probe, null !== $resolver );
	}

	/** @param mixed $writes_configured @return array<string,mixed> */
	public function health( $writes_configured ): array {
		$snapshot = $this->probe->snapshot( $writes_configured );

		$repository_ready = true === ( $snapshot['repository_ready'] ?? false );
		$health = array(
			'repository_available'   => true === ( $snapshot['repository_available'] ?? false ),
			'resolver_available'     => $this->resolver_available,
			'read_ready'             => true === ( $snapshot['read_ready'] ?? false ),
			'write_configured'       => true === ( $snapshot['write_configured'] ?? false ),
			'collection_write_ready' => true === ( $snapshot['collection_write_ready'] ?? false ),
			'knowledge_write_ready'  => true === ( $snapshot['knowledge_write_ready'] ?? false ),
			'any_write_ready'        => true === ( $snapshot['any_write_ready'] ?? false ),
			'write_enabled'          => true === ( $snapshot['write_enabled'] ?? false ),
			'repository_health'      => array(
				'healthy'            => $repository_ready,
				'database_ready'     => $repository_ready,
				'schema_ready'       => $repository_ready,
				'schema_version'     => is_string( $snapshot['repository_schema_version'] ?? null ) ? $snapshot['repository_schema_version'] : '',
				'code'               => is_string( $snapshot['repository_code'] ?? null ) ? $snapshot['repository_code'] : 'repository_health_invalid',
				'cached_for_request' => true === ( $snapshot['repository_cached_for_request'] ?? false ),
			),
		);

		return $this->valid_health_projection( $health ) ? $health : $this->invalid_health();
	}

	/** @return true|WP_Error */
	public function require_read_ready() {
		return $this->probe->require_read_ready();
	}

	/**
	 * @param mixed $requires_resolver
	 * @param mixed $writes_configured
	 * @return true|WP_Error
	 */
	public function require_write_ready( $requires_resolver, $writes_configured ) {
		if ( ! is_bool( $requires_resolver ) ) {
			return $this->error(
				'spdb_collections_readiness_input_invalid',
				'The collection service readiness requirement is invalid.'
			);
		}

		return $requires_resolver
			? $this->probe->require_knowledge_write_ready( $writes_configured )
			: $this->probe->require_collection_write_ready( $writes_configured );
	}

	/** @param array<string,mixed> $health */
	private function valid_health_projection( array $health ): bool {
		if ( array_diff( array_keys( $health ), self::HEALTH_KEYS )
			|| array_diff( self::HEALTH_KEYS, array_keys( $health ) )
			|| ! is_array( $health['repository_health'] ?? null )
		) {
			return false;
		}

		foreach ( array(
			'repository_available',
			'resolver_available',
			'read_ready',
			'write_configured',
			'collection_write_ready',
			'knowledge_write_ready',
			'any_write_ready',
			'write_enabled',
		) as $key ) {
			if ( ! is_bool( $health[ $key ] ) ) {
				return false;
			}
		}

		$repository = $health['repository_health'];
		if ( array_diff( array_keys( $repository ), self::REPOSITORY_HEALTH_KEYS )
			|| array_diff( self::REPOSITORY_HEALTH_KEYS, array_keys( $repository ) )
			|| ! is_bool( $repository['healthy'] ?? null )
			|| ! is_bool( $repository['database_ready'] ?? null )
			|| ! is_bool( $repository['schema_ready'] ?? null )
			|| ! is_string( $repository['schema_version'] ?? null )
			|| ! is_string( $repository['code'] ?? null )
			|| '' === $repository['code']
			|| ! is_bool( $repository['cached_for_request'] ?? null )
		) {
			return false;
		}

		return $health['read_ready'] === $repository['healthy']
			&& $repository['healthy'] === $repository['database_ready']
			&& $repository['healthy'] === $repository['schema_ready']
			&& $health['any_write_ready'] === ( $health['collection_write_ready'] || $health['knowledge_write_ready'] )
			&& $health['write_enabled'] === $health['any_write_ready']
			&& ( ! $health['knowledge_write_ready'] || $health['collection_write_ready'] )
			&& ( ! $health['knowledge_write_ready'] || $health['resolver_available'] )
			&& ( $health['write_configured'] || ! $health['any_write_ready'] );
	}

	/** @return array<string,mixed> */
	private function invalid_health(): array {
		return array(
			'repository_available'   => false,
			'resolver_available'     => $this->resolver_available,
			'read_ready'             => false,
			'write_configured'       => false,
			'collection_write_ready' => false,
			'knowledge_write_ready'  => false,
			'any_write_ready'        => false,
			'write_enabled'          => false,
			'repository_health'      => array(
				'healthy'            => false,
				'database_ready'     => false,
				'schema_ready'       => false,
				'schema_version'     => '',
				'code'               => 'service_readiness_projection_invalid',
				'cached_for_request' => false,
			),
		);
	}

	private function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 503 ) );
	}
}
