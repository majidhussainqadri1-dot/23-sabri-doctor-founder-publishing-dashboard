<?php
/** Fail-closed readiness boundary for aggregate native-reference resolution. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Native_Reference_Readiness_Bridge implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	private SPDB_Native_Reference_Resolver $resolver;
	private SPDB_Native_Reference_Readiness $readiness;

	public function __construct( SPDB_Native_Reference_Resolver $resolver, SPDB_Native_Reference_Readiness $readiness ) {
		$this->resolver  = $resolver;
		$this->readiness = $readiness;
	}

	public function is_ready(): bool {
		$snapshot = $this->probe();
		return true === $snapshot['ready'];
	}

	/** @return array{available:bool,ready:bool,code:string} */
	public function readiness_snapshot(): array {
		return $this->probe();
	}

	/** @return array<string,mixed>|WP_Error */
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		$snapshot = $this->probe();
		if ( true !== $snapshot['ready'] ) {
			return new WP_Error(
				'spdb_native_reference_resolver_not_ready',
				__( 'The native-reference resolver is not operationally ready.', 'sabri-publishing-dashboard' ),
				array( 'status' => 503 )
			);
		}

		try {
			return $this->resolver->resolve_reference( $provider_key, $object_type, $object_id, $context );
		} catch ( Throwable $throwable ) {
			return new WP_Error(
				'spdb_native_reference_resolver_failed',
				__( 'The native-reference resolver failed and was isolated.', 'sabri-publishing-dashboard' ),
				array( 'status' => 503 )
			);
		}
	}

	/** @return array{available:bool,ready:bool,code:string} */
	private function probe(): array {
		try {
			$declared_ready = $this->readiness->is_ready();
			$source = $this->readiness->readiness_snapshot();
		} catch ( Throwable $throwable ) {
			return array( 'available' => false, 'ready' => false, 'code' => 'readiness_exception' );
		}

		$available = true === ( $source['available'] ?? false );
		$snapshot_ready = true === ( $source['ready'] ?? false );
		$shape_valid = 3 === count( $source )
			&& array_keys( $source ) === array( 'available', 'ready', 'code' )
			&& is_bool( $source['available'] ?? null )
			&& is_bool( $source['ready'] ?? null )
			&& is_string( $source['code'] ?? null );

		if ( ! $shape_valid ) {
			return array( 'available' => false, 'ready' => false, 'code' => 'readiness_invalid' );
		}
		if ( ! $available ) {
			return array( 'available' => false, 'ready' => false, 'code' => 'resolver_unavailable' );
		}
		if ( true !== $declared_ready || ! $snapshot_ready ) {
			return array( 'available' => true, 'ready' => false, 'code' => 'resolver_not_ready' );
		}

		return array( 'available' => true, 'ready' => true, 'code' => 'ready' );
	}
}
