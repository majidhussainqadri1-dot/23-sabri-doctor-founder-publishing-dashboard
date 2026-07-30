<?php
/**
 * Build the current user's Founder or Doctor publishing workspace.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Role_Workspace_Service {
	private SPDB_Adapter_Registry $registry;

	public function __construct( SPDB_Adapter_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * @param array<string,mixed> $workspace Server-resolved workspace.
	 * @return array<string,mixed>
	 */
	public function build( array $workspace ): array {
		$user_id = get_current_user_id();
		if ( $user_id < 1 || (int) ( $workspace['user_id'] ?? 0 ) !== $user_id ) {
			return $this->empty_projection( $workspace, 'denied' );
		}

		$key         = (string) ( $workspace['key'] ?? 'denied' );
		$read_only   = ! empty( $workspace['read_only'] );
		$is_founder  = 'founder' === $key && function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
		$is_trusted  = 'trusted_doctor' === $key && function_exists( 'smc_is_trusted_publisher' ) && smc_is_trusted_publisher( $user_id );
		$is_approved = SPDB_Membership_Guard::is_user_approved( $user_id );

		$context = array(
			'user_id'        => $user_id,
			'workspace_key'  => $key,
			'account_status' => (string) ( $workspace['account_status'] ?? 'unknown' ),
			'read_only'      => $read_only,
			'is_founder'     => $is_founder,
			'is_trusted'     => $is_trusted,
			'is_approved'    => $is_approved,
			'allowed_scopes' => $is_founder ? array( 'own', 'institution' ) : array( 'own' ),
			'environment'    => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		);

		$result = array(
			'workspace_key'          => $key,
			'read_only'              => $read_only,
			'cards'                  => array(),
			'actions'                => array(),
			'profiles'               => array(),
			'knowledge'              => array(),
			'activity'               => array(),
			'alerts'                 => array(),
			'provider_count'         => 0,
			'provider_errors'        => 0,
			'blocked_action_count'   => 0,
			'generated_at_gmt'       => current_time( 'mysql', true ),
			'publishing_policy'      => $this->publishing_policy( $key ),
		);

		if ( in_array( $key, array( 'denied', 'dependency_unavailable' ), true ) ) {
			$result['alerts'][] = array(
				'key'     => 'workspace_unavailable',
				'level'   => 'critical',
				'message' => __( 'The role-specific publishing workspace is unavailable until identity and dependency checks pass.', 'sabri-publishing-dashboard' ),
			);
			return $result;
		}

		if ( $read_only ) {
			$result['alerts'][] = array(
				'key'     => 'workspace_read_only',
				'level'   => 'warning',
				'message' => __( 'Your current account state permits a restricted read-only publishing workspace. Native publishing actions are disabled.', 'sabri-publishing-dashboard' ),
			);
		}

		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			if ( ! $adapter instanceof SPDB_Workspace_Provider_Adapter ) {
				continue;
			}

			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) || ! $this->provider_is_read_capable( $provider_key, $metadata ) ) {
				continue;
			}

			++$result['provider_count'];
			try {
				$raw = $adapter->get_workspace_projection( $context );
			} catch ( Throwable $throwable ) {
				$raw = new WP_Error( 'spdb_workspace_provider_exception', __( 'The workspace provider failed.', 'sabri-publishing-dashboard' ) );
			}

			if ( is_wp_error( $raw ) ) {
				++$result['provider_errors'];
				continue;
			}

			$projection = SPDB_Workspace_Projection_Validator::normalize( $raw, $provider_key, $metadata, $context );
			if ( is_wp_error( $projection ) ) {
				++$result['provider_errors'];
				continue;
			}

			$result['cards']   = array_merge( $result['cards'], $projection['cards'] );
			$result['activity']= array_merge( $result['activity'], $projection['activity'] );
			$result['alerts']  = array_merge( $result['alerts'], $projection['alerts'] );

			if ( is_array( $projection['profile'] ) ) {
				$result['profiles'][] = $projection['profile'];
			}
			if ( is_array( $projection['knowledge'] ) ) {
				$result['knowledge'][] = $projection['knowledge'];
			}

			foreach ( $projection['actions'] as $action ) {
				if ( $this->action_is_visible( $action, $context ) ) {
					$result['actions'][] = $action;
				} else {
					++$result['blocked_action_count'];
				}
			}
		}

		$this->sort_cards( $result['cards'] );
		$this->sort_activity( $result['activity'] );
		$result['activity'] = array_slice( $result['activity'], 0, 30 );

		if ( 0 === $result['provider_count'] ) {
			$result['alerts'][] = array(
				'key'     => 'workspace_provider_unavailable',
				'level'   => 'information',
				'message' => __( 'No compatible role-workspace adapter is registered. Counts, profile status, knowledge links, and native launch destinations remain unavailable rather than being fabricated.', 'sabri-publishing-dashboard' ),
			);
		}
		if ( $result['blocked_action_count'] > 0 ) {
			$result['alerts'][] = array(
				'key'     => 'workspace_actions_gated',
				'level'   => 'warning',
				'message' => __( 'One or more native launch actions are hidden because current capability, account-state, ownership, Founder policy, or adapter-acceptance gates are not satisfied.', 'sabri-publishing-dashboard' ),
			);
		}
		if ( $result['provider_errors'] > 0 ) {
			$result['alerts'][] = array(
				'key'     => 'workspace_provider_errors',
				'level'   => 'warning',
				'message' => __( 'One or more workspace providers returned an invalid or unavailable projection. Other valid providers remain available.', 'sabri-publishing-dashboard' ),
			);
		}

		return $result;
	}

	/** @param array<string,mixed> $metadata */
	private function provider_is_read_capable( string $provider_key, array $metadata ): bool {
		if ( SPDB_Adapter_Registry::ACCEPTANCE_REVOKED === $this->registry->get_acceptance_state( $provider_key ) ) {
			return false;
		}
		$declared = (string) ( $metadata['declared_capability'] ?? SPDB_Adapter_Registry::CAPABILITY_UNAVAILABLE );
		return in_array(
			$declared,
			array(
				SPDB_Adapter_Registry::CAPABILITY_READ_ONLY,
				SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE,
				SPDB_Adapter_Registry::CAPABILITY_REVIEW_CAPABLE,
			),
			true
		);
	}

	/**
	 * @param array<string,mixed> $action
	 * @param array<string,mixed> $context
	 */
	private function action_is_visible( array $action, array $context ): bool {
		if ( ! SPDB_Capabilities::current_user_can( (string) $action['required_capability'] ) ) {
			return false;
		}
		if ( ! empty( $action['founder_only'] ) && empty( $context['is_founder'] ) ) {
			return false;
		}
		if ( 'official_create' === $action['action_type'] && empty( $context['is_founder'] ) ) {
			return false;
		}
		if ( ! empty( $action['mutating'] ) ) {
			if ( ! empty( $context['read_only'] ) || empty( $context['is_approved'] ) ) {
				return false;
			}
			if ( ! $this->registry->is_environment_write_eligible( (string) $action['provider_key'] ) ) {
				return false;
			}
		}
		return true;
	}

	/** @param array<int,array<string,mixed>> $cards */
	private function sort_cards( array &$cards ): void {
		$weights = array( 'critical' => 0, 'warning' => 1, 'information' => 2 );
		usort(
			$cards,
			static function ( array $left, array $right ) use ( $weights ): int {
				$priority = ( $weights[ $left['priority'] ] ?? 9 ) <=> ( $weights[ $right['priority'] ] ?? 9 );
				return 0 !== $priority ? $priority : strcasecmp( (string) $left['label'], (string) $right['label'] );
			}
		);
	}

	/** @param array<int,array<string,mixed>> $activity */
	private function sort_activity( array &$activity ): void {
		usort(
			$activity,
			static function ( array $left, array $right ): int {
				return strcmp( (string) $right['occurred_at'], (string) $left['occurred_at'] );
			}
		);
	}

	/** @return array<string,mixed> */
	private function publishing_policy( string $workspace_key ): array {
		if ( 'founder' === $workspace_key ) {
			return array(
				'mode'            => 'founder_official',
				'label'           => __( 'Founder Official Publishing', 'sabri-publishing-dashboard' ),
				'summary'         => __( 'Official publications may use a native direct-publication path only when the provider is accepted and privacy, security, malware, copyright, and legal blockers are clear.', 'sabri-publishing-dashboard' ),
				'content_classes' => array(
					'Founder Update', 'Official Guidance', 'Platform Announcement', 'Platform News', 'Breaking News',
					'Book Announcement', 'Research Announcement', 'Clinic Announcement', 'Institution-wide Correction',
					'Retraction Notice', 'Pinned Official Publication',
				),
			);
		}
		if ( in_array( $workspace_key, array( 'doctor', 'trusted_doctor' ), true ) ) {
			return array(
				'mode'            => 'trusted_doctor' === $workspace_key ? 'trusted_professional' : 'doctor_reviewed',
				'label'           => __( 'Doctor Professional Publishing', 'sabri-publishing-dashboard' ),
				'summary'         => 'trusted_doctor' === $workspace_key
					? __( 'Trusted publishing remains limited to explicitly allowed categories and native policy. Every action still requires current verification, ownership, capability, and accepted provider status.', 'sabri-publishing-dashboard' )
					: __( 'Doctor publications use the native Submit for Review path by default. Patient consent, anonymity, references, medical-claim restrictions, and reviewer feedback remain authoritative.', 'sabri-publishing-dashboard' ),
				'content_classes' => array(
					'Articles', 'Clinical Education', 'Patient Education', 'Successful Cases', 'Remedy Notes', 'Disease Notes',
					'Materia Medica', 'Repertory', 'Research', 'Nutrition', 'Preventive Health', 'Videos', 'Reels', 'PDFs', 'Q&A',
				),
			);
		}
		return array(
			'mode'            => 'restricted',
			'label'           => __( 'Restricted Publishing Status', 'sabri-publishing-dashboard' ),
			'summary'         => __( 'Publishing actions remain unavailable while the account is pending, suspended, rejected, expired, or otherwise not approved.', 'sabri-publishing-dashboard' ),
			'content_classes' => array(),
		);
	}

	/** @return array<string,mixed> */
	private function empty_projection( array $workspace, string $reason ): array {
		return array(
			'workspace_key'        => (string) ( $workspace['key'] ?? 'denied' ),
			'read_only'            => true,
			'cards'                => array(),
			'actions'              => array(),
			'profiles'             => array(),
			'knowledge'            => array(),
			'activity'             => array(),
			'alerts'               => array(
				array(
					'key'     => 'workspace_' . $reason,
					'level'   => 'critical',
					'message' => __( 'The publishing workspace could not be resolved safely.', 'sabri-publishing-dashboard' ),
				),
			),
			'provider_count'       => 0,
			'provider_errors'      => 0,
			'blocked_action_count' => 0,
			'generated_at_gmt'     => current_time( 'mysql', true ),
			'publishing_policy'    => $this->publishing_policy( 'restricted' ),
		);
	}
}
