<?php
/** Fail-closed write-readiness decision boundary for Collections service integration. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Service_Readiness_Gate {
	private const MAX_CODE_LENGTH = 64;

	private ?SPDB_Native_Reference_Resolver $resolver;
	private ?SPDB_Native_Reference_Readiness $readiness;

	public function __construct( ?SPDB_Native_Reference_Resolver $resolver = null ) {
		$this->resolver  = $resolver;
		$this->readiness = $resolver instanceof SPDB_Native_Reference_Readiness ? $resolver : null;
	}

	/**
	 * @return array{
	 *   resolver_available:bool,
	 *   resolver_readiness_available:bool,
	 *   resolver_ready:bool,
	 *   resolver_code:string,
	 *   collection_write_ready:bool,
	 *   knowledge_write_ready:bool,
	 *   any_write_ready:bool
	 * }
	 */
	public function snapshot( bool $writes_configured, bool $repository_ready ): array {
		$resolver = $this->resolver_snapshot();
		$collection_write_ready = $writes_configured && $repository_ready;
		$knowledge_write_ready  = $collection_write_ready && $resolver['ready'];

		return array(
			'resolver_available'           => $resolver['available'],
			'resolver_readiness_available' => $resolver['readiness_available'],
			'resolver_ready'               => $resolver['ready'],
			'resolver_code'                => $resolver['code'],
			'collection_write_ready'       => $collection_write_ready,
			'knowledge_write_ready'        => $knowledge_write_ready,
			'any_write_ready'              => $collection_write_ready || $knowledge_write_ready,
		);
	}

	/** @return true|WP_Error */
	public function require_knowledge_write_ready( bool $writes_configured, bool $repository_ready ) {
		if ( ! $writes_configured ) {
			return $this->error( 'spdb_phase23f_writes_disabled', 'Metadata writes remain disabled until reviewed staging acceptance.' );
		}
		if ( ! $repository_ready ) {
			return $this->error( 'spdb_collections_repository_not_ready', 'The collection metadata repository is not ready.' );
		}

		$resolver = $this->resolver_snapshot();
		if ( ! $resolver['available'] ) {
			return $this->error( 'spdb_native_reference_resolver_unavailable', 'The native-reference resolver is unavailable.' );
		}
		if ( ! $resolver['readiness_available'] || ! $resolver['ready'] ) {
			return $this->error( 'spdb_native_reference_resolver_not_ready', 'The native-reference resolver is not operationally ready.' );
		}
		return true;
	}

	/** @return array{available:bool,readiness_available:bool,ready:bool,code:string} */
	private function resolver_snapshot(): array {
		if ( null === $this->resolver ) {
			return $this->resolver_state( false, false, false, 'resolver_absent' );
		}
		if ( null === $this->readiness ) {
			return $this->resolver_state( true, false, false, 'resolver_readiness_missing' );
		}

		try {
			$declared_ready = $this->readiness->is_ready();
			$source = $this->readiness->readiness_snapshot();
		} catch ( Throwable $throwable ) {
			return $this->resolver_state( true, true, false, 'resolver_readiness_exception' );
		}

		if ( ! $this->valid_readiness_snapshot( $source ) ) {
			return $this->resolver_state( true, true, false, 'resolver_readiness_invalid' );
		}
		if ( ! $source['available'] ) {
			return $this->resolver_state( true, true, false, 'resolver_unavailable' );
		}
		if ( true !== $declared_ready || ! $source['ready'] ) {
			return $this->resolver_state( true, true, false, 'resolver_not_ready' );
		}
		return $this->resolver_state( true, true, true, 'ready' );
	}

	private function valid_readiness_snapshot( $source ): bool {
		$required = array( 'available', 'ready', 'code' );
		if ( ! is_array( $source )
			|| array_diff( array_keys( $source ), $required )
			|| array_diff( $required, array_keys( $source ) )
			|| ! is_bool( $source['available'] ?? null )
			|| ! is_bool( $source['ready'] ?? null )
			|| ! is_string( $source['code'] ?? null )
			|| '' === $source['code']
			|| strlen( $source['code'] ) > self::MAX_CODE_LENGTH
			|| ! SPDB_Adapter_Registry::is_canonical_key( $source['code'] )
		) {
			return false;
		}
		return $source['available'] || ! $source['ready'];
	}

	/** @return array{available:bool,readiness_available:bool,ready:bool,code:string} */
	private function resolver_state( bool $available, bool $readiness_available, bool $ready, string $code ): array {
		return array(
			'available'           => $available,
			'readiness_available' => $readiness_available,
			'ready'               => $ready,
			'code'                => $code,
		);
	}

	private function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 503 ) );
	}
}
