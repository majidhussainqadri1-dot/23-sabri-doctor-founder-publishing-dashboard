<?php
/**
 * Bounded repository-and-resolver readiness integration for Collections service.
 *
 * This class does not execute repository writes, native reference resolution,
 * REST mutations, or plugin wiring. It produces a fail-closed decision state
 * for a later separately reviewed SPDB_Collections_Service integration.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Service_Readiness_Integration {
	private const MAX_CODE_LENGTH = 64;
	private const HEALTH_KEYS = array(
		'healthy',
		'database_ready',
		'schema_ready',
		'schema_version',
		'code',
		'cached_for_request',
	);

	private SPDB_Collections_Service_Readiness_Gate $gate;

	public function __construct( SPDB_Collections_Service_Readiness_Gate $gate ) {
		$this->gate = $gate;
	}

	/**
	 * @param mixed $writes_configured
	 * @param mixed $repository_available
	 * @param mixed $repository_health
	 * @return array<string,mixed>
	 */
	public function snapshot( $writes_configured, $repository_available, $repository_health ): array {
		if ( ! $this->valid_authority_inputs( $writes_configured, $repository_available ) ) {
			$gate = $this->gate->snapshot( null, null );
			return $this->projection(
				false,
				'integration_input_invalid',
				false,
				false,
				'repository_not_evaluated',
				'',
				false,
				false,
				$gate
			);
		}

		$repository = $this->repository_snapshot( $repository_available, $repository_health );
		$gate       = $this->gate->snapshot( $writes_configured, $repository['ready'] );
		$code       = $this->integration_code( $writes_configured, $repository, $gate );

		return $this->projection(
			true,
			$code,
			$repository['available'],
			$repository['ready'],
			$repository['code'],
			$repository['schema_version'],
			$repository['cached_for_request'],
			$writes_configured,
			$gate
		);
	}

	/**
	 * @param mixed $writes_configured
	 * @param mixed $repository_available
	 * @param mixed $repository_health
	 * @return true|WP_Error
	 */
	public function require_collection_write_ready( $writes_configured, $repository_available, $repository_health ) {
		$inputs = $this->require_valid_inputs( $writes_configured, $repository_available );
		if ( is_wp_error( $inputs ) ) {
			return $inputs;
		}
		if ( ! $writes_configured ) {
			return $this->gate->require_collection_write_ready( false, true );
		}

		$repository = $this->repository_snapshot( $repository_available, $repository_health );
		$ready      = $this->require_repository_ready( $repository );
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}
		return $this->gate->require_collection_write_ready( true, true );
	}

	/**
	 * @param mixed $writes_configured
	 * @param mixed $repository_available
	 * @param mixed $repository_health
	 * @return true|WP_Error
	 */
	public function require_knowledge_write_ready( $writes_configured, $repository_available, $repository_health ) {
		$inputs = $this->require_valid_inputs( $writes_configured, $repository_available );
		if ( is_wp_error( $inputs ) ) {
			return $inputs;
		}
		if ( ! $writes_configured ) {
			return $this->gate->require_knowledge_write_ready( false, true );
		}

		$repository = $this->repository_snapshot( $repository_available, $repository_health );
		$ready      = $this->require_repository_ready( $repository );
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}
		return $this->gate->require_knowledge_write_ready( true, true );
	}

	/** @param mixed $source @return array{available:bool,ready:bool,code:string,schema_version:string,cached_for_request:bool} */
	private function repository_snapshot( bool $available, $source ): array {
		if ( ! $available ) {
			return $this->repository_state( false, false, 'repository_unavailable', '', false );
		}
		if ( ! is_array( $source )
			|| array_diff( array_keys( $source ), self::HEALTH_KEYS )
			|| array_diff( self::HEALTH_KEYS, array_keys( $source ) )
			|| ! is_bool( $source['healthy'] ?? null )
			|| ! is_bool( $source['database_ready'] ?? null )
			|| ! is_bool( $source['schema_ready'] ?? null )
			|| ! is_string( $source['schema_version'] ?? null )
			|| ! is_string( $source['code'] ?? null )
			|| ! is_bool( $source['cached_for_request'] ?? null )
			|| '' === $source['code']
			|| strlen( $source['code'] ) > self::MAX_CODE_LENGTH
			|| ! SPDB_Adapter_Registry::is_canonical_key( $source['code'] )
			|| ! $this->current_schema_version( $source['schema_version'] )
		) {
			return $this->repository_state( true, false, 'repository_health_invalid', '', false );
		}

		$expected_healthy = true === $source['database_ready'] && true === $source['schema_ready'];
		if ( $source['healthy'] !== $expected_healthy ) {
			return $this->repository_state( true, false, 'repository_health_invalid', '', false );
		}

		$ready = true === $source['healthy'];
		if ( ( $ready && 'ready' !== $source['code'] ) || ( ! $ready && 'ready' === $source['code'] ) ) {
			return $this->repository_state( true, false, 'repository_health_invalid', '', false );
		}

		return $this->repository_state(
			true,
			$ready,
			$ready ? 'ready' : 'repository_not_ready',
			$source['schema_version'],
			$source['cached_for_request']
		);
	}

	/** @return true|WP_Error */
	private function require_valid_inputs( $writes_configured, $repository_available ) {
		return $this->valid_authority_inputs( $writes_configured, $repository_available )
			? true
			: $this->error( 'spdb_collections_readiness_input_invalid', 'The collection readiness authority inputs are invalid.' );
	}

	private function valid_authority_inputs( $writes_configured, $repository_available ): bool {
		return is_bool( $writes_configured ) && is_bool( $repository_available );
	}

	private function current_schema_version( string $version ): bool {
		return class_exists( 'SPDB_Collections_Schema', false )
			&& 1 === preg_match( '/^(?:0|[1-9][0-9]*)$/', $version )
			&& SPDB_Collections_Schema::VERSION === $version;
	}

	/** @param array{available:bool,ready:bool,code:string,schema_version:string,cached_for_request:bool} $repository @return true|WP_Error */
	private function require_repository_ready( array $repository ) {
		if ( ! $repository['available'] ) {
			return $this->error( 'spdb_collections_repository_unavailable', 'The collection metadata repository is unavailable.' );
		}
		if ( 'repository_health_invalid' === $repository['code'] ) {
			return $this->error( 'spdb_collections_repository_health_invalid', 'The collection metadata repository returned an invalid health response.' );
		}
		if ( ! $repository['ready'] ) {
			return $this->error( 'spdb_collections_repository_not_ready', 'The collection metadata repository is not ready.' );
		}
		return true;
	}

	/** @param array<string,mixed> $repository @param array<string,mixed> $gate */
	private function integration_code( bool $writes_configured, array $repository, array $gate ): string {
		if ( ! $repository['available'] ) {
			return 'repository_unavailable';
		}
		if ( 'repository_health_invalid' === $repository['code'] ) {
			return 'repository_health_invalid';
		}
		if ( ! $repository['ready'] ) {
			return 'repository_not_ready';
		}
		if ( ! $writes_configured ) {
			return 'writes_disabled';
		}
		return true === ( $gate['knowledge_write_ready'] ?? false ) ? 'knowledge_ready' : 'collection_ready';
	}

	/** @return array{available:bool,ready:bool,code:string,schema_version:string,cached_for_request:bool} */
	private function repository_state( bool $available, bool $ready, string $code, string $schema_version, bool $cached_for_request ): array {
		return array(
			'available'             => $available,
			'ready'                 => $ready,
			'code'                  => $code,
			'schema_version'        => $schema_version,
			'cached_for_request'    => $cached_for_request,
		);
	}

	/** @param array<string,mixed> $gate @return array<string,mixed> */
	private function projection(
		bool $inputs_valid,
		string $integration_code,
		bool $repository_available,
		bool $repository_ready,
		string $repository_code,
		string $repository_schema_version,
		bool $repository_cached_for_request,
		bool $writes_configured,
		array $gate
	): array {
		return array(
			'inputs_valid'                   => $inputs_valid,
			'integration_code'               => $integration_code,
			'repository_available'           => $repository_available,
			'repository_ready'               => $repository_ready,
			'repository_code'                => $repository_code,
			'repository_schema_version'      => $repository_schema_version,
			'repository_cached_for_request'  => $repository_cached_for_request,
			'read_ready'                     => $repository_ready,
			'write_configured'               => $inputs_valid && $writes_configured,
			'resolver_available'             => true === ( $gate['resolver_available'] ?? false ),
			'resolver_readiness_available'   => true === ( $gate['resolver_readiness_available'] ?? false ),
			'resolver_ready'                 => true === ( $gate['resolver_ready'] ?? false ),
			'resolver_code'                  => is_string( $gate['resolver_code'] ?? null ) ? $gate['resolver_code'] : 'resolver_state_invalid',
			'collection_write_ready'         => true === ( $gate['collection_write_ready'] ?? false ),
			'knowledge_write_ready'          => true === ( $gate['knowledge_write_ready'] ?? false ),
			'any_write_ready'                => true === ( $gate['any_write_ready'] ?? false ),
			'write_enabled'                  => true === ( $gate['any_write_ready'] ?? false ),
		);
	}

	private function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 503 ) );
	}
}
