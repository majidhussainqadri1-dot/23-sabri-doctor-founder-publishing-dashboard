<?php
/**
 * Provision File 23 capabilities onto existing WordPress and File 00 roles.
 *
 * File 23 never creates roles. Plugin activation is the administrator-approved
 * process that attaches this plugin's capability keys to roles that already
 * exist. Membership Core account-state checks remain mandatory at runtime.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Capability_Installer {
	private const SCHEMA_VERSION     = '2';
	private const VERSION_OPTION     = 'spdb_capability_schema_version';
	private const FINGERPRINT_OPTION = 'spdb_capability_role_fingerprint';

	/**
	 * Return the least-privilege capability matrix for existing roles.
	 *
	 * @return array<string,string[]>
	 */
	public static function role_matrix(): array {
		$restricted = SPDB_Capabilities::restricted_view_capabilities();

		return array(
			'administrator'          => SPDB_Capabilities::all(),
			'sabri_pending'          => $restricted,
			'sabri_doctor'           => array_merge(
				$restricted,
				array(
					'spdb_manage_own_content',
					'spdb_view_own_analytics',
					'spdb_manage_schedule',
					'spdb_manage_interactions',
					'spdb_manage_tasks',
					'spdb_export_reports',
					'spdb_request_ai_assistance',
				)
			),
			'sabri_verified_doctor'  => array_merge(
				$restricted,
				array(
					'spdb_manage_own_content',
					'spdb_view_own_analytics',
					'spdb_manage_schedule',
					'spdb_manage_interactions',
					'spdb_manage_tasks',
					'spdb_export_reports',
					'spdb_request_ai_assistance',
				)
			),
			'sabri_medical_reviewer' => array(
				'spdb_view_dashboard',
				'spdb_view_own_content',
				'spdb_view_review_queue',
				'spdb_review_assigned_content',
				'spdb_manage_tasks',
			),
			'sabri_moderator'        => array(
				'spdb_view_dashboard',
				'spdb_manage_interactions',
				'spdb_manage_tasks',
			),
		);
	}

	/**
	 * Attach missing capabilities to roles that already exist.
	 *
	 * @return array<string,mixed> Non-sensitive installation result.
	 */
	public static function ensure(): array {
		$available_roles = array();
		$granted         = 0;

		foreach ( self::role_matrix() as $role_key => $capabilities ) {
			$role = get_role( $role_key );
			if ( ! $role ) {
				continue;
			}

			$available_roles[] = $role_key;
			foreach ( array_values( array_unique( $capabilities ) ) as $capability ) {
				if ( ! in_array( $capability, SPDB_Capabilities::all(), true ) ) {
					continue;
				}

				if ( empty( $role->capabilities[ $capability ] ) ) {
					$role->add_cap( $capability, true );
					++$granted;
				}
			}
		}

		sort( $available_roles );
		$fingerprint = self::fingerprint( $available_roles );
		update_option( self::VERSION_OPTION, self::SCHEMA_VERSION, false );
		update_option( self::FINGERPRINT_OPTION, $fingerprint, false );

		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'roles_seen'     => $available_roles,
			'capabilities_added' => $granted,
			'fingerprint'    => $fingerprint,
		);
	}

	/**
	 * Reconcile after File 00 creates or changes its existing role inventory.
	 */
	public static function maybe_upgrade(): void {
		$available_roles = self::available_role_keys();
		$fingerprint     = self::fingerprint( $available_roles );

		if (
			self::SCHEMA_VERSION !== (string) get_option( self::VERSION_OPTION, '' )
			|| $fingerprint !== (string) get_option( self::FINGERPRINT_OPTION, '' )
		) {
			self::ensure();
		}
	}

	/**
	 * @return string[]
	 */
	private static function available_role_keys(): array {
		$available = array();
		foreach ( array_keys( self::role_matrix() ) as $role_key ) {
			if ( get_role( $role_key ) ) {
				$available[] = $role_key;
			}
		}
		sort( $available );
		return $available;
	}

	/**
	 * @param string[] $role_keys Existing role keys.
	 */
	private static function fingerprint( array $role_keys ): string {
		return hash( 'sha256', implode( '|', $role_keys ) );
	}
}
