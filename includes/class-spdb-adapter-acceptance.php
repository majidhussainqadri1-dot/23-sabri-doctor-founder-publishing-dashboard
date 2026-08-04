<?php
/**
 * File 23-owned provider maturity acceptance records.
 *
 * Providers may declare technical capability, but they cannot self-approve.
 * Every acceptance is bound to the exact provider, File 23 contract and plugin
 * version, with Founder/MFA authority and immutable dashboard audit evidence.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Adapter_Acceptance {
	private const OPTION_KEY = 'spdb_adapter_acceptance_records_v1';

	private SPDB_Adapter_Registry $registry;
	private SPDB_Operations_Repository $repository;

	public function __construct( SPDB_Adapter_Registry $registry, SPDB_Operations_Repository $repository ) {
		$this->registry   = $registry;
		$this->repository = $repository;
	}

	/** @return array<string,array<string,mixed>> */
	public static function records(): array {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		$out = array();
		foreach ( $stored as $provider_key => $record ) {
			$provider_key = (string) $provider_key;
			if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! is_array( $record ) ) {
				continue;
			}
			$state = sanitize_key( (string) ( $record['state'] ?? '' ) );
			if ( ! in_array( $state, SPDB_Adapter_Registry::acceptance_states(), true ) ) {
				continue;
			}
			$out[ $provider_key ] = array(
				'state'             => $state,
				'provider_version'  => sanitize_text_field( (string) ( $record['provider_version'] ?? '' ) ),
				'contract_version'  => sanitize_text_field( (string) ( $record['contract_version'] ?? '' ) ),
				'plugin_version'    => sanitize_text_field( (string) ( $record['plugin_version'] ?? '' ) ),
				'environment'       => sanitize_key( (string) ( $record['environment'] ?? '' ) ),
				'evidence_id'       => sanitize_text_field( (string) ( $record['evidence_id'] ?? '' ) ),
				'evidence_hash'     => sanitize_text_field( (string) ( $record['evidence_hash'] ?? '' ) ),
				'accepted_by'       => max( 0, (int) ( $record['accepted_by'] ?? 0 ) ),
				'accepted_at_gmt'   => sanitize_text_field( (string) ( $record['accepted_at_gmt'] ?? '' ) ),
			);
		}
		return $out;
	}

	/** @return array<string,mixed>|WP_Error */
	public function record( string $provider_key, array $input ) {
		$provider_key = sanitize_key( $provider_key );
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) ) {
			return self::error( 'spdb_adapter_acceptance_provider_invalid', __( 'The provider identifier is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_dashboard_settings' ) ) {
			return self::error( 'spdb_adapter_acceptance_forbidden', __( 'You are not authorized to govern provider acceptance.', 'sabri-publishing-dashboard' ), 403 );
		}

		$actor_id   = get_current_user_id();
		$assertions = SPDB_Membership_Guard::assertions( $actor_id );
		if ( ! is_array( $assertions ) || ! SPDB_Membership_Guard::is_user_founder( $actor_id ) || true !== ( $assertions['session_two_factor'] ?? null ) ) {
			return self::error( 'spdb_adapter_acceptance_founder_required', __( 'Founder authority and current two-factor authentication are required.', 'sabri-publishing-dashboard' ), 403 );
		}

		$metadata = $this->registry->metadata( $provider_key );
		if ( ! is_array( $metadata ) ) {
			return self::error( 'spdb_adapter_acceptance_provider_unavailable', __( 'The provider must be registered before acceptance can be recorded.', 'sabri-publishing-dashboard' ), 404 );
		}
		$state = sanitize_key( (string) ( $input['state'] ?? '' ) );
		if ( ! in_array( $state, array( SPDB_Adapter_Registry::ACCEPTANCE_STAGING_ACCEPTED, SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED, SPDB_Adapter_Registry::ACCEPTANCE_REVOKED ), true ) ) {
			return self::error( 'spdb_adapter_acceptance_state_invalid', __( 'The provider acceptance state is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}

		$environment = function_exists( 'wp_get_environment_type' ) ? sanitize_key( wp_get_environment_type() ) : 'production';
		if ( SPDB_Adapter_Registry::ACCEPTANCE_STAGING_ACCEPTED === $state && 'production' === $environment ) {
			return self::error( 'spdb_adapter_acceptance_staging_required', __( 'Staging acceptance must be recorded in a non-production environment.', 'sabri-publishing-dashboard' ), 409 );
		}
		if ( SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED === $state && ! self::file23_release_accepted() ) {
			return self::error( 'spdb_adapter_acceptance_release_gate', __( 'File 23 staging and Founder release acceptance must be complete before production provider acceptance.', 'sabri-publishing-dashboard' ), 409 );
		}

		$evidence_id = isset( $input['evidence_id'] ) && is_scalar( $input['evidence_id'] ) ? trim( (string) $input['evidence_id'] ) : '';
		$reason      = isset( $input['reason'] ) && is_scalar( $input['reason'] ) ? trim( wp_strip_all_tags( (string) $input['reason'] ) ) : '';
		if ( ! self::valid_identifier( $evidence_id ) || ! self::valid_reason( $reason ) ) {
			return self::error( 'spdb_adapter_acceptance_evidence_invalid', __( 'A valid evidence identifier and privacy-safe reason are required.', 'sabri-publishing-dashboard' ), 400 );
		}

		$record = array(
			'state'            => $state,
			'provider_version' => (string) $metadata['provider_version'],
			'contract_version' => SPDB_CONTRACT_VERSION,
			'plugin_version'   => SPDB_VERSION,
			'environment'      => $environment,
			'evidence_id'      => $evidence_id,
			'evidence_hash'    => hash( 'sha256', $evidence_id . '|' . $reason ),
			'accepted_by'      => $actor_id,
			'accepted_at_gmt'  => gmdate( 'c' ),
		);
		$records  = self::records();
		$previous = $records[ $provider_key ] ?? null;
		$records[ $provider_key ] = $record;
		if ( ! update_option( self::OPTION_KEY, $records, false ) && get_option( self::OPTION_KEY, array() ) !== $records ) {
			return self::error( 'spdb_adapter_acceptance_persistence_failed', __( 'The provider acceptance record could not be persisted.', 'sabri-publishing-dashboard' ), 500 );
		}

		$audit = $this->repository->append_audit(
			$actor_id,
			'provider_acceptance_changed',
			'provider:' . $provider_key,
			array(
				'provider_key'     => $provider_key,
				'provider_version' => $record['provider_version'],
				'contract_version' => SPDB_CONTRACT_VERSION,
				'state'            => $state,
				'evidence_hash'    => $record['evidence_hash'],
			)
		);
		if ( is_wp_error( $audit ) ) {
			if ( null === $previous ) {
				unset( $records[ $provider_key ] );
			} else {
				$records[ $provider_key ] = $previous;
			}
			update_option( self::OPTION_KEY, $records, false );
			return $audit;
		}
		return array( 'provider_key' => $provider_key, 'record' => $record );
	}

	private static function file23_release_accepted(): bool {
		$record = get_option( 'spdb_activation_acceptance_v1', array() );
		return is_array( $record )
			&& true === ( $record['staging_accepted'] ?? null )
			&& true === ( $record['founder_accepted'] ?? null )
			&& hash_equals( SPDB_VERSION, (string) ( $record['plugin_version'] ?? '' ) );
	}

	private static function valid_identifier( string $value ): bool {
		return strlen( $value ) >= 3 && strlen( $value ) <= 191 && 1 === preg_match( '/\A[A-Za-z0-9][A-Za-z0-9._:\/-]*\z/', $value );
	}

	private static function valid_reason( string $value ): bool {
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
		return $length >= 10 && $length <= 500
			&& ! preg_match( '/[\x00-\x1F\x7F]/', $value )
			&& ! preg_match( '/password|secret|token|nonce|otp|cvv|patient|diagnosis|prescription|message[ _-]?body/i', $value );
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
