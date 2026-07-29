<?php
/**
 * File 23 capability definitions.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Capabilities {
	/**
	 * Return the canonical File 23 capability keys.
	 *
	 * File 23 deliberately does not create WordPress roles. File 00 or an
	 * administrator-approved integration grants these capabilities.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			'spdb_view_dashboard',
			'spdb_view_own_content',
			'spdb_manage_own_content',
			'spdb_view_review_queue',
			'spdb_review_assigned_content',
			'spdb_manage_schedule',
			'spdb_manage_campaigns',
			'spdb_view_own_analytics',
			'spdb_view_global_analytics',
			'spdb_manage_interactions',
			'spdb_export_reports',
			'spdb_manage_delegations',
			'spdb_manage_dashboard_settings',
			'spdb_run_system_check',
			'spdb_repair_owned_data',
			'spdb_manage_safe_mode',
		);
	}

	public static function current_user_can( string $capability, ...$args ): bool {
		if ( ! in_array( $capability, self::all(), true ) ) {
			return false;
		}

		return current_user_can( $capability, ...$args );
	}
}
