<?php
/** Build the current user's Founder or Doctor publishing workspace. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Role_Workspace_Service {
	private const MAX_PROVIDERS = 20;
	private const MAX_CARDS = 48;
	private const MAX_ACTIONS = 24;
	private const MAX_ACTIVITY = 30;
	private const MAX_ALERTS = 30;
	private const MAX_PROFILES = 10;
	private const MAX_KNOWLEDGE = 10;
	private SPDB_Adapter_Registry $registry;

	public function __construct( SPDB_Adapter_Registry $registry ) { $this->registry = $registry; }

	/** @param array<string,mixed> $workspace @return array<string,mixed> */
	public function build( array $workspace ): array {
		$user_id = get_current_user_id();
		if ( $user_id < 1 || (int) ( $workspace['user_id'] ?? 0 ) !== $user_id ) {
			return $this->empty_projection( 'denied', 'denied' );
		}
		$context = $this->derive_context( $user_id );
		$key = (string) $context['workspace_key'];
		$result = $this->empty_projection( $key, '' );
		$result['read_only'] = (bool) $context['read_only'];
		$result['publishing_policy'] = $this->publishing_policy( $key );
		if ( in_array( $key, array( 'denied', 'dependency_unavailable' ), true ) ) {
			$result['alerts'][] = $this->system_alert( 'workspace_unavailable', 'critical', __( 'The role-specific publishing workspace is unavailable until identity and dependency checks pass.', 'sabri-publishing-dashboard' ) );
			return $result;
		}
		if ( ! empty( $context['read_only'] ) ) {
			$result['alerts'][] = $this->system_alert( 'workspace_read_only', 'warning', __( 'Your current account state permits a restricted read-only publishing workspace. Native management actions are disabled.', 'sabri-publishing-dashboard' ) );
		}

		$seen = array( 'cards' => array(), 'actions' => array(), 'activity' => array(), 'alerts' => array(), 'profiles' => array(), 'knowledge' => array() );
		$truncated = false;
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			if ( $result['provider_count'] >= self::MAX_PROVIDERS ) { $truncated = true; break; }
			if ( ! $adapter instanceof SPDB_Workspace_Provider_Adapter ) { continue; }
			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) || ! $this->provider_is_read_capable( $provider_key, $metadata ) ) { continue; }
			++$result['provider_count'];
			try { $raw = $adapter->get_workspace_projection( $context ); }
			catch ( Throwable $throwable ) { $raw = new WP_Error( 'spdb_workspace_provider_exception', __( 'The workspace provider failed.', 'sabri-publishing-dashboard' ) ); }
			if ( is_wp_error( $raw ) ) { ++$result['provider_errors']; continue; }
			$projection = SPDB_Workspace_Projection_Validator::normalize( $raw, $provider_key, $metadata, $context );
			if ( is_wp_error( $projection ) ) { ++$result['provider_errors']; continue; }

			$truncated = $this->append_records( $result['cards'], $projection['cards'], $seen['cards'], self::MAX_CARDS, 'key' ) || $truncated;
			$truncated = $this->append_records( $result['activity'], $projection['activity'], $seen['activity'], self::MAX_ACTIVITY, 'key' ) || $truncated;
			$truncated = $this->append_records( $result['alerts'], $projection['alerts'], $seen['alerts'], self::MAX_ALERTS, 'key' ) || $truncated;

			$profile = is_array( $projection['profile'] ) ? $projection['profile'] : null;
			$knowledge = is_array( $projection['knowledge'] ) ? $projection['knowledge'] : null;
			foreach ( array_merge( $projection['actions'], $this->derived_actions( $profile, $knowledge, $metadata ) ) as $action ) {
				$signature = (string) $action['action_type'] . '|' . (string) $action['destination'];
				if ( isset( $seen['actions'][ $signature ] ) ) { continue; }
				$seen['actions'][ $signature ] = true;
				if ( ! $this->action_is_visible( $action, $context ) ) { ++$result['blocked_action_count']; continue; }
				if ( count( $result['actions'] ) >= self::MAX_ACTIONS ) { $truncated = true; continue; }
				$result['actions'][] = $action;
			}
			if ( null !== $profile ) {
				unset( $profile['edit_destination'], $profile['public_destination'] );
				$signature = (string) $profile['provider_key'] . ':' . (string) $profile['owner_user_id'];
				if ( ! isset( $seen['profiles'][ $signature ] ) ) {
					$seen['profiles'][ $signature ] = true;
					if ( count( $result['profiles'] ) < self::MAX_PROFILES ) { $result['profiles'][] = $profile; } else { $truncated = true; }
				}
			}
			if ( null !== $knowledge ) {
				unset( $knowledge['destination'] );
				$signature = (string) $knowledge['provider_key'] . ':' . (string) $knowledge['owner_user_id'];
				if ( ! isset( $seen['knowledge'][ $signature ] ) ) {
					$seen['knowledge'][ $signature ] = true;
					if ( count( $result['knowledge'] ) < self::MAX_KNOWLEDGE ) { $result['knowledge'][] = $knowledge; } else { $truncated = true; }
				}
			}
		}
		$this->sort_cards( $result['cards'] );
		$this->sort_activity( $result['activity'] );
		if ( 0 === $result['provider_count'] ) { $result['alerts'][] = $this->system_alert( 'workspace_provider_unavailable', 'information', __( 'No compatible role-workspace adapter is registered. Counts, profile status, knowledge links, and native launch destinations remain unavailable rather than being fabricated.', 'sabri-publishing-dashboard' ) ); }
		if ( $result['blocked_action_count'] > 0 ) { $result['alerts'][] = $this->system_alert( 'workspace_actions_gated', 'warning', __( 'One or more native launch actions are hidden because current capability, account-state, ownership, Founder policy, semantic action contract, or adapter-acceptance gates are not satisfied.', 'sabri-publishing-dashboard' ) ); }
		if ( $result['provider_errors'] > 0 ) { $result['alerts'][] = $this->system_alert( 'workspace_provider_errors', 'warning', __( 'One or more workspace providers returned an invalid or unavailable projection. Other valid providers remain available.', 'sabri-publishing-dashboard' ) ); }
		if ( $truncated ) { $result['alerts'][] = $this->system_alert( 'workspace_projection_bounded', 'information', __( 'The workspace projection reached a safety limit. Additional native records remain with their owning modules and are not displayed in this bounded view.', 'sabri-publishing-dashboard' ) ); }
		$result['alerts'] = array_slice( $result['alerts'], 0, self::MAX_ALERTS );
		return $result;
	}

	/** @return array<string,mixed> */
	private function derive_context( int $user_id ): array {
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		if ( ! SPDB_Membership_Guard::is_available() ) { return $this->context( $user_id, 'dependency_unavailable', 'dependency_unavailable', true, false, false, false, $environment ); }
		$status = SPDB_Membership_Guard::user_status( $user_id );
		if ( ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id ) ) { return $this->context( $user_id, 'denied', $status, true, false, false, false, $environment ); }
		$is_approved = SPDB_Membership_Guard::is_user_approved( $user_id );
		if ( ! $is_approved ) { return $this->context( $user_id, 'restricted', $status, true, false, false, false, $environment ); }
		$is_founder = function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
		$is_trusted = ! $is_founder && function_exists( 'smc_is_trusted_publisher' ) && smc_is_trusted_publisher( $user_id );
		$key = $is_founder ? 'founder' : ( $is_trusted ? 'trusted_doctor' : 'doctor' );
		return $this->context( $user_id, $key, $status, false, $is_founder, $is_trusted, true, $environment );
	}

	/** @return array<string,mixed> */
	private function context( int $user_id, string $key, string $status, bool $read_only, bool $is_founder, bool $is_trusted, bool $is_approved, string $environment ): array {
		return array( 'user_id' => $user_id, 'workspace_key' => $key, 'account_status' => $status, 'read_only' => $read_only, 'is_founder' => $is_founder, 'is_trusted' => $is_trusted, 'is_approved' => $is_approved, 'allowed_scopes' => $is_founder ? array( 'own', 'institution' ) : array( 'own' ), 'environment' => $environment );
	}

	private function provider_is_read_capable( string $provider_key, array $metadata ): bool {
		if ( SPDB_Adapter_Registry::ACCEPTANCE_REVOKED === $this->registry->get_acceptance_state( $provider_key ) ) { return false; }
		return in_array( (string) ( $metadata['declared_capability'] ?? '' ), array( SPDB_Adapter_Registry::CAPABILITY_READ_ONLY, SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE, SPDB_Adapter_Registry::CAPABILITY_REVIEW_CAPABLE ), true );
	}

	private function action_is_visible( array $action, array $context ): bool {
		$contract = SPDB_Workspace_Projection_Validator::action_contract( (string) ( $action['action_type'] ?? '' ) );
		if ( null === $contract || ( $action['required_capability'] ?? '' ) !== $contract['required_capability'] || ( $action['mutating'] ?? null ) !== $contract['mutating'] || ( $action['founder_only'] ?? null ) !== $contract['founder_only'] ) { return false; }
		if ( ! SPDB_Capabilities::current_user_can( $contract['required_capability'] ) ) { return false; }
		if ( $contract['founder_only'] && empty( $context['is_founder'] ) ) { return false; }
		if ( 'own' === ( $action['scope'] ?? '' ) && (int) ( $action['owner_user_id'] ?? 0 ) !== (int) $context['user_id'] ) { return false; }
		if ( 'institution' === ( $action['scope'] ?? '' ) && ( empty( $context['is_founder'] ) || 0 !== (int) ( $action['owner_user_id'] ?? -1 ) ) ) { return false; }
		if ( $contract['mutating'] && ( ! empty( $context['read_only'] ) || empty( $context['is_approved'] ) || ! $this->registry->is_environment_write_eligible( (string) $action['provider_key'] ) ) ) { return false; }
		return true;
	}

	/** @return array<int,array<string,mixed>> */
	private function derived_actions( ?array $profile, ?array $knowledge, array $metadata ): array {
		$actions = array();
		$provider_key = (string) ( $metadata['provider_key'] ?? '' );
		$declared = is_array( $metadata['supported_capabilities'] ?? null ) ? $metadata['supported_capabilities'] : array();
		if ( null !== $profile ) {
			if ( '' !== (string) ( $profile['edit_destination'] ?? '' ) && in_array( 'spdb_manage_own_content', $declared, true ) ) { $actions[] = $this->derived_action( $provider_key, 'profile_edit', __( 'Edit Profile', 'sabri-publishing-dashboard' ), __( 'Open the native profile editor.', 'sabri-publishing-dashboard' ), 'edit_profile', (string) $profile['edit_destination'], (int) $profile['owner_user_id'] ); }
			if ( '' !== (string) ( $profile['public_destination'] ?? '' ) && in_array( 'spdb_view_own_content', $declared, true ) ) { $actions[] = $this->derived_action( $provider_key, 'profile_public', __( 'View Public Profile', 'sabri-publishing-dashboard' ), __( 'Open the native public profile.', 'sabri-publishing-dashboard' ), 'view_public_profile', (string) $profile['public_destination'], (int) $profile['owner_user_id'] ); }
		}
		if ( null !== $knowledge && '' !== (string) ( $knowledge['destination'] ?? '' ) && in_array( 'spdb_manage_own_content', $declared, true ) ) { $actions[] = $this->derived_action( $provider_key, 'knowledge_open', __( 'Manage Knowledge Portfolio', 'sabri-publishing-dashboard' ), __( 'Open the native knowledge owner.', 'sabri-publishing-dashboard' ), 'open_knowledge', (string) $knowledge['destination'], (int) $knowledge['owner_user_id'] ); }
		return $actions;
	}

	private function derived_action( string $provider_key, string $key, string $label, string $description, string $type, string $destination, int $owner_user_id ): array {
		$contract = SPDB_Workspace_Projection_Validator::action_contract( $type );
		return array( 'provider_key' => $provider_key, 'key' => $key, 'label' => $label, 'description' => $description, 'action_type' => $type, 'destination' => $destination, 'required_capability' => $contract['required_capability'], 'mutating' => $contract['mutating'], 'founder_only' => $contract['founder_only'], 'scope' => 'own', 'owner_user_id' => $owner_user_id );
	}

	private function append_records( array &$target, array $incoming, array &$seen, int $limit, string $key_field ): bool {
		$truncated = false;
		foreach ( $incoming as $record ) {
			$signature = (string) ( $record['provider_key'] ?? 'system' ) . ':' . (string) ( $record[ $key_field ] ?? '' );
			if ( isset( $seen[ $signature ] ) ) { continue; }
			$seen[ $signature ] = true;
			if ( count( $target ) >= $limit ) { $truncated = true; continue; }
			$target[] = $record;
		}
		return $truncated;
	}

	private function sort_cards( array &$cards ): void {
		$weights = array( 'critical' => 0, 'warning' => 1, 'information' => 2 );
		usort( $cards, static function ( array $left, array $right ) use ( $weights ): int { $priority = ( $weights[ $left['priority'] ] ?? 9 ) <=> ( $weights[ $right['priority'] ] ?? 9 ); return 0 !== $priority ? $priority : strcasecmp( (string) $left['label'], (string) $right['label'] ); } );
	}
	private function sort_activity( array &$activity ): void { usort( $activity, static fn( array $left, array $right ): int => strcmp( (string) $right['occurred_at'], (string) $left['occurred_at'] ) ); }

	private function publishing_policy( string $workspace_key ): array {
		if ( 'founder' === $workspace_key ) { return array( 'mode' => 'founder_official', 'label' => __( 'Founder Official Publishing', 'sabri-publishing-dashboard' ), 'summary' => __( 'Official publications may use a native direct-publication path only when the provider is accepted and privacy, security, malware, copyright, and legal blockers are clear.', 'sabri-publishing-dashboard' ), 'content_classes' => array( 'Founder Update', 'Official Guidance', 'Platform Announcement', 'Platform News', 'Breaking News', 'Book Announcement', 'Research Announcement', 'Clinic Announcement', 'Institution-wide Correction', 'Retraction Notice', 'Pinned Official Publication' ) ); }
		if ( in_array( $workspace_key, array( 'doctor', 'trusted_doctor' ), true ) ) { return array( 'mode' => 'trusted_doctor' === $workspace_key ? 'trusted_professional' : 'doctor_reviewed', 'label' => __( 'Doctor Professional Publishing', 'sabri-publishing-dashboard' ), 'summary' => 'trusted_doctor' === $workspace_key ? __( 'Trusted publishing remains limited to explicitly allowed categories and native policy. Every action still requires current verification, ownership, capability, and accepted provider status.', 'sabri-publishing-dashboard' ) : __( 'Doctor publications use the native Submit for Review path by default. Patient consent, anonymity, references, medical-claim restrictions, and reviewer feedback remain authoritative.', 'sabri-publishing-dashboard' ), 'content_classes' => array( 'Articles', 'Clinical Education', 'Patient Education', 'Successful Cases', 'Remedy Notes', 'Disease Notes', 'Materia Medica', 'Repertory', 'Research', 'Nutrition', 'Preventive Health', 'Videos', 'Reels', 'PDFs', 'Q&A' ) ); }
		return array( 'mode' => 'restricted', 'label' => __( 'Restricted Publishing Status', 'sabri-publishing-dashboard' ), 'summary' => __( 'Publishing actions remain unavailable while the account is pending, suspended, rejected, expired, or otherwise not approved.', 'sabri-publishing-dashboard' ), 'content_classes' => array() );
	}

	private function empty_projection( string $workspace_key, string $reason ): array {
		return array( 'workspace_key' => $workspace_key, 'read_only' => true, 'cards' => array(), 'actions' => array(), 'profiles' => array(), 'knowledge' => array(), 'activity' => array(), 'alerts' => '' === $reason ? array() : array( $this->system_alert( 'workspace_' . $reason, 'critical', __( 'The publishing workspace could not be resolved safely.', 'sabri-publishing-dashboard' ) ) ), 'provider_count' => 0, 'provider_errors' => 0, 'blocked_action_count' => 0, 'generated_at_gmt' => gmdate( 'c' ), 'publishing_policy' => $this->publishing_policy( 'restricted' ) );
	}
	private function system_alert( string $key, string $level, string $message ): array { return array( 'provider_key' => 'system', 'key' => $key, 'level' => $level, 'message' => $message, 'scope' => 'own', 'owner_user_id' => get_current_user_id() ); }
}
