<?php
/**
 * Membership Core dependency and account-state guard.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Membership_Guard {
	/**
	 * Return whether the canonical File 00 contract is available.
	 */
	public static function is_available(): bool {
		return defined( 'SMC_VERSION' )
			&& function_exists( 'smc_user_status' )
			&& function_exists( 'smc_is_founder' )
			&& function_exists( 'smc_is_trusted_publisher' );
	}

	/**
	 * Return the current canonical Membership Core status.
	 */
	public static function user_status( int $user_id ): string {
		if ( $user_id < 1 || ! self::is_available() ) {
			return 'dependency_unavailable';
		}

		$status = (string) smc_user_status( $user_id );

		return '' !== $status ? sanitize_key( $status ) : 'unknown';
	}

	/**
	 * Approved and verified accounts may enter privileged dashboard workflows.
	 */
	public static function is_user_approved( int $user_id ): bool {
		return in_array( self::user_status( $user_id ), array( 'approved', 'verified' ), true );
	}

	/**
	 * Return whether the account is explicitly suspended.
	 */
	public static function is_user_suspended( int $user_id ): bool {
		return 'suspended' === self::user_status( $user_id );
	}

	/**
	 * Fail closed when File 00 is missing or the current account is not approved.
	 */
	public static function current_user_is_approved(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return self::is_user_approved( get_current_user_id() );
	}

	/**
	 * Non-sensitive dependency snapshot for diagnostics.
	 *
	 * @return array<string,mixed>
	 */
	public static function health_snapshot(): array {
		return array(
			'available' => self::is_available(),
			'version'   => defined( 'SMC_VERSION' ) ? (string) SMC_VERSION : null,
		);
	}
}
