<?php
/**
 * Non-destructive File 23 activation readiness wizard.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Activation_Wizard {
	private const OPTION = 'spdb_activation_acceptance_v1';

	/** @var string[] */
	private const REQUIRED_EVIDENCE = array(
		'role_matrix_evidence',
		'provider_contract_evidence',
		'cache_privacy_evidence',
		'accessibility_evidence',
		'backup_restore_evidence',
		'rollback_evidence',
	);

	private SPDB_Local_Repair $repair;
	private SPDB_Operations_Repository $repository;

	public function __construct( SPDB_Local_Repair $repair, SPDB_Operations_Repository $repository ) {
		$this->repair     = $repair;
		$this->repository = $repository;
	}

	/** @return array<string,mixed> */
	public function state(): array {
		$system   = $this->repair->system_check();
		$accepted = get_option( self::OPTION, array() );
		$accepted = is_array( $accepted ) ? $accepted : array();

		$current_version   = hash_equals( SPDB_VERSION, (string) ( $accepted['plugin_version'] ?? '' ) );
		$evidence_complete = $this->stored_evidence_complete( $accepted );
		$environment       = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$artifact_verified = $this->current_attestation_matches( $accepted, $environment );
		$schemas_ready     = ! empty( $system['checks']['operations_schema']['ok'] ) && ! empty( $system['checks']['collections_schema']['ok'] );
		$staging_accepted  = true === ( $accepted['staging_accepted'] ?? false ) && $current_version && $evidence_complete && $artifact_verified && $schemas_ready;
		$founder_accepted  = true === ( $accepted['founder_accepted'] ?? false );

		$steps = array(
			array( 'key' => 'environment', 'label' => __( 'Environment and schemas', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['php']['ok'] ) && ! empty( $system['checks']['wordpress']['ok'] ) && ! empty( $system['checks']['operations_schema']['ok'] ) && ! empty( $system['checks']['collections_schema']['ok'] ) ),
			array( 'key' => 'identity', 'label' => __( 'Membership authority and capabilities', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['membership']['ok'] ) ),
			array( 'key' => 'providers', 'label' => __( 'Provider registry and versioned contracts', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['provider_registry']['ok'] ) ),
			array( 'key' => 'privacy', 'label' => __( 'Private route, cache and privacy controls', 'sabri-publishing-dashboard' ), 'ready' => $staging_accepted && '' !== (string) ( $accepted['cache_privacy_evidence'] ?? '' ) ),
			array( 'key' => 'accessibility', 'label' => __( 'Browser, mobile, RTL and accessibility acceptance', 'sabri-publishing-dashboard' ), 'ready' => $staging_accepted && '' !== (string) ( $accepted['accessibility_evidence'] ?? '' ) ),
			array( 'key' => 'jobs', 'label' => __( 'Background jobs, retries and dead-letter queue', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['cron']['ok'] ) ),
			array( 'key' => 'audit', 'label' => __( 'Append-only dashboard audit evidence', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['audit']['ok'] ) ),
			array( 'key' => 'recovery', 'label' => __( 'Backup, restore and rollback rehearsal', 'sabri-publishing-dashboard' ), 'ready' => $staging_accepted && '' !== (string) ( $accepted['backup_restore_evidence'] ?? '' ) && '' !== (string) ( $accepted['rollback_evidence'] ?? '' ) ),
			array( 'key' => 'staging', 'label' => __( 'Hostinger staging and Founder acceptance', 'sabri-publishing-dashboard' ), 'ready' => $staging_accepted ),
		);

		$code_ready = true;
		foreach ( $steps as $step ) {
			if ( ! in_array( $step['key'], array( 'privacy', 'accessibility', 'recovery', 'staging' ), true ) ) {
				$code_ready = $code_ready && $step['ready'];
			}
		}

		return array(
			'steps'                    => $steps,
			'code_environment_ready'   => $code_ready,
			'evidence_complete'        => $evidence_complete,
			'acceptance_version_valid' => $current_version,
			'acceptance_artifact_verified' => $artifact_verified,
			'staging_accepted'         => $staging_accepted,
			'founder_accepted'         => $founder_accepted,
			'live_activation_allowed'  => $staging_accepted && $founder_accepted,
			'acceptance'               => $this->public_acceptance( $accepted ),
			'generated_at_gmt'         => gmdate( 'c' ),
		);
	}

	/**
	 * Record explicit staging evidence. This does not change native-provider
	 * maturity, acceptance, or write authority and cannot enable an adapter.
	 *
	 * @param array<string,mixed> $input Acceptance payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function record_acceptance( array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_dashboard_settings' ) ) {
			return self::error( 'spdb_activation_forbidden', __( 'You are not authorized to record activation acceptance.', 'sabri-publishing-dashboard' ), 403 );
		}

		$actor_id   = get_current_user_id();
		$assertions = SPDB_Membership_Guard::assertions( $actor_id );
		if ( ! is_array( $assertions ) || ! SPDB_Membership_Guard::is_user_founder( $actor_id ) || empty( $assertions['session_two_factor'] ) ) {
			return self::error( 'spdb_activation_founder_required', __( 'Founder authority and current two-factor authentication are required.', 'sabri-publishing-dashboard' ), 403 );
		}

		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		if ( 'production' === $environment ) {
			return self::error( 'spdb_activation_staging_required', __( 'Staging acceptance must be recorded from a non-production environment.', 'sabri-publishing-dashboard' ), 409 );
		}

		$reason = isset( $input['reason'] ) && is_scalar( $input['reason'] ) ? trim( wp_strip_all_tags( (string) $input['reason'] ) ) : '';
		if ( '' === $reason || strlen( $reason ) > 500 ) {
			return self::error( 'spdb_activation_reason_required', __( 'A bounded staging acceptance reason is required.', 'sabri-publishing-dashboard' ), 400 );
		}

		$source_commit  = isset( $input['source_commit'] ) && is_scalar( $input['source_commit'] ) ? strtolower( trim( (string) $input['source_commit'] ) ) : '';
		$package_sha256 = isset( $input['package_sha256'] ) && is_scalar( $input['package_sha256'] ) ? strtolower( trim( (string) $input['package_sha256'] ) ) : '';
		if ( 1 !== preg_match( '/\A[a-f0-9]{40}\z/', $source_commit ) || 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $package_sha256 ) ) {
			return self::error( 'spdb_activation_artifact_identity_invalid', __( 'Exact source commit and package SHA-256 evidence are required.', 'sabri-publishing-dashboard' ), 400 );
		}

		// Founder-entered IDs are evidence references, not installed-code proof.
		// This attestation must originate from a trusted server-side deployment
		// provider. In its absence even a syntactically valid record is denied.
		if ( ! $this->current_attestation_matches(
			array(
				'plugin_version' => SPDB_VERSION,
				'source_commit' => $source_commit,
				'package_sha256' => $package_sha256,
				'environment' => $environment,
			),
			$environment
		) ) {
			return self::error( 'spdb_activation_attestation_unverified', __( 'Current deployed artifact and schema attestation are required before staging acceptance.', 'sabri-publishing-dashboard' ), 409 );
		}

		$evidence = array();
		foreach ( self::REQUIRED_EVIDENCE as $field ) {
			$value = isset( $input[ $field ] ) && is_scalar( $input[ $field ] ) ? sanitize_text_field( (string) $input[ $field ] ) : '';
			if ( ! $this->valid_evidence_identifier( $value ) ) {
				return self::error( 'spdb_activation_evidence_required', sprintf( __( 'A valid %s identifier is required.', 'sabri-publishing-dashboard' ), $field ), 400 );
			}
			$evidence[ $field ] = $value;
		}

		$record = array_merge(
			$evidence,
			array(
				'staging_accepted' => true,
				'founder_accepted' => true === ( $input['founder_accepted'] ?? false ),
				'source_commit'    => $source_commit,
				'package_sha256'   => $package_sha256,
				'reason_hash'      => hash( 'sha256', $reason ),
				'actor_user_id'    => $actor_id,
				'plugin_version'   => SPDB_VERSION,
				'environment'      => sanitize_key( (string) $environment ),
				'recorded_at_gmt'  => gmdate( 'c' ),
			)
		);

		$previous = get_option( self::OPTION, array() );
		$previous = is_array( $previous ) ? $previous : array();
		if ( ! update_option( self::OPTION, $record, false ) && $previous !== $record ) {
			return self::error( 'spdb_activation_persistence_failed', __( 'The staging acceptance record could not be persisted.', 'sabri-publishing-dashboard' ), 500 );
		}

		$audit = $this->repository->append_audit(
			$actor_id,
			'staging_acceptance_recorded',
			'file23:' . SPDB_VERSION,
			array(
				'source_commit'    => $source_commit,
				'package_sha256'   => $package_sha256,
				'founder_accepted' => $record['founder_accepted'],
				'evidence_keys'    => array_values( self::REQUIRED_EVIDENCE ),
			)
		);
		if ( is_wp_error( $audit ) ) {
			update_option( self::OPTION, $previous, false );
			return self::error( 'spdb_activation_audit_failed', __( 'The staging acceptance was not retained because its audit evidence could not be written.', 'sabri-publishing-dashboard' ), 500 );
		}

		return $this->state();
	}

	/**
	 * Consume a trusted deployment operator / File 20 attestation on each
	 * acceptance read or write. An absent provider can NEVER be replaced by a
	 * Founder-entered SHA or merely a matching plugin version.
	 *
	 * The owning infrastructure must independently measure installed bytes,
	 * deployed package SHA, schema and migrations. This consumer cannot certify
	 * the authenticity of another plugin's assertion or of the host by itself.
	 *
	 * @param array<string,mixed> $record Stored or proposed acceptance.
	 */
	private function current_attestation_matches( array $record, string $environment ): bool {
		if ( ! function_exists( 'apply_filters' ) ) {
			return false;
		}
		try {
			$attestation = apply_filters( 'spdb/file23_deployed_artifact_attestation', null );
		} catch ( Throwable $error ) {
			return false;
		}
		return self::attestation_matches_record( $record, $attestation, $environment, time() );
	}

	/**
	 * Strict, deterministic validation of the trusted provider's *assertion*.
	 * The provider must bind this assertion to real installed bytes and DB
	 * before supplying it. The caller fails closed when that provider is absent.
	 *
	 * @param array<string,mixed> $record Current staging acceptance record.
	 * @param mixed $attestation Live, server-side deployment provider result.
	 */
	private static function attestation_matches_record( array $record, $attestation, string $environment, int $now ): bool {
		if ( 'staging' !== $environment || ! is_array( $attestation ) ||
			'file23_deployed_artifact_v1' !== ( $attestation['contract'] ?? null ) ||
			true !== ( $attestation['verified'] ?? false ) ) {
			return false;
		}

		foreach ( array( 'source_commit' => 40, 'package_sha256' => 64 ) as $field => $length ) {
			$accepted = $record[ $field ] ?? null;
			$installed = $attestation[ $field ] ?? null;
			if ( ! is_string( $accepted ) || ! is_string( $installed ) ||
				1 !== preg_match( '/\A[a-f0-9]{' . $length . '}\z/', $accepted ) ||
				1 !== preg_match( '/\A[a-f0-9]{' . $length . '}\z/', $installed ) ||
				! hash_equals( $accepted, $installed ) ) {
				return false;
			}
		}

		if ( ! hash_equals( SPDB_VERSION, (string) ( $record['plugin_version'] ?? '' ) ) ||
			! hash_equals( SPDB_VERSION, (string) ( $attestation['plugin_version'] ?? '' ) ) ||
			! hash_equals( $environment, (string) ( $record['environment'] ?? '' ) ) ||
			! hash_equals( $environment, (string) ( $attestation['environment'] ?? '' ) ) ||
			! hash_equals( SPDB_Operations_Schema::VERSION, (string) ( $attestation['operations_schema'] ?? '' ) ) ||
			! hash_equals( SPDB_Collections_Schema::VERSION, (string) ( $attestation['collections_schema'] ?? '' ) ) ||
			'complete' !== ( $attestation['migration_state'] ?? null ) ) {
			return false;
		}

		$observed = $attestation['observed_at_unix'] ?? null;
		return is_int( $observed ) && $observed <= $now && $observed >= $now - 300;
	}

	/** @param array<string,mixed> $record */
	private function stored_evidence_complete( array $record ): bool {
		if ( 1 !== preg_match( '/\A[a-f0-9]{40}\z/', (string) ( $record['source_commit'] ?? '' ) ) ) {
			return false;
		}
		if ( 1 !== preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $record['package_sha256'] ?? '' ) ) ) {
			return false;
		}
		foreach ( self::REQUIRED_EVIDENCE as $field ) {
			if ( ! $this->valid_evidence_identifier( (string) ( $record[ $field ] ?? '' ) ) ) {
				return false;
			}
		}
		return true;
	}

	private function valid_evidence_identifier( string $value ): bool {
		return strlen( $value ) >= 3 && strlen( $value ) <= 191 && 1 === preg_match( '/\A[A-Za-z0-9][A-Za-z0-9._:\/-]*\z/', $value );
	}

	/** @param array<string,mixed> $record @return array<string,mixed> */
	private function public_acceptance( array $record ): array {
		$out = array(
			'staging_accepted' => true === ( $record['staging_accepted'] ?? false ),
			'founder_accepted' => true === ( $record['founder_accepted'] ?? false ),
			'source_commit'    => sanitize_text_field( (string) ( $record['source_commit'] ?? '' ) ),
			'package_sha256'   => sanitize_text_field( (string) ( $record['package_sha256'] ?? '' ) ),
			'plugin_version'   => sanitize_text_field( (string) ( $record['plugin_version'] ?? '' ) ),
			'environment'      => sanitize_key( (string) ( $record['environment'] ?? '' ) ),
			'recorded_at_gmt'  => sanitize_text_field( (string) ( $record['recorded_at_gmt'] ?? '' ) ),
		);
		foreach ( self::REQUIRED_EVIDENCE as $field ) {
			$out[ $field ] = sanitize_text_field( (string) ( $record[ $field ] ?? '' ) );
		}
		return $out;
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
