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

	/**
	 * Capabilities previously created by File 23 that are no longer valid.
	 *
	 * Global Safe Mode is owned by File 20 and must not remain grantable through
	 * File 23 after an upgrade.
	 *
	 * @return string[]
	 */
	public static function retired(): array {
		return array( 'spdb_manage_safe_mode' );
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
	 * Privileged File 23 capabilities require the current File 00 session to have
	 * completed its MFA/step-up assertion. An approved account without a verified
	 * current session may still use ordinary private dashboard views, but cannot
	 * perform sensitive writes, privileged review/security reads, exports, or AI
	 * provider requests.
	 *
	 * @return string[]
	 */
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
	 * Enforce canonical capability and current File 00 account state.
	 *
	 * Pending, rejected, expired-document, appeal-review, and suspended accounts
	 * may use only explicitly assigned restricted-view capabilities. Every other
	 * File 23 capability requires an approved or verified File 00 account. Sensitive
	 * capabilities additionally require the current File 00 session MFA assertion.
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

		if ( ! SPDB_Membership_Guard::current_user_is_approved() ) {
			return false;
		}

		if ( in_array( $capability, self::verified_session_capabilities(), true ) ) {
			$assertions = SPDB_Membership_Guard::assertions( get_current_user_id() );
			return is_array( $assertions ) && true === ( $assertions['session_two_factor'] ?? false );
		}

		return true;
	}
}
