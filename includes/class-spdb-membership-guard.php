<?php
/**
 * Membership Core dependency and account-state guard.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Membership_Guard {
	public const MINIMUM_VERSION           = '1.0.1';
	public const MAXIMUM_VERSION_EXCLUSIVE = '2.0.0';
	public const MINIMUM_CONTRACT_VERSION  = '1.1.2';
	public const MAXIMUM_CONTRACT_EXCLUSIVE = '2.0.0';

	public static function supports_version( string $version ): bool {
		return 1 === preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version )
			&& version_compare( $version, self::MINIMUM_VERSION, '>=' )
			&& version_compare( $version, self::MAXIMUM_VERSION_EXCLUSIVE, '<' );
	}

	public static function supports_contract_version( string $version ): bool {
		return 1 === preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version )
			&& version_compare( $version, self::MINIMUM_CONTRACT_VERSION, '>=' )
			&& version_compare( $version, self::MAXIMUM_CONTRACT_EXCLUSIVE, '<' );
	}

	/** Canonical File 00 assertions are preferred; legacy helpers remain a bounded compatibility path. */
	public static function has_canonical_assertions(): bool {
		return class_exists( 'SMC_Contracts' )
			&& is_callable( array( 'SMC_Contracts', 'assertions' ) )
			&& defined( 'SMC_CONTRACT_VERSION' )
			&& self::supports_contract_version( (string) SMC_CONTRACT_VERSION );
	}

	public static function is_available(): bool {
		if ( ! defined( 'SMC_VERSION' ) || ! self::supports_version( (string) SMC_VERSION ) ) {
			return false;
		}
		if ( self::has_canonical_assertions() ) {
			return true;
		}
		return function_exists( 'smc_user_status' )
			&& function_exists( 'smc_is_founder' )
			&& function_exists( 'smc_is_trusted_publisher' );
	}

	/** @return array<string,mixed>|null */
	public static function assertions( int $user_id ): ?array {
		if ( $user_id < 1 || ! self::is_available() ) {
			return null;
		}
		if ( self::has_canonical_assertions() ) {
			try {
				$assertions = SMC_Contracts::assertions( $user_id );
			} catch ( Throwable $exception ) {
				return null;
			}
			if ( ! is_array( $assertions ) ) {
				return null;
			}
			$required = array( 'contract_version', 'user_id', 'status', 'approved', 'suspended', 'eligible', 'session_two_factor', 'can_publish' );
			foreach ( $required as $key ) {
				if ( ! array_key_exists( $key, $assertions ) ) {
					return null;
				}
			}
			if ( ! is_string( $assertions['contract_version'] ) || ! self::supports_contract_version( $assertions['contract_version'] ) || (int) $assertions['user_id'] !== $user_id ) {
				return null;
			}
			return array(
				'contract_version'      => $assertions['contract_version'],
				'user_id'               => $user_id,
				'status'                => sanitize_key( (string) $assertions['status'] ),
				'approved'              => true === $assertions['approved'],
				'suspended'             => true === $assertions['suspended'],
				'eligible'              => true === $assertions['eligible'],
				'session_two_factor'    => true === $assertions['session_two_factor'],
				'can_publish'           => true === $assertions['can_publish'],
				'institutional_account' => ! empty( $assertions['institutional_account'] ),
				'account_class'         => sanitize_key( (string) ( $assertions['account_class'] ?? '' ) ),
				'membership_type'       => sanitize_key( (string) ( $assertions['membership_type'] ?? '' ) ),
			);
		}
		$status = sanitize_key( (string) smc_user_status( $user_id ) );
		$approved = in_array( $status, array( 'approved', 'verified' ), true );
		return array(
			'contract_version'      => 'legacy',
			'user_id'               => $user_id,
			'status'                => $status,
			'approved'              => $approved,
			'suspended'             => in_array( $status, array( 'suspended', 'rejected', 'expired', 'expired_document', 'appeal_review', 'erasure_pending', 'invalid_application' ), true ),
			'eligible'              => $approved,
			'session_two_factor'    => $approved,
			'can_publish'           => $approved && ( smc_is_founder( $user_id ) || smc_is_trusted_publisher( $user_id ) ),
			'institutional_account' => smc_is_founder( $user_id ),
			'account_class'         => '',
			'membership_type'       => '',
		);
	}

	public static function restricted_view_statuses(): array {
		return array(
			'draft', 'email_pending', 'phone_pending', '2fa_pending', 'documents_incomplete',
			'submitted', 'under_review', 'more_information', 'resubmitted', 'approval_pending',
			'approved', 'verified', 'rejected', 'suspended', 'expired', 'expired_document',
			'appeal_review', 'erasure_pending', 'invalid_application',
		);
	}

	public static function user_status( int $user_id ): string {
		$assertions = self::assertions( $user_id );
		if ( null === $assertions ) {
			return 'dependency_unavailable';
		}
		$status = sanitize_key( (string) $assertions['status'] );
		return in_array( $status, self::restricted_view_statuses(), true ) ? $status : 'unknown';
	}

	public static function is_user_approved( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		return is_array( $assertions ) && true === $assertions['approved'] && false === $assertions['suspended'];
	}

	public static function can_user_publish( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		return is_array( $assertions )
			&& true === $assertions['approved']
			&& false === $assertions['suspended']
			&& true === $assertions['eligible']
			&& true === $assertions['session_two_factor']
			&& true === $assertions['can_publish'];
	}

	public static function can_user_view_restricted_dashboard( int $user_id ): bool {
		return in_array( self::user_status( $user_id ), self::restricted_view_statuses(), true );
	}

	public static function is_user_suspended( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		return is_array( $assertions ) && true === $assertions['suspended'];
	}

	public static function current_user_is_approved(): bool {
		return is_user_logged_in() && self::is_user_approved( get_current_user_id() );
	}

	public static function current_user_can_publish(): bool {
		return is_user_logged_in() && self::can_user_publish( get_current_user_id() );
	}

	public static function current_user_can_view_restricted_dashboard(): bool {
		return is_user_logged_in() && self::can_user_view_restricted_dashboard( get_current_user_id() );
	}

	/** @return array<string,mixed> */
	public static function health_snapshot(): array {
		$version = defined( 'SMC_VERSION' ) ? (string) SMC_VERSION : null;
		$contract = defined( 'SMC_CONTRACT_VERSION' ) ? (string) SMC_CONTRACT_VERSION : null;
		return array(
			'available'                   => self::is_available(),
			'canonical_assertions'        => self::has_canonical_assertions(),
			'version'                     => $version,
			'contract_version'            => $contract,
			'minimum_supported'           => self::MINIMUM_VERSION,
			'maximum_exclusive'           => self::MAXIMUM_VERSION_EXCLUSIVE,
			'minimum_contract_supported'  => self::MINIMUM_CONTRACT_VERSION,
			'maximum_contract_exclusive'  => self::MAXIMUM_CONTRACT_EXCLUSIVE,
			'version_compatible'          => is_string( $version ) ? self::supports_version( $version ) : false,
			'contract_compatible'         => is_string( $contract ) ? self::supports_contract_version( $contract ) : false,
		);
	}
}
