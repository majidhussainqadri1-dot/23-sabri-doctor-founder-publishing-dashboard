<?php
/**
 * Bounded repository-and-resolver readiness integration for the Collections service.
 *
 * This class does not execute repository writes, native reference resolution, REST
 * mutations, or plugin wiring. It produces the exact fail-closed decision state
 * that a later reviewed SPDB_Collections_Service integration will consume.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Service_Readiness_Integration {
	private const MAX_CODE_LENGTH = 64;

	private SPDB_Collections_Service_Readiness_Gate $gate;

	public function __construct( SPDB_Collections_Service_Readiness_Gate $gate ) {
		$this->gate = $gate;
	}

	/**
	 * Authority booleans are runtime-validated to prevent weak scalar coercion.
	 *
	 * @param mixed $writes_configured
	 * @param mixed $repository_available
	 * @param mixed $repository_health
	 * @return array{
	 *   repository_available:bool,
	 *   repository_ready:bool,
	 *   repository_code:string,
	 *   read_ready:bool,
	 *   write_configured:bool,
	 *   resolver_available:bool,
	 *   resolver_readiness_available:bool,
	 *   resolver_ready:bool,
	 *   resolver_code:string,
	 *   collection_write_ready:bool,
	 *   knowledge_write_ready:bool,
	 *   any_write_ready:bool,
	 *   write_enabled:bool
	 * }
	 */
	public function snapshot( $writes_configured, $repository_available, $repository_health ): array {
		if ( ! $this->valid_authority_inputs( $writes_configured, $repository_available ) ) {
			return $this->invalid_input_snapshot();
		}

		$repository = $this->repository_snapshot( $repository_available, $repository_health );
		$gate       = $this->gate->snapshot( $writes_configured, $repository['ready'] );

		return array(
			'repository_available'           => $repository['available'],
			'repository_ready'               => $repository['ready'],
			'repository_code'                => $repository['code'],
			'read_ready'                     => $repository['ready'],
			'write_configured'               => $writes_configured,
			'resolver_available'             => $gate['resolver_available'],
			'resolver_readiness_available'   => $gate['resolver_readiness_available'],
			'resolver_ready'                 => $gate['resolver_ready'],
			'resolver_code'                  => $gate['resolver_code'],
			'collection_write_ready'         => $gate['collection_write_ready'],
			'knowledge_write_ready'          => $gate['knowledge_write_ready'],
			'any_write_ready'                => $gate['any_write_ready'],
			'write_enabled'                  => $gate['any_write_ready'],
		);
	}

	/** @param mixed $writes_configured @param mixed $repository_available @param mixed $repository_health @return true|WP_Error */
	public function require_collection_write_ready( $writes_configured, $repository_available, $repository_health ) {
		$inputs = $this->require_valid_authority_inputs( $writes_configured, $repository_available );
		if ( is_wp_error( $inputs ) ) {
			return $inputs;
		}

		$configured = $this->require_writes_configured( $writes_configured );
		if ( is_wp_error( $configured ) ) {
			return $configured;
		}

		$repository = $this->repository_snapshot( $repository_available, $repository_health );
		return $this->require_repository_ready( $repository );
	}

	/** @param mixed $writes_configured @param mixed $repository_available @param mixed $repository_health @return true|WP_Error */
	public function require_knowledge_write_ready( $writes_configured, $repository_available, $repository_health ) {
		$inputs = $this->require_valid_authority_inputs( $writes_configured, $repository_available );
		if ( is_wp_error( $inputs ) ) {
			return $inputs;
		}

		$configured = $this->require_writes_configured( $writes_configured );
		if ( is_wp_error( $configured ) ) {
			return $configured;
		}

		$repository = $this->repository_snapshot( $repository_available, $repository_health );
		$repository_gate = $this->require_repository_ready( $repository );
		if ( is_wp_error( $repository_gate ) ) {
			return $repository_gate;
		}

		return $this->gate->require_knowledge_write_ready( true, true );
	}

	/** @param mixed $source @return array{available:bool,ready:bool,code:string} */
	private function repository_snapshot( bool $available, $source ): array {
		if ( ! $available ) {
			return $this->repository_state( false, false, 'repository_unavailable' );
		}

		if ( ! is_array( $source )
			|| ! is_bool( $source['healthy'] ?? null )
			|| ! is_bool( $source['database_ready'] ?? null )
			|| ! is_bool( $source['schema_ready'] ?? null )
			|| ! is_string( $source['code'] ?? null )
			|| '' === $source['code']
			|| strlen( $source['code'] ) > self::MAX_CODE_LENGTH
			|| ! SPDB_Adapter_Registry::is_canonical_key( $source['code'] )
		) {
			return $this->repository_state( true, false, 'repository_health_invalid' );
		}

		$expected_healthy = true === $source['database_ready'] && true === $source['schema_ready'];
		if ( $source['healthy'] !== $expected_healthy ) {
			return $this->repository_state( true, false, 'repository_health_invalid' );
		}

		$ready = true === $source['healthy'];
		if ( $ready && 'ready' !== $source['code'] ) {
			return $this->repository_state( true, false, 'repository_health_invalid' );
		}
		if ( ! $ready && 'ready' === $source['code'] ) {
			return $this->repository_state( true, false, 'repository_health_invalid' );
		}

		return $this->repository_state( true, $ready, $ready ? 'ready' : 'repository_not_ready' );
	}

	/** @return array<string,bool|string> */
	private function invalid_input_snapshot(): array {
		$gate = $this->gate->snapshot( 'invalid', false );

		return array(
			'repository_available'           => false,
			'repository_ready'               => false,
			'repository_code'                => 'integration_input_invalid',
			'read_ready'                     => false,
			'write_configured'               => false,
			'resolver_available'             => $gate['resolver_available'],
			'resolver_readiness_available'   => $gate['resolver_readiness_available'],
			'resolver_ready'                 => false,
			'resolver_code'                  => 'resolver_not_evaluated',
			'collection_write_ready'         => false,
			'knowledge_write_ready'          => false,
			'any_write_ready'                => false,
			'write_enabled'                  => false,
		);
	}

	private function valid_authority_inputs( $writes_configured, $repository_available ): bool {
		return is_bool( $writes_configured ) && is_bool( $repository_available );
	}

	/** @return true|WP_Error */
	private function require_valid_authority_inputs( $writes_configured, $repository_available ) {
		return $this->valid_authority_inputs( $writes_configured, $repository_available )
			? true
			: $this->error(
				'spdb_collections_readiness_input_invalid',
				__( 'The collection readiness authority inputs are invalid.', 'sabri-publishing-dashboard' )
			);
	}

	/** @return true|WP_Error */
	private function require_writes_configured( bool $writes_configured ) {
		return $this->gate->require_collection_write_ready( $writes_configured, true );
	}

	/** @param array{available:bool,ready:bool,code:string} $repository @return true|WP_Error */
	private function require_repository_ready( array $repository ) {
		if ( ! $repository['available'] ) {
			return $this->error(
				'spdb_collections_repository_unavailable',
				__( 'The collection metadata repository is unavailable.', 'sabri-publishing-dashboard' )
			);
		}
		if ( 'repository_health_invalid' === $repository['code'] ) {
			return $this->error(
				'spdb_collections_repository_health_invalid',
				__( 'The collection metadata repository returned an invalid health response.', 'sabri-publishing-dashboard' )
			);
		}
		if ( ! $repository['ready'] ) {
			return $this->error(
				'spdb_collections_repository_not_ready',
				__( 'The collection metadata repository is not ready.', 'sabri-publishing-dashboard' )
			);
		}
		return true;
	}

	/** @return array{available:bool,ready:bool,code:string} */
	private function repository_state( bool $available, bool $ready, string $code ): array {
		return array(
			'available' => $available,
			'ready'     => $ready,
			'code'      => $code,
		);
	}

	private function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => 503 ) );
	}
}
