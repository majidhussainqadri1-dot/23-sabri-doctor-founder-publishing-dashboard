<?php
/**
 * Versioned File 23-only settings. Settings cannot grant native authority.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Admin_Settings {
	private const OPTION = 'spdb_settings_v3';

	/** @return array<string,mixed> */
	public static function defaults(): array {
		return array(
			'analytics_min_cohort'       => 20,
			'export_ttl_hours'           => 24,
			'max_export_rows'            => 1000,
			'task_retention_days'        => 365,
			'failed_job_retention_days'  => 30,
			'automation_enabled'         => false,
			'ai_assistance_enabled'      => false,
			'local_projection_pause'     => false,
			'jobs_per_run'               => 10,
			'job_max_attempts'           => 5,
		);
	}

	/** @return array<string,mixed> */
	public static function get(): array {
		$raw = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $raw ) ? $raw : array() );
	}

	/**
	 * @param array<string,mixed> $input Raw setting changes.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function update( array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_dashboard_settings' ) ) {
			return new WP_Error(
				'spdb_settings_forbidden',
				__( 'You are not authorized to change publishing-dashboard settings.', 'sabri-publishing-dashboard' ),
				array( 'status' => 403 )
			);
		}

		$current  = self::get();
		$settings = self::sanitize( array_merge( $current, $input ) );
		if ( ! update_option( self::OPTION, $settings, false ) && self::get() !== $settings ) {
			return new WP_Error(
				'spdb_settings_write_failed',
				__( 'Publishing-dashboard settings could not be saved.', 'sabri-publishing-dashboard' ),
				array( 'status' => 500 )
			);
		}

		do_action( 'spdb/settings_updated', $settings, $current, get_current_user_id() );
		return $settings;
	}

	/**
	 * @param array<string,mixed> $input Raw settings.
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $input ): array {
		$defaults = self::defaults();
		return array(
			'analytics_min_cohort'      => min( 100, max( 20, (int) ( $input['analytics_min_cohort'] ?? $defaults['analytics_min_cohort'] ) ) ),
			'export_ttl_hours'          => min( 72, max( 24, (int) ( $input['export_ttl_hours'] ?? $defaults['export_ttl_hours'] ) ) ),
			'max_export_rows'           => min( 10000, max( 100, (int) ( $input['max_export_rows'] ?? $defaults['max_export_rows'] ) ) ),
			'task_retention_days'       => min( 730, max( 365, (int) ( $input['task_retention_days'] ?? $defaults['task_retention_days'] ) ) ),
			'failed_job_retention_days' => min( 90, max( 30, (int) ( $input['failed_job_retention_days'] ?? $defaults['failed_job_retention_days'] ) ) ),
			'automation_enabled'        => true === ( $input['automation_enabled'] ?? $defaults['automation_enabled'] ),
			'ai_assistance_enabled'     => true === ( $input['ai_assistance_enabled'] ?? $defaults['ai_assistance_enabled'] ),
			'local_projection_pause'    => true === ( $input['local_projection_pause'] ?? $defaults['local_projection_pause'] ),
			'jobs_per_run'              => min( 25, max( 1, (int) ( $input['jobs_per_run'] ?? $defaults['jobs_per_run'] ) ) ),
			'job_max_attempts'          => min( 10, max( 1, (int) ( $input['job_max_attempts'] ?? $defaults['job_max_attempts'] ) ) ),
		);
	}
}
