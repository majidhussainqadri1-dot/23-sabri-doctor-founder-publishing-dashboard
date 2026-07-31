<?php
/** Bounded readiness projection for the File 23 native-reference registry. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Native_Reference_Registry_Readiness implements SPDB_Native_Reference_Readiness {
	private const MAX_PROVIDERS           = 1000;
	private const MAX_REGISTRATION_ERRORS = 1000;
	private const MAX_HEALTH_CODE_LENGTH  = 64;

	private Closure $health_reader;

	private function __construct( Closure $health_reader ) {
		$this->health_reader = $health_reader;
	}

	public static function from_registry( SPDB_Native_Reference_Registry $registry ): self {
		return new self( Closure::fromCallable( array( $registry, 'health_snapshot' ) ) );
	}

	/**
	 * Create a synthetic health source only inside the isolated executable-test runtime.
	 *
	 * @throws LogicException When called outside the explicit test runtime.
	 */
	public static function from_health_reader_for_tests( Closure $health_reader ): self {
		if ( ! defined( 'SPDB_TESTING' ) || true !== SPDB_TESTING ) {
			throw new LogicException( 'Synthetic registry health readers are available only in the isolated test runtime.' );
		}
		return new self( $health_reader );
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

		$resolver_count      = $this->bounded_count( $source['resolver_count'], self::MAX_PROVIDERS );
		$ready_count         = $this->bounded_count( $source['ready_count'], self::MAX_PROVIDERS );
		$registration_errors = $this->bounded_count( $source['registration_errors'], self::MAX_REGISTRATION_ERRORS );
		if ( null === $resolver_count || null === $ready_count || null === $registration_errors || $ready_count > $resolver_count || count( $source['providers'] ) !== $resolver_count ) {
			return null;
		}
		if ( ! $source['available'] && ( $source['ready'] || $ready_count > 0 ) ) {
			return null;
		}

		$projected_ready_count = 0;
		$provider_keys         = array();
		foreach ( $source['providers'] as $provider ) {
			if ( ! $this->valid_provider_health( $provider ) ) {
				return null;
			}
			$provider_key = $provider['provider_key'];
			if ( isset( $provider_keys[ $provider_key ] ) ) {
				return null;
			}
			$provider_keys[ $provider_key ] = true;
			if ( true === $provider['ready'] ) {
				++$projected_ready_count;
			}
		}

		$expected_ready = $ready_count > 0;
		if ( $projected_ready_count !== $ready_count || $source['ready'] !== $expected_ready ) {
			return null;
		}

		return array(
			'available'           => $source['available'],
			'resolver_count'      => $resolver_count,
			'ready_count'         => $ready_count,
			'registration_errors' => $registration_errors,
		);
	}

	private function valid_provider_health( $provider ): bool {
		if ( ! is_array( $provider )
			|| ! is_string( $provider['provider_key'] ?? null )
			|| ! SPDB_Adapter_Registry::is_canonical_key( $provider['provider_key'] )
			|| ! is_bool( $provider['healthy'] ?? null )
			|| ! is_bool( $provider['ready'] ?? null )
			|| ! is_string( $provider['code'] ?? null )
			|| '' === $provider['code']
			|| strlen( $provider['code'] ) > self::MAX_HEALTH_CODE_LENGTH
			|| ! SPDB_Adapter_Registry::is_canonical_key( $provider['code'] )
		) {
			return false;
		}
		return true !== $provider['ready'] || true === $provider['healthy'];
	}

	private function bounded_count( $value, int $maximum ): ?int {
		return is_int( $value ) && $value >= 0 && $value <= $maximum ? $value : null;
	}

	private function is_list( array $value ): bool {
		$index = 0;
		foreach ( array_keys( $value ) as $key ) {
			if ( $key !== $index ) {
				return false;
			}
			++$index;
		}
		return true;
	}

	/** @return array{available:bool,ready:bool,code:string} */
	private function snapshot( bool $available, bool $ready, string $code ): array {
		return array( 'available' => $available, 'ready' => $ready, 'code' => $code );
	}
}
