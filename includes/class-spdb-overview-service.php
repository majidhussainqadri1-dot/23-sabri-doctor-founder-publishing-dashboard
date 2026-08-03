<?php
/**
 * Build truthful dashboard overview data without fabricated publishing counts.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Overview_Service {
	private SPDB_System_State $system_state;

	public function __construct( SPDB_System_State $system_state ) {
		$this->system_state = $system_state;
	}

	/**
	 * @param array<string,mixed> $workspace Resolved workspace.
	 * @return array<string,mixed>
	 */
	public function build( array $workspace ): array {
		$state  = $this->system_state->snapshot( $workspace );
		$alerts = array();

		if ( ! $state['membership']['available'] ) {
			$alerts[] = array(
				'level'   => 'critical',
				'message' => __( 'Sabri Membership Core is unavailable or incompatible. The dashboard is fail-closed.', 'sabri-publishing-dashboard' ),
			);
		}

		if ( $workspace['read_only'] ) {
			$alerts[] = array(
				'level'   => 'warning',
				'message' => __( 'This account currently has a restricted read-only workspace. Publishing actions are not available.', 'sabri-publishing-dashboard' ),
			);
		}

		if ( 0 === $state['provider_count'] ) {
			$alerts[] = array(
				'level'   => 'information',
				'message' => __( 'No native content provider is registered yet. Content totals are intentionally not fabricated.', 'sabri-publishing-dashboard' ),
			);
		}

		if ( $state['provider_errors'] > 0 ) {
			$alerts[] = array(
				'level'   => 'warning',
				'message' => sprintf(
					/* translators: %d: bounded provider registration error count. */
					_n( '%d provider registration error requires attention.', '%d provider registration errors require attention.', $state['provider_errors'], 'sabri-publishing-dashboard' ),
					$state['provider_errors']
				),
			);
		}

		return array(
			'cards' => array(
				array(
					'label' => __( 'Workspace', 'sabri-publishing-dashboard' ),
					'value' => $workspace['label'],
					'note'  => $workspace['read_only'] ? __( 'Read-only', 'sabri-publishing-dashboard' ) : __( 'Operational access', 'sabri-publishing-dashboard' ),
				),
				array(
					'label' => __( 'Account Status', 'sabri-publishing-dashboard' ),
					'value' => ucwords( str_replace( '_', ' ', (string) $workspace['account_status'] ) ),
					'note'  => __( 'Supplied by File 00', 'sabri-publishing-dashboard' ),
				),
				array(
					'label' => __( 'Registered Providers', 'sabri-publishing-dashboard' ),
					'value' => (string) $state['provider_count'],
					'note'  => __( 'Federated adapters only', 'sabri-publishing-dashboard' ),
				),
				array(
					'label' => __( 'Production Writes', 'sabri-publishing-dashboard' ),
					'value' => __( 'Disabled', 'sabri-publishing-dashboard' ),
					'note'  => __( 'Phase 23C safety boundary', 'sabri-publishing-dashboard' ),
				),
			),
			'alerts'       => $alerts,
			'providers'    => $state['providers'],
			'system_state' => $state,
		);
	}
}
