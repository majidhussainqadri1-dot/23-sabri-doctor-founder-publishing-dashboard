<?php
/**
 * Resolve the current role-aware dashboard workspace without trusting labels.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Workspace_Resolver {
	public function resolve( int $user_id ): array {
		if ( $user_id < 1 || $user_id !== get_current_user_id() ) {
			return $this->workspace( 'denied', __( 'Access Denied', 'sabri-publishing-dashboard' ), true, 'unknown', $user_id );
		}
		$status = SPDB_Membership_Guard::user_status( $user_id );
		if ( ! SPDB_Membership_Guard::is_available() ) {
			return $this->workspace( 'dependency_unavailable', __( 'Dependency Unavailable', 'sabri-publishing-dashboard' ), true, $status, $user_id );
		}
		if ( ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id ) || ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' ) ) {
			return $this->workspace( 'denied', __( 'Access Denied', 'sabri-publishing-dashboard' ), true, $status, $user_id );
		}
		if ( ! SPDB_Membership_Guard::is_user_approved( $user_id ) ) {
			return $this->workspace( 'restricted', __( 'Restricted Read-Only Workspace', 'sabri-publishing-dashboard' ), true, $status, $user_id );
		}
		if ( SPDB_Membership_Guard::is_user_founder( $user_id ) ) {
			return $this->workspace( 'founder', __( 'Founder Publishing Workspace', 'sabri-publishing-dashboard' ), false, $status, $user_id );
		}
		if ( SPDB_Membership_Guard::is_user_trusted_publisher( $user_id ) ) {
			return $this->workspace( 'trusted_doctor', __( 'Trusted Doctor Publishing Workspace', 'sabri-publishing-dashboard' ), false, $status, $user_id );
		}
		return $this->workspace( 'doctor', __( 'Doctor Publishing Workspace', 'sabri-publishing-dashboard' ), false, $status, $user_id );
	}

	/** @param array<string,mixed> $workspace @return array<string,array<string,string>> */
	public function navigation( array $workspace ): array {
		$items = array( 'overview' => $this->item( 'overview', __( 'Overview', 'sabri-publishing-dashboard' ) ) );
		$key = (string) ( $workspace['key'] ?? 'denied' );
		if ( in_array( $key, array( 'denied', 'dependency_unavailable' ), true ) ) { return $items; }
		$workspace_label = __( 'Publishing Workspace', 'sabri-publishing-dashboard' );
		if ( 'founder' === $key ) { $workspace_label = __( 'Founder Workspace', 'sabri-publishing-dashboard' ); }
		elseif ( in_array( $key, array( 'doctor', 'trusted_doctor' ), true ) ) { $workspace_label = __( 'Doctor Workspace', 'sabri-publishing-dashboard' ); }
		elseif ( 'restricted' === $key ) { $workspace_label = __( 'Publishing Status', 'sabri-publishing-dashboard' ); }
		$items['workspace'] = $this->item( 'workspace', $workspace_label );
		if ( SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ) ) { $items['create'] = $this->item( 'create', __( 'Create', 'sabri-publishing-dashboard' ) ); }
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) { $items['inventory'] = $this->item( 'inventory', __( 'My Content', 'sabri-publishing-dashboard' ) ); }
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_review_queue' ) ) { $items['review'] = $this->item( 'review', __( 'Review', 'sabri-publishing-dashboard' ) ); }
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			$items['calendar'] = $this->item( 'calendar', __( 'Calendar', 'sabri-publishing-dashboard' ) );
			if ( SPDB_Membership_Guard::current_user_is_approved() ) {
				$items['collections'] = $this->item( 'collections', __( 'Series & Collections', 'sabri-publishing-dashboard' ) );
				$items['knowledge'] = $this->item( 'knowledge', __( 'Knowledge', 'sabri-publishing-dashboard' ) );
				$items['sources'] = $this->item( 'sources', __( 'Sources & Evidence', 'sabri-publishing-dashboard' ) );
				$items['media'] = $this->item( 'media', __( 'Media Usage', 'sabri-publishing-dashboard' ) );
				$items['revisions'] = $this->item( 'revisions', __( 'Corrections & Retractions', 'sabri-publishing-dashboard' ) );
			}
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_manage_interactions' ) || SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) { $items['interactions'] = $this->item( 'interactions', __( 'Interactions', 'sabri-publishing-dashboard' ) ); }
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_own_analytics' ) || SPDB_Capabilities::current_user_can( 'spdb_view_global_analytics' ) ) { $items['analytics'] = $this->item( 'analytics', __( 'Analytics', 'sabri-publishing-dashboard' ) ); }
		if ( SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			$items['notifications'] = $this->item( 'notifications', __( 'Notifications', 'sabri-publishing-dashboard' ) );
			$this->append_native_professional_surfaces( $items );
		}
		if ( SPDB_Capabilities::current_user_can( 'spdb_manage_tasks' ) || SPDB_Capabilities::current_user_can( 'spdb_manage_delegations' ) ) { $items['tasks'] = $this->item( 'tasks', __( 'Team & Tasks', 'sabri-publishing-dashboard' ) ); }
		if ( SPDB_Capabilities::current_user_can( 'spdb_export_reports' ) ) { $items['reports'] = $this->item( 'reports', __( 'Reports', 'sabri-publishing-dashboard' ) ); }
		$items['saved-views'] = $this->item( 'saved-views', __( 'Saved Views', 'sabri-publishing-dashboard' ) );
		if ( SPDB_Capabilities::current_user_can( 'spdb_manage_dashboard_settings' ) ) { $items['settings'] = $this->item( 'settings', __( 'Settings', 'sabri-publishing-dashboard' ) ); }
		if ( SPDB_Capabilities::current_user_can( 'spdb_run_system_check' ) ) { $items['system-status'] = $this->item( 'system-status', __( 'System Status', 'sabri-publishing-dashboard' ) ); }
		return $items;
	}

	/** @param array<string,array<string,string>> $items */
	private function append_native_professional_surfaces( array &$items ): void {
		if ( ! SPDB_Membership_Guard::current_user_is_approved() ) { return; }
		$registry = SPDB_Provider_Registration::registry();
		if ( ! $registry instanceof SPDB_Adapter_Registry ) { return; }
		$surfaces = array(
			'appointments' => __( 'Appointments', 'sabri-publishing-dashboard' ),
			'messages' => __( 'Smail & Messages', 'sabri-publishing-dashboard' ),
			'reviews' => __( 'Doctor & Clinic Reviews', 'sabri-publishing-dashboard' ),
			'followers' => __( 'Followers', 'sabri-publishing-dashboard' ),
			'downloads' => __( 'Downloads', 'sabri-publishing-dashboard' ),
			'support' => __( 'Support & Appeals', 'sabri-publishing-dashboard' ),
			'learning' => __( 'Books, Courses & Learning', 'sabri-publishing-dashboard' ),
		);
		foreach ( $surfaces as $surface => $label ) {
			$contract = apply_filters( 'spdb_native_professional_surface_contract', array(), $surface, get_current_user_id() );
			if ( ! is_array( $contract ) || true !== ( $contract['enabled'] ?? null ) ) { continue; }
			$raw_provider = is_scalar( $contract['provider_key'] ?? null ) ? trim( (string) $contract['provider_key'] ) : '';
			$provider = sanitize_key( $raw_provider );
			$provider_version = is_scalar( $contract['provider_version'] ?? null ) ? trim( (string) $contract['provider_version'] ) : '';
			$contract_version = is_scalar( $contract['contract_version'] ?? null ) ? trim( (string) $contract['contract_version'] ) : '';
			$raw_capability = is_scalar( $contract['capability'] ?? null ) ? trim( (string) $contract['capability'] ) : '';
			$capability = sanitize_key( $raw_capability );
			if ( $provider !== $raw_provider || ! SPDB_Adapter_Registry::is_canonical_key( $provider ) || ! $this->is_semver( $provider_version ) || ! hash_equals( SPDB_CONTRACT_VERSION, $contract_version ) || '' === $capability || $capability !== $raw_capability ) { continue; }
			$metadata = $registry->metadata( $provider );
			if ( ! is_array( $metadata ) || ! hash_equals( $provider_version, (string) ( $metadata['provider_version'] ?? '' ) ) ) { continue; }
			if ( ! $this->provider_is_accepted( $registry, $provider ) ) { continue; }
			if ( ! current_user_can( $capability ) ) { continue; }
			$url = $this->same_origin_url( is_scalar( $contract['url'] ?? null ) ? (string) $contract['url'] : '' );
			if ( '' !== $url ) { $items[ 'native-' . $surface ] = array( 'label' => $label, 'url' => $url ); }
		}
	}

	private function provider_is_accepted( SPDB_Adapter_Registry $registry, string $provider ): bool {
		$state = $registry->get_acceptance_state( $provider );
		$environment = function_exists( 'wp_get_environment_type' ) ? sanitize_key( wp_get_environment_type() ) : 'production';
		if ( 'production' === $environment ) { return SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED === $state; }
		return in_array( $state, array( SPDB_Adapter_Registry::ACCEPTANCE_STAGING_ACCEPTED, SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ), true );
	}

	private function same_origin_url( string $url ): string {
		$url = esc_url_raw( trim( $url ), array( 'http', 'https' ) );
		if ( '' === $url ) { return ''; }
		$home = wp_parse_url( home_url( '/' ) ); $target = wp_parse_url( $url );
		if ( ! is_array( $home ) || ! is_array( $target ) || empty( $home['scheme'] ) || empty( $target['scheme'] ) || empty( $home['host'] ) || empty( $target['host'] ) || strtolower( (string) $home['scheme'] ) !== strtolower( (string) $target['scheme'] ) || strtolower( (string) $home['host'] ) !== strtolower( (string) $target['host'] ) || isset( $target['user'] ) || isset( $target['pass'] ) || isset( $target['fragment'] ) ) { return ''; }
		if ( $this->normalized_port( $home ) !== $this->normalized_port( $target ) ) { return ''; }
		return $url;
	}

	/** @param array<string,mixed> $parts */
	private function normalized_port( array $parts ): int {
		if ( isset( $parts['port'] ) ) { return (int) $parts['port']; }
		return 'https' === strtolower( (string) ( $parts['scheme'] ?? '' ) ) ? 443 : 80;
	}

	private function is_semver( string $version ): bool {
		return 1 === preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version );
	}

	/** @return array<string,string> */
	private function item( string $view, string $label ): array { return array( 'label' => $label, 'url' => SPDB_Dashboard_Router::route_url( $view ) ); }
	/** @return array<string,mixed> */
	private function workspace( string $key, string $label, bool $read_only, string $status, int $user_id ): array {
		return array( 'key' => $key, 'label' => $label, 'read_only' => $read_only, 'account_status' => $status, 'user_id' => $user_id );
	}
}
