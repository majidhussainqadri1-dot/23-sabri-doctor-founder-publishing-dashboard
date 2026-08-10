<?php
/**
 * Persistence for File 23-owned operational metadata.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Operations_Repository {
	/** @return array<string,mixed> */
	public function health_check(): array {
		$verified = SPDB_Operations_Schema::verify();
		return array(
			'healthy'        => true === $verified,
			'schema_version' => SPDB_Operations_Schema::VERSION,
			'code'           => true === $verified ? 'ready' : 'schema_unavailable',
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_preferences( int $user_id ): array {
		if ( $user_id < 1 ) {
			return array( 'preferences' => array(), 'version' => 0, 'updated_at_gmt' => '' );
		}

		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'preferences' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT preferences_json, version, updated_at_gmt FROM {$table} WHERE user_id = %d", $user_id ),
			self::array_output()
		);

		if ( ! is_array( $row ) ) {
			return array( 'preferences' => array(), 'version' => 0, 'updated_at_gmt' => '' );
		}

		return array(
			'preferences'    => self::decode_object( $row['preferences_json'] ?? '' ),
			'version'        => max( 1, (int) ( $row['version'] ?? 1 ) ),
			'updated_at_gmt' => self::mysql_datetime( $row['updated_at_gmt'] ?? '' ),
		);
	}

	/**
	 * @param array<string,mixed> $preferences Validated preferences.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_preferences( int $user_id, int $expected_version, array $preferences ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'preferences' );
		$now   = current_time( 'mysql', true );
		$json  = self::encode_json( $preferences );
		if ( is_wp_error( $json ) ) {
			return $json;
		}

		$current = $this->get_preferences( $user_id );
		if ( 0 === (int) $current['version'] ) {
			if ( 0 !== $expected_version ) {
				return self::error( 'spdb_preferences_conflict', __( 'Dashboard preferences changed in another request.', 'sabri-publishing-dashboard' ), 409 );
			}
			$inserted = $wpdb->insert(
				$table,
				array(
					'user_id'          => $user_id,
					'preferences_json' => $json,
					'version'          => 1,
					'updated_at_gmt'   => $now,
				)
			);
			if ( false === $inserted ) {
				return self::error( 'spdb_preferences_write_failed', __( 'Dashboard preferences could not be saved.', 'sabri-publishing-dashboard' ), 500 );
			}
		} else {
			if ( (int) $current['version'] !== $expected_version ) {
				return self::error( 'spdb_preferences_conflict', __( 'Dashboard preferences changed in another request.', 'sabri-publishing-dashboard' ), 409 );
			}
			$updated = $wpdb->update(
				$table,
				array(
					'preferences_json' => $json,
					'version'          => $expected_version + 1,
					'updated_at_gmt'   => $now,
				),
				array(
					'user_id' => $user_id,
					'version' => $expected_version,
				)
			);
			if ( false === $updated ) {
				return self::error( 'spdb_preferences_write_failed', __( 'Dashboard preferences could not be saved.', 'sabri-publishing-dashboard' ), 500 );
			}
			if ( 0 === $wpdb->rows_affected ) {
				return self::error( 'spdb_preferences_conflict', __( 'Dashboard preferences changed in another request.', 'sabri-publishing-dashboard' ), 409 );
			}
		}

		$audit = $this->append_audit( $user_id, 'preferences_updated', 'user:' . $user_id, array( 'version' => $expected_version + 1 ) );
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return $this->get_preferences( $user_id );
	}

	/**
	 * @return array<int,array<string,mixed>>|WP_Error
	 */

	/** @return array<int,array<string,mixed>> */
	public function list_saved_views( int $user_id ): array {
		if ( $user_id < 1 ) {
			return array();
		}
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'saved_views' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT view_id, label, filters_json, version, created_at_gmt, updated_at_gmt FROM {$table} WHERE owner_user_id = %d ORDER BY updated_at_gmt DESC LIMIT 25", $user_id ),
			self::array_output()
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$out[] = array(
				'id'         => sanitize_key( (string) ( $row['view_id'] ?? '' ) ),
				'label'      => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'filters'    => self::decode_object( $row['filters_json'] ?? '' ),
				'version'    => max( 1, (int) ( $row['version'] ?? 1 ) ),
				'created_at' => self::mysql_datetime( $row['created_at_gmt'] ?? '' ),
				'updated_at' => self::mysql_datetime( $row['updated_at_gmt'] ?? '' ),
			);
		}
		return $out;
	}

	/** @param array<string,mixed> $filters @return array<string,mixed>|WP_Error */
	public function create_saved_view( int $user_id, string $label, array $filters ) {
		if ( $user_id < 1 || '' === $label || strlen( $label ) > 80 ) {
			return self::error( 'spdb_saved_view_invalid', __( 'The saved view is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$current = $this->list_saved_views( $user_id );
		if ( count( $current ) >= 25 ) {
			return self::error( 'spdb_saved_view_limit', __( 'The saved-view limit has been reached.', 'sabri-publishing-dashboard' ), 409 );
		}
		$json = self::encode_json( $filters );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		global $wpdb;
		$table   = SPDB_Operations_Schema::table( 'saved_views' );
		$view_id = self::id( 'view' );
		$now     = current_time( 'mysql', true );
		$inserted = $wpdb->insert(
			$table,
			array(
				'view_id'        => $view_id,
				'owner_user_id'  => $user_id,
				'label'          => $label,
				'filters_json'   => $json,
				'version'        => 1,
				'created_at_gmt' => $now,
				'updated_at_gmt' => $now,
			)
		);
		if ( false === $inserted ) {
			return self::error( 'spdb_saved_view_write_failed', __( 'The saved view could not be stored.', 'sabri-publishing-dashboard' ), 500 );
		}
		$audit = $this->append_audit( $user_id, 'saved_view_created', 'saved-view:' . $view_id, array() );
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return array( 'id' => $view_id, 'label' => $label, 'filters' => $filters, 'version' => 1, 'created_at' => $now, 'updated_at' => $now );
	}

	/** @return true|WP_Error */
	public function delete_saved_view( int $user_id, string $view_id ) {
		if ( $user_id < 1 || 1 !== preg_match( '/^view_[a-z0-9]{32}$/', $view_id ) ) {
			return self::error( 'spdb_saved_view_invalid', __( 'The saved view identifier is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'saved_views' );
		$deleted = $wpdb->delete( $table, array( 'view_id' => $view_id, 'owner_user_id' => $user_id ), array( '%s', '%d' ) );
		if ( false === $deleted ) {
			return self::error( 'spdb_saved_view_delete_failed', __( 'The saved view could not be deleted.', 'sabri-publishing-dashboard' ), 500 );
		}
		if ( 0 === $deleted ) {
			return self::error( 'spdb_saved_view_not_found', __( 'The saved view was not found.', 'sabri-publishing-dashboard' ), 404 );
		}
		$audit = $this->append_audit( $user_id, 'saved_view_deleted', 'saved-view:' . $view_id, array() );
		return is_wp_error( $audit ) ? $audit : true;
	}

	public function list_tasks( int $user_id, bool $institution, int $page = 1, int $per_page = 25 ) {
		global $wpdb;
		$table    = SPDB_Operations_Schema::table( 'tasks' );
		$page     = max( 1, $page );
		$per_page = min( 100, max( 1, $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		if ( $institution ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY updated_at_gmt DESC, id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE owner_user_id = %d OR assignee_user_id = %d ORDER BY updated_at_gmt DESC, id DESC LIMIT %d OFFSET %d",
				$user_id,
				$user_id,
				$per_page,
				$offset
			);
		}

		$rows = $wpdb->get_results( $sql, self::array_output() );
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_tasks_read_failed', __( 'Tasks could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}

		return array_map( array( $this, 'normalize_task_row' ), $rows );
	}

	/**
	 * @param array<string,mixed> $data Validated task data.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_task( array $data ) {
		global $wpdb;
		$table   = SPDB_Operations_Schema::table( 'tasks' );
		$now     = current_time( 'mysql', true );
		$task_id = self::id( 'task' );
		$row     = array(
			'task_id'          => $task_id,
			'owner_user_id'    => (int) $data['owner_user_id'],
			'assignee_user_id' => (int) $data['assignee_user_id'],
			'scope'            => (string) $data['scope'],
			'provider_key'     => (string) $data['provider_key'],
			'object_type'      => (string) $data['object_type'],
			'object_id'        => (string) $data['object_id'],
			'title'            => (string) $data['title'],
			'description'      => (string) $data['description'],
			'priority'         => (string) $data['priority'],
			'status'           => 'open',
			'due_at_gmt'       => $data['due_at_gmt'] ?: null,
			'version'          => 1,
			'created_by'       => (int) $data['created_by'],
			'audit_reason'     => (string) $data['audit_reason'],
			'created_at_gmt'   => $now,
			'updated_at_gmt'   => $now,
			'completed_at_gmt' => null,
		);

		if ( false === $wpdb->insert( $table, $row ) ) {
			return self::error( 'spdb_task_create_failed', __( 'The task could not be created.', 'sabri-publishing-dashboard' ), 500 );
		}

		$audit = $this->append_audit(
			(int) $data['created_by'],
			'task_created',
			$task_id,
			array(
				'scope'            => $row['scope'],
				'assignee_user_id' => $row['assignee_user_id'],
				'provider_key'     => $row['provider_key'],
				'object_type'      => $row['object_type'],
				'status'           => 'open',
				'reason_hash'      => hash( 'sha256', (string) $data['audit_reason'] ),
			)
		);
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}

		return $this->get_task( $task_id, (int) $data['created_by'], true );
	}

	/** @return array<string,mixed>|WP_Error */
	public function get_task( string $task_id, int $user_id, bool $institution = false ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'tasks' );
		if ( $institution ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE task_id = %s", $task_id );
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE task_id = %s AND (owner_user_id = %d OR assignee_user_id = %d)",
				$task_id,
				$user_id,
				$user_id
			);
		}
		$row = $wpdb->get_row( $sql, self::array_output() );
		return is_array( $row )
			? $this->normalize_task_row( $row )
			: self::error( 'spdb_task_not_found', __( 'The task was not found.', 'sabri-publishing-dashboard' ), 404 );
	}

	/**
	 * @param array<string,mixed> $changes Validated changes.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_task( string $task_id, int $actor_id, int $expected_version, array $changes, bool $institution = false, string $audit_reason = '' ) {
		$current = $this->get_task( $task_id, $actor_id, $institution );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		if ( (int) $current['version'] !== $expected_version ) {
			return self::error( 'spdb_task_conflict', __( 'The task changed in another request.', 'sabri-publishing-dashboard' ), 409 );
		}

		$allowed = array( 'status', 'priority', 'assignee_user_id', 'due_at_gmt', 'description' );
		$update  = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $changes ) ) {
				$update[ $key ] = $changes[ $key ];
			}
		}
		if ( empty( $update ) ) {
			return self::error( 'spdb_task_no_changes', __( 'No valid task changes were supplied.', 'sabri-publishing-dashboard' ), 400 );
		}

		$update['version']        = $expected_version + 1;
		$update['updated_at_gmt'] = current_time( 'mysql', true );
		if ( isset( $update['status'] ) ) {
			$update['completed_at_gmt'] = 'completed' === $update['status'] ? $update['updated_at_gmt'] : null;
		}

		global $wpdb;
		$table  = SPDB_Operations_Schema::table( 'tasks' );
		$result = $wpdb->update(
			$table,
			$update,
			array( 'task_id' => $task_id, 'version' => $expected_version )
		);
		if ( false === $result ) {
			return self::error( 'spdb_task_update_failed', __( 'The task could not be updated.', 'sabri-publishing-dashboard' ), 500 );
		}
		if ( 0 === $wpdb->rows_affected ) {
			return self::error( 'spdb_task_conflict', __( 'The task changed in another request.', 'sabri-publishing-dashboard' ), 409 );
		}

		$audit = $this->append_audit(
			$actor_id,
			'task_updated',
			$task_id,
			array(
				'fields'      => array_values( array_diff( array_keys( $update ), array( 'updated_at_gmt', 'completed_at_gmt' ) ) ),
				'version'     => $expected_version + 1,
				'reason_hash' => hash( 'sha256', $audit_reason ),
			)
		);
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return $this->get_task( $task_id, $actor_id, $institution );
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	public function list_delegations( int $user_id, bool $institution ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'delegations' );
		$sql   = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE principal_user_id = %d OR delegate_user_id = %d ORDER BY created_at_gmt DESC LIMIT %d",
			$user_id,
			$user_id,
			100
		);
		$rows = $wpdb->get_results( $sql, self::array_output() );
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_delegations_read_failed', __( 'Delegations could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}
		return array_map( array( $this, 'normalize_delegation_row' ), $rows );
	}

	/**
	 * @param array<string,mixed> $data Validated delegation data.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_delegation( array $data ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'delegations' );
		$id    = self::id( 'delegation' );
		$now   = current_time( 'mysql', true );
		$scope = self::encode_json( $data['scope'] );
		if ( is_wp_error( $scope ) ) {
			return $scope;
		}
		$row = array(
			'delegation_id'    => $id,
			'principal_user_id' => (int) $data['principal_user_id'],
			'delegate_user_id' => (int) $data['delegate_user_id'],
			'scope_json'       => $scope,
			'status'           => 'active',
			'requires_mfa'     => 1,
			'starts_at_gmt'    => (string) $data['starts_at_gmt'],
			'expires_at_gmt'   => (string) $data['expires_at_gmt'],
			'version'          => 1,
			'created_by'       => (int) $data['created_by'],
			'reason'           => (string) $data['reason'],
			'created_at_gmt'   => $now,
			'revoked_at_gmt'   => null,
		);
		if ( false === $wpdb->insert( $table, $row ) ) {
			return self::error( 'spdb_delegation_create_failed', __( 'The delegation could not be created.', 'sabri-publishing-dashboard' ), 500 );
		}
		$audit = $this->append_audit(
			(int) $data['created_by'],
			'delegation_created',
			$id,
			array(
				'principal_user_id' => $row['principal_user_id'],
				'delegate_user_id'  => $row['delegate_user_id'],
				'expires_at_gmt'    => $row['expires_at_gmt'],
				'reason_hash'       => hash( 'sha256', (string) $data['reason'] ),
			)
		);
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return $this->get_delegation( $id, (int) $data['created_by'], true );
	}

	/** @return array<string,mixed>|WP_Error */
	public function get_delegation( string $id, int $user_id, bool $institution = false ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'delegations' );
		if ( $institution ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE delegation_id = %s", $id );
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE delegation_id = %s AND (principal_user_id = %d OR delegate_user_id = %d)",
				$id,
				$user_id,
				$user_id
			);
		}
		$row = $wpdb->get_row( $sql, self::array_output() );
		return is_array( $row )
			? $this->normalize_delegation_row( $row )
			: self::error( 'spdb_delegation_not_found', __( 'The delegation was not found.', 'sabri-publishing-dashboard' ), 404 );
	}

	/** @return array<string,mixed>|WP_Error */
	public function revoke_delegation( string $id, int $actor_id, int $expected_version, bool $institution = false, string $audit_reason = '' ) {
		$current = $this->get_delegation( $id, $actor_id, $institution );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		if ( (int) $current['version'] !== $expected_version ) {
			return self::error( 'spdb_delegation_conflict', __( 'The delegation changed in another request.', 'sabri-publishing-dashboard' ), 409 );
		}

		global $wpdb;
		$table  = SPDB_Operations_Schema::table( 'delegations' );
		$now    = current_time( 'mysql', true );
		$result = $wpdb->update(
			$table,
			array(
				'status'         => 'revoked',
				'version'        => $expected_version + 1,
				'revoked_at_gmt' => $now,
			),
			array( 'delegation_id' => $id, 'version' => $expected_version )
		);
		if ( false === $result ) {
			return self::error( 'spdb_delegation_revoke_failed', __( 'The delegation could not be revoked.', 'sabri-publishing-dashboard' ), 500 );
		}
		if ( 0 === $wpdb->rows_affected ) {
			return self::error( 'spdb_delegation_conflict', __( 'The delegation changed in another request.', 'sabri-publishing-dashboard' ), 409 );
		}
		$audit = $this->append_audit( $actor_id, 'delegation_revoked', $id, array( 'version' => $expected_version + 1, 'reason_hash' => hash( 'sha256', $audit_reason ) ) );
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return $this->get_delegation( $id, $actor_id, true );
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	public function list_rules( int $owner_user_id, bool $institution ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'automation_rules' );
		if ( $institution ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} ORDER BY updated_at_gmt DESC LIMIT %d", 100 );
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE owner_user_id = %d ORDER BY updated_at_gmt DESC LIMIT %d",
				$owner_user_id,
				100
			);
		}
		$rows = $wpdb->get_results( $sql, self::array_output() );
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_rules_read_failed', __( 'Automation rules could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}
		return array_map( array( $this, 'normalize_rule_row' ), $rows );
	}

	/**
	 * @param array<string,mixed> $data Validated automation-rule data.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_rule( array $data ) {
		global $wpdb;
		$table     = SPDB_Operations_Schema::table( 'automation_rules' );
		$id        = self::id( 'rule' );
		$now       = current_time( 'mysql', true );
		$condition = self::encode_json( $data['condition'] );
		$action    = self::encode_json( $data['action'] );
		if ( is_wp_error( $condition ) ) {
			return $condition;
		}
		if ( is_wp_error( $action ) ) {
			return $action;
		}
		$row = array(
			'rule_id'         => $id,
			'owner_user_id'   => (int) $data['owner_user_id'],
			'rule_type'       => (string) $data['rule_type'],
			'event_key'       => (string) $data['event_key'],
			'condition_json'  => $condition,
			'action_json'     => $action,
			'status'          => 'disabled',
			'version'         => 1,
			'last_run_at_gmt' => null,
			'next_run_at_gmt' => null,
			'created_by'      => (int) $data['created_by'],
			'audit_reason'    => (string) $data['audit_reason'],
			'created_at_gmt'  => $now,
			'updated_at_gmt'  => $now,
		);
		if ( false === $wpdb->insert( $table, $row ) ) {
			return self::error( 'spdb_rule_create_failed', __( 'The automation rule could not be created.', 'sabri-publishing-dashboard' ), 500 );
		}
		$audit = $this->append_audit(
			(int) $data['created_by'],
			'automation_rule_created',
			$id,
			array(
				'rule_type'   => $row['rule_type'],
				'event_key'   => $row['event_key'],
				'status'      => 'disabled',
				'reason_hash' => hash( 'sha256', (string) $data['audit_reason'] ),
			)
		);
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return $this->get_rule( $id, (int) $data['created_by'], true );
	}

	/** @return array<string,mixed>|WP_Error */
	public function get_rule( string $id, int $user_id, bool $institution = false ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'automation_rules' );
		if ( $institution ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE rule_id = %s", $id );
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE rule_id = %s AND owner_user_id = %d", $id, $user_id );
		}
		$row = $wpdb->get_row( $sql, self::array_output() );
		return is_array( $row )
			? $this->normalize_rule_row( $row )
			: self::error( 'spdb_rule_not_found', __( 'The automation rule was not found.', 'sabri-publishing-dashboard' ), 404 );
	}

	/** @return array<string,mixed>|WP_Error */
	public function update_rule_status( string $id, int $actor_id, int $expected_version, string $status, bool $institution = false, string $audit_reason = '' ) {
		$current = $this->get_rule( $id, $actor_id, $institution );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		if ( (int) $current['version'] !== $expected_version ) {
			return self::error( 'spdb_rule_conflict', __( 'The automation rule changed in another request.', 'sabri-publishing-dashboard' ), 409 );
		}

		global $wpdb;
		$table  = SPDB_Operations_Schema::table( 'automation_rules' );
		$result = $wpdb->update(
			$table,
			array(
				'status'         => $status,
				'version'        => $expected_version + 1,
				'updated_at_gmt' => current_time( 'mysql', true ),
			),
			array( 'rule_id' => $id, 'version' => $expected_version )
		);
		if ( false === $result ) {
			return self::error( 'spdb_rule_update_failed', __( 'The automation rule could not be updated.', 'sabri-publishing-dashboard' ), 500 );
		}
		if ( 0 === $wpdb->rows_affected ) {
			return self::error( 'spdb_rule_conflict', __( 'The automation rule changed in another request.', 'sabri-publishing-dashboard' ), 409 );
		}
		$audit = $this->append_audit( $actor_id, 'automation_rule_status_changed', $id, array( 'status' => $status, 'version' => $expected_version + 1, 'reason_hash' => hash( 'sha256', $audit_reason ) ) );
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return $this->get_rule( $id, $actor_id, true );
	}


	/** @return array<int,array<string,mixed>>|WP_Error */
	public function list_enabled_rules_for_event( string $event_key, int $limit = 100 ) {
		$event_key = sanitize_key( $event_key );
		if ( '' === $event_key ) {
			return self::error( 'spdb_rule_event_invalid', __( 'The automation event is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'automation_rules' );
		$limit = min( 100, max( 1, $limit ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE event_key = %s AND status = 'enabled' ORDER BY id ASC LIMIT %d", $event_key, $limit ),
			self::array_output()
		);
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_rules_read_failed', __( 'Automation rules could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}
		return array_map( array( $this, 'normalize_rule_row' ), $rows );
	}

	/** @return array<string,mixed>|WP_Error */
	public function mark_rule_run( string $rule_id, int $expected_version, ?string $next_run_at_gmt = null ) {
		$result = $this->atomic(
			function () use ( $rule_id, $expected_version, $next_run_at_gmt ) {
				global $wpdb;
				$table = SPDB_Operations_Schema::table( 'automation_rules' );
				$now   = current_time( 'mysql', true );
				$updated = $wpdb->update(
					$table,
					array(
						'last_run_at_gmt' => $now,
						'next_run_at_gmt' => $next_run_at_gmt,
						'version'         => $expected_version + 1,
						'updated_at_gmt'  => $now,
					),
					array( 'rule_id' => $rule_id, 'version' => $expected_version, 'status' => 'enabled' )
				);
				if ( false === $updated ) {
					return self::error( 'spdb_rule_run_update_failed', __( 'The automation rule run could not be recorded.', 'sabri-publishing-dashboard' ), 500 );
				}
				if ( 1 !== (int) $wpdb->rows_affected ) {
					return self::error( 'spdb_rule_run_conflict', __( 'The automation rule changed before execution completed.', 'sabri-publishing-dashboard' ), 409 );
				}
				$audit = $this->append_audit( 0, 'automation_rule_executed', $rule_id, array( 'version' => $expected_version + 1 ) );
				return is_wp_error( $audit ) ? $audit : true;
			},
			'rule_run'
		);
		return is_wp_error( $result ) ? $result : $this->get_rule( $rule_id, 0, true );
	}

	/**
	 * Insert or replace a bounded aggregate metric snapshot.
	 *
	 * @param array<string,mixed> $metric Validated metric.
	 * @return true|WP_Error
	 */
	public function store_metric_snapshot( array $metric ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'metric_snapshots' );
		$value = self::encode_json( array(
			'value'      => $metric['value'],
			'unit'       => $metric['unit'],
			'label'      => $metric['label'],
			'definition' => substr( wp_strip_all_tags( (string) $metric['definition'] ), 0, 500 ),
			'interval'   => sanitize_key( (string) ( $metric['interval'] ?? 'custom' ) ),
		) );
		if ( is_wp_error( $value ) ) {
			return $value;
		}
		$current_threshold = class_exists( 'SPDB_Admin_Settings' ) ? (int) SPDB_Admin_Settings::get()['analytics_min_cohort'] : 20;
		$effective_threshold = max( 20, $current_threshold, (int) ( $metric['privacy_threshold'] ?? 0 ) );
		$row = array(
			'snapshot_id'      => self::id( 'metric' ),
			'provider_key'     => (string) $metric['provider_key'],
			'metric_key'       => (string) $metric['metric_key'],
			'scope'            => (string) $metric['scope'],
			'owner_user_id'    => (int) $metric['owner_user_id'],
			'period_start_gmt' => (string) $metric['period_start_gmt'],
			'period_end_gmt'   => (string) $metric['period_end_gmt'],
			'definition_hash'  => hash( 'sha256', (string) $metric['definition'] ),
			'value_json'       => $value,
			'cohort_count'     => (int) $metric['cohort_count'],
			'privacy_threshold' => $effective_threshold,
			'generated_at_gmt' => (string) $metric['generated_at_gmt'],
			'expires_at_gmt'   => (string) $metric['expires_at_gmt'],
		);
		$result = $wpdb->replace( $table, $row );
		return false === $result
			? self::error( 'spdb_metric_snapshot_failed', __( 'An aggregate metric snapshot could not be stored.', 'sabri-publishing-dashboard' ), 500 )
			: true;
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	public function list_metric_snapshots( int $user_id, bool $institution, int $limit = 100 ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'metric_snapshots' );
		$limit = min( 500, max( 1, $limit ) );
		$now   = current_time( 'mysql', true );
		if ( $institution ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE expires_at_gmt > %s ORDER BY period_end_gmt DESC, id DESC LIMIT %d",
				$now,
				$limit
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE owner_user_id = %d AND scope = 'own' AND expires_at_gmt > %s ORDER BY period_end_gmt DESC, id DESC LIMIT %d",
				$user_id,
				$now,
				$limit
			);
		}
		$rows = $wpdb->get_results( $sql, self::array_output() );
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_metric_read_failed', __( 'Aggregate metrics could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}
		return array_map( array( $this, 'normalize_metric_row' ), $rows );
	}

	/**
	 * @param array<string,mixed> $data Validated export metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_export_job( array $data ) {
		global $wpdb;
		$table     = SPDB_Operations_Schema::table( 'export_jobs' );
		$export_id = self::id( 'export' );
		$now       = current_time( 'mysql', true );
		$filters   = self::encode_json( $data['filters'] );
		if ( is_wp_error( $filters ) ) {
			return $filters;
		}
		$reason_hash = strtolower( trim( (string) ( $data['reason_hash'] ?? '' ) ) );
		if ( 1 !== preg_match( '/^[a-f0-9]{64}$/', $reason_hash ) ) {
			return self::error( 'spdb_export_reason_invalid', __( 'The secure export audit reason is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$result = $this->atomic(
			function () use ( $wpdb, $table, $export_id, $now, $filters, $data, $reason_hash ) {
				$row = array(
					'export_id'       => $export_id,
					'owner_user_id'   => (int) $data['owner_user_id'],
					'report_key'      => (string) $data['report_key'],
					'format'          => (string) $data['format'],
					'scope'           => (string) $data['scope'],
					'status'          => 'queued',
					'filters_json'    => $filters,
					'storage_ref'     => '',
					'file_hash'       => '',
					'row_count'       => 0,
					'error_code'      => '',
					'created_at_gmt'  => $now,
					'updated_at_gmt'  => $now,
					'expires_at_gmt'  => (string) $data['expires_at_gmt'],
				);
				if ( false === $wpdb->insert( $table, $row ) ) {
					return self::error( 'spdb_export_create_failed', __( 'The export job could not be created.', 'sabri-publishing-dashboard' ), 500 );
				}
				$audit = $this->append_audit(
					(int) $data['owner_user_id'],
					'export_requested',
					$export_id,
					array( 'report_key' => $row['report_key'], 'format' => $row['format'], 'scope' => $row['scope'], 'reason_hash' => $reason_hash )
				);
				return is_wp_error( $audit ) ? $audit : true;
			},
			'export_request'
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return $this->get_export_job( $export_id, (int) $data['owner_user_id'], true );
	}

	/** @return array<string,mixed>|WP_Error */
	public function get_export_job( string $export_id, int $user_id, bool $institution = false ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'export_jobs' );
		if ( $institution ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE export_id = %s", $export_id );
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE export_id = %s AND owner_user_id = %d", $export_id, $user_id );
		}
		$row = $wpdb->get_row( $sql, self::array_output() );
		return is_array( $row )
			? $this->normalize_export_row( $row )
			: self::error( 'spdb_export_not_found', __( 'The export job was not found.', 'sabri-publishing-dashboard' ), 404 );
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	public function list_export_jobs( int $user_id, bool $institution, int $limit = 50 ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'export_jobs' );
		$limit = min( 100, max( 1, $limit ) );
		if ( $institution ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at_gmt DESC LIMIT %d", $limit );
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE owner_user_id = %d ORDER BY created_at_gmt DESC LIMIT %d", $user_id, $limit );
		}
		$rows = $wpdb->get_results( $sql, self::array_output() );
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_exports_read_failed', __( 'Export jobs could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}
		return array_map( array( $this, 'normalize_export_row' ), $rows );
	}

	/** @return true|WP_Error */
	public function mark_export_processing( string $export_id ) {
		if ( 1 !== preg_match( '/^export_[a-z0-9]{32}$/', $export_id ) ) {
			return self::error( 'spdb_export_invalid', __( 'The export identifier is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		global $wpdb;
		$table  = SPDB_Operations_Schema::table( 'export_jobs' );
		$result = $wpdb->update(
			$table,
			array( 'status' => 'processing', 'updated_at_gmt' => current_time( 'mysql', true ), 'error_code' => '' ),
			array( 'export_id' => $export_id, 'status' => 'queued' )
		);
		if ( false === $result ) {
			return self::error( 'spdb_export_update_failed', __( 'The export job could not be updated.', 'sabri-publishing-dashboard' ), 500 );
		}
		return 1 === (int) $wpdb->rows_affected
			? true
			: self::error( 'spdb_export_state_conflict', __( 'The export job is not queued or was already claimed.', 'sabri-publishing-dashboard' ), 409 );
	}

	/** @return true|WP_Error */
	public function complete_export_job( string $export_id, string $storage_ref, string $file_hash, int $row_count ) {
		$storage_ref = trim( $storage_ref );
		$file_hash   = strtolower( trim( $file_hash ) );
		if (
			1 !== preg_match( '/^export_[a-z0-9]{32}$/', $export_id )
			|| '' === $storage_ref
			|| strlen( $storage_ref ) > 500
			|| preg_match( '/[\x00-\x1F\x7F]/', $storage_ref )
			|| 1 !== preg_match( '/^[a-f0-9]{64}$/', $file_hash )
			|| $row_count < 0
		) {
			return self::error( 'spdb_export_completion_invalid', __( 'The export completion evidence is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}

		return $this->atomic(
			function () use ( $export_id, $storage_ref, $file_hash, $row_count ) {
				global $wpdb;
				$table  = SPDB_Operations_Schema::table( 'export_jobs' );
				$result = $wpdb->update(
					$table,
					array(
						'status'         => 'ready',
						'storage_ref'    => $storage_ref,
						'file_hash'      => $file_hash,
						'row_count'      => $row_count,
						'error_code'     => '',
						'updated_at_gmt' => current_time( 'mysql', true ),
					),
					array( 'export_id' => $export_id, 'status' => 'processing' )
				);
				if ( false === $result ) {
					return self::error( 'spdb_export_update_failed', __( 'The export job could not be completed.', 'sabri-publishing-dashboard' ), 500 );
				}
				if ( 1 !== (int) $wpdb->rows_affected ) {
					return self::error( 'spdb_export_state_conflict', __( 'The export job is not processing or was already finalized.', 'sabri-publishing-dashboard' ), 409 );
				}
				$audit = $this->append_audit( 0, 'export_generated', $export_id, array( 'row_count' => $row_count, 'file_hash' => $file_hash ) );
				return is_wp_error( $audit ) ? $audit : true;
			},
			'export_complete'
		);
	}

	/** @return true|WP_Error */
	public function fail_export_job( string $export_id, string $error_code ) {
		$error_code = sanitize_key( $error_code );
		if ( 1 !== preg_match( '/^export_[a-z0-9]{32}$/', $export_id ) || '' === $error_code ) {
			return self::error( 'spdb_export_failure_invalid', __( 'The export failure evidence is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		return $this->atomic(
			function () use ( $export_id, $error_code ) {
				global $wpdb;
				$table  = SPDB_Operations_Schema::table( 'export_jobs' );
				$result = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table} SET status = 'failed', error_code = %s, updated_at_gmt = %s WHERE export_id = %s AND status IN ('queued','processing')",
						$error_code,
						current_time( 'mysql', true ),
						$export_id
					)
				);
				if ( false === $result ) {
					return self::error( 'spdb_export_update_failed', __( 'The export job could not be marked as failed.', 'sabri-publishing-dashboard' ), 500 );
				}
				if ( 1 !== (int) $result ) {
					return self::error( 'spdb_export_state_conflict', __( 'The export job was already finalized or does not exist.', 'sabri-publishing-dashboard' ), 409 );
				}
				$audit = $this->append_audit( 0, 'export_failed', $export_id, array( 'error_code' => $error_code ) );
				return is_wp_error( $audit ) ? $audit : true;
			},
			'export_fail'
		);
	}

	/**
	 * Idempotently enqueue one background job.
	 *
	 * @param array<string,mixed> $payload Bounded job payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function enqueue_job( string $job_type, int $owner_user_id, array $payload, string $idempotency_key, int $max_attempts = 5, ?string $available_at_gmt = null ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'background_jobs' );
		$job_type = sanitize_key( $job_type );
		$idempotency_key = substr( trim( $idempotency_key ), 0, 128 );
		if ( '' === $job_type || '' === $idempotency_key ) {
			return self::error( 'spdb_job_invalid', __( 'The background job definition is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$json = self::encode_json( $payload );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE job_type = %s AND idempotency_key = %s", $job_type, $idempotency_key ),
			self::array_output()
		);
		if ( is_array( $existing ) ) {
			return $this->normalize_job_row( $existing );
		}

		$now = current_time( 'mysql', true );
		$row = array(
			'job_id'           => self::id( 'job' ),
			'job_type'         => $job_type,
			'owner_user_id'    => max( 0, $owner_user_id ),
			'payload_json'     => $json,
			'status'           => 'queued',
			'attempts'         => 0,
			'max_attempts'     => min( 10, max( 1, $max_attempts ) ),
			'available_at_gmt' => $available_at_gmt ?: $now,
			'locked_at_gmt'    => null,
			'lock_token'       => '',
			'last_error_code'  => '',
			'idempotency_key'  => $idempotency_key,
			'created_at_gmt'   => $now,
			'updated_at_gmt'   => $now,
			'finished_at_gmt'  => null,
		);
		if ( false === $wpdb->insert( $table, $row ) ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE job_type = %s AND idempotency_key = %s", $job_type, $idempotency_key ),
				self::array_output()
			);
			if ( is_array( $existing ) ) {
				return $this->normalize_job_row( $existing );
			}
			return self::error( 'spdb_job_enqueue_failed', __( 'The background job could not be queued.', 'sabri-publishing-dashboard' ), 500 );
		}
		return $this->get_job( (string) $row['job_id'] );
	}

	/** @return array<string,mixed>|WP_Error */
	public function get_job( string $job_id ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'background_jobs' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE job_id = %s", $job_id ), self::array_output() );
		return is_array( $row )
			? $this->normalize_job_row( $row )
			: self::error( 'spdb_job_not_found', __( 'The background job was not found.', 'sabri-publishing-dashboard' ), 404 );
	}

	/**
	 * Atomically claim due jobs. Expired locks are returned to queued state first.
	 *
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	public function claim_due_jobs( int $limit = 10, int $lock_ttl = 300 ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'background_jobs' );
		$limit = min( 25, max( 1, $limit ) );
		$now   = current_time( 'mysql', true );
		$stale = gmdate( 'Y-m-d H:i:s', time() - max( 60, $lock_ttl ) );
		$recovered = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'queued', lock_token = '', locked_at_gmt = NULL, updated_at_gmt = %s WHERE status = 'processing' AND locked_at_gmt < %s",
				$now,
				$stale
			)
		);
		if ( false === $recovered ) {
			return self::error( 'spdb_job_lock_recovery_failed', __( 'Expired background-job locks could not be recovered safely.', 'sabri-publishing-dashboard' ), 500 );
		}
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT job_id FROM {$table} WHERE status = 'queued' AND available_at_gmt <= %s ORDER BY available_at_gmt ASC, id ASC LIMIT %d",
				$now,
				$limit
			)
		);
		if ( ! is_array( $ids ) ) {
			return self::error( 'spdb_jobs_read_failed', __( 'Background jobs could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}

		$claimed = array();
		foreach ( $ids as $job_id ) {
			$job_id = (string) $job_id;
			$token  = hash( 'sha256', wp_generate_uuid4() . '|' . $job_id . '|' . microtime( true ) );
			$result = $wpdb->update(
				$table,
				array(
					'status'         => 'processing',
					'lock_token'     => $token,
					'locked_at_gmt'  => $now,
					'updated_at_gmt' => $now,
				),
				array( 'job_id' => $job_id, 'status' => 'queued' )
			);
			if ( false === $result || 0 === $wpdb->rows_affected ) {
				continue;
			}
			$job = $this->get_job( $job_id );
			if ( is_array( $job ) ) {
				$claimed[] = $job;
			}
		}
		return $claimed;
	}

	/** @return true|WP_Error */
	public function complete_job( string $job_id, string $lock_token ) {
		global $wpdb;
		$table  = SPDB_Operations_Schema::table( 'background_jobs' );
		$now    = current_time( 'mysql', true );
		$result = $wpdb->update(
			$table,
			array(
				'status'          => 'completed',
				'lock_token'      => '',
				'locked_at_gmt'   => null,
				'updated_at_gmt'  => $now,
				'finished_at_gmt' => $now,
				'last_error_code' => '',
			),
			array( 'job_id' => $job_id, 'status' => 'processing', 'lock_token' => $lock_token )
		);
		return false === $result || 0 === $wpdb->rows_affected
			? self::error( 'spdb_job_lock_conflict', __( 'The background job lock is no longer current.', 'sabri-publishing-dashboard' ), 409 )
			: true;
	}

	/** @return true|WP_Error */
	public function fail_or_retry_job( string $job_id, string $lock_token, string $error_code ) {
		$error_code = sanitize_key( $error_code );
		if ( '' === $error_code || 1 !== preg_match( '/^job_[a-z0-9]{32}$/', $job_id ) || 1 !== preg_match( '/^[a-f0-9]{64}$/', $lock_token ) ) {
			return self::error( 'spdb_job_transition_invalid', __( 'The background-job transition evidence is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		return $this->atomic(
			function () use ( $job_id, $lock_token, $error_code ) {
				$job = $this->get_job( $job_id );
				if ( is_wp_error( $job ) ) {
					return $job;
				}
				if ( 'processing' !== $job['status'] || ! hash_equals( (string) $job['lock_token'], $lock_token ) ) {
					return self::error( 'spdb_job_lock_conflict', __( 'The background job lock is no longer current.', 'sabri-publishing-dashboard' ), 409 );
				}
				$attempts = (int) $job['attempts'] + 1;
				$dead     = $attempts >= (int) $job['max_attempts'];
				$delay    = min( DAY_IN_SECONDS, 60 * ( 2 ** min( 10, $attempts - 1 ) ) );
				$now      = current_time( 'mysql', true );

				global $wpdb;
				$table  = SPDB_Operations_Schema::table( 'background_jobs' );
				$result = $wpdb->update(
					$table,
					array(
						'status'           => $dead ? 'dead_letter' : 'queued',
						'attempts'         => $attempts,
						'available_at_gmt' => $dead ? $now : gmdate( 'Y-m-d H:i:s', time() + $delay ),
						'lock_token'       => '',
						'locked_at_gmt'    => null,
						'last_error_code'  => $error_code,
						'updated_at_gmt'   => $now,
						'finished_at_gmt'  => $dead ? $now : null,
					),
					array( 'job_id' => $job_id, 'status' => 'processing', 'lock_token' => $lock_token )
				);
				if ( false === $result || 1 !== (int) $wpdb->rows_affected ) {
					return self::error( 'spdb_job_lock_conflict', __( 'The background job lock is no longer current.', 'sabri-publishing-dashboard' ), 409 );
				}
				if ( $dead ) {
					$audit = $this->append_audit( 0, 'background_job_dead_lettered', $job_id, array( 'job_type' => $job['job_type'], 'error_code' => $error_code, 'attempts' => $attempts ) );
					if ( is_wp_error( $audit ) ) {
						return $audit;
					}
				}
				return true;
			},
			'job_fail_retry'
		);
	}

	/** @return array<string,int>|WP_Error */
	public function job_counts() {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'background_jobs' );
		$rows  = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status", self::array_output() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static allowlisted table and query.
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_jobs_read_failed', __( 'Background-job counts could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}
		$counts = array( 'queued' => 0, 'processing' => 0, 'completed' => 0, 'dead_letter' => 0 );
		foreach ( $rows as $row ) {
			$status = sanitize_key( (string) ( $row['status'] ?? '' ) );
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ] = max( 0, (int) ( $row['total'] ?? 0 ) );
			}
		}
		return $counts;
	}

	/**
	 * Store a privacy-minimized provider health projection.
	 *
	 * @param array<string,mixed> $health Validated non-sensitive health data.
	 * @return true|WP_Error
	 */
	public function store_adapter_health( string $provider_key, string $maturity_state, array $health, int $ttl = 300 ) {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'adapter_health' );
		$json  = self::encode_json( $health );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		$now = current_time( 'mysql', true );
		$result = $wpdb->replace(
			$table,
			array(
				'provider_key'   => $provider_key,
				'maturity_state' => $maturity_state,
				'health_json'    => $json,
				'checked_at_gmt' => $now,
				'expires_at_gmt' => gmdate( 'Y-m-d H:i:s', time() + min( HOUR_IN_SECONDS, max( 60, $ttl ) ) ),
			)
		);
		return false === $result
			? self::error( 'spdb_adapter_health_write_failed', __( 'Adapter health could not be cached.', 'sabri-publishing-dashboard' ), 500 )
			: true;
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	public function list_adapter_health() {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'adapter_health' );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY provider_key ASC", self::array_output() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static allowlisted table and query.
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_adapter_health_read_failed', __( 'Adapter health could not be read.', 'sabri-publishing-dashboard' ), 500 );
		}
		$out = array();
		foreach ( $rows as $row ) {
			$out[] = array(
				'provider_key'   => sanitize_key( (string) ( $row['provider_key'] ?? '' ) ),
				'maturity_state' => sanitize_key( (string) ( $row['maturity_state'] ?? '' ) ),
				'health'         => self::decode_object( $row['health_json'] ?? '' ),
				'checked_at_gmt' => self::mysql_datetime( $row['checked_at_gmt'] ?? '' ),
				'expires_at_gmt' => self::mysql_datetime( $row['expires_at_gmt'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * Append a privacy-minimized hash-chained dashboard audit event.
	 *
	 * @param array<string,mixed> $payload Bounded non-sensitive payload.
	 * @return true|WP_Error
	 */
	public function append_audit( int $actor_user_id, string $event_key, string $object_reference, array $payload ) {
		global $wpdb;
		$table     = SPDB_Operations_Schema::table( 'dashboard_audit' );
		$event_key = sanitize_key( $event_key );
		if ( '' === $event_key || '' === $table ) {
			return self::error( 'spdb_audit_event_invalid', __( 'The audit event is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$payload = self::sanitize_audit_payload( $payload );
		$json    = self::encode_json( $payload );
		if ( is_wp_error( $json ) ) {
			return $json;
		}

		$lock_name = 'spdb_audit_' . substr( hash( 'sha256', $table ), 0, 40 );
		$locked    = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name ) );
		if ( '1' !== (string) $locked ) {
			return self::error( 'spdb_audit_lock_unavailable', __( 'The dashboard audit sequence is busy and could not be secured.', 'sabri-publishing-dashboard' ), 503 );
		}

		try {
			$previous = $wpdb->get_var( "SELECT event_hash FROM {$table} ORDER BY id DESC LIMIT 1 FOR UPDATE" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static allowlisted table.
			$previous = is_string( $previous ) && 64 === strlen( $previous ) ? $previous : str_repeat( '0', 64 );
			$event_id = self::id( 'audit' );
			$created  = current_time( 'mysql', true );
			$object_hash = hash( 'sha256', $object_reference );
			$event_hash  = hash( 'sha256', implode( '|', array( $event_id, (string) max( 0, $actor_user_id ), $event_key, $object_hash, $json, $previous, $created ) ) );
			$result = $wpdb->insert(
				$table,
				array(
					'event_id'        => $event_id,
					'actor_user_id'   => max( 0, $actor_user_id ),
					'event_key'       => $event_key,
					'object_ref_hash' => $object_hash,
					'payload_json'    => $json,
					'previous_hash'   => $previous,
					'event_hash'      => $event_hash,
					'created_at_gmt'  => $created,
				)
			);
			return false === $result
				? self::error( 'spdb_audit_write_failed', __( 'The dashboard audit event could not be written.', 'sabri-publishing-dashboard' ), 500 )
				: true;
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		}
	}

	/** @return array<string,mixed> */
	public function audit_health(): array {
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'dashboard_audit' );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", self::array_output() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static allowlisted table and query.
		if ( ! is_array( $rows ) ) {
			return array( 'healthy' => false, 'events' => 0, 'code' => 'read_failed' );
		}
		$previous = str_repeat( '0', 64 );
		foreach ( $rows as $row ) {
			$expected = hash(
				'sha256',
				implode(
					'|',
					array(
						(string) ( $row['event_id'] ?? '' ),
						(string) max( 0, (int) ( $row['actor_user_id'] ?? 0 ) ),
						(string) ( $row['event_key'] ?? '' ),
						(string) ( $row['object_ref_hash'] ?? '' ),
						(string) ( $row['payload_json'] ?? '' ),
						$previous,
						(string) ( $row['created_at_gmt'] ?? '' ),
					)
				)
			);
			if ( ! hash_equals( $previous, (string) ( $row['previous_hash'] ?? '' ) ) || ! hash_equals( $expected, (string) ( $row['event_hash'] ?? '' ) ) ) {
				return array( 'healthy' => false, 'events' => count( $rows ), 'code' => 'chain_invalid' );
			}
			$previous = (string) $row['event_hash'];
		}
		return array( 'healthy' => true, 'events' => count( $rows ), 'code' => 'ready' );
	}

	/**
	 * Return File 23-owned user data only. No native provider content is queried.
	 *
	 * @return array<string,mixed>
	 */
	public function privacy_export( int $user_id, int $page = 1, int $per_page = 100 ): array {
		global $wpdb;
		$page     = min( 10000, max( 1, $page ) );
		$per_page = min( 200, max( 1, $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		$tasks = $this->list_tasks( $user_id, false, $page, $per_page );
		$tasks = is_array( $tasks ) ? $tasks : array();

		$saved_table = SPDB_Operations_Schema::table( 'saved_views' );
		$saved_rows  = $wpdb->get_results( $wpdb->prepare( "SELECT view_id, label, filters_json, version, created_at_gmt, updated_at_gmt FROM {$saved_table} WHERE owner_user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id, $per_page, $offset ), self::array_output() );
		$saved_views = array();
		foreach ( is_array( $saved_rows ) ? $saved_rows : array() as $row ) {
			$saved_views[] = array(
				'view_id'        => sanitize_key( (string) ( $row['view_id'] ?? '' ) ),
				'label'          => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'filters'        => self::decode_object( $row['filters_json'] ?? '' ),
				'version'        => max( 1, (int) ( $row['version'] ?? 1 ) ),
				'created_at_gmt' => self::mysql_datetime( $row['created_at_gmt'] ?? '' ),
				'updated_at_gmt' => self::mysql_datetime( $row['updated_at_gmt'] ?? '' ),
			);
		}

		$delegation_table = SPDB_Operations_Schema::table( 'delegations' );
		$delegation_rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$delegation_table} WHERE principal_user_id = %d OR delegate_user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id, $user_id, $per_page, $offset ), self::array_output() );
		$delegations = array_map( array( $this, 'normalize_delegation_row' ), is_array( $delegation_rows ) ? $delegation_rows : array() );

		$rule_table = SPDB_Operations_Schema::table( 'automation_rules' );
		$rule_rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$rule_table} WHERE owner_user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id, $per_page, $offset ), self::array_output() );
		$rules = array_map( array( $this, 'normalize_rule_row' ), is_array( $rule_rows ) ? $rule_rows : array() );

		$export_table = SPDB_Operations_Schema::table( 'export_jobs' );
		$export_rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$export_table} WHERE owner_user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id, $per_page, $offset ), self::array_output() );
		$exports = array_map( array( $this, 'normalize_export_row' ), is_array( $export_rows ) ? $export_rows : array() );

		$collection_page = $this->privacy_collection_export( $user_id, $page, $per_page );
		$preferences     = 1 === $page ? $this->get_preferences( $user_id ) : array();
		$counts = array( count( $tasks ), count( $saved_views ), count( $delegations ), count( $rules ), count( $exports ) );
		$has_more = in_array( $per_page, $counts, true ) || true === ( $collection_page['has_more'] ?? false );

		return array(
			'preferences'      => $preferences,
			'saved_views'      => $saved_views,
			'tasks'            => $tasks,
			'delegations'      => $delegations,
			'automation_rules' => $rules,
			'export_jobs'      => $exports,
			'collections'      => $collection_page['data'] ?? array(),
			'_done'            => ! $has_more,
		);
	}

	/**
	 * Erase user-owned preferences, views and expired temporary artifacts while
	 * pseudonymizing audit references. Institutional tasks/campaign evidence may
	 * be retained under approved policy and is therefore not silently deleted.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function privacy_erase( int $user_id ) {
		if ( $user_id < 1 ) {
			return self::error( 'spdb_privacy_user_invalid', __( 'The privacy-erasure user is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}

		return $this->atomic(
			function () use ( $user_id ) {
				global $wpdb;
				$now         = current_time( 'mysql', true );
				$deleted     = 0;
				$pseudonymized = 0;

				$deletes = array(
					array( SPDB_Operations_Schema::table( 'preferences' ), 'user_id = %d' ),
					array( SPDB_Operations_Schema::table( 'saved_views' ), 'owner_user_id = %d' ),
					array( SPDB_Operations_Schema::table( 'delegations' ), '(principal_user_id = %d OR delegate_user_id = %d)' ),
					array( SPDB_Operations_Schema::table( 'automation_rules' ), 'owner_user_id = %d' ),
					array( SPDB_Operations_Schema::table( 'metric_snapshots' ), "scope = 'own' AND owner_user_id = %d" ),
					array( SPDB_Operations_Schema::table( 'export_jobs' ), 'owner_user_id = %d' ),
					array( SPDB_Operations_Schema::table( 'background_jobs' ), 'owner_user_id = %d' ),
				);
				foreach ( $deletes as $delete ) {
					$args = str_contains( $delete[1], ' OR ' ) ? array( $user_id, $user_id ) : array( $user_id );
					$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$delete[0]} WHERE {$delete[1]}", ...$args ) );
					if ( false === $result ) {
						return self::error( 'spdb_privacy_erase_failed', __( 'File 23-owned personal data could not be erased.', 'sabri-publishing-dashboard' ), 500 );
					}
					$deleted += (int) $result;
				}

				$tasks = SPDB_Operations_Schema::table( 'tasks' );
				$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$tasks} WHERE scope = 'own' AND (owner_user_id = %d OR assignee_user_id = %d)", $user_id, $user_id ) );
				if ( false === $result ) { return self::error( 'spdb_privacy_erase_failed', __( 'Personal tasks could not be erased.', 'sabri-publishing-dashboard' ), 500 ); }
				$deleted += (int) $result;
				$result = $wpdb->query( $wpdb->prepare( "UPDATE {$tasks} SET owner_user_id = IF(owner_user_id = %d, 0, owner_user_id), assignee_user_id = IF(assignee_user_id = %d, 0, assignee_user_id), updated_at_gmt = %s WHERE scope = 'institution' AND (owner_user_id = %d OR assignee_user_id = %d)", $user_id, $user_id, $now, $user_id, $user_id ) );
				if ( false === $result ) { return self::error( 'spdb_privacy_pseudonymize_failed', __( 'Institutional task references could not be pseudonymized.', 'sabri-publishing-dashboard' ), 500 ); }
				$pseudonymized += (int) $result;

				$collections_table = SPDB_Collections_Schema::collections_table();
				$items_table       = SPDB_Collections_Schema::items_table();
				$links_table       = SPDB_Collections_Schema::links_table();
				$own_ids = $wpdb->get_col( $wpdb->prepare( "SELECT collection_id FROM {$collections_table} WHERE scope = 'own' AND owner_user_id = %d", $user_id ) );
				if ( ! is_array( $own_ids ) ) { return self::error( 'spdb_privacy_collection_read_failed', __( 'Personal collections could not be inspected.', 'sabri-publishing-dashboard' ), 500 ); }
				$own_ids = array_values( array_filter( array_map( 'strval', $own_ids ) ) );
				if ( $own_ids ) {
					$placeholders = implode( ',', array_fill( 0, count( $own_ids ), '%s' ) );
					$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$items_table} WHERE collection_id IN ({$placeholders})", ...$own_ids ) );
					if ( false === $result ) { return self::error( 'spdb_privacy_collection_erase_failed', __( 'Personal collection items could not be erased.', 'sabri-publishing-dashboard' ), 500 ); }
					$deleted += (int) $result;
				}
				$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$collections_table} WHERE scope = 'own' AND owner_user_id = %d", $user_id ) );
				if ( false === $result ) { return self::error( 'spdb_privacy_collection_erase_failed', __( 'Personal collections could not be erased.', 'sabri-publishing-dashboard' ), 500 ); }
				$deleted += (int) $result;
				$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$links_table} WHERE scope = 'own' AND owner_user_id = %d", $user_id ) );
				if ( false === $result ) { return self::error( 'spdb_privacy_collection_erase_failed', __( 'Personal knowledge links could not be erased.', 'sabri-publishing-dashboard' ), 500 ); }
				$deleted += (int) $result;
				$result = $wpdb->query( $wpdb->prepare( "UPDATE {$links_table} SET owner_user_id = 0, updated_at_gmt = %s WHERE scope = 'institution' AND owner_user_id = %d", $now, $user_id ) );
				if ( false === $result ) { return self::error( 'spdb_privacy_pseudonymize_failed', __( 'Institutional knowledge-link references could not be pseudonymized.', 'sabri-publishing-dashboard' ), 500 ); }
				$pseudonymized += (int) $result;

				$pattern = '%' . $wpdb->esc_like( (string) $user_id ) . '%';
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT collection_id, owner_user_id, contributors_json FROM {$collections_table} WHERE scope = 'institution' AND (owner_user_id = %d OR contributors_json LIKE %s) LIMIT 5000", $user_id, $pattern ), self::array_output() );
				if ( ! is_array( $rows ) ) { return self::error( 'spdb_privacy_collection_read_failed', __( 'Institutional collection references could not be inspected.', 'sabri-publishing-dashboard' ), 500 ); }
				foreach ( $rows as $row ) {
					$contributors = self::decode_object( $row['contributors_json'] ?? '' );
					if ( self::is_list_array( $contributors ) ) {
						$contributors = array_values( array_filter( array_map( 'intval', $contributors ), static fn( int $id ): bool => $id > 0 && $id !== $user_id ) );
					} else {
						$contributors = array();
					}
					$encoded = self::encode_json( $contributors );
					if ( is_wp_error( $encoded ) ) { return $encoded; }
					$result = $wpdb->update(
						$collections_table,
						array( 'owner_user_id' => (int) ( $row['owner_user_id'] ?? 0 ) === $user_id ? 0 : (int) $row['owner_user_id'], 'contributors_json' => $encoded, 'updated_at_gmt' => $now ),
						array( 'collection_id' => (string) $row['collection_id'], 'scope' => 'institution' )
					);
					if ( false === $result ) { return self::error( 'spdb_privacy_pseudonymize_failed', __( 'Institutional collection references could not be pseudonymized.', 'sabri-publishing-dashboard' ), 500 ); }
					$pseudonymized += (int) $result;
				}

				$audit = $this->append_audit( 0, 'privacy_erasure_completed', 'user:' . $user_id, array( 'deleted_personal_rows' => $deleted, 'pseudonymized_rows' => $pseudonymized ) );
				if ( is_wp_error( $audit ) ) {
					return $audit;
				}
				return array( 'items_removed' => $deleted, 'items_pseudonymized' => $pseudonymized, 'items_retained' => $pseudonymized > 0, 'messages' => array() );
			},
			'privacy_erase'
		);
	}

	/**
	 * Delete expired temporary rows and stale completed jobs.
	 *
	 * @return array<string,int>|WP_Error
	 */
	public function cleanup_retention( array $settings ) {
		return $this->atomic(
			function () use ( $settings ) {
				global $wpdb;
				$now = current_time( 'mysql', true );
				$deleted = array( 'metrics' => 0, 'exports' => 0, 'jobs' => 0, 'tasks' => 0, 'health' => 0 );
				$queries = array(
					'metrics' => $wpdb->prepare( 'DELETE FROM ' . SPDB_Operations_Schema::table( 'metric_snapshots' ) . ' WHERE expires_at_gmt <= %s', $now ),
					'exports' => $wpdb->prepare( 'DELETE FROM ' . SPDB_Operations_Schema::table( 'export_jobs' ) . ' WHERE expires_at_gmt <= %s', $now ),
					'health'  => $wpdb->prepare( 'DELETE FROM ' . SPDB_Operations_Schema::table( 'adapter_health' ) . ' WHERE expires_at_gmt <= %s', $now ),
					'jobs'    => $wpdb->prepare(
						'DELETE FROM ' . SPDB_Operations_Schema::table( 'background_jobs' ) . " WHERE status IN ('completed','dead_letter') AND updated_at_gmt < %s",
						gmdate( 'Y-m-d H:i:s', time() - max( 30, (int) $settings['failed_job_retention_days'] ) * DAY_IN_SECONDS )
					),
					'tasks'   => $wpdb->prepare(
						'DELETE FROM ' . SPDB_Operations_Schema::table( 'tasks' ) . " WHERE status IN ('completed','cancelled') AND updated_at_gmt < %s",
						gmdate( 'Y-m-d H:i:s', time() - max( 365, (int) $settings['task_retention_days'] ) * DAY_IN_SECONDS )
					),
				);
				foreach ( $queries as $key => $sql ) {
					$result = $wpdb->query( $sql );
					if ( false === $result ) {
						return self::error( 'spdb_retention_cleanup_failed', __( 'File 23 retention cleanup failed.', 'sabri-publishing-dashboard' ), 500 );
					}
					$deleted[ $key ] = (int) $result;
				}
				$audit = $this->append_audit( 0, 'retention_cleanup_completed', 'operations', $deleted );
				return is_wp_error( $audit ) ? $audit : $deleted;
			},
			'retention_cleanup'
		);
	}

	/** @return array<string,mixed> */
	private function privacy_collection_export( int $user_id, int $page, int $per_page ): array {
		global $wpdb;
		$collections_table = SPDB_Collections_Schema::collections_table();
		$items_table       = SPDB_Collections_Schema::items_table();
		$links_table       = SPDB_Collections_Schema::links_table();
		$pattern           = '%' . $wpdb->esc_like( (string) $user_id ) . '%';
		$offset            = ( $page - 1 ) * $per_page;
		$raw = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$collections_table} WHERE owner_user_id = %d OR contributors_json LIKE %s ORDER BY id ASC LIMIT %d OFFSET %d", $user_id, $pattern, $per_page, $offset ), self::array_output() );
		$raw = is_array( $raw ) ? $raw : array();
		$collections = array_values( array_filter( $raw, static function ( array $row ) use ( $user_id ): bool {
			if ( (int) ( $row['owner_user_id'] ?? 0 ) === $user_id ) { return true; }
			$contributors = self::decode_object( $row['contributors_json'] ?? '' );
			return in_array( $user_id, array_map( 'intval', $contributors ), true );
		} ) );
		$ids = array_values( array_filter( array_map( static fn( array $row ): string => (string) ( $row['collection_id'] ?? '' ), $collections ) ) );
		$items = array();
		if ( $ids ) {
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%s' ) );
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$items_table} WHERE collection_id IN ({$placeholders}) ORDER BY id ASC LIMIT 5000", ...$ids ), self::array_output() );
			$items = is_array( $rows ) ? $rows : array();
		}
		$links = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$links_table} WHERE owner_user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id, $per_page, $offset ), self::array_output() );
		return array(
			'data' => array( 'collections' => $collections, 'items' => $items, 'knowledge_links' => is_array( $links ) ? $links : array() ),
			'has_more' => count( $raw ) === $per_page || ( is_array( $links ) && count( $links ) === $per_page ),
		);
	}

	private static function is_list_array( array $value ): bool {
		return array() === $value || array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	public function normalize_task_row( array $row ): array {
		return array(
			'task_id'          => sanitize_key( (string) ( $row['task_id'] ?? '' ) ),
			'owner_user_id'    => max( 0, (int) ( $row['owner_user_id'] ?? 0 ) ),
			'assignee_user_id' => max( 0, (int) ( $row['assignee_user_id'] ?? 0 ) ),
			'scope'            => sanitize_key( (string) ( $row['scope'] ?? '' ) ),
			'provider_key'     => sanitize_key( (string) ( $row['provider_key'] ?? '' ) ),
			'object_type'      => sanitize_key( (string) ( $row['object_type'] ?? '' ) ),
			'object_id'        => substr( (string) ( $row['object_id'] ?? '' ), 0, 128 ),
			'title'            => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'description'      => sanitize_textarea_field( (string) ( $row['description'] ?? '' ) ),
			'priority'         => sanitize_key( (string) ( $row['priority'] ?? '' ) ),
			'status'           => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'due_at_gmt'       => self::mysql_datetime( $row['due_at_gmt'] ?? '' ),
			'version'          => max( 1, (int) ( $row['version'] ?? 1 ) ),
			'created_at_gmt'   => self::mysql_datetime( $row['created_at_gmt'] ?? '' ),
			'updated_at_gmt'   => self::mysql_datetime( $row['updated_at_gmt'] ?? '' ),
			'completed_at_gmt' => self::mysql_datetime( $row['completed_at_gmt'] ?? '' ),
		);
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private function normalize_delegation_row( array $row ): array {
		return array(
			'delegation_id'    => sanitize_key( (string) ( $row['delegation_id'] ?? '' ) ),
			'principal_user_id' => max( 0, (int) ( $row['principal_user_id'] ?? 0 ) ),
			'delegate_user_id' => max( 0, (int) ( $row['delegate_user_id'] ?? 0 ) ),
			'scope'            => self::decode_object( $row['scope_json'] ?? '' ),
			'status'           => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'requires_mfa'     => 1 === (int) ( $row['requires_mfa'] ?? 0 ),
			'starts_at_gmt'    => self::mysql_datetime( $row['starts_at_gmt'] ?? '' ),
			'expires_at_gmt'   => self::mysql_datetime( $row['expires_at_gmt'] ?? '' ),
			'version'          => max( 1, (int) ( $row['version'] ?? 1 ) ),
			'created_at_gmt'   => self::mysql_datetime( $row['created_at_gmt'] ?? '' ),
			'revoked_at_gmt'   => self::mysql_datetime( $row['revoked_at_gmt'] ?? '' ),
		);
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private function normalize_rule_row( array $row ): array {
		return array(
			'rule_id'         => sanitize_key( (string) ( $row['rule_id'] ?? '' ) ),
			'owner_user_id'   => max( 0, (int) ( $row['owner_user_id'] ?? 0 ) ),
			'rule_type'       => sanitize_key( (string) ( $row['rule_type'] ?? '' ) ),
			'event_key'       => sanitize_key( (string) ( $row['event_key'] ?? '' ) ),
			'condition'       => self::decode_object( $row['condition_json'] ?? '' ),
			'action'          => self::decode_object( $row['action_json'] ?? '' ),
			'status'          => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'version'         => max( 1, (int) ( $row['version'] ?? 1 ) ),
			'last_run_at_gmt' => self::mysql_datetime( $row['last_run_at_gmt'] ?? '' ),
			'next_run_at_gmt' => self::mysql_datetime( $row['next_run_at_gmt'] ?? '' ),
			'created_at_gmt'  => self::mysql_datetime( $row['created_at_gmt'] ?? '' ),
			'updated_at_gmt'  => self::mysql_datetime( $row['updated_at_gmt'] ?? '' ),
		);
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private function normalize_metric_row( array $row ): array {
		$value = self::decode_object( $row['value_json'] ?? '' );
		return array(
			'snapshot_id'      => sanitize_key( (string) ( $row['snapshot_id'] ?? '' ) ),
			'provider_key'     => sanitize_key( (string) ( $row['provider_key'] ?? '' ) ),
			'metric_key'       => sanitize_key( (string) ( $row['metric_key'] ?? '' ) ),
			'scope'            => sanitize_key( (string) ( $row['scope'] ?? '' ) ),
			'owner_user_id'    => max( 0, (int) ( $row['owner_user_id'] ?? 0 ) ),
			'period_start_gmt' => self::mysql_datetime( $row['period_start_gmt'] ?? '' ),
			'period_end_gmt'   => self::mysql_datetime( $row['period_end_gmt'] ?? '' ),
			'definition_hash'  => (string) ( $row['definition_hash'] ?? '' ),
			'value'            => $value['value'] ?? null,
			'unit'             => sanitize_key( (string) ( $value['unit'] ?? 'count' ) ),
			'label'            => sanitize_text_field( (string) ( $value['label'] ?? '' ) ),
			'definition'       => sanitize_text_field( (string) ( $value['definition'] ?? __( 'Cached provider-owned aggregate metric.', 'sabri-publishing-dashboard' ) ) ),
			'interval'         => in_array( sanitize_key( (string) ( $value['interval'] ?? 'custom' ) ), array( 'realtime', 'hourly', 'daily', 'weekly', 'monthly', 'custom' ), true ) ? sanitize_key( (string) ( $value['interval'] ?? 'custom' ) ) : 'custom',
			'cohort_count'     => max( 0, (int) ( $row['cohort_count'] ?? 0 ) ),
			'privacy_threshold' => max( 2, (int) ( $row['privacy_threshold'] ?? 5 ) ),
			'generated_at_gmt' => self::mysql_datetime( $row['generated_at_gmt'] ?? '' ),
			'expires_at_gmt'   => self::mysql_datetime( $row['expires_at_gmt'] ?? '' ),
		);
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private function normalize_export_row( array $row ): array {
		return array(
			'export_id'      => sanitize_key( (string) ( $row['export_id'] ?? '' ) ),
			'owner_user_id'  => max( 0, (int) ( $row['owner_user_id'] ?? 0 ) ),
			'report_key'     => sanitize_key( (string) ( $row['report_key'] ?? '' ) ),
			'format'         => sanitize_key( (string) ( $row['format'] ?? '' ) ),
			'scope'          => sanitize_key( (string) ( $row['scope'] ?? '' ) ),
			'status'         => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'filters'        => self::decode_object( $row['filters_json'] ?? '' ),
			'storage_ref'    => substr( (string) ( $row['storage_ref'] ?? '' ), 0, 255 ),
			'file_hash'      => (string) ( $row['file_hash'] ?? '' ),
			'row_count'      => max( 0, (int) ( $row['row_count'] ?? 0 ) ),
			'error_code'     => sanitize_key( (string) ( $row['error_code'] ?? '' ) ),
			'created_at_gmt' => self::mysql_datetime( $row['created_at_gmt'] ?? '' ),
			'updated_at_gmt' => self::mysql_datetime( $row['updated_at_gmt'] ?? '' ),
			'expires_at_gmt' => self::mysql_datetime( $row['expires_at_gmt'] ?? '' ),
		);
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private function normalize_job_row( array $row ): array {
		return array(
			'job_id'          => sanitize_key( (string) ( $row['job_id'] ?? '' ) ),
			'job_type'        => sanitize_key( (string) ( $row['job_type'] ?? '' ) ),
			'owner_user_id'   => max( 0, (int) ( $row['owner_user_id'] ?? 0 ) ),
			'payload'         => self::decode_object( $row['payload_json'] ?? '' ),
			'status'          => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'attempts'        => max( 0, (int) ( $row['attempts'] ?? 0 ) ),
			'max_attempts'    => max( 1, (int) ( $row['max_attempts'] ?? 1 ) ),
			'available_at_gmt' => self::mysql_datetime( $row['available_at_gmt'] ?? '' ),
			'locked_at_gmt'   => self::mysql_datetime( $row['locked_at_gmt'] ?? '' ),
			'lock_token'      => (string) ( $row['lock_token'] ?? '' ),
			'last_error_code' => sanitize_key( (string) ( $row['last_error_code'] ?? '' ) ),
			'idempotency_key' => substr( (string) ( $row['idempotency_key'] ?? '' ), 0, 128 ),
			'created_at_gmt'  => self::mysql_datetime( $row['created_at_gmt'] ?? '' ),
			'updated_at_gmt'  => self::mysql_datetime( $row['updated_at_gmt'] ?? '' ),
			'finished_at_gmt' => self::mysql_datetime( $row['finished_at_gmt'] ?? '' ),
		);
	}


	/**
	 * Execute a bounded repository mutation atomically.
	 *
	 * Reuses the caller transaction through a savepoint where one already
	 * exists, otherwise opens and owns a short transaction. All errors roll
	 * back the exact File 23-owned mutation scope.
	 *
	 * @param callable():mixed $callback Mutation callback.
	 * @return mixed|WP_Error
	 */
	private function atomic( callable $callback, string $label ) {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'query' ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return self::error( 'spdb_transaction_unavailable', __( 'The database transaction service is unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}

		$label     = sanitize_key( $label );
		$savepoint = 'spdb_' . substr( hash( 'sha256', $label . '|' . wp_generate_uuid4() ), 0, 24 );
		$nested    = 1 === (int) $wpdb->get_var( 'SELECT @@in_transaction' );
		$opened    = $nested
			? false !== $wpdb->query( "SAVEPOINT {$savepoint}" )
			: false !== $wpdb->query( 'START TRANSACTION' );
		if ( ! $opened ) {
			return self::error( 'spdb_transaction_start_failed', __( 'The database transaction could not be started.', 'sabri-publishing-dashboard' ), 503 );
		}

		try {
			$result = $callback();
			if ( is_wp_error( $result ) ) {
				if ( $nested ) {
					$wpdb->query( "ROLLBACK TO SAVEPOINT {$savepoint}" );
					$wpdb->query( "RELEASE SAVEPOINT {$savepoint}" );
				} else {
					$wpdb->query( 'ROLLBACK' );
				}
				return $result;
			}

			$committed = $nested
				? false !== $wpdb->query( "RELEASE SAVEPOINT {$savepoint}" )
				: false !== $wpdb->query( 'COMMIT' );
			if ( ! $committed ) {
				if ( $nested ) {
					$wpdb->query( "ROLLBACK TO SAVEPOINT {$savepoint}" );
					$wpdb->query( "RELEASE SAVEPOINT {$savepoint}" );
				} else {
					$wpdb->query( 'ROLLBACK' );
				}
				return self::error( 'spdb_transaction_commit_failed', __( 'The database transaction could not be committed.', 'sabri-publishing-dashboard' ), 500 );
			}
			return $result;
		} catch ( Throwable $error ) {
			if ( $nested ) {
				$wpdb->query( "ROLLBACK TO SAVEPOINT {$savepoint}" );
				$wpdb->query( "RELEASE SAVEPOINT {$savepoint}" );
			} else {
				$wpdb->query( 'ROLLBACK' );
			}
			return self::error( 'spdb_transaction_exception', __( 'The database transaction failed safely.', 'sabri-publishing-dashboard' ), 500 );
		}
	}

	/** @param mixed $value @return string|WP_Error */
	private static function encode_json( $value ) {
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $json || strlen( $json ) > 1048576 ) {
			return self::error( 'spdb_json_invalid', __( 'The operational metadata payload is invalid or too large.', 'sabri-publishing-dashboard' ), 400 );
		}
		return $json;
	}

	/** @param mixed $value @return array<string,mixed> */
	private static function decode_object( $value ): array {
		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}
		$decoded = json_decode( $value, true, 32 );
		return is_array( $decoded ) ? $decoded : array();
	}

	/** @param array<string,mixed> $payload @return array<string,mixed> */
	private static function sanitize_audit_payload( array $payload ): array {
		$out = array();
		foreach ( array_slice( $payload, 0, 30, true ) as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key || 1 === preg_match( '/(?:password|token|secret|patient|message|email|phone|address|ip|consent|document)/', $key ) ) {
				continue;
			}
			if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$out[ $key ] = $value;
			} elseif ( is_scalar( $value ) ) {
				$out[ $key ] = substr( sanitize_text_field( (string) $value ), 0, 240 );
			} elseif ( is_array( $value ) ) {
				$out[ $key ] = array_slice( array_values( array_map( 'sanitize_text_field', array_filter( $value, 'is_scalar' ) ) ), 0, 20 );
			}
		}
		return $out;
	}

	private static function mysql_datetime( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ? $value : '';
	}

	private static function id( string $prefix ): string {
		return sanitize_key( $prefix ) . '_' . str_replace( '-', '', wp_generate_uuid4() );
	}

	private static function array_output() {
		return defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A';
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
