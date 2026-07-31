<?php
/** Bounded readiness projection for the File 23 native-reference registry. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Native_Reference_Registry_Readiness implements SPDB_Native_Reference_Readiness {
	private const MAX_PROVIDERS = 1000;
	private Closure $health_reader;

	/**
	 * @param SPDB_Native_Reference_Registry|Closure $source Registry or isolated test health reader.
	 */
	public function __construct( $source ) {
		if ( $source instanceof SPDB_Native_Reference_Registry ) {
			$this->health_reader = Closure::fromCallable( array( $source, 'health_snapshot' ) );
			return;
		}
		if ( $source instanceof Closure ) {
			$this->health_reader = $source;
			return;
		}
		throw new InvalidArgumentException( 'A native-reference registry or health reader is required.' );
	}

	public function is_ready(): bool {
		$snapshot = $this->readiness_snapshot();
		return true === $snapshot['ready'];
	}

	/** @return array{available:bool,ready:bool,code:string} */
	public function readiness_snapshot(): array {
		try {
			$source = ( $this->health_reader )();
		} catch ( Throwable $throwable ) {
			return $this->snapshot( false, false, 'registry_health_exception' );
		}

		$validated = $this->validate_health( $source );
		if ( null === $validated ) {
			return $this->snapshot( false, false, 'registry_health_invalid' );
		}
		if ( ! $validated['available'] ) {
			return $this->snapshot( false, false, 'registry_unavailable' );
		}
		if ( $validated['ready_count'] > 0 ) {
			return $this->snapshot( true, true, 'ready' );
		}
		if ( $validated['registration_errors'] > 0 ) {
			return $this->snapshot( true, false, 'registry_registration_errors' );
		}
		if ( 0 === $validated['resolver_count'] ) {
			return $this->snapshot( true, false, 'registry_empty' );
		}
		return $this->snapshot( true, false, 'no_ready_provider' );
	}

	/**
	 * @return array{available:bool,resolver_count:int,ready_count:int,registration_errors:int}|null
	 */
	private function validate_health( $source ): ?array {
		$required = array( 'available', 'resolver_count', 'ready_count', 'registration_errors', 'ready', 'providers' );
		if ( ! is_array( $source ) || array_diff( array_keys( $source ), $required ) || array_diff( $required, array_keys( $source ) ) ) {
			return null;
		}
		if ( ! is_bool( $source['available'] ) || ! is_bool( $source['ready'] ) || ! is_array( $source['providers'] ) || ! $this->is_list( $source['providers'] ) ) {
			return null;
		}

		$resolver_count = $this->bounded_count( $source['resolver_count'] );
		$ready_count = $this->bounded_count( $source['ready_count'] );
		$registration_errors = $this->bounded_count( $source['registration_errors'] );
		if ( null === $resolver_count || null === $ready_count || null === $registration_errors || $ready_count > $resolver_count || count( $source['providers'] ) !== $resolver_count ) {
			return null;
		}

		$projected_ready_count = 0;
		foreach ( $source['providers'] as $provider ) {
			if ( ! is_array( $provider ) || ! array_key_exists( 'ready', $provider ) || ! is_bool( $provider['ready'] ) ) {
				return null;
			}
			if ( true === $provider['ready'] ) { ++$projected_ready_count; }
		}
		$expected_ready = $ready_count > 0;
		if ( $projected_ready_count !== $ready_count || $source['ready'] !== $expected_ready ) {
			return null;
		}

		return array(
			'available' => $source['available'],
			'resolver_count' => $resolver_count,
			'ready_count' => $ready_count,
			'registration_errors' => $registration_errors,
		);
	}

	private function bounded_count( $value ): ?int {
		return is_int( $value ) && $value >= 0 && $value <= self::MAX_PROVIDERS ? $value : null;
	}

	private function is_list( array $value ): bool {
		$index = 0;
		foreach ( array_keys( $value ) as $key ) {
			if ( $key !== $index ) { return false; }
			++$index;
		}
		return true;
	}

	/** @return array{available:bool,ready:bool,code:string} */
	private function snapshot( bool $available, bool $ready, string $code ): array {
		return array( 'available' => $available, 'ready' => $ready, 'code' => $code );
	}
}
