<?php
/**
 * Build a non-sensitive dashboard system-state snapshot.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_System_State {
	private SPDB_Adapter_Registry $registry;

	public function __construct( SPDB_Adapter_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * @param array<string,mixed> $workspace Resolved workspace.
	 * @return array<string,mixed>
	 */
	public function snapshot( array $workspace ): array {
		$providers = array();
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) ) {
				continue;
			}

			$providers[] = array(
				'provider_key'        => $provider_key,
				'provider_name'       => (string) $metadata['provider_name'],
				'provider_version'    => (string) $metadata['provider_version'],
				'declared_capability' => (string) $metadata['declared_capability'],
				'acceptance_state'    => (string) $metadata['acceptance_state'],
				'effective_state'     => $this->registry->get_effective_state( $provider_key ),
			);
		}

		$errors = 0;
		foreach ( $this->registry->registration_errors() as $provider_errors ) {
			$errors += is_array( $provider_errors ) ? count( $provider_errors ) : 0;
		}

		$membership = SPDB_Membership_Guard::health_snapshot();
		$degraded   = ! $membership['available'] || $errors > 0;

		return array(
			'environment'         => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'plugin_version'      => SPDB_VERSION,
			'contract_version'    => SPDB_CONTRACT_VERSION,
			'workspace'           => array(
				'key'            => (string) $workspace['key'],
				'label'          => (string) $workspace['label'],
				'read_only'      => (bool) $workspace['read_only'],
				'account_status' => (string) $workspace['account_status'],
			),
			'membership'          => $membership,
			'provider_count'      => count( $providers ),
			'provider_errors'     => $errors,
			'providers'           => $providers,
			'degraded'            => $degraded,
			'generated_at_gmt'    => gmdate( 'c' ),
			'production_writes'   => false,
			'phase'               => '23C',
		);
	}
}
