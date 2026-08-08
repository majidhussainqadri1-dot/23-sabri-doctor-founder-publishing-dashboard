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
	private const SCHEMA_VERSION     = '6';
	private const VERSION_OPTION     = 'spdb_capability_schema_version';
	private const FINGERPRINT_OPTION = 'spdb_capability_role_fingerprint';

	/**
	 * Return the least-privilege capability matrix for existing roles.
	 *
	 * Canonical File 00 keys are taken from its current role contract. Historic
	 * File 23 aliases are retained only for non-destructive migration support and
	 * are never created by File 23.
	 *
	 * @return array<string,string[]>
	 */
	public static function role_matrix(): array {
		$restricted = SPDB_Capabilities::restricted_view_capabilities();
		$doctor     = array_merge(
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
		);
		$reviewer = array(
			'spdb_view_dashboard',
			'spdb_view_own_content',
			'spdb_view_review_queue',
			'spdb_review_assigned_content',
			'spdb_manage_tasks',
		);

		return array(
			'administrator'                    => SPDB_Capabilities::all(),
			/* Current File 00 doctor lifecycle roles. */
			'sabri_doctor_pending'             => $restricted,
			'sabri_doctor_verified'            => $doctor,
			/* Current File 00 verification-review roles. */
			'sabri_membership_reviewer'        => $reviewer,
			'sabri_membership_senior_reviewer' => $reviewer,
			/* Historical aliases kept only where an existing site still has them. */
			'sabri_pending'                    => $restricted,
			'sabri_doctor'                     => $doctor,
			'sabri_verified_doctor'            => $doctor,
			'sabri_medical_reviewer'           => $reviewer,
			'sabri_moderator'                  => array(
				'spdb_view_dashboard',
				'spdb_manage_interactions',
				'spdb_manage_tasks',
			),
		);
	}

	/**
	 * Reconcile exact File 23 capabilities on roles that already exist.
	 *
	 * @return array<string,mixed> Non-sensitive installation result.
	 */
	public static function ensure(): array {
		$available_roles = array();
		$granted         = 0;
		$revoked         = 0;
		$canonical       = SPDB_Capabilities::all();
		$managed_keys    = array_values( array_unique( array_merge( $canonical, SPDB_Capabilities::retired() ) ) );

		foreach ( self::role_matrix() as $role_key => $capabilities ) {
			$role = get_role( $role_key );
			if ( ! $role ) {
				continue;
			}

			$available_roles[] = $role_key;
			$expected = array_values( array_unique( array_intersect( $capabilities, $canonical ) ) );

			foreach ( $managed_keys as $capability ) {
				$should_have = in_array( $capability, $expected, true );
				$has_cap     = ! empty( $role->capabilities[ $capability ] );
				if ( $should_have && ! $has_cap ) {
					$role->add_cap( $capability, true );
					++$granted;
				} elseif ( ! $should_have && $has_cap && method_exists( $role, 'remove_cap' ) ) {
					$role->remove_cap( $capability );
					++$revoked;
				}
			}
		}

		sort( $available_roles );
		$fingerprint = self::fingerprint( $available_roles );
		update_option( self::VERSION_OPTION, self::SCHEMA_VERSION, false );
		update_option( self::FINGERPRINT_OPTION, $fingerprint, false );

		return array(
			'schema_version'       => self::SCHEMA_VERSION,
			'roles_seen'           => $available_roles,
			'capabilities_added'   => $granted,
			'capabilities_removed' => $revoked,
			'fingerprint'          => $fingerprint,
		);
	}

	/** Reconcile after File 00 creates, changes, or repairs its role inventory. */
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

	/** @return string[] */
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

	/** @param string[] $role_keys Existing role keys. */
	private static function fingerprint( array $role_keys ): string {
		$state  = array();
		$matrix = self::role_matrix();
		foreach ( $role_keys as $role_key ) {
			$role = get_role( $role_key );
			$assigned = array();
			foreach ( array_merge( SPDB_Capabilities::all(), SPDB_Capabilities::retired() ) as $capability ) {
				$assigned[ $capability ] = $role && ! empty( $role->capabilities[ $capability ] );
			}
			$state[ $role_key ] = array(
				'expected' => array_values( array_unique( $matrix[ $role_key ] ?? array() ) ),
				'assigned' => $assigned,
			);
		}
		$json = wp_json_encode( array( 'schema' => self::SCHEMA_VERSION, 'state' => $state ) );
		return hash( 'sha256', is_string( $json ) ? $json : '' );
	}
}
