<?php
/**
 * Membership Core dependency and account-state guard.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Membership_Guard {
	public const MINIMUM_VERSION          = '1.0.1';
	public const MAXIMUM_VERSION_EXCLUSIVE = '2.0.0';

	/**
	 * Return whether a Membership Core version satisfies this contract.
	 */
	public static function supports_version( string $version ): bool {
		return 1 === preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version )
			&& version_compare( $version, self::MINIMUM_VERSION, '>=' )
			&& version_compare( $version, self::MAXIMUM_VERSION_EXCLUSIVE, '<' );
	}

	/**
	 * Return whether the canonical File 00 contract is available and compatible.
	 */
	public static function is_available(): bool {
		return defined( 'SMC_VERSION' )
			&& self::supports_version( (string) SMC_VERSION )
			&& function_exists( 'smc_user_status' )
			&& function_exists( 'smc_is_founder' )
			&& function_exists( 'smc_is_trusted_publisher' );
	}

	/**
	 * Return all File 00 statuses accepted for a restricted, read-only workspace.
	 *
	 * Capability assignment still limits the user population; this list only
	 * prevents pending, rejected, expired-document, or suspended doctors from
	 * being incorrectly given write authority while retaining a status/appeal
	 * and owned-content read-only path required by the governing plan.
	 *
	 * @return string[]
	 */
	public static function restricted_view_statuses(): array {
		return array(
			'draft',
			'email_pending',
			'phone_pending',
			'2fa_pending',
			'documents_incomplete',
			'submitted',
			'under_review',
			'more_information',
			'resubmitted',
			'approved',
			'verified',
			'rejected',
			'suspended',
			'expired_document',
			'appeal_review',
		);
	}

	/**
	 * Return the current canonical Membership Core status.
	 */
	public static function user_status( int $user_id ): string {
		if ( $user_id < 1 || ! self::is_available() ) {
			return 'dependency_unavailable';
		}

		$status = (string) smc_user_status( $user_id );
		$status = sanitize_key( $status );

		return in_array( $status, self::restricted_view_statuses(), true ) ? $status : 'unknown';
	}

	/**
	 * Approved and verified accounts may enter privileged dashboard workflows.
	 */
	public static function is_user_approved( int $user_id ): bool {
		return in_array( self::user_status( $user_id ), array( 'approved', 'verified' ), true );
	}

	/**
	 * Return whether an authenticated File 00 member may see a restricted view.
	 */
	public static function can_user_view_restricted_dashboard( int $user_id ): bool {
		return in_array( self::user_status( $user_id ), self::restricted_view_statuses(), true );
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
		return is_user_logged_in() && self::is_user_approved( get_current_user_id() );
	}

	/**
	 * Return whether the current authenticated account may enter read-only views.
	 */
	public static function current_user_can_view_restricted_dashboard(): bool {
		return is_user_logged_in() && self::can_user_view_restricted_dashboard( get_current_user_id() );
	}

	/**
	 * Non-sensitive dependency snapshot for diagnostics.
	 *
	 * @return array<string,mixed>
	 */
	public static function health_snapshot(): array {
		$version = defined( 'SMC_VERSION' ) ? (string) SMC_VERSION : null;

		return array(
			'available'          => self::is_available(),
			'version'            => $version,
			'minimum_supported'  => self::MINIMUM_VERSION,
			'maximum_exclusive'  => self::MAXIMUM_VERSION_EXCLUSIVE,
			'version_compatible' => is_string( $version ) ? self::supports_version( $version ) : false,
		);
	}
}
