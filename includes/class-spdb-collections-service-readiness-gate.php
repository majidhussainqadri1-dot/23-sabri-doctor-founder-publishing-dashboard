<?php
/** Fail-closed write-readiness decision boundary for Collections service integration. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Service_Readiness_Gate {
	private const MAX_CODE_LENGTH = 64;

	private ?SPDB_Native_Reference_Resolver $resolver;
	private ?SPDB_Native_Reference_Readiness $readiness;
	private bool $evaluating = false;

	public function __construct( ?SPDB_Native_Reference_Resolver $resolver = null ) {
		$this->resolver  = $resolver;
		$this->readiness = $resolver instanceof SPDB_Native_Reference_Readiness ? $resolver : null;
	}

	/**
	 * Scalar authority inputs are intentionally validated at runtime instead of
	 * relying on weak PHP scalar coercion.
	 *
	 * @param mixed $writes_configured
	 * @param mixed $repository_ready
	 * @return array{
	 *   inputs_valid:bool,
	 *   gate_code:string,
	 *   resolver_available:bool,
	 *   resolver_readiness_available:bool,
	 *   resolver_ready:bool,
	 *   resolver_code:string,
	 *   collection_write_ready:bool,
	 *   knowledge_write_ready:bool,
	 *   any_write_ready:bool
	 * }
	 */
	public function snapshot( $writes_configured, $repository_ready ): array {
		if ( ! $this->valid_gate_inputs( $writes_configured, $repository_ready ) ) {
			return array(
				'inputs_valid'                 => false,
				'gate_code'                    => 'gate_input_invalid',
				'resolver_available'           => null !== $this->resolver,
				'resolver_readiness_available' => null !== $this->readiness,
				'resolver_ready'               => false,
				'resolver_code'                => 'resolver_not_evaluated',
				'collection_write_ready'       => false,
				'knowledge_write_ready'        => false,
				'any_write_ready'              => false,
			);
		}

		$collection_write_ready = $writes_configured && $repository_ready;
		$resolver               = $collection_write_ready
			? $this->resolver_snapshot()
			: $this->resolver_not_evaluated_state();
		$knowledge_write_ready  = $collection_write_ready && $resolver['ready'];
		$gate_code              = ! $writes_configured
			? 'writes_disabled'
			: ( ! $repository_ready
				? 'repository_not_ready'
				: ( $knowledge_write_ready ? 'knowledge_ready' : 'collection_ready' ) );

		return array(
			'inputs_valid'                 => true,
			'gate_code'                    => $gate_code,
			'resolver_available'           => $resolver['available'],
			'resolver_readiness_available' => $resolver['readiness_available'],
			'resolver_ready'               => $resolver['ready'],
			'resolver_code'                => $resolver['code'],
			'collection_write_ready'       => $collection_write_ready,
			'knowledge_write_ready'        => $knowledge_write_ready,
			'any_write_ready'              => $collection_write_ready || $knowledge_write_ready,
		);
	}

	/** @param mixed $writes_configured @param mixed $repository_ready @return true|WP_Error */
	public function require_collection_write_ready( $writes_configured, $repository_ready ) {
		if ( ! $this->valid_gate_inputs( $writes_configured, $repository_ready ) ) {
			return $this->error( 'spdb_collections_readiness_input_invalid', 'The collection readiness authority inputs are invalid.' );
		}
		if ( ! $writes_configured ) {
			return $this->error( 'spdb_collections_writes_disabled', 'Collection metadata writes remain disabled until reviewed staging acceptance.' );
		}
		if ( ! $repository_ready ) {
			return $this->error( 'spdb_collections_repository_not_ready', 'The collection metadata repository is not ready.' );
		}
		return true;
	}

	/** @param mixed $writes_configured @param mixed $repository_ready @return true|WP_Error */
	public function require_knowledge_write_ready( $writes_configured, $repository_ready ) {
		$collection = $this->require_collection_write_ready( $writes_configured, $repository_ready );
		if ( is_wp_error( $collection ) ) {
			return $collection;
		}

		$resolver = $this->resolver_snapshot();
		switch ( $resolver['code'] ) {
			case 'ready':
				return true;
			case 'resolver_absent':
				return $this->error( 'spdb_native_reference_resolver_unavailable', 'The native-reference resolver is unavailable.' );
			case 'resolver_readiness_missing':
				return $this->error( 'spdb_native_reference_readiness_missing', 'The native-reference resolver does not provide the required readiness contract.' );
			case 'resolver_unavailable':
				return $this->error( 'spdb_native_reference_resolver_unavailable', 'The native-reference resolver is unavailable.' );
			case 'resolver_not_ready':
				return $this->error( 'spdb_native_reference_resolver_not_ready', 'The native-reference resolver is not operationally ready.' );
			case 'resolver_readiness_invalid':
				return $this->error( 'spdb_native_reference_readiness_invalid', 'The native-reference readiness response is invalid.' );
			case 'resolver_readiness_exception':
				return $this->error( 'spdb_native_reference_readiness_failed', 'The native-reference readiness check failed and was isolated.' );
			case 'resolver_readiness_reentrant':
				return $this->error( 'spdb_native_reference_readiness_reentrant', 'The native-reference readiness check was re-entered and was denied.' );
			default:
				return $this->error( 'spdb_native_reference_resolver_not_ready', 'The native-reference resolver is not operationally ready.' );
		}
	}

	/** @return array{available:bool,readiness_available:bool,ready:bool,code:string} */
	private function resolver_snapshot(): array {
		if ( null === $this->resolver ) {
			return $this->resolver_state( false, false, false, 'resolver_absent' );
		}
		if ( null === $this->readiness ) {
			return $this->resolver_state( true, false, false, 'resolver_readiness_missing' );
		}
		if ( $this->evaluating ) {
			return $this->resolver_state( true, true, false, 'resolver_readiness_reentrant' );
		}

		$this->evaluating = true;
		try {
			$declared_ready = $this->readiness->is_ready();
			$source         = $this->readiness->readiness_snapshot();

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
		} catch ( Throwable $throwable ) {
			return $this->resolver_state( true, true, false, 'resolver_readiness_exception' );
		} finally {
			$this->evaluating = false;
		}
	}

	/** @return array{available:bool,readiness_available:bool,ready:bool,code:string} */
	private function resolver_not_evaluated_state(): array {
		return $this->resolver_state(
			null !== $this->resolver,
			null !== $this->readiness,
			false,
			'resolver_not_evaluated'
		);
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
		if ( ! $source['available'] && $source['ready'] ) {
			return false;
		}
		if ( $source['ready'] && 'ready' !== $source['code'] ) {
			return false;
		}
		if ( ! $source['ready'] && 'ready' === $source['code'] ) {
			return false;
		}
		return true;
	}

	private function valid_gate_inputs( $writes_configured, $repository_ready ): bool {
		return is_bool( $writes_configured ) && is_bool( $repository_ready );
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
