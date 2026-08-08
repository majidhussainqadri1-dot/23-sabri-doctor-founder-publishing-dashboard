<?php
/**
 * File 23 capability definitions.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Capabilities {
	/** @return string[] */
	public static function all(): array {
		return array(
			'spdb_view_dashboard',
			'spdb_view_own_content',
			'spdb_manage_own_content',
			'spdb_view_review_queue',
			'spdb_review_assigned_content',
			'spdb_manage_schedule',
			'spdb_manage_campaigns',
			'spdb_manage_tasks',
			'spdb_manage_automation_rules',
			'spdb_manage_interactions',
			'spdb_manage_delegations',
			'spdb_view_own_analytics',
			'spdb_view_global_analytics',
			'spdb_view_assurance_status',
			'spdb_export_reports',
			'spdb_request_ai_assistance',
			'spdb_reconcile_projections',
			'spdb_manage_dashboard_settings',
			'spdb_run_system_check',
			'spdb_repair_owned_data',
		);
	}

	/** @return string[] */
	public static function retired(): array {
		return array( 'spdb_manage_safe_mode' );
	}

	/** @return string[] */
	public static function restricted_view_capabilities(): array {
		return array( 'spdb_view_dashboard', 'spdb_view_own_content' );
	}

	/** @return string[] */
	public static function verified_session_capabilities(): array {
		return array(
			'spdb_manage_own_content',
			'spdb_view_review_queue',
			'spdb_review_assigned_content',
			'spdb_manage_schedule',
			'spdb_manage_campaigns',
			'spdb_manage_tasks',
			'spdb_manage_automation_rules',
			'spdb_manage_interactions',
			'spdb_manage_delegations',
			'spdb_view_global_analytics',
			'spdb_view_assurance_status',
			'spdb_export_reports',
			'spdb_request_ai_assistance',
			'spdb_reconcile_projections',
			'spdb_manage_dashboard_settings',
			'spdb_run_system_check',
			'spdb_repair_owned_data',
		);
	}

	/**
	 * Capabilities that are semantically tied to the Founder/verified-doctor
	 * publishing identity. A stale WordPress role or migrated capability must
	 * not manufacture a Doctor authority that File 00 does not currently assert.
	 *
	 * @return string[]
	 */
	public static function verified_publishing_identity_capabilities(): array {
		return array(
			'spdb_manage_own_content',
			'spdb_manage_schedule',
			'spdb_view_own_analytics',
		);
	}

	public static function current_user_can( string $capability, ...$args ): bool {
		if ( ! in_array( $capability, self::all(), true ) || ! current_user_can( $capability, ...$args ) ) {
			return false;
		}

		if ( in_array( $capability, self::restricted_view_capabilities(), true ) ) {
			return SPDB_Membership_Guard::current_user_can_view_restricted_dashboard();
		}

		if ( ! SPDB_Membership_Guard::current_user_is_approved() ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( in_array( $capability, self::verified_publishing_identity_capabilities(), true )
			&& ! SPDB_Membership_Guard::is_user_founder( $user_id )
			&& ! SPDB_Membership_Guard::is_user_verified_doctor( $user_id ) ) {
			return false;
		}

		if ( in_array( $capability, self::verified_session_capabilities(), true ) ) {
			return SPDB_Membership_Guard::has_sensitive_session( $user_id );
		}

		return true;
	}
}
