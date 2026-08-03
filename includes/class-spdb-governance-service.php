<?php
/**
 * File 23-owned tasks, scoped delegations, and bounded automation governance.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Governance_Service {
	private SPDB_Operations_Repository $repository;

	public function __construct( SPDB_Operations_Repository $repository ) {
		$this->repository = $repository;
	}

	public function is_institutional( int $user_id ): bool {
		$assertions = SPDB_Membership_Guard::assertions( $user_id );
		return is_array( $assertions ) && ! empty( $assertions['institutional_account'] );
	}

	/** @return array<string,mixed> */
	public function snapshot(): array {
		$user_id       = get_current_user_id();
		$institutional = $this->is_institutional( $user_id );
		$tasks         = $this->repository->list_tasks( $user_id, $institutional );
		$delegations   = $this->repository->list_delegations( $user_id, $institutional );
		$rules         = $this->repository->list_rules( $user_id, $institutional );

		return array(
			'tasks'            => is_array( $tasks ) ? $tasks : array(),
			'delegations'      => is_array( $delegations ) ? $delegations : array(),
			'automation_rules' => is_array( $rules ) ? $rules : array(),
			'institutional'    => $institutional,
			'settings'         => SPDB_Admin_Settings::get(),
		);
	}

	/**
	 * @param array<string,mixed> $input Raw task payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_task( array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_tasks' ) ) {
			return self::error( 'spdb_task_forbidden', __( 'You are not authorized to create tasks.', 'sabri-publishing-dashboard' ), 403 );
		}

		$user_id     = get_current_user_id();
		$title       = isset( $input['title'] ) && is_scalar( $input['title'] ) ? trim( wp_strip_all_tags( (string) $input['title'] ) ) : '';
		$description = isset( $input['description'] ) && is_scalar( $input['description'] ) ? trim( wp_strip_all_tags( (string) $input['description'] ) ) : '';
		$assignee    = max( 0, (int) ( $input['assignee_user_id'] ?? $user_id ) );
		$scope       = sanitize_key( (string) ( $input['scope'] ?? 'own' ) );
		$priority    = sanitize_key( (string) ( $input['priority'] ?? 'normal' ) );
		$reason      = isset( $input['audit_reason'] ) && is_scalar( $input['audit_reason'] ) ? trim( wp_strip_all_tags( (string) $input['audit_reason'] ) ) : '';

		if (
			'' === $title || self::length( $title ) > 200
			|| self::length( $description ) > 2000
			|| $assignee < 1
			|| ! in_array( $scope, array( 'own', 'institution' ), true )
			|| ! in_array( $priority, array( 'low', 'normal', 'high', 'urgent' ), true )
			|| '' === $reason || self::length( $reason ) > 500
		) {
			return self::error( 'spdb_task_invalid', __( 'The task payload is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		if ( 'institution' === $scope && ! $this->is_institutional( $user_id ) ) {
			return self::error( 'spdb_task_scope_forbidden', __( 'Institution task scope is not authorized.', 'sabri-publishing-dashboard' ), 403 );
		}
		if ( 'own' === $scope && $assignee !== $user_id ) {
			return self::error( 'spdb_task_assignee_forbidden', __( 'Personal tasks cannot be assigned to another account.', 'sabri-publishing-dashboard' ), 403 );
		}
		if ( ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $assignee ) ) {
			return self::error( 'spdb_task_assignee_invalid', __( 'The task assignee is not an eligible platform account.', 'sabri-publishing-dashboard' ), 400 );
		}

		$due_at = $this->datetime( $input['due_at_gmt'] ?? null, true );
		if ( is_wp_error( $due_at ) ) {
			return $due_at;
		}

		$provider_key = sanitize_key( (string) ( $input['provider_key'] ?? '' ) );
		$object_type  = sanitize_key( (string) ( $input['object_type'] ?? '' ) );
		$object_id    = isset( $input['object_id'] ) && is_scalar( $input['object_id'] ) ? trim( (string) $input['object_id'] ) : '';
		if ( self::length( $object_id ) > 128 ) {
			return self::error( 'spdb_task_object_invalid', __( 'The task object reference is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}

		return $this->repository->create_task(
			array(
				'owner_user_id'    => $user_id,
				'assignee_user_id' => $assignee,
				'scope'            => $scope,
				'provider_key'     => $provider_key,
				'object_type'      => $object_type,
				'object_id'        => $object_id,
				'title'            => $title,
				'description'      => $description,
				'priority'         => $priority,
				'due_at_gmt'       => $due_at,
				'created_by'       => $user_id,
				'audit_reason'     => $reason,
			)
		);
	}

	/**
	 * @param array<string,mixed> $input Raw task update.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_task( string $id, array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_tasks' ) ) {
			return self::error( 'spdb_task_forbidden', __( 'You are not authorized to update tasks.', 'sabri-publishing-dashboard' ), 403 );
		}
		$changes = array();
		if ( isset( $input['status'] ) ) {
			$status = sanitize_key( (string) $input['status'] );
			if ( ! in_array( $status, array( 'open', 'in_progress', 'waiting', 'completed', 'cancelled' ), true ) ) {
				return self::error( 'spdb_task_status_invalid', __( 'The task status is invalid.', 'sabri-publishing-dashboard' ), 400 );
			}
			$changes['status'] = $status;
		}
		if ( isset( $input['priority'] ) ) {
			$priority = sanitize_key( (string) $input['priority'] );
			if ( ! in_array( $priority, array( 'low', 'normal', 'high', 'urgent' ), true ) ) {
				return self::error( 'spdb_task_priority_invalid', __( 'The task priority is invalid.', 'sabri-publishing-dashboard' ), 400 );
			}
			$changes['priority'] = $priority;
		}
		if ( isset( $input['description'] ) && is_scalar( $input['description'] ) ) {
			$description = trim( wp_strip_all_tags( (string) $input['description'] ) );
			if ( self::length( $description ) > 2000 ) {
				return self::error( 'spdb_task_description_invalid', __( 'The task description is too long.', 'sabri-publishing-dashboard' ), 400 );
			}
			$changes['description'] = $description;
		}
		if ( isset( $input['assignee_user_id'] ) ) {
			$assignee = max( 0, (int) $input['assignee_user_id'] );
			if ( $assignee < 1 || ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $assignee ) ) {
				return self::error( 'spdb_task_assignee_invalid', __( 'The task assignee is not an eligible platform account.', 'sabri-publishing-dashboard' ), 400 );
			}
			$changes['assignee_user_id'] = $assignee;
		}
		if ( array_key_exists( 'due_at_gmt', $input ) ) {
			$due_at = $this->datetime( $input['due_at_gmt'], true );
			if ( is_wp_error( $due_at ) ) {
				return $due_at;
			}
			$changes['due_at_gmt'] = $due_at;
		}

		return $this->repository->update_task(
			$id,
			get_current_user_id(),
			max( 0, (int) ( $input['version'] ?? 0 ) ),
			$changes,
			$this->is_institutional( get_current_user_id() )
		);
	}

	/**
	 * @param array<string,mixed> $input Raw delegation request.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_delegation( array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_delegations' ) ) {
			return self::error( 'spdb_delegation_forbidden', __( 'You are not authorized to manage delegations.', 'sabri-publishing-dashboard' ), 403 );
		}
		$user_id    = get_current_user_id();
		$assertions = SPDB_Membership_Guard::assertions( $user_id );
		if ( ! is_array( $assertions ) || empty( $assertions['session_two_factor'] ) ) {
			return self::error( 'spdb_delegation_mfa_required', __( 'A current two-factor-authenticated session is required.', 'sabri-publishing-dashboard' ), 403 );
		}

		$delegate = max( 0, (int) ( $input['delegate_user_id'] ?? 0 ) );
		$reason   = isset( $input['reason'] ) && is_scalar( $input['reason'] ) ? trim( wp_strip_all_tags( (string) $input['reason'] ) ) : '';
		$start    = $this->datetime( $input['starts_at_gmt'] ?? gmdate( 'c' ), false );
		$expiry   = $this->datetime( $input['expires_at_gmt'] ?? null, false );
		if ( is_wp_error( $start ) || is_wp_error( $expiry ) ) {
			return is_wp_error( $start ) ? $start : $expiry;
		}
		if (
			$delegate < 1 || $delegate === $user_id
			|| ! SPDB_Membership_Guard::is_user_approved( $delegate )
			|| '' === $reason || self::length( $reason ) > 500
			|| strtotime( $expiry ) <= strtotime( $start )
			|| strtotime( $expiry ) > time() + 90 * DAY_IN_SECONDS
		) {
			return self::error( 'spdb_delegation_invalid', __( 'The delegation payload or validity period is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}

		$scope = $this->delegation_scope( $input['scope'] ?? array() );
		if ( is_wp_error( $scope ) ) {
			return $scope;
		}

		return $this->repository->create_delegation(
			array(
				'principal_user_id' => $user_id,
				'delegate_user_id'  => $delegate,
				'scope'             => $scope,
				'starts_at_gmt'     => $start,
				'expires_at_gmt'    => $expiry,
				'created_by'        => $user_id,
				'reason'            => $reason,
			)
		);
	}

	/** @param array<string,mixed> $input @return array<string,mixed>|WP_Error */
	public function revoke_delegation( string $id, array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_delegations' ) ) {
			return self::error( 'spdb_delegation_forbidden', __( 'You are not authorized to manage delegations.', 'sabri-publishing-dashboard' ), 403 );
		}
		return $this->repository->revoke_delegation(
			$id,
			get_current_user_id(),
			max( 0, (int) ( $input['version'] ?? 0 ) ),
			$this->is_institutional( get_current_user_id() )
		);
	}

	/**
	 * @param array<string,mixed> $input Raw automation rule.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_rule( array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_automation_rules' ) ) {
			return self::error( 'spdb_rule_forbidden', __( 'You are not authorized to manage automation rules.', 'sabri-publishing-dashboard' ), 403 );
		}
		$type  = sanitize_key( (string) ( $input['rule_type'] ?? '' ) );
		$event = sanitize_key( (string) ( $input['event_key'] ?? '' ) );
		$allowed = array(
			'stale_draft_reminder', 'scheduled_publish', 'series_cadence_reminder', 'broken_source_check',
			'failed_publication_retry', 'knowledge_link_reminder', 'analytics_digest', 'approved_unpublished_alert',
		);
		if ( ! in_array( $type, $allowed, true ) || '' === $event ) {
			return self::error( 'spdb_rule_invalid', __( 'The automation rule type is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$condition = isset( $input['condition'] ) && is_array( $input['condition'] ) ? $input['condition'] : array( 'event_key' => $event );
		$action    = isset( $input['action'] ) && is_array( $input['action'] ) ? $input['action'] : array();
		if ( empty( $action ) && isset( $input['action_key'] ) && is_scalar( $input['action_key'] ) ) {
			$action_key = sanitize_key( (string) $input['action_key'] );
			$allowed_actions = array( 'send_reminder', 'enqueue_native_retry', 'create_task', 'request_native_schedule', 'create_knowledge_reminder', 'send_analytics_digest' );
			if ( in_array( $action_key, $allowed_actions, true ) ) {
				$action = array( 'action_key' => $action_key, 'human_confirmation' => true, 'idempotent' => true );
			}
		}
		if ( ! self::safe_rule_payload( $condition ) || ! self::safe_rule_payload( $action ) ) {
			return self::error( 'spdb_rule_unsafe', __( 'The automation rule contains an unsafe or unsupported action.', 'sabri-publishing-dashboard' ), 400 );
		}
		$reason = isset( $input['audit_reason'] ) && is_scalar( $input['audit_reason'] ) ? trim( wp_strip_all_tags( (string) $input['audit_reason'] ) ) : '';
		if ( '' === $reason || self::length( $reason ) > 500 ) {
			return self::error( 'spdb_rule_reason_required', __( 'A valid audit reason is required.', 'sabri-publishing-dashboard' ), 400 );
		}

		return $this->repository->create_rule(
			array(
				'owner_user_id' => get_current_user_id(),
				'rule_type'     => $type,
				'event_key'     => $event,
				'condition'     => $condition,
				'action'        => $action,
				'created_by'    => get_current_user_id(),
				'audit_reason'  => $reason,
			)
		);
	}

	/** @param array<string,mixed> $input @return array<string,mixed>|WP_Error */
	public function update_rule_status( string $id, array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_manage_automation_rules' ) ) {
			return self::error( 'spdb_rule_forbidden', __( 'You are not authorized to manage automation rules.', 'sabri-publishing-dashboard' ), 403 );
		}
		$status = sanitize_key( (string) ( $input['status'] ?? '' ) );
		if ( ! in_array( $status, array( 'enabled', 'disabled', 'archived' ), true ) ) {
			return self::error( 'spdb_rule_status_invalid', __( 'The automation rule status is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		if ( 'enabled' === $status && empty( SPDB_Admin_Settings::get()['automation_enabled'] ) ) {
			return self::error( 'spdb_automation_disabled', __( 'Automation rules are disabled.', 'sabri-publishing-dashboard' ), 503 );
		}
		return $this->repository->update_rule_status(
			$id,
			get_current_user_id(),
			max( 0, (int) ( $input['version'] ?? 0 ) ),
			$status,
			$this->is_institutional( get_current_user_id() )
		);
	}

	/** @param mixed $scope @return array<string,mixed>|WP_Error */
	private function delegation_scope( $scope ) {
		if ( ! is_array( $scope ) ) {
			return self::error( 'spdb_delegation_scope_invalid', __( 'Delegation scope must be an object.', 'sabri-publishing-dashboard' ), 400 );
		}
		$is_list = array_keys( $scope ) === range( 0, count( $scope ) - 1 );
		$raw_actions = $is_list ? $scope : ( isset( $scope['actions'] ) && is_array( $scope['actions'] ) ? $scope['actions'] : array() );
		$aliases = array( 'manage_tasks' => 'manage_task', 'respond_interactions' => 'reply_comment', 'schedule_proposal' => 'propose_schedule' );
		$actions = array();
		foreach ( array_filter( $raw_actions, 'is_scalar' ) as $raw_action ) {
			$action = sanitize_key( (string) $raw_action );
			$actions[] = $aliases[ $action ] ?? $action;
		}
		$actions = array_values( array_unique( $actions ) );
		$allowed = array( 'view_content', 'edit_draft', 'reply_comment', 'manage_task', 'view_analytics', 'propose_schedule' );
		foreach ( $actions as $action ) {
			if ( ! in_array( $action, $allowed, true ) ) {
				return self::error( 'spdb_delegation_scope_invalid', __( 'Delegation contains a forbidden action.', 'sabri-publishing-dashboard' ), 400 );
			}
		}
		if ( empty( $actions ) ) {
			return self::error( 'spdb_delegation_scope_empty', __( 'Delegation requires at least one scoped action.', 'sabri-publishing-dashboard' ), 400 );
		}

		$providers = isset( $scope['provider_keys'] ) && is_array( $scope['provider_keys'] ) ? $scope['provider_keys'] : array();
		$providers = array_slice( array_values( array_unique( array_map( 'sanitize_key', array_filter( $providers, 'is_scalar' ) ) ) ), 0, 20 );
		$object_ids = array();
		foreach ( isset( $scope['object_ids'] ) && is_array( $scope['object_ids'] ) ? $scope['object_ids'] : array() as $object_id ) {
			if ( ! is_scalar( $object_id ) ) {
				continue;
			}
			$object_id = trim( (string) $object_id );
			if ( '' !== $object_id && self::length( $object_id ) <= 128 ) {
				$object_ids[] = $object_id;
			}
		}

		return array(
			'actions'                 => $actions,
			'provider_keys'           => $providers,
			'object_ids'              => array_slice( array_values( array_unique( $object_ids ) ), 0, 50 ),
			'can_publish'             => false,
			'can_change_author'       => false,
			'can_export'              => false,
			'can_access_patient_data' => false,
		);
	}

	/** @param array<string,mixed> $payload */
	private static function safe_rule_payload( array $payload ): bool {
		$encoded = wp_json_encode( $payload );
		if ( false === $encoded || strlen( $encoded ) > 8000 ) {
			return false;
		}
		return 0 === preg_match(
			'/(?:patient|diagnos|prescription|potency|dosage|auto[_-]?publish|delete|impersonat|mass[_-]?publish|cure[_-]?claim|change[_-]?author|export[_-]?patient)/i',
			$encoded
		);
	}

	/** @param mixed $value @return string|null|WP_Error */
	private function datetime( $value, bool $nullable ) {
		if ( $nullable && ( null === $value || '' === $value ) ) {
			return null;
		}
		if ( ! is_scalar( $value ) ) {
			return self::error( 'spdb_datetime_invalid', __( 'A date/time value is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		try {
			$date = new DateTimeImmutable( (string) $value );
		} catch ( Throwable $exception ) {
			return self::error( 'spdb_datetime_invalid', __( 'A date/time value is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}

	private static function length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
