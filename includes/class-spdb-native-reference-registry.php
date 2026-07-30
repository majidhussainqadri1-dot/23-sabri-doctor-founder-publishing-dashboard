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
	/** @var array<string,array{healthy:bool,code:string}> */
	private array $health_cache = array();

	/** @param array<string,string> $acceptance File 23-owned acceptance map. */
	public function __construct( SPDB_Adapter_Registry $adapters, array $acceptance = array() ) {
		$this->adapters = $adapters;
		foreach ( $acceptance as $provider_key => $state ) {
			if ( ! is_string( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! is_string( $state ) || ! in_array( $state, self::acceptance_states(), true ) ) {
				$this->record_error( 'system', new WP_Error( 'spdb_native_resolver_acceptance_invalid', __( 'A native resolver governance acceptance entry is invalid.', 'sabri-publishing-dashboard' ) ) );
				continue;
			}
			$this->acceptance[ $provider_key ] = $state;
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
			if ( is_wp_error( $object_types ) ) { return $this->reject_error( $key, $object_types ); }

			$this->providers[ $key ] = $provider;
			$this->metadata[ $key ] = array(
				'provider_key'     => $key,
				'provider_version' => $provider_version,
				'resolver_version' => $resolver_version,
				'object_types'     => $object_types,
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
			$contract_current = $this->provider_contract_is_current( $provider_key );
			$health = $contract_current ? $this->provider_health( $provider_key ) : array( 'healthy' => false, 'code' => 'contract_drift' );
			$ready = $this->provider_is_ready( $provider_key, $health, $contract_current );
			if ( $ready ) { ++$ready_count; }
			$providers[] = array(
				'provider_key'             => $provider_key,
				'provider_version'         => $metadata['provider_version'],
				'resolver_version'         => $metadata['resolver_version'],
				'object_types'             => $metadata['object_types'],
				'acceptance_state'         => $this->acceptance_state( $provider_key ),
				'adapter_acceptance_state' => $this->adapters->get_acceptance_state( $provider_key ),
				'technical_state'          => $this->technical_state( $provider_key ),
				'healthy'                  => $health['healthy'],
				'ready'                    => $ready,
				'code'                     => $health['code'],
			);
		}
		$error_count = 0;
		foreach ( $this->registration_errors as $errors ) { $error_count += count( $errors ); }
		return array(
			'available'           => true,
			'resolver_count'      => count( $this->providers ),
			'ready_count'         => $ready_count,
			'registration_errors' => $error_count,
			'ready'               => $ready_count > 0,
			'providers'           => $providers,
		);
	}

	/** @return array<string,WP_Error[]> */
	public function registration_errors(): array { return $this->registration_errors; }

	/** Store only a bounded generic registration error; never retain provider text or data. */
	public function record_error( string $provider_key, WP_Error $error ): void {
		$key = SPDB_Adapter_Registry::is_canonical_key( $provider_key ) ? $provider_key : 'system';
		$code = $error->get_error_code();
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $code ) ) { $code = 'spdb_native_resolver_registration_error'; }
		$this->registration_errors[ $key ][] = new WP_Error( $code, __( 'A native resolver registration error was recorded.', 'sabri-publishing-dashboard' ) );
	}

	/** @return array<string,mixed>|WP_Error */
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) {
			return $this->error( 'spdb_native_resolver_reference_invalid', 'The native reference is invalid.' );
		}
		$server_context = $this->validated_context( $context );
		if ( is_wp_error( $server_context ) ) { return $server_context; }
		if ( ! $this->has( $provider_key ) ) {
			return $this->unavailable( 'spdb_native_resolver_provider_unavailable', 'No native resolver is registered for this provider.' );
		}
		$metadata = $this->metadata[ $provider_key ];
		if ( ! in_array( $object_type, $metadata['object_types'], true ) ) {
			return $this->error( 'spdb_native_resolver_object_type_unsupported', 'The native resolver does not support this object type.' );
		}
		if ( ! $this->provider_is_ready( $provider_key ) || ! $this->provider_contract_is_current( $provider_key ) ) {
			return $this->unavailable( 'spdb_native_resolver_provider_not_ready', 'The native resolver is not accepted, healthy, and contract-current for this environment.' );
		}

		try {
			$result = $this->providers[ $provider_key ]->resolve_reference( $object_type, $object_id, $server_context );
		} catch ( Throwable $throwable ) {
			return $this->unavailable( 'spdb_native_resolver_provider_exception', 'The native resolver failed and was isolated.' );
		}
		if ( is_wp_error( $result ) ) {
			return $this->unavailable( 'spdb_native_resolver_provider_error', 'The native resolver could not resolve the current reference.' );
		}
		if ( ! is_array( $result ) ) {
			return $this->unavailable( 'spdb_native_resolver_response_invalid', 'The native resolver returned an invalid response.' );
		}

		$scope = $server_context['scope'];
		$exact = is_string( $result['provider_key'] ?? null ) && $provider_key === $result['provider_key']
			&& is_string( $result['object_type'] ?? null ) && $object_type === $result['object_type']
			&& is_string( $result['object_id'] ?? null ) && $object_id === $result['object_id']
			&& is_string( $result['scope'] ?? null ) && $scope === $result['scope'];
		if ( ! $exact ) { return $this->unavailable( 'spdb_native_resolver_response_mismatch', 'The native resolver returned a mismatched reference.' ); }
		if ( ! is_bool( $result['exists'] ?? null ) || ! is_bool( $result['visible'] ?? null ) || ! is_bool( $result['reference_allowed'] ?? null ) ) {
			return $this->unavailable( 'spdb_native_resolver_response_invalid', 'The native resolver returned invalid authorization flags.' );
		}
		$owner = $this->nonnegative_integer( $result['owner_user_id'] ?? null );
		$native_version = $this->native_version( $result['native_version'] ?? null );
		if ( null === $owner || null === $native_version ) {
			return $this->unavailable( 'spdb_native_resolver_response_invalid', 'The native resolver returned invalid owner or version metadata.' );
		}
		$destination = '';
		if ( array_key_exists( 'destination', $result ) && '' !== $result['destination'] ) {
			if ( ! is_string( $result['destination'] ) ) { return $this->unavailable( 'spdb_native_resolver_destination_invalid', 'The native resolver returned an invalid destination.' ); }
			$normalized = SPDB_Safe_Destination::normalize( $result['destination'] );
			if ( is_wp_error( $normalized ) ) { return $this->unavailable( 'spdb_native_resolver_destination_invalid', 'The native resolver returned an unsafe destination.' ); }
			$destination = $normalized;
		}
		return array(
			'provider_key'       => $provider_key,
			'object_type'        => $object_type,
			'object_id'          => $object_id,
			'exists'             => $result['exists'],
			'visible'            => $result['visible'],
			'reference_allowed'  => $result['reference_allowed'],
			'owner_user_id'      => $owner,
			'native_version'     => $native_version,
			'scope'              => $scope,
			'destination'        => $destination,
		);
	}

	/** @return string[] */
	public static function acceptance_states(): array {
		return array( self::ACCEPTANCE_UNREVIEWED, self::ACCEPTANCE_STAGING_ACCEPTED, self::ACCEPTANCE_PRODUCTION_ACCEPTED, self::ACCEPTANCE_REVOKED );
	}

	private function provider_is_ready( string $provider_key, ?array $health = null, ?bool $contract_current = null ): bool {
		$contract_current = null === $contract_current ? $this->provider_contract_is_current( $provider_key ) : $contract_current;
		if ( ! $contract_current || ! $this->acceptance_is_ready( $provider_key ) || ! $this->adapter_acceptance_is_ready( $provider_key ) ) { return false; }
		if ( ! in_array( $this->technical_state( $provider_key ), array( SPDB_Adapter_Registry::CAPABILITY_READ_ONLY, SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE, SPDB_Adapter_Registry::CAPABILITY_REVIEW_CAPABLE ), true ) ) { return false; }
		$health = null === $health ? $this->provider_health( $provider_key ) : $health;
		return true === $health['healthy'] && $this->provider_contract_is_current( $provider_key );
	}

	private function provider_contract_is_current( string $provider_key ): bool {
		if ( ! isset( $this->providers[ $provider_key ], $this->metadata[ $provider_key ] ) ) { return false; }
		$metadata = $this->metadata[ $provider_key ];
		try {
			$object_types = $this->providers[ $provider_key ]->get_object_types();
			return is_array( $object_types )
				&& $provider_key === $this->providers[ $provider_key ]->get_provider_key()
				&& $metadata['provider_version'] === $this->providers[ $provider_key ]->get_provider_version()
				&& $metadata['resolver_version'] === $this->providers[ $provider_key ]->get_resolver_version()
				&& $metadata['object_types'] === array_values( $object_types );
		} catch ( Throwable $throwable ) {
			return false;
		}
	}

	private function acceptance_is_ready( string $provider_key ): bool { return $this->state_is_ready( $this->acceptance_state( $provider_key ) ); }
	private function adapter_acceptance_is_ready( string $provider_key ): bool { return $this->state_is_ready( $this->adapters->get_acceptance_state( $provider_key ) ); }
	private function state_is_ready( string $state ): bool {
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		return 'production' === $environment ? self::ACCEPTANCE_PRODUCTION_ACCEPTED === $state : in_array( $state, array( self::ACCEPTANCE_STAGING_ACCEPTED, self::ACCEPTANCE_PRODUCTION_ACCEPTED ), true );
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
			} else { $health = array( 'healthy' => false, 'code' => 'invalid_health' ); }
		} catch ( Throwable $throwable ) { $health = array( 'healthy' => false, 'code' => 'health_exception' ); }
		$this->health_cache[ $provider_key ] = $health;
		return $health;
	}

	/** @return array<string,mixed>|WP_Error */
	private function validated_context( array $context ) {
		$required = array( 'user_id', 'scope', 'is_founder', 'environment', 'generated_at' );
		if ( array_diff( array_keys( $context ), $required ) || array_diff( $required, array_keys( $context ) ) ) {
			return $this->error( 'spdb_native_resolver_context_invalid', 'The native resolver context shape is invalid.', 403 );
		}
		$user_id = get_current_user_id();
		$scope = is_string( $context['scope'] ) ? $context['scope'] : '';
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$is_founder = $user_id > 0 && SPDB_Membership_Guard::is_user_approved( $user_id ) && function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
		if ( ! is_int( $context['user_id'] ) || $context['user_id'] !== $user_id || ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) || ! is_bool( $context['is_founder'] ) || $context['is_founder'] !== $is_founder || ! is_string( $context['environment'] ) || $context['environment'] !== $environment || ! is_string( $context['generated_at'] ) || strlen( $context['generated_at'] ) > 40 ) {
			return $this->error( 'spdb_native_resolver_context_invalid', 'The native resolver context does not match current server authority.', 403 );
		}
		if ( ! SPDB_Membership_Guard::is_user_approved( $user_id ) ) { return $this->error( 'spdb_native_resolver_context_forbidden', 'An approved current account is required.', 403 ); }
		if ( 'own' === $scope && ! SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ) ) { return $this->error( 'spdb_native_resolver_context_forbidden', 'Own-scope reference authority is required.', 403 ); }
		if ( 'institution' === $scope && ( ! $is_founder || ! SPDB_Capabilities::current_user_can( 'spdb_manage_campaigns' ) ) ) { return $this->error( 'spdb_native_resolver_context_forbidden', 'Institution reference authority is required.', 403 ); }
		return array( 'user_id' => $user_id, 'scope' => $scope, 'is_founder' => $is_founder, 'environment' => $environment, 'generated_at' => gmdate( 'c' ) );
	}

	/** @return string[]|WP_Error */
	private function validate_object_types( $raw, array $adapter_types ) {
		if ( ! is_array( $raw ) || array() === $raw || count( $raw ) > 64 ) { return $this->error( 'spdb_native_resolver_object_types_invalid', 'The native resolver object-type list is invalid.' ); }
		$result = array();
		foreach ( $raw as $value ) {
			if ( ! is_string( $value ) || ! SPDB_Adapter_Registry::is_canonical_key( $value ) || sanitize_key( $value ) !== $value || ! in_array( $value, $adapter_types, true ) || in_array( $value, $result, true ) ) {
				return $this->error( 'spdb_native_resolver_object_type_invalid', 'A native resolver object type is invalid, duplicated, or undeclared by its adapter.' );
			}
			$result[] = $value;
		}
		return $result;
	}

	private function native_version( $raw ): ?string {
		if ( ! is_string( $raw ) || 1 !== preg_match( '//u', $raw ) ) { return null; }
		$value = trim( $raw );
		return $raw === $value && '' !== $value && strlen( $value ) <= 191 && ! preg_match( '/[\x00-\x1F\x7F]/u', $value ) ? $value : null;
	}
	private function nonnegative_integer( $raw ): ?int {
		if ( is_int( $raw ) ) { return $raw >= 0 ? $raw : null; }
		if ( is_string( $raw ) && 1 === preg_match( '/^(?:0|[1-9]\d*)$/', $raw ) ) { $value = (int) $raw; return (string) $value === $raw ? $value : null; }
		return null;
	}
	private function is_semver( string $version ): bool { return 1 === preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version ); }
	private function reject( string $key, string $code, string $message ): WP_Error { $error = $this->error( $code, $message ); $this->record_error( $key, $error ); return $error; }
	private function reject_error( string $key, WP_Error $error ): WP_Error { $this->record_error( $key, $error ); return $error; }
	private function unavailable( string $code, string $message ): WP_Error { return $this->error( $code, $message, 503 ); }
	private function error( string $code, string $message, int $status = 422 ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) ); }
}
