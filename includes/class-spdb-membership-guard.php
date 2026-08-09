<?php
/**
 * Membership Core dependency and account-state guard.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Membership_Guard {
	public const MINIMUM_VERSION            = '1.0.1';
	public const MAXIMUM_VERSION_EXCLUSIVE  = '2.0.0';
	public const MINIMUM_CONTRACT_VERSION   = '1.1.2';
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

	public static function canonical_contract_present(): bool {
		return class_exists( 'SMC_Contracts' ) || defined( 'SMC_CONTRACT_VERSION' );
	}

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
		if ( self::canonical_contract_present() ) {
			return self::has_canonical_assertions();
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
			$required = array( 'contract_version', 'user_id', 'status', 'approved', 'suspended', 'eligible', 'session_two_factor', 'can_publish', 'institutional_account' );
			foreach ( $required as $key ) {
				if ( ! array_key_exists( $key, $assertions ) ) {
					return null;
				}
			}
			if ( ! is_string( $assertions['contract_version'] ) || ! self::supports_contract_version( $assertions['contract_version'] ) || (int) $assertions['user_id'] !== $user_id ) {
				return null;
			}
			foreach ( array( 'approved', 'suspended', 'eligible', 'session_two_factor', 'can_publish', 'institutional_account' ) as $boolean_key ) {
				if ( ! is_bool( $assertions[ $boolean_key ] ) ) {
					return null;
				}
			}

			$institutional_ai = false;
			if ( array_key_exists( 'institutional_ai', $assertions ) ) {
				if ( ! is_bool( $assertions['institutional_ai'] ) ) {
					return null;
				}
				$institutional_ai = $assertions['institutional_ai'];
			}

			$publishing = array();
			if ( array_key_exists( 'publishing', $assertions ) ) {
				if ( ! is_array( $assertions['publishing'] ) ) {
					return null;
				}
				$publishing = self::sanitize_publishing_assertions( $assertions['publishing'] );
				if ( null === $publishing ) {
					return null;
				}
			}

			$institutional = true === $assertions['institutional_account'];
			$authority     = sanitize_key( (string) ( $publishing['authority_class'] ?? '' ) );
			$founder       = $institutional && (
				'founder' === $authority
				|| ( function_exists( 'smc_is_founder' ) && true === smc_is_founder( $user_id ) )
			);
			return array(
				'contract_version'      => $assertions['contract_version'],
				'user_id'               => $user_id,
				'status'                => sanitize_key( (string) $assertions['status'] ),
				'approved'              => $assertions['approved'],
				'suspended'             => $assertions['suspended'],
				'eligible'              => $assertions['eligible'],
				'session_two_factor'    => $assertions['session_two_factor'],
				'can_publish'           => $assertions['can_publish'],
				'institutional_account' => $institutional,
				'institutional_ai'      => $institutional_ai,
				'founder'               => $founder,
				'account_class'         => sanitize_key( (string) ( $assertions['account_class'] ?? '' ) ),
				'membership_type'       => sanitize_key( (string) ( $assertions['membership_type'] ?? '' ) ),
				'publishing'            => $publishing,
			);
		}

		$status   = sanitize_key( (string) smc_user_status( $user_id ) );
		$approved = in_array( $status, array( 'approved', 'verified' ), true );
		$founder  = true === smc_is_founder( $user_id );
		$trusted  = true === smc_is_trusted_publisher( $user_id );
		return array(
			'contract_version'      => 'legacy',
			'user_id'               => $user_id,
			'status'                => $status,
			'approved'              => $approved,
			'suspended'             => in_array( $status, array( 'suspended', 'rejected', 'expired', 'expired_document', 'appeal_review', 'erasure_pending', 'invalid_application' ), true ),
			'eligible'              => $approved,
			'session_two_factor'    => $approved,
			'can_publish'           => $approved && ( $founder || $trusted ),
			'institutional_account' => $founder,
			'institutional_ai'      => false,
			'founder'               => $founder,
			'account_class'         => '',
			'membership_type'       => '',
			'publishing'            => array(
				'authority_class'                 => $founder ? 'founder' : ( $trusted ? 'trusted_publisher' : '' ),
				'can_open_composer'               => $approved && ( $founder || $trusted ),
				'can_submit_for_review'            => $approved && ( $founder || $trusted ),
				'can_direct_publish'               => $approved && $founder,
				'requires_human_review'            => false,
				'doctor_verification_claim'        => false,
				'ai_generated_disclosure_required' => false,
			),
		);
	}

	/** @param array<string,mixed> $publishing @return array<string,mixed>|null */
	private static function sanitize_publishing_assertions( array $publishing ): ?array {
		$authority = sanitize_key( (string) ( $publishing['authority_class'] ?? '' ) );
		$out       = array( 'authority_class' => $authority );
		foreach ( array( 'can_open_composer', 'can_submit_for_review', 'can_direct_publish', 'requires_human_review', 'doctor_verification_claim', 'ai_generated_disclosure_required' ) as $key ) {
			if ( array_key_exists( $key, $publishing ) && ! is_bool( $publishing[ $key ] ) ) {
				return null;
			}
			$out[ $key ] = true === ( $publishing[ $key ] ?? false );
		}
		return $out;
	}

	public static function restricted_view_statuses(): array {
		return array(
			'draft', 'guardian_pending', 'email_pending', 'phone_pending', '2fa_pending', 'documents_incomplete',
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

	public static function is_user_founder( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		return is_array( $assertions )
			&& true === ( $assertions['founder'] ?? false )
			&& true === $assertions['approved']
			&& false === $assertions['suspended'];
	}

	public static function is_user_institutional_ai( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		return is_array( $assertions )
			&& true === ( $assertions['institutional_ai'] ?? false )
			&& true === $assertions['approved']
			&& false === $assertions['suspended'];
	}

	public static function publishing_authority_class( int $user_id ): string {
		$assertions = self::assertions( $user_id );
		if ( ! is_array( $assertions ) || ! is_array( $assertions['publishing'] ?? null ) ) {
			return '';
		}
		return sanitize_key( (string) ( $assertions['publishing']['authority_class'] ?? '' ) );
	}

	public static function is_user_verified_doctor( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		if ( ! is_array( $assertions ) || true !== $assertions['approved'] || true !== $assertions['eligible'] || true === $assertions['suspended'] || true === ( $assertions['institutional_ai'] ?? false ) ) {
			return false;
		}
		$publishing = is_array( $assertions['publishing'] ?? null ) ? $assertions['publishing'] : array();
		return true === ( $publishing['doctor_verification_claim'] ?? false )
			|| 'verified_doctor' === sanitize_key( (string) ( $publishing['authority_class'] ?? '' ) );
	}

	public static function is_user_trusted_publisher( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		if ( ! is_array( $assertions ) || true !== $assertions['approved'] || true !== $assertions['eligible'] || true === $assertions['suspended'] || true === ( $assertions['founder'] ?? false ) || true === ( $assertions['institutional_ai'] ?? false ) ) {
			return false;
		}
		$publishing = is_array( $assertions['publishing'] ?? null ) ? $assertions['publishing'] : array();
		return 'trusted_publisher' === sanitize_key( (string) ( $publishing['authority_class'] ?? '' ) );
	}

	public static function is_user_approved( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		return is_array( $assertions )
			&& true === $assertions['approved']
			&& true === $assertions['eligible']
			&& false === $assertions['suspended'];
	}

	public static function has_sensitive_session( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		return is_array( $assertions )
			&& true === $assertions['approved']
			&& true === $assertions['eligible']
			&& false === $assertions['suspended']
			&& true === $assertions['session_two_factor']
			&& false === ( $assertions['institutional_ai'] ?? false );
	}

	public static function can_user_open_composer( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		$publishing = is_array( $assertions['publishing'] ?? null ) ? $assertions['publishing'] : array();
		return self::has_sensitive_session( $user_id )
			&& true === ( $publishing['can_open_composer'] ?? false );
	}

	public static function can_user_publish( int $user_id ): bool {
		$assertions = self::assertions( $user_id );
		return is_array( $assertions )
			&& true === $assertions['approved']
			&& false === $assertions['suspended']
			&& true === $assertions['eligible']
			&& true === $assertions['session_two_factor']
			&& true === $assertions['can_publish']
			&& false === ( $assertions['institutional_ai'] ?? false );
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

	public static function current_user_has_sensitive_session(): bool {
		return is_user_logged_in() && self::has_sensitive_session( get_current_user_id() );
	}

	public static function current_user_can_open_composer(): bool {
		return is_user_logged_in() && self::can_user_open_composer( get_current_user_id() );
	}

	public static function current_user_can_publish(): bool {
		return is_user_logged_in() && self::can_user_publish( get_current_user_id() );
	}

	public static function current_user_can_view_restricted_dashboard(): bool {
		return is_user_logged_in() && self::can_user_view_restricted_dashboard( get_current_user_id() );
	}

	/** @return array<string,mixed> */
	public static function health_snapshot(): array {
		$version  = defined( 'SMC_VERSION' ) ? (string) SMC_VERSION : null;
		$contract = defined( 'SMC_CONTRACT_VERSION' ) ? (string) SMC_CONTRACT_VERSION : null;
		return array(
			'available'                  => self::is_available(),
			'canonical_contract_present' => self::canonical_contract_present(),
			'canonical_assertions'       => self::has_canonical_assertions(),
			'version'                    => $version,
			'contract_version'           => $contract,
			'minimum_supported'          => self::MINIMUM_VERSION,
			'maximum_exclusive'          => self::MAXIMUM_VERSION_EXCLUSIVE,
			'minimum_contract_supported' => self::MINIMUM_CONTRACT_VERSION,
			'maximum_contract_exclusive' => self::MAXIMUM_CONTRACT_EXCLUSIVE,
			'version_compatible'         => is_string( $version ) ? self::supports_version( $version ) : false,
			'contract_compatible'        => is_string( $contract ) ? self::supports_contract_version( $contract ) : false,
		);
	}
}
