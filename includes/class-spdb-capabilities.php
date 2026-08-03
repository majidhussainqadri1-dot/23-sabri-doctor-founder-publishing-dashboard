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

	/**
	 * Read-only capabilities permitted for the restricted pending/suspended view.
	 *
	 * @return string[]
	 */
	public static function restricted_view_capabilities(): array {
		return array(
			'spdb_view_dashboard',
			'spdb_view_own_content',
		);
	}

	/**
	 * Enforce canonical capability and current File 00 account state.
	 *
	 * Pending, rejected, expired-document, appeal-review, and suspended accounts
	 * may use only explicitly assigned restricted-view capabilities. Every other
	 * File 23 capability requires an approved or verified File 00 account.
	 */
	public static function current_user_can( string $capability, ...$args ): bool {
		if ( ! in_array( $capability, self::all(), true ) ) {
			return false;
		}

		if ( ! current_user_can( $capability, ...$args ) ) {
			return false;
		}

		if ( in_array( $capability, self::restricted_view_capabilities(), true ) ) {
			return SPDB_Membership_Guard::current_user_can_view_restricted_dashboard();
		}

		return SPDB_Membership_Guard::current_user_is_approved();
	}
}
