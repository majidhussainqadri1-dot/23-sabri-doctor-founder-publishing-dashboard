<?php
/**
 * Resolve the current role-aware dashboard workspace without trusting labels.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Workspace_Resolver {
	/**
	 * Resolve only the currently authenticated user. File 23 must not use this
	 * current-user capability path to project another person's workspace or
	 * Membership Core status.
	 *
	 * @return array<string,mixed>
	 */
	public function resolve( int $user_id ): array {
		if ( $user_id < 1 || $user_id !== get_current_user_id() ) {
			return $this->workspace( 'denied', __( 'Access Denied', 'sabri-publishing-dashboard' ), true, 'unknown', $user_id );
		}

		$status = SPDB_Membership_Guard::user_status( $user_id );
		if ( ! SPDB_Membership_Guard::is_available() ) {
			return $this->workspace( 'dependency_unavailable', __( 'Dependency Unavailable', 'sabri-publishing-dashboard' ), true, $status, $user_id );
		}

		if (
			! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id )
			|| ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' )
		) {
			return $this->workspace( 'denied', __( 'Access Denied', 'sabri-publishing-dashboard' ), true, $status, $user_id );
		}

		if ( ! SPDB_Membership_Guard::is_user_approved( $user_id ) ) {
			return $this->workspace( 'restricted', __( 'Restricted Read-Only Workspace', 'sabri-publishing-dashboard' ), true, $status, $user_id );
		}

		if ( function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id ) ) {
			return $this->workspace( 'founder', __( 'Founder Publishing Workspace', 'sabri-publishing-dashboard' ), false, $status, $user_id );
		}

		if ( function_exists( 'smc_is_trusted_publisher' ) && smc_is_trusted_publisher( $user_id ) ) {
			return $this->workspace( 'trusted_doctor', __( 'Trusted Doctor Publishing Workspace', 'sabri-publishing-dashboard' ), false, $status, $user_id );
		}

		return $this->workspace( 'doctor', __( 'Doctor Publishing Workspace', 'sabri-publishing-dashboard' ), false, $status, $user_id );
	}

	/**
	 * Return only implemented navigation destinations.
	 *
	 * @param array<string,mixed> $workspace Resolved workspace.
	 * @return array<string,array<string,string>>
	 */
	public function navigation( array $workspace ): array {
		$items = array(
			'overview' => $this->item( 'overview', __( 'Overview', 'sabri-publishing-dashboard' ) ),
		);
		$key = (string) ( $workspace['key'] ?? 'denied' );
		if ( in_array( $key, array( 'denied', 'dependency_unavailable' ), true ) ) {
			return $items;
		}

		$workspace_label = __( 'Publishing Workspace', 'sabri-publishing-dashboard' );
		if ( 'founder' === $key ) {
			$workspace_label = __( 'Founder Workspace', 'sabri-publishing-dashboard' );
		} elseif ( in_array( $key, array( 'doctor', 'trusted_doctor' ), true ) ) {
			$workspace_label = __( 'Doctor Workspace', 'sabri-publishing-dashboard' );
		} elseif ( 'restricted' === $key ) {
			$workspace_label = __( 'Publishing Status', 'sabri-publishing-dashboard' );
		}
		$items['workspace'] = $this->item( 'workspace', $workspace_label );

		if ( SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ) ) {
			$items['create'] = $this->item( 'create', __( 'Create', 'sabri-publishing-dashboard' ) );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			$items['inventory'] = $this->item( 'inventory', __( 'My Content', 'sabri-publishing-dashboard' ) );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_review_queue' ) ) {
			$items['review'] = $this->item( 'review', __( 'Review', 'sabri-publishing-dashboard' ) );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			$items['calendar'] = $this->item( 'calendar', __( 'Calendar', 'sabri-publishing-dashboard' ) );
			if ( SPDB_Membership_Guard::current_user_is_approved() ) {
				$items['collections'] = $this->item( 'collections', __( 'Series & Collections', 'sabri-publishing-dashboard' ) );
				$items['knowledge']   = $this->item( 'knowledge', __( 'Knowledge', 'sabri-publishing-dashboard' ) );
				$items['sources']     = $this->item( 'sources', __( 'Sources & Evidence', 'sabri-publishing-dashboard' ) );
				$items['media']       = $this->item( 'media', __( 'Media Usage', 'sabri-publishing-dashboard' ) );
				$items['revisions']   = $this->item( 'revisions', __( 'Corrections & Retractions', 'sabri-publishing-dashboard' ) );
			}
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_manage_interactions' ) || SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			$items['interactions'] = $this->item( 'interactions', __( 'Interactions', 'sabri-publishing-dashboard' ) );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_own_analytics' ) || SPDB_Capabilities::current_user_can( 'spdb_view_global_analytics' ) ) {
			$items['analytics'] = $this->item( 'analytics', __( 'Analytics', 'sabri-publishing-dashboard' ) );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			$items['notifications'] = $this->item( 'notifications', __( 'Notifications', 'sabri-publishing-dashboard' ) );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_manage_tasks' ) || SPDB_Capabilities::current_user_can( 'spdb_manage_delegations' ) ) {
			$items['tasks'] = $this->item( 'tasks', __( 'Team & Tasks', 'sabri-publishing-dashboard' ) );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_export_reports' ) ) {
			$items['reports'] = $this->item( 'reports', __( 'Reports', 'sabri-publishing-dashboard' ) );
		}
		$items['saved-views'] = $this->item( 'saved-views', __( 'Saved Views', 'sabri-publishing-dashboard' ) );

		if ( SPDB_Capabilities::current_user_can( 'spdb_manage_dashboard_settings' ) ) {
			$items['settings'] = $this->item( 'settings', __( 'Settings', 'sabri-publishing-dashboard' ) );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_run_system_check' ) ) {
			$items['system-status'] = $this->item( 'system-status', __( 'System Status', 'sabri-publishing-dashboard' ) );
		}
		return $items;
	}

	/** @return array<string,string> */
	private function item( string $view, string $label ): array {
		return array( 'label' => $label, 'url' => SPDB_Dashboard_Router::route_url( $view ) );
	}

	/** @return array<string,mixed> */
	private function workspace( string $key, string $label, bool $read_only, string $status, int $user_id ): array {
		return array(
			'key'            => $key,
			'label'          => $label,
			'read_only'      => $read_only,
			'account_status' => $status,
			'user_id'        => $user_id,
		);
	}
}
