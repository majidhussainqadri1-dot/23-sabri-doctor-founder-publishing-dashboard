<?php
/**
 * Federated provider adapter registry.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Adapter_Registry {
	public const MATURITY_UNAVAILABLE           = 'unavailable';
	public const MATURITY_DETECTED              = 'detected';
	public const MATURITY_INCOMPATIBLE          = 'incompatible';
	public const MATURITY_READ_ONLY             = 'read_only';
	public const MATURITY_WRITE_CAPABLE         = 'write_capable';
	public const MATURITY_REVIEW_CAPABLE        = 'review_capable';
	public const MATURITY_STAGING_ACCEPTED      = 'staging_accepted';
	public const MATURITY_PRODUCTION_ACCEPTED   = 'production_accepted';
	public const MATURITY_TEMPORARILY_SUSPENDED = 'temporarily_suspended';

	/** @var array<string,SPDB_Provider_Adapter> */
	private array $adapters = array();

	/**
	 * Register an adapter exactly once.
	 *
	 * @return true|WP_Error
	 */
	public function register( SPDB_Provider_Adapter $adapter ) {
		$key = sanitize_key( $adapter->get_provider_key() );

		if ( '' === $key ) {
			return new WP_Error( 'spdb_invalid_provider_key', __( 'The provider key is invalid.', 'sabri-publishing-dashboard' ) );
		}

		if ( isset( $this->adapters[ $key ] ) ) {
			return new WP_Error( 'spdb_duplicate_provider', __( 'The provider is already registered.', 'sabri-publishing-dashboard' ) );
		}

		if ( ! $this->supports_contract( $adapter->get_contract_version() ) ) {
			return new WP_Error( 'spdb_incompatible_contract', __( 'The provider adapter contract is incompatible.', 'sabri-publishing-dashboard' ) );
		}

		if ( ! in_array( $adapter->get_maturity_state(), self::maturity_states(), true ) ) {
			return new WP_Error( 'spdb_invalid_maturity', __( 'The provider maturity state is invalid.', 'sabri-publishing-dashboard' ) );
		}

		$this->adapters[ $key ] = $adapter;

		return true;
	}

	public function unregister( string $provider_key ): void {
		unset( $this->adapters[ sanitize_key( $provider_key ) ] );
	}

	public function has( string $provider_key ): bool {
		return isset( $this->adapters[ sanitize_key( $provider_key ) ] );
	}

	public function get( string $provider_key ): ?SPDB_Provider_Adapter {
		$key = sanitize_key( $provider_key );
		return $this->adapters[ $key ] ?? null;
	}

	/**
	 * @return array<string,SPDB_Provider_Adapter>
	 */
	public function all(): array {
		return $this->adapters;
	}

	public function can_write( string $provider_key, bool $is_production ): bool {
		$adapter = $this->get( $provider_key );

		if ( null === $adapter ) {
			return false;
		}

		$maturity = $adapter->get_maturity_state();

		if ( self::MATURITY_TEMPORARILY_SUSPENDED === $maturity ) {
			return false;
		}

		if ( $is_production ) {
			return self::MATURITY_PRODUCTION_ACCEPTED === $maturity;
		}

		return in_array(
			$maturity,
			array( self::MATURITY_STAGING_ACCEPTED, self::MATURITY_PRODUCTION_ACCEPTED ),
			true
		);
	}

	/**
	 * @return string[]
	 */
	public static function maturity_states(): array {
		return array(
			self::MATURITY_UNAVAILABLE,
			self::MATURITY_DETECTED,
			self::MATURITY_INCOMPATIBLE,
			self::MATURITY_READ_ONLY,
			self::MATURITY_WRITE_CAPABLE,
			self::MATURITY_REVIEW_CAPABLE,
			self::MATURITY_STAGING_ACCEPTED,
			self::MATURITY_PRODUCTION_ACCEPTED,
			self::MATURITY_TEMPORARILY_SUSPENDED,
		);
	}

	private function supports_contract( string $version ): bool {
		$expected_major = (int) explode( '.', SPDB_CONTRACT_VERSION )[0];
		$actual_major   = (int) explode( '.', $version )[0];

		return $expected_major === $actual_major;
	}
}
