<?php
/**
 * Federated provider adapter registry.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Adapter_Registry {
	public const CAPABILITY_UNAVAILABLE           = 'unavailable';
	public const CAPABILITY_DETECTED              = 'detected';
	public const CAPABILITY_INCOMPATIBLE          = 'incompatible';
	public const CAPABILITY_READ_ONLY             = 'read_only';
	public const CAPABILITY_WRITE_CAPABLE         = 'write_capable';
	public const CAPABILITY_REVIEW_CAPABLE        = 'review_capable';
	public const CAPABILITY_TEMPORARILY_SUSPENDED = 'temporarily_suspended';

	public const ACCEPTANCE_UNREVIEWED          = 'unreviewed';
	public const ACCEPTANCE_STAGING_ACCEPTED    = 'staging_accepted';
	public const ACCEPTANCE_PRODUCTION_ACCEPTED = 'production_accepted';
	public const ACCEPTANCE_REVOKED             = 'revoked';

	/** @var array<string,SPDB_Provider_Adapter> */
	private array $adapters = array();

	/** @var array<string,array<string,mixed>> */
	private array $metadata = array();

	/** @var array<string,string> */
	private array $acceptance_states = array();

	/** @var array<string,array<string,mixed>> */
	private array $acceptance_records = array();

	/** @var array<string,WP_Error[]> */
	private array $registration_errors = array();

	/**
	 * Acceptance is injected by File 23-owned governance, never by providers.
	 *
	 * @param array<string,string> $acceptance_states Provider acceptance map.
	 */
	public function __construct( array $acceptance_states = array() ) {
		foreach ( $acceptance_states as $provider_key => $state ) {
			$provider_key = (string) $provider_key;
			if ( ! self::is_canonical_key( $provider_key ) ) {
				continue;
			}
			if ( is_string( $state ) && in_array( $state, self::acceptance_states(), true ) ) {
				// Explicit constructor injection is retained for executable contract tests.
				$this->acceptance_states[ $provider_key ] = $state;
				continue;
			}
			if ( is_array( $state ) ) {
				$this->acceptance_records[ $provider_key ] = $state;
			}
		}
	}

	/**
	 * Register and validate an adapter exactly once.
	 *
	 * @return true|WP_Error
	 */
	public function register( SPDB_Provider_Adapter $adapter ) {
		try {
			$raw_key = $adapter->get_provider_key();
			$key     = (string) $raw_key;

			if ( ! self::is_canonical_key( $key ) || sanitize_key( $key ) !== $key ) {
				return $this->reject( $key, 'spdb_invalid_provider_key', __( 'The provider key must already be canonical and may not be silently normalized.', 'sabri-publishing-dashboard' ) );
			}

			if ( isset( $this->adapters[ $key ] ) ) {
				return $this->reject( $key, 'spdb_duplicate_provider', __( 'The provider is already registered.', 'sabri-publishing-dashboard' ) );
			}

			$provider_name    = trim( $adapter->get_provider_name() );
			$provider_version = trim( $adapter->get_provider_version() );
			$minimum_contract = trim( $adapter->get_minimum_contract_version() );
			$maximum_contract = trim( $adapter->get_maximum_contract_version() );
			$capability_state = $adapter->get_declared_capability_state();
			$object_types     = $this->validate_key_list( $adapter->get_object_types(), 'object_type' );
			$privacy_classes  = $this->validate_key_list( $adapter->get_privacy_classifications(), 'privacy_classification' );
			$capabilities     = $this->validate_key_list( $adapter->get_supported_capabilities(), 'capability' );
			$operations       = $this->validate_operation_definitions( $adapter->get_operation_definitions() );

			if ( '' === $provider_name || strlen( $provider_name ) > 160 ) {
				return $this->reject( $key, 'spdb_invalid_provider_name', __( 'The provider name is invalid.', 'sabri-publishing-dashboard' ) );
			}

			if ( ! self::is_semver( $provider_version ) ) {
				return $this->reject( $key, 'spdb_invalid_provider_version', __( 'The provider version must be valid semantic versioning.', 'sabri-publishing-dashboard' ) );
			}

			if ( ! $this->supports_contract_range( $minimum_contract, $maximum_contract ) ) {
				return $this->reject( $key, 'spdb_incompatible_contract', __( 'The provider adapter contract range is incompatible.', 'sabri-publishing-dashboard' ) );
			}

			if ( ! in_array( $capability_state, self::capability_states(), true ) ) {
				return $this->reject( $key, 'spdb_invalid_capability_state', __( 'The provider technical capability state is invalid.', 'sabri-publishing-dashboard' ) );
			}

			if ( is_wp_error( $object_types ) ) {
				return $this->reject_error( $key, $object_types );
			}

			if ( is_wp_error( $privacy_classes ) ) {
				return $this->reject_error( $key, $privacy_classes );
			}

			if ( is_wp_error( $capabilities ) ) {
				return $this->reject_error( $key, $capabilities );
			}

			if ( is_wp_error( $operations ) ) {
				return $this->reject_error( $key, $operations );
			}

			if ( ! class_exists( 'SPDB_Capabilities' ) ) {
				return $this->reject( $key, 'spdb_capability_contract_unavailable', __( 'The File 23 capability contract is unavailable.', 'sabri-publishing-dashboard' ) );
			}

			foreach ( $operations as $definition ) {
				$required_capability = $definition['required_capability'];
				if ( ! in_array( $required_capability, $capabilities, true ) || ! in_array( $required_capability, SPDB_Capabilities::all(), true ) ) {
					return $this->reject( $key, 'spdb_undeclared_operation_capability', __( 'An operation references an undeclared or non-canonical File 23 capability.', 'sabri-publishing-dashboard' ) );
				}
			}

			$this->adapters[ $key ] = $adapter;
			$this->bind_acceptance_record( $key, $provider_version );
			$this->metadata[ $key ] = array(
				'provider_key'           => $key,
				'provider_name'          => $provider_name,
				'provider_version'       => $provider_version,
				'minimum_contract'       => $minimum_contract,
				'maximum_contract'       => $maximum_contract,
				'declared_capability'    => $capability_state,
				'acceptance_state'       => $this->get_acceptance_state( $key ),
				'object_types'           => $object_types,
				'privacy_classes'        => $privacy_classes,
				'supported_capabilities' => $capabilities,
				'operation_definitions'  => $operations,
			);

			return true;
		} catch ( Throwable $throwable ) {
			$key = isset( $key ) && self::is_canonical_key( $key ) ? $key : 'unknown_provider';
			return $this->reject( $key, 'spdb_adapter_registration_exception', __( 'The provider adapter failed during registration.', 'sabri-publishing-dashboard' ) );
		}
	}

	public function unregister( string $provider_key ): void {
		if ( ! self::is_canonical_key( $provider_key ) ) {
			return;
		}

		unset( $this->adapters[ $provider_key ], $this->metadata[ $provider_key ] );
	}

	public function has( string $provider_key ): bool {
		return self::is_canonical_key( $provider_key ) && isset( $this->adapters[ $provider_key ] );
	}

	public function get( string $provider_key ): ?SPDB_Provider_Adapter {
		return $this->has( $provider_key ) ? $this->adapters[ $provider_key ] : null;
	}

	/**
	 * @return array<string,SPDB_Provider_Adapter>
	 */
	public function all(): array {
		return $this->adapters;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function metadata( string $provider_key ): ?array {
		return $this->has( $provider_key ) ? $this->metadata[ $provider_key ] : null;
	}

	/**
	 * @return array<string,WP_Error[]>
	 */
	public function registration_errors(): array {
		return $this->registration_errors;
	}

	/**
	 * This is an environment gate only, not complete action authorization.
	 *
	 * The environment is resolved server-side and cannot be supplied by a
	 * request, route, controller, or adapter.
	 */
	public function is_environment_write_eligible( string $provider_key ): bool {
		$effective_state = $this->get_effective_state( $provider_key );
		$environment     = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		if ( 'production' === $environment ) {
			return self::ACCEPTANCE_PRODUCTION_ACCEPTED === $effective_state;
		}

		return in_array(
			$effective_state,
			array( self::ACCEPTANCE_STAGING_ACCEPTED, self::ACCEPTANCE_PRODUCTION_ACCEPTED ),
			true
		);
	}

	public function get_effective_state( string $provider_key ): string {
		$metadata = $this->metadata( $provider_key );
		if ( null === $metadata ) {
			return self::CAPABILITY_UNAVAILABLE;
		}

		$declared = $metadata['declared_capability'];
		if ( in_array( $declared, array( self::CAPABILITY_UNAVAILABLE, self::CAPABILITY_INCOMPATIBLE, self::CAPABILITY_READ_ONLY, self::CAPABILITY_DETECTED, self::CAPABILITY_TEMPORARILY_SUSPENDED ), true ) ) {
			return $declared;
		}

		$acceptance = $this->get_acceptance_state( $provider_key );
		if ( self::ACCEPTANCE_REVOKED === $acceptance ) {
			return self::ACCEPTANCE_REVOKED;
		}

		if ( in_array( $acceptance, array( self::ACCEPTANCE_STAGING_ACCEPTED, self::ACCEPTANCE_PRODUCTION_ACCEPTED ), true ) ) {
			return $acceptance;
		}

		return $declared;
	}

	public function get_acceptance_state( string $provider_key ): string {
		return $this->acceptance_states[ $provider_key ] ?? self::ACCEPTANCE_UNREVIEWED;
	}

	private function bind_acceptance_record( string $provider_key, string $provider_version ): void {
		$record = $this->acceptance_records[ $provider_key ] ?? null;
		if ( ! is_array( $record ) ) {
			return;
		}
		$state = sanitize_key( (string) ( $record['state'] ?? '' ) );
		if ( self::ACCEPTANCE_REVOKED === $state ) {
			// Revocation remains fail-closed across provider/plugin upgrades until explicitly replaced.
			$this->acceptance_states[ $provider_key ] = $state;
			return;
		}
		if (
			! in_array( $state, array( self::ACCEPTANCE_STAGING_ACCEPTED, self::ACCEPTANCE_PRODUCTION_ACCEPTED ), true )
			|| ! hash_equals( $provider_version, (string) ( $record['provider_version'] ?? '' ) )
			|| ! hash_equals( SPDB_CONTRACT_VERSION, (string) ( $record['contract_version'] ?? '' ) )
			|| ! hash_equals( SPDB_VERSION, (string) ( $record['plugin_version'] ?? '' ) )
			|| 1 !== preg_match( '/\A[A-Za-z0-9][A-Za-z0-9._:\/-]{2,190}\z/', (string) ( $record['evidence_id'] ?? '' ) )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $record['evidence_hash'] ?? '' ) )
		) {
			return;
		}
		$record_environment = sanitize_key( (string) ( $record['environment'] ?? '' ) );
		if ( self::ACCEPTANCE_STAGING_ACCEPTED === $state && 'production' === $record_environment ) {
			return;
		}
		$this->acceptance_states[ $provider_key ] = $state;
	}

	/**
	 * Record a bounded non-sensitive registry error.
	 */
	public function record_error( string $provider_key, WP_Error $error ): void {
		$key = self::is_canonical_key( $provider_key ) ? $provider_key : 'system';
		$this->registration_errors[ $key ][] = $error;
	}

	/**
	 * @return string[]
	 */
	public static function capability_states(): array {
		return array(
			self::CAPABILITY_UNAVAILABLE,
			self::CAPABILITY_DETECTED,
			self::CAPABILITY_INCOMPATIBLE,
			self::CAPABILITY_READ_ONLY,
			self::CAPABILITY_WRITE_CAPABLE,
			self::CAPABILITY_REVIEW_CAPABLE,
			self::CAPABILITY_TEMPORARILY_SUSPENDED,
		);
	}

	/**
	 * @return string[]
	 */
	public static function acceptance_states(): array {
		return array(
			self::ACCEPTANCE_UNREVIEWED,
			self::ACCEPTANCE_STAGING_ACCEPTED,
			self::ACCEPTANCE_PRODUCTION_ACCEPTED,
			self::ACCEPTANCE_REVOKED,
		);
	}

	public static function is_canonical_key( string $key ): bool {
		return 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{1,63}$/', $key );
	}

	private static function is_semver( string $version ): bool {
		return 1 === preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version );
	}

	private function supports_contract_range( string $minimum, string $maximum ): bool {
		if ( ! self::is_semver( $minimum ) || ! self::is_semver( $maximum ) ) {
			return false;
		}

		if ( version_compare( $minimum, $maximum, '>' ) ) {
			return false;
		}

		return version_compare( SPDB_CONTRACT_VERSION, $minimum, '>=' )
			&& version_compare( SPDB_CONTRACT_VERSION, $maximum, '<=' );
	}

	/**
	 * @param mixed  $values Values to validate.
	 * @param string $label  Error label.
	 * @return string[]|WP_Error
	 */
	private function validate_key_list( $values, string $label ) {
		if ( ! is_array( $values ) || array() === $values ) {
			return new WP_Error( 'spdb_invalid_' . $label . '_list', sprintf( __( 'The %s list is missing or invalid.', 'sabri-publishing-dashboard' ), $label ) );
		}

		$validated = array();
		foreach ( $values as $value ) {
			$value = (string) $value;
			if ( ! self::is_canonical_key( $value ) || sanitize_key( $value ) !== $value ) {
				return new WP_Error( 'spdb_invalid_' . $label, sprintf( __( 'A %s key is not canonical.', 'sabri-publishing-dashboard' ), $label ) );
			}
			$validated[] = $value;
		}

		return array_values( array_unique( $validated ) );
	}

	/**
	 * @param mixed $definitions Operation definitions.
	 * @return array<string,array<string,mixed>>|WP_Error
	 */
	private function validate_operation_definitions( $definitions ) {
		if ( ! is_array( $definitions ) ) {
			return new WP_Error( 'spdb_invalid_operation_definitions', __( 'Operation definitions must be an array.', 'sabri-publishing-dashboard' ) );
		}

		$required = array(
			'required_capability',
			'requires_ownership',
			'requires_verified_account',
			'requires_state_guard',
			'requires_object_version',
			'requires_idempotency_key',
			'requires_audit_reason',
			'payload_schema',
			'rate_limit',
			'success_schema',
			'error_schema',
		);

		$validated = array();
		foreach ( $definitions as $operation_key => $definition ) {
			$operation_key = (string) $operation_key;
			if ( ! self::is_canonical_key( $operation_key ) || ! is_array( $definition ) ) {
				return new WP_Error( 'spdb_invalid_operation', __( 'An operation key or definition is invalid.', 'sabri-publishing-dashboard' ) );
			}

			if ( array_diff( $required, array_keys( $definition ) ) ) {
				return new WP_Error( 'spdb_incomplete_operation', __( 'An operation definition is incomplete.', 'sabri-publishing-dashboard' ) );
			}

			if ( ! self::is_canonical_key( (string) $definition['required_capability'] ) ) {
				return new WP_Error( 'spdb_invalid_operation_capability', __( 'An operation capability is invalid.', 'sabri-publishing-dashboard' ) );
			}

			foreach ( array( 'requires_ownership', 'requires_verified_account', 'requires_state_guard', 'requires_object_version', 'requires_idempotency_key', 'requires_audit_reason' ) as $boolean_key ) {
				if ( ! is_bool( $definition[ $boolean_key ] ) ) {
					return new WP_Error( 'spdb_invalid_operation_flag', __( 'An operation requirement flag is invalid.', 'sabri-publishing-dashboard' ) );
				}
			}

			foreach ( array( 'payload_schema', 'rate_limit', 'success_schema', 'error_schema' ) as $schema_key ) {
				if ( ! is_array( $definition[ $schema_key ] ) ) {
					return new WP_Error( 'spdb_invalid_operation_schema', __( 'An operation schema is invalid.', 'sabri-publishing-dashboard' ) );
				}
			}

			$validated[ $operation_key ] = $definition;
		}

		return $validated;
	}

	private function reject( string $provider_key, string $code, string $message ): WP_Error {
		$error = new WP_Error( $code, $message );
		$this->record_error( $provider_key, $error );
		return $error;
	}

	private function reject_error( string $provider_key, WP_Error $error ): WP_Error {
		$this->record_error( $provider_key, $error );
		return $error;
	}
}
