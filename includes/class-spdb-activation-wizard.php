<?php
/**
 * Non-destructive File 23 activation readiness wizard.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Activation_Wizard {
	private const OPTION = 'spdb_activation_acceptance_v1';

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
		$staging_accepted = is_array( $accepted ) && ! empty( $accepted['staging_accepted'] );
		$steps  = array(
			array( 'key' => 'environment', 'label' => __( 'Environment and schemas', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['php']['ok'] ) && ! empty( $system['checks']['wordpress']['ok'] ) && ! empty( $system['checks']['operations_schema']['ok'] ) && ! empty( $system['checks']['collections_schema']['ok'] ) ),
			array( 'key' => 'identity', 'label' => __( 'Membership authority and capabilities', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['membership']['ok'] ) ),
			array( 'key' => 'providers', 'label' => __( 'Provider registry and versioned contracts', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['provider_registry']['ok'] ) ),
			array( 'key' => 'privacy', 'label' => __( 'Private route, cache and privacy controls', 'sabri-publishing-dashboard' ), 'ready' => true ),
			array( 'key' => 'jobs', 'label' => __( 'Background jobs, retries and dead-letter queue', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['cron']['ok'] ) ),
			array( 'key' => 'audit', 'label' => __( 'Append-only dashboard audit evidence', 'sabri-publishing-dashboard' ), 'ready' => ! empty( $system['checks']['audit']['ok'] ) ),
			array( 'key' => 'staging', 'label' => __( 'Hostinger staging and Founder acceptance', 'sabri-publishing-dashboard' ), 'ready' => $staging_accepted ),
		);
		$code_ready = true;
		foreach ( $steps as $step ) {
			if ( 'staging' !== $step['key'] ) {
				$code_ready = $code_ready && $step['ready'];
			}
		}
		return array(
			'steps'                  => $steps,
			'code_environment_ready' => $code_ready,
			'staging_accepted'       => is_array( $accepted ) && ! empty( $accepted['staging_accepted'] ),
			'live_activation_allowed' => is_array( $accepted ) && ! empty( $accepted['staging_accepted'] ) && ! empty( $accepted['founder_accepted'] ),
			'acceptance'             => is_array( $accepted ) ? $this->public_acceptance( $accepted ) : array(),
			'generated_at_gmt'       => gmdate( 'c' ),
		);
	}

	/**
	 * Record an explicit staging acceptance statement. This never changes native
	 * provider maturity or production acceptance and therefore cannot enable a
	 * write-capable adapter by itself.
	 *
	 * @param array<string,mixed> $input Acceptance payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function record_acceptance( array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_dashboard_settings' ) ) {
			return self::error( 'spdb_activation_forbidden', __( 'You are not authorized to record activation acceptance.', 'sabri-publishing-dashboard' ), 403 );
		}
		$assertions = SPDB_Membership_Guard::assertions( get_current_user_id() );
		if ( ! is_array( $assertions ) || ! SPDB_Membership_Guard::is_user_founder( get_current_user_id() ) || empty( $assertions['session_two_factor'] ) ) {
			return self::error( 'spdb_activation_founder_required', __( 'Founder authority and current two-factor authentication are required.', 'sabri-publishing-dashboard' ), 403 );
		}
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		if ( 'production' === $environment ) {
			return self::error( 'spdb_activation_staging_required', __( 'Staging acceptance must be recorded from a non-production environment.', 'sabri-publishing-dashboard' ), 409 );
		}
		$reason = isset( $input['reason'] ) && is_scalar( $input['reason'] ) ? trim( wp_strip_all_tags( (string) $input['reason'] ) ) : '';
		$evidence = isset( $input['evidence_id'] ) && is_scalar( $input['evidence_id'] ) ? sanitize_text_field( (string) $input['evidence_id'] ) : '';
		if ( '' === $reason || strlen( $reason ) > 500 || '' === $evidence || strlen( $evidence ) > 191 ) {
			return self::error( 'spdb_activation_evidence_required', __( 'A bounded acceptance reason and evidence identifier are required.', 'sabri-publishing-dashboard' ), 400 );
		}
		$record = array(
			'staging_accepted' => true,
			'founder_accepted' => true === ( $input['founder_accepted'] ?? false ),
			'evidence_id'      => $evidence,
			'reason_hash'      => hash( 'sha256', $reason ),
			'actor_user_id'    => get_current_user_id(),
			'plugin_version'   => SPDB_VERSION,
			'recorded_at_gmt'  => gmdate( 'c' ),
		);
		update_option( self::OPTION, $record, false );
		$this->repository->append_audit( get_current_user_id(), 'staging_acceptance_recorded', 'file23:' . SPDB_VERSION, array( 'evidence_id' => $evidence, 'founder_accepted' => $record['founder_accepted'] ) );
		return $this->state();
	}

	/** @param array<string,mixed> $record @return array<string,mixed> */
	private function public_acceptance( array $record ): array {
		return array(
			'staging_accepted' => true === ( $record['staging_accepted'] ?? false ),
			'founder_accepted' => true === ( $record['founder_accepted'] ?? false ),
			'evidence_id'      => sanitize_text_field( (string) ( $record['evidence_id'] ?? '' ) ),
			'plugin_version'   => sanitize_text_field( (string) ( $record['plugin_version'] ?? '' ) ),
			'recorded_at_gmt'  => sanitize_text_field( (string) ( $record['recorded_at_gmt'] ?? '' ) ),
		);
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
