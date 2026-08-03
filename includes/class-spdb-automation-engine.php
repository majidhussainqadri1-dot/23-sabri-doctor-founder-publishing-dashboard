<?php
/**
 * Human-governed, event-driven File 23 automation orchestrator.
 *
 * Rules can enqueue only reviewed File 23 jobs. Native mutations remain the
 * responsibility of accepted adapters and are executed through explicit
 * provider filters with current authorization and version checks.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Automation_Engine {
	private SPDB_Operations_Repository $repository;

	public function __construct( SPDB_Operations_Repository $repository ) {
		$this->repository = $repository;
	}

	public function register(): void {
		add_action( 'spdb/automation_event', array( $this, 'receive_event' ), 10, 2 );
	}

	/** @param array<string,mixed> $payload Privacy-minimized event payload. */
	public function receive_event( string $event_key, array $payload = array() ): void {
		if ( empty( SPDB_Admin_Settings::get()['automation_enabled'] ) ) {
			return;
		}
		$event_key = sanitize_key( $event_key );
		$payload   = $this->sanitize_event_payload( $payload );
		if ( '' === $event_key ) {
			return;
		}
		$rules = $this->repository->list_enabled_rules_for_event( $event_key, 100 );
		if ( ! is_array( $rules ) ) {
			return;
		}
		foreach ( $rules as $rule ) {
			$rule_id = sanitize_key( (string) ( $rule['rule_id'] ?? '' ) );
			$version = max( 1, (int) ( $rule['version'] ?? 1 ) );
			if ( '' === $rule_id || ! $this->condition_matches( $rule['condition'] ?? array(), $payload ) ) {
				continue;
			}
			$dedupe = 'automation:' . $rule_id . ':' . $version . ':' . hash( 'sha256', wp_json_encode( $payload ) ?: '' );
			$this->repository->enqueue_job(
				'automation_rule',
				max( 0, (int) ( $rule['owner_user_id'] ?? 0 ) ),
				array( 'rule_id' => $rule_id, 'rule_version' => $version, 'event_key' => $event_key, 'event' => $payload ),
				$dedupe,
				(int) SPDB_Admin_Settings::get()['job_max_attempts']
			);
		}
	}

	/** @param array<string,mixed> $job @return true|WP_Error */
	public function execute( array $job ) {
		if ( empty( SPDB_Admin_Settings::get()['automation_enabled'] ) ) {
			return $this->error( 'spdb_automation_disabled', __( 'Automation is disabled.', 'sabri-publishing-dashboard' ), 503 );
		}
		$payload = is_array( $job['payload'] ?? null ) ? $job['payload'] : array();
		$rule_id = sanitize_key( (string) ( $payload['rule_id'] ?? '' ) );
		$expected_version = max( 1, (int) ( $payload['rule_version'] ?? 0 ) );
		if ( '' === $rule_id ) {
			return $this->error( 'spdb_automation_job_invalid', __( 'The automation job is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$rule = $this->repository->get_rule( $rule_id, 0, true );
		if ( is_wp_error( $rule ) ) {
			return $rule;
		}
		if ( 'enabled' !== ( $rule['status'] ?? '' ) || (int) $rule['version'] !== $expected_version ) {
			return $this->error( 'spdb_automation_rule_stale', __( 'The automation rule changed or was disabled before execution.', 'sabri-publishing-dashboard' ), 409 );
		}
		$event = is_array( $payload['event'] ?? null ) ? $this->sanitize_event_payload( $payload['event'] ) : array();
		$action = is_array( $rule['action'] ?? null ) ? $rule['action'] : array();
		if ( empty( $action['human_confirmation'] ) || empty( $action['idempotent'] ) ) {
			return $this->error( 'spdb_automation_rule_unsafe', __( 'The automation rule is not marked as human-governed and idempotent.', 'sabri-publishing-dashboard' ), 409 );
		}

		$result = apply_filters( 'spdb/automation_action_result', null, $rule, $event, $job );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( true !== $result ) {
			return $this->error( 'spdb_automation_action_unavailable', __( 'No accepted native provider executed the automation action.', 'sabri-publishing-dashboard' ), 503 );
		}
		$recorded = $this->repository->mark_rule_run( $rule_id, $expected_version );
		return is_wp_error( $recorded ) ? $recorded : true;
	}

	/** @param array<string,mixed> $condition @param array<string,mixed> $payload */
	private function condition_matches( array $condition, array $payload ): bool {
		foreach ( $condition as $key => $expected ) {
			$key = sanitize_key( (string) $key );
			if ( 'event_key' === $key ) {
				continue;
			}
			if ( ! array_key_exists( $key, $payload ) || ! is_scalar( $expected ) || ! is_scalar( $payload[ $key ] ) || (string) $expected !== (string) $payload[ $key ] ) {
				return false;
			}
		}
		return true;
	}

	/** @param array<string,mixed> $payload @return array<string,mixed> */
	private function sanitize_event_payload( array $payload ): array {
		$out = array();
		foreach ( array_slice( $payload, 0, 30, true ) as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key || preg_match( '/(?:patient|message|email|phone|address|token|secret|password|consent|document|ip)/', $key ) || ! is_scalar( $value ) ) {
				continue;
			}
			$out[ $key ] = substr( sanitize_text_field( (string) $value ), 0, 240 );
		}
		return $out;
	}

	private function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
