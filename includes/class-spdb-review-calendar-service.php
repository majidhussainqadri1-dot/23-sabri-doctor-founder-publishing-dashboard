<?php
/**
 * Federate native review queues and native publishing calendars.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Review_Calendar_Service {
	private const MAX_PROVIDERS = 20;
	private const MAX_ITEMS     = 200;

	private SPDB_Adapter_Registry $registry;

	public function __construct( SPDB_Adapter_Registry $registry ) {
		$this->registry = $registry;
	}

	/** @return array<string,mixed> */
	public function review_queue( array $input = array() ): array {
		$context = $this->context();
		$result  = $this->empty_result( 'review' );
		if ( empty( $context['can_review'] ) ) {
			$result['alerts'][] = $this->alert( 'review_access_denied', 'warning', __( 'The Universal Review Inbox is available only to currently approved users with review-queue authority.', 'sabri-publishing-dashboard' ) );
			return $result;
		}
		$query = SPDB_Review_Calendar_Validator::normalize_query( $input, 'review' );
		$result['query'] = $query;
		$seen = array();
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			if ( $result['provider_count'] >= self::MAX_PROVIDERS || count( $result['items'] ) >= self::MAX_ITEMS ) {
				$result['truncated'] = true;
				break;
			}
			if ( ! $adapter instanceof SPDB_Review_Calendar_Provider_Adapter ) {
				continue;
			}
			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) || ! $this->review_provider_allowed( $provider_key, $metadata ) ) {
				continue;
			}
			if ( isset( $query['provider'] ) && $query['provider'] !== $provider_key ) {
				continue;
			}
			++$result['provider_count'];
			try {
				$raw = $adapter->get_review_queue( $context, $query );
			} catch ( Throwable $throwable ) {
				$raw = new WP_Error( 'spdb_review_provider_exception', __( 'The native review provider failed.', 'sabri-publishing-dashboard' ) );
			}
			if ( is_wp_error( $raw ) ) {
				++$result['provider_errors'];
				continue;
			}
			$projection = SPDB_Review_Calendar_Validator::normalize_review_queue( $raw, $provider_key, $metadata, $context );
			if ( is_wp_error( $projection ) ) {
				++$result['provider_errors'];
				continue;
			}
			$result['reported_total'] += (int) $projection['reported_total'];
			$result['has_more'] = $result['has_more'] || ! empty( $projection['has_more'] );
			foreach ( $projection['items'] as $item ) {
				if ( ! $this->review_item_matches_query( $item, $query, $context ) ) {
					continue;
				}
				$signature = $provider_key . ':' . $item['object_type'] . ':' . $item['object_id'];
				if ( isset( $seen[ $signature ] ) ) {
					continue;
				}
				$seen[ $signature ] = true;
				$item['allowed_operations'] = $this->authorized_operations( $adapter, $metadata, $item, 'review', $context );
				if ( count( $result['items'] ) >= self::MAX_ITEMS ) {
					$result['truncated'] = true;
					break 2;
				}
				$result['items'][] = $item;
			}
		}
		usort( $result['items'], array( $this, 'sort_review_items' ) );
		$this->finalize_result( $result, __( 'No compatible native review provider is available. File 23 does not create a replacement moderation queue.', 'sabri-publishing-dashboard' ) );
		return $result;
	}

	/** @return array<string,mixed> */
	public function calendar( array $input = array() ): array {
		$context = $this->context();
		$result  = $this->empty_result( 'calendar' );
		if ( empty( $context['can_view_calendar'] ) ) {
			$result['alerts'][] = $this->alert( 'calendar_access_denied', 'warning', __( 'The Federated Publishing Calendar is unavailable for the current account state or capability set.', 'sabri-publishing-dashboard' ) );
			return $result;
		}
		$query = SPDB_Review_Calendar_Validator::normalize_query( $input, 'calendar' );
		$result['query'] = $query;
		$seen = array();
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			if ( $result['provider_count'] >= self::MAX_PROVIDERS || count( $result['items'] ) >= self::MAX_ITEMS ) {
				$result['truncated'] = true;
				break;
			}
			if ( ! $adapter instanceof SPDB_Review_Calendar_Provider_Adapter ) {
				continue;
			}
			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) || ! $this->calendar_provider_allowed( $provider_key, $metadata ) ) {
				continue;
			}
			if ( isset( $query['provider'] ) && $query['provider'] !== $provider_key ) {
				continue;
			}
			++$result['provider_count'];
			try {
				$raw = $adapter->get_calendar_entries( $context, $query );
			} catch ( Throwable $throwable ) {
				$raw = new WP_Error( 'spdb_calendar_provider_exception', __( 'The native calendar provider failed.', 'sabri-publishing-dashboard' ) );
			}
			if ( is_wp_error( $raw ) ) {
				++$result['provider_errors'];
				continue;
			}
			$projection = SPDB_Review_Calendar_Validator::normalize_calendar( $raw, $provider_key, $metadata, $context );
			if ( is_wp_error( $projection ) ) {
				++$result['provider_errors'];
				continue;
			}
			$result['reported_total'] += (int) $projection['reported_total'];
			$result['has_more'] = $result['has_more'] || ! empty( $projection['has_more'] );
			foreach ( $projection['items'] as $item ) {
				if ( ! $this->calendar_item_matches_query( $item, $query, $context ) ) {
					continue;
				}
				$signature = $provider_key . ':' . $item['object_type'] . ':' . $item['object_id'];
				if ( isset( $seen[ $signature ] ) ) {
					continue;
				}
				$seen[ $signature ] = true;
				$item['allowed_operations'] = $this->authorized_operations( $adapter, $metadata, $item, 'calendar', $context );
				if ( count( $result['items'] ) >= self::MAX_ITEMS ) {
					$result['truncated'] = true;
					break 2;
				}
				$result['items'][] = $item;
			}
		}
		usort( $result['items'], array( $this, 'sort_calendar_items' ) );
		$this->finalize_result( $result, __( 'No compatible native schedule provider is available. File 23 does not create or own a replacement schedule table.', 'sabri-publishing-dashboard' ) );
		return $result;
	}

	/** @return array<string,mixed> */
	public function context(): array {
		$user_id     = get_current_user_id();
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$available   = $user_id > 0 && SPDB_Membership_Guard::is_available();
		$approved    = $available && SPDB_Membership_Guard::is_user_approved( $user_id );
		$is_founder  = $approved && function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
		$can_review  = $approved && SPDB_Capabilities::current_user_can( 'spdb_view_review_queue' );
		return array(
			'user_id'           => $user_id,
			'account_status'    => $available ? SPDB_Membership_Guard::user_status( $user_id ) : 'dependency_unavailable',
			'is_approved'       => $approved,
			'is_founder'        => $is_founder,
			'can_review'        => $can_review,
			'can_decide_review' => $can_review && SPDB_Capabilities::current_user_can( 'spdb_review_assigned_content' ),
			'can_view_calendar' => $approved && SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ),
			'can_schedule'      => $approved && SPDB_Capabilities::current_user_can( 'spdb_manage_schedule' ),
			'allowed_scopes'    => $is_founder ? array( 'own', 'institution' ) : array( 'own' ),
			'environment'       => $environment,
		);
	}

	/** @return string[] */
	private function authorized_operations( SPDB_Provider_Adapter $adapter, array $metadata, array $item, string $surface, array $context ): array {
		$projected = is_array( $item['allowed_operations'] ?? null ) ? $item['allowed_operations'] : array();
		if ( 'review' === $surface && empty( $context['can_decide_review'] ) ) {
			return array();
		}
		if ( 'calendar' === $surface && empty( $context['can_schedule'] ) ) {
			return array();
		}
		if ( ! $this->registry->is_environment_write_eligible( (string) $item['provider_key'] ) ) {
			return array();
		}
		try {
			$native = $adapter->get_allowed_operations( (string) $item['object_type'], (string) $item['object_id'] );
		} catch ( Throwable $throwable ) {
			return array();
		}
		if ( ! is_array( $native ) ) {
			return array();
		}
		$definitions = is_array( $metadata['operation_definitions'] ?? null ) ? $metadata['operation_definitions'] : array();
		$result = array();
		foreach ( array_intersect( $projected, $native ) as $operation ) {
			$definition = $definitions[ $operation ] ?? null;
			if ( ! is_array( $definition ) || ! SPDB_Capabilities::current_user_can( (string) $definition['required_capability'] ) ) {
				continue;
			}
			if ( 'review' === $surface && ! empty( $item['separation_required'] ) && (int) $item['author_id'] === (int) $context['user_id'] && in_array( $operation, array( 'approve_review', 'reject_review' ), true ) ) {
				continue;
			}
			$result[] = $operation;
		}
		return array_values( array_unique( $result ) );
	}

	private function review_provider_allowed( string $provider_key, array $metadata ): bool {
		if ( SPDB_Adapter_Registry::ACCEPTANCE_REVOKED === $this->registry->get_acceptance_state( $provider_key ) ) {
			return false;
		}
		return SPDB_Adapter_Registry::CAPABILITY_REVIEW_CAPABLE === (string) ( $metadata['declared_capability'] ?? '' );
	}

	private function calendar_provider_allowed( string $provider_key, array $metadata ): bool {
		if ( SPDB_Adapter_Registry::ACCEPTANCE_REVOKED === $this->registry->get_acceptance_state( $provider_key ) ) {
			return false;
		}
		return in_array(
			(string) ( $metadata['declared_capability'] ?? '' ),
			array( SPDB_Adapter_Registry::CAPABILITY_READ_ONLY, SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE, SPDB_Adapter_Registry::CAPABILITY_REVIEW_CAPABLE ),
			true
		);
	}

	private function review_item_matches_query( array $item, array $query, array $context ): bool {
		if ( isset( $query['review_state'] ) && $query['review_state'] !== $item['review_state'] ) {
			return false;
		}
		if ( isset( $query['assigned'] ) ) {
			if ( 'me' === $query['assigned'] && (int) $item['assigned_reviewer_id'] !== (int) $context['user_id'] ) {
				return false;
			}
			if ( 'unassigned' === $query['assigned'] && 0 !== (int) $item['assigned_reviewer_id'] ) {
				return false;
			}
		}
		$due = '' !== $item['due_at'] ? substr( $item['due_at'], 0, 10 ) : '';
		if ( isset( $query['due_from'] ) && ( '' === $due || $due < $query['due_from'] ) ) {
			return false;
		}
		if ( isset( $query['due_to'] ) && ( '' === $due || $due > $query['due_to'] ) ) {
			return false;
		}
		return true;
	}

	private function calendar_item_matches_query( array $item, array $query, array $context ): bool {
		if ( ! empty( $context['is_founder'] ) ) {
			// Founder may receive institution and own items after provider validation.
		} elseif ( 'own' !== $item['scope'] || (int) $item['author_id'] !== (int) $context['user_id'] ) {
			return false;
		}
		if ( isset( $query['status'] ) && $query['status'] !== $item['status'] ) {
			return false;
		}
		$date = substr( $item['scheduled_at_utc'], 0, 10 );
		if ( isset( $query['date_from'] ) && $date < $query['date_from'] ) {
			return false;
		}
		if ( isset( $query['date_to'] ) && $date > $query['date_to'] ) {
			return false;
		}
		if ( isset( $query['timezone'] ) && $query['timezone'] !== $item['native_timezone'] ) {
			return false;
		}
		return true;
	}

	/** @param array<string,mixed> $result */
	private function finalize_result( array &$result, string $empty_message ): void {
		$result['validated_count'] = count( $result['items'] );
		$result['generated_at_gmt'] = gmdate( 'c' );
		if ( 0 === $result['provider_count'] ) {
			$result['alerts'][] = $this->alert( $result['surface'] . '_provider_unavailable', 'information', $empty_message );
		}
		if ( $result['provider_errors'] > 0 ) {
			$result['alerts'][] = $this->alert( $result['surface'] . '_provider_errors', 'warning', __( 'One or more native providers failed or returned an invalid projection. Valid providers remain available.', 'sabri-publishing-dashboard' ) );
		}
		if ( $result['truncated'] || $result['has_more'] ) {
			$result['alerts'][] = $this->alert( $result['surface'] . '_bounded', 'information', __( 'This federated view reached a safety limit. Additional records remain with their native owners.', 'sabri-publishing-dashboard' ) );
		}
	}

	/** @return array<string,mixed> */
	private function empty_result( string $surface ): array {
		return array(
			'surface'          => $surface,
			'items'            => array(),
			'query'            => array(),
			'alerts'           => array(),
			'provider_count'   => 0,
			'provider_errors'  => 0,
			'reported_total'   => 0,
			'validated_count'  => 0,
			'has_more'         => false,
			'truncated'        => false,
			'generated_at_gmt' => gmdate( 'c' ),
		);
	}

	/** @return array<string,string> */
	private function alert( string $key, string $level, string $message ): array {
		return array( 'key' => $key, 'level' => $level, 'message' => $message );
	}

	private function sort_review_items( array $left, array $right ): int {
		$left_due  = '' !== $left['due_at'] ? $left['due_at'] : '9999-12-31T23:59:59Z';
		$right_due = '' !== $right['due_at'] ? $right['due_at'] : '9999-12-31T23:59:59Z';
		return $left_due <=> $right_due ?: strcasecmp( $left['title'], $right['title'] );
	}

	private function sort_calendar_items( array $left, array $right ): int {
		return $left['scheduled_at_utc'] <=> $right['scheduled_at_utc'] ?: strcasecmp( $left['title'], $right['title'] );
	}
}
