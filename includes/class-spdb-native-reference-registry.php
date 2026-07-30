<?php
/** File 23-owned provider resolver registry and aggregate router. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Native_Reference_Registry implements SPDB_Native_Reference_Resolver {
	public const ACCEPTANCE_UNREVIEWED          = 'unreviewed';
	public const ACCEPTANCE_STAGING_ACCEPTED    = 'staging_accepted';
	public const ACCEPTANCE_PRODUCTION_ACCEPTED = 'production_accepted';
	public const ACCEPTANCE_REVOKED             = 'revoked';

	private SPDB_Adapter_Registry $adapters;
	/** @var array<string,SPDB_Native_Reference_Provider> */
	private array $providers = array();
	/** @var array<string,array<string,mixed>> */
	private array $metadata = array();
	/** @var array<string,string> */
	private array $acceptance = array();
	/** @var array<string,WP_Error[]> */
	private array $registration_errors = array();
	/** @var array<string,array<string,mixed>> */
	private array $health_cache = array();

	/** @param array<string,string> $acceptance File 23-owned acceptance map. */
	public function __construct( SPDB_Adapter_Registry $adapters, array $acceptance = array() ) {
		$this->adapters = $adapters;
		foreach ( $acceptance as $provider_key => $state ) {
			$key = (string) $provider_key;
			if ( SPDB_Adapter_Registry::is_canonical_key( $key ) && in_array( $state, self::acceptance_states(), true ) ) {
				$this->acceptance[ $key ] = $state;
			}
		}
	}

	/** @return true|WP_Error */
	public function register( SPDB_Native_Reference_Provider $provider ) {
		$key = 'unknown_provider';
		try {
			$key = $provider->get_provider_key();
			if ( ! SPDB_Adapter_Registry::is_canonical_key( $key ) || sanitize_key( $key ) !== $key ) {
				return $this->reject( $key, 'spdb_native_resolver_key_invalid', 'The native resolver provider key must already be canonical.' );
			}
			if ( isset( $this->providers[ $key ] ) ) {
				return $this->reject( $key, 'spdb_native_resolver_duplicate', 'A native resolver is already registered for this provider.' );
			}
			$adapter = $this->adapters->get( $key );
			$adapter_metadata = $this->adapters->metadata( $key );
			if ( null === $adapter || ! is_array( $adapter_metadata ) ) {
				return $this->reject( $key, 'spdb_native_resolver_adapter_missing', 'A native resolver requires an already registered provider adapter.' );
			}

			$provider_version = trim( $provider->get_provider_version() );
			$resolver_version = trim( $provider->get_resolver_version() );
			if ( ! $this->is_semver( $provider_version ) || $provider_version !== (string) $adapter_metadata['provider_version'] ) {
				return $this->reject( $key, 'spdb_native_resolver_provider_version_mismatch', 'The resolver provider version does not match the registered adapter.' );
			}
			if ( ! $this->is_semver( $resolver_version ) ) {
				return $this->reject( $key, 'spdb_native_resolver_version_invalid', 'The native resolver version is invalid.' );
			}

			$object_types = $this->validate_object_types( $provider->get_object_types(), (array) $adapter_metadata['object_types'] );
			if ( is_wp_error( $object_types ) ) {
				return $this->reject_error( $key, $object_types );
			}

			$this->providers[ $key ] = $provider;
			$this->metadata[ $key ] = array(
				'provider_key'      => $key,
				'provider_version'  => $provider_version,
				'resolver_version'  => $resolver_version,
				'object_types'      => $object_types,
				'acceptance_state'  => $this->acceptance_state( $key ),
			);
			unset( $this->health_cache[ $key ] );
			return true;
		} catch ( Throwable $throwable ) {
			return $this->reject( $key, 'spdb_native_resolver_registration_exception', 'The native resolver failed during registration.' );
		}
	}

	public function has( string $provider_key ): bool {
		return SPDB_Adapter_Registry::is_canonical_key( $provider_key ) && isset( $this->providers[ $provider_key ] );
	}

	public function is_ready(): bool {
		foreach ( array_keys( $this->providers ) as $provider_key ) {
			if ( $this->provider_is_ready( $provider_key ) ) { return true; }
		}
		return false;
	}

	/** @return array<string,mixed> */
	public function health_snapshot(): array {
		$providers = array();
		$ready_count = 0;
		foreach ( $this->metadata as $provider_key => $metadata ) {
			$health = $this->provider_health( $provider_key );
			$ready = $this->provider_is_ready( $provider_key, $health );
			if ( $ready ) { ++$ready_count; }
			$providers[] = array(
				'provider_key'     => $provider_key,
				'provider_version' => $metadata['provider_version'],
				'resolver_version' => $metadata['resolver_version'],
				'object_types'     => $metadata['object_types'],
				'acceptance_state' => $this->acceptance_state( $provider_key ),
				'technical_state'  => $this->technical_state( $provider_key ),
				'healthy'          => $health['healthy'],
				'ready'            => $ready,
				'code'             => $health['code'],
			);
		}
		$error_count = 0;
		foreach ( $this->registration_errors as $errors ) { $error_count += count( $errors ); }
		return array(
			'available'          => true,
			'resolver_count'     => count( $this->providers ),
			'ready_count'        => $ready_count,
			'registration_errors'=> $error_count,
			'ready'              => $ready_count > 0,
			'providers'          => $providers,
		);
	}

	/** @return array<string,WP_Error[]> */
	public function registration_errors(): array { return $this->registration_errors; }

	public function record_error( string $provider_key, WP_Error $error ): void {
		$key = SPDB_Adapter_Registry::is_canonical_key( $provider_key ) ? $provider_key : 'system';
		$this->registration_errors[ $key ][] = $error;
	}

	/** @return array<string,mixed>|WP_Error */
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) {
			return $this->error( 'spdb_native_resolver_reference_invalid', 'The native reference is invalid.' );
		}
		if ( ! $this->has( $provider_key ) ) {
			return $this->unavailable( 'spdb_native_resolver_provider_unavailable', 'No native resolver is registered for this provider.' );
		}
		$metadata = $this->metadata[ $provider_key ];
		if ( ! in_array( $object_type, $metadata['object_types'], true ) ) {
			return $this->error( 'spdb_native_resolver_object_type_unsupported', 'The native resolver does not support this object type.' );
		}
		if ( ! $this->provider_is_ready( $provider_key ) ) {
			return $this->unavailable( 'spdb_native_resolver_provider_not_ready', 'The native resolver is not accepted and healthy for this environment.' );
		}

		try {
			$result = $this->providers[ $provider_key ]->resolve_reference( $object_type, $object_id, $context );
		} catch ( Throwable $throwable ) {
			return $this->unavailable( 'spdb_native_resolver_provider_exception', 'The native resolver failed and was isolated.' );
		}
		if ( is_wp_error( $result ) ) { return $result; }
		if ( ! is_array( $result ) ) {
			return $this->unavailable( 'spdb_native_resolver_response_invalid', 'The native resolver returned an invalid response.' );
		}
		$exact = is_string( $result['provider_key'] ?? null ) && $provider_key === $result['provider_key']
			&& is_string( $result['object_type'] ?? null ) && $object_type === $result['object_type']
			&& is_string( $result['object_id'] ?? null ) && $object_id === $result['object_id'];
		return $exact ? $result : $this->unavailable( 'spdb_native_resolver_response_mismatch', 'The native resolver returned a mismatched reference.' );
	}

	/** @return string[] */
	public static function acceptance_states(): array {
		return array( self::ACCEPTANCE_UNREVIEWED, self::ACCEPTANCE_STAGING_ACCEPTED, self::ACCEPTANCE_PRODUCTION_ACCEPTED, self::ACCEPTANCE_REVOKED );
	}

	private function provider_is_ready( string $provider_key, ?array $health = null ): bool {
		if ( ! $this->acceptance_is_ready( $provider_key ) ) { return false; }
		if ( ! in_array( $this->technical_state( $provider_key ), array( SPDB_Adapter_Registry::CAPABILITY_READ_ONLY, SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE, SPDB_Adapter_Registry::CAPABILITY_REVIEW_CAPABLE ), true ) ) { return false; }
		$health = null === $health ? $this->provider_health( $provider_key ) : $health;
		return true === $health['healthy'];
	}

	private function acceptance_is_ready( string $provider_key ): bool {
		$state = $this->acceptance_state( $provider_key );
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		return 'production' === $environment
			? self::ACCEPTANCE_PRODUCTION_ACCEPTED === $state
			: in_array( $state, array( self::ACCEPTANCE_STAGING_ACCEPTED, self::ACCEPTANCE_PRODUCTION_ACCEPTED ), true );
	}

	private function acceptance_state( string $provider_key ): string { return $this->acceptance[ $provider_key ] ?? self::ACCEPTANCE_UNREVIEWED; }

	private function technical_state( string $provider_key ): string {
		$metadata = $this->adapters->metadata( $provider_key );
		return is_array( $metadata ) && is_string( $metadata['declared_capability'] ?? null ) ? $metadata['declared_capability'] : SPDB_Adapter_Registry::CAPABILITY_UNAVAILABLE;
	}

	/** @return array{healthy:bool,code:string} */
	private function provider_health( string $provider_key ): array {
		if ( isset( $this->health_cache[ $provider_key ] ) ) { return $this->health_cache[ $provider_key ]; }
		$health = array( 'healthy' => false, 'code' => 'unavailable' );
		try {
			$raw = $this->providers[ $provider_key ]->health_check();
			if ( is_array( $raw ) && is_bool( $raw['healthy'] ?? null ) && is_string( $raw['code'] ?? null ) && SPDB_Adapter_Registry::is_canonical_key( $raw['code'] ) ) {
				$health = array( 'healthy' => $raw['healthy'], 'code' => $raw['code'] );
			} else {
				$health = array( 'healthy' => false, 'code' => 'invalid_health' );
			}
		} catch ( Throwable $throwable ) {
			$health = array( 'healthy' => false, 'code' => 'health_exception' );
		}
		$this->health_cache[ $provider_key ] = $health;
		return $health;
	}

	/** @return string[]|WP_Error */
	private function validate_object_types( $raw, array $adapter_types ) {
		if ( ! is_array( $raw ) || array() === $raw || count( $raw ) > 64 ) {
			return $this->error( 'spdb_native_resolver_object_types_invalid', 'The native resolver object-type list is invalid.' );
		}
		$result = array();
		foreach ( $raw as $value ) {
			if ( ! is_string( $value ) || ! SPDB_Adapter_Registry::is_canonical_key( $value ) || sanitize_key( $value ) !== $value || ! in_array( $value, $adapter_types, true ) || in_array( $value, $result, true ) ) {
				return $this->error( 'spdb_native_resolver_object_type_invalid', 'A native resolver object type is invalid, duplicated, or undeclared by its adapter.' );
			}
			$result[] = $value;
		}
		return $result;
	}

	private function is_semver( string $version ): bool { return 1 === preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version ); }
	private function reject( string $key, string $code, string $message ): WP_Error { $error = $this->error( $code, $message ); $this->record_error( $key, $error ); return $error; }
	private function reject_error( string $key, WP_Error $error ): WP_Error { $this->record_error( $key, $error ); return $error; }
	private function unavailable( string $code, string $message ): WP_Error { return $this->error( $code, $message, 503 ); }
	private function error( string $code, string $message, int $status = 422 ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) ); }
}
