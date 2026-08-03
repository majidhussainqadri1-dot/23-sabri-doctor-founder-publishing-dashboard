<?php
/**
 * Validate Phase 23E native review and calendar projections.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Review_Calendar_Validator {
	public const MAX_PROVIDER_ITEMS = 100;
	public const MAX_FLAGS          = 12;
	public const MAX_OPERATIONS     = 8;
	public const MAX_REPORTED_TOTAL = 1000000000;

	/** @return array<string,mixed>|WP_Error */
	public static function normalize_query( array $input, string $surface ) {
		if ( ! in_array( $surface, array( 'review', 'calendar' ), true ) ) {
			return self::error( 'spdb_projection_surface_invalid', 'The requested projection surface is invalid.', 400 );
		}

		$allowed = 'review' === $surface
			? array( 'provider', 'review_state', 'assigned', 'due_from', 'due_to', 'page', 'per_page' )
			: array( 'provider', 'status', 'date_from', 'date_to', 'timezone', 'page', 'per_page' );
		$query = array();

		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			if ( ! is_scalar( $input[ $key ] ) ) {
				return self::error( 'spdb_projection_query_shape_invalid', 'A projection filter has an invalid shape.', 400 );
			}
			$value = trim( (string) $input[ $key ] );
			if ( '' === $value ) {
				if ( in_array( $key, array( 'page', 'per_page' ), true ) ) {
					return self::error( 'spdb_projection_query_integer_invalid', 'Pagination values must be positive integers.', 400 );
				}
				continue;
			}
			$query[ $key ] = $value;
		}

		$page = self::query_integer( $query['page'] ?? '1', 1, 1000 );
		if ( is_wp_error( $page ) ) {
			return $page;
		}
		$per_page = self::query_integer( $query['per_page'] ?? '25', 1, 50 );
		if ( is_wp_error( $per_page ) ) {
			return $per_page;
		}
		$query['page']     = $page;
		$query['per_page'] = $per_page;

		if ( isset( $query['provider'] ) && ! SPDB_Adapter_Registry::is_canonical_key( $query['provider'] ) ) {
			return self::error( 'spdb_projection_provider_filter_invalid', 'The provider filter is invalid.', 400 );
		}
		if ( isset( $query['assigned'] ) && ! in_array( $query['assigned'], array( 'me', 'unassigned', 'all' ), true ) ) {
			return self::error( 'spdb_projection_assignment_filter_invalid', 'The reviewer assignment filter is invalid.', 400 );
		}
		if ( isset( $query['review_state'] ) && ! in_array( $query['review_state'], self::review_states(), true ) ) {
			return self::error( 'spdb_projection_review_filter_invalid', 'The review-state filter is invalid.', 400 );
		}
		if ( isset( $query['status'] ) && ! in_array( $query['status'], self::calendar_states(), true ) ) {
			return self::error( 'spdb_projection_calendar_filter_invalid', 'The calendar-state filter is invalid.', 400 );
		}
		foreach ( array( 'due_from', 'due_to', 'date_from', 'date_to' ) as $date_key ) {
			if ( isset( $query[ $date_key ] ) && ! self::valid_date( $query[ $date_key ] ) ) {
				return self::error( 'spdb_projection_date_filter_invalid', 'A date filter must use a real YYYY-MM-DD date.', 400 );
			}
		}
		if ( isset( $query['due_from'], $query['due_to'] ) && $query['due_from'] > $query['due_to'] ) {
			return self::error( 'spdb_projection_date_range_invalid', 'The review due-date range is reversed.', 400 );
		}
		if ( isset( $query['date_from'], $query['date_to'] ) && $query['date_from'] > $query['date_to'] ) {
			return self::error( 'spdb_projection_date_range_invalid', 'The calendar date range is reversed.', 400 );
		}
		if ( isset( $query['timezone'] ) && ! self::valid_timezone( $query['timezone'] ) ) {
			return self::error( 'spdb_projection_timezone_filter_invalid', 'The time-zone filter is invalid.', 400 );
		}

		return $query;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function normalize_review_queue( $raw, string $provider_key, array $metadata, array $context ) {
		$envelope = self::normalize_envelope( $raw, 'review' );
		if ( is_wp_error( $envelope ) ) {
			return $envelope;
		}
		$provider_name = self::required_text( $metadata['provider_name'] ?? $provider_key, 160, 'spdb_review_provider_name_invalid' );
		if ( is_wp_error( $provider_name ) ) {
			return $provider_name;
		}
		$items = array();
		$seen  = array();
		foreach ( $envelope['items'] as $item ) {
			$normalized = self::normalize_review_item( $item, $provider_key, $metadata, $context );
			if ( is_wp_error( $normalized ) ) {
				return $normalized;
			}
			$signature = $normalized['object_type'] . ':' . $normalized['object_id'];
			if ( isset( $seen[ $signature ] ) ) {
				return self::error( 'spdb_review_duplicate_item', 'The provider returned a duplicate review item.' );
			}
			$seen[ $signature ] = true;
			$items[] = $normalized;
		}
		return array(
			'provider_key'   => $provider_key,
			'provider_name'  => $provider_name,
			'items'          => $items,
			'reported_total' => $envelope['total'],
			'has_more'       => $envelope['has_more'],
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public static function normalize_calendar( $raw, string $provider_key, array $metadata, array $context ) {
		$envelope = self::normalize_envelope( $raw, 'calendar' );
		if ( is_wp_error( $envelope ) ) {
			return $envelope;
		}
		$provider_name = self::required_text( $metadata['provider_name'] ?? $provider_key, 160, 'spdb_calendar_provider_name_invalid' );
		if ( is_wp_error( $provider_name ) ) {
			return $provider_name;
		}
		$items = array();
		$seen  = array();
		foreach ( $envelope['items'] as $item ) {
			$normalized = self::normalize_calendar_item( $item, $provider_key, $metadata, $context );
			if ( is_wp_error( $normalized ) ) {
				return $normalized;
			}
			$signature = $normalized['object_type'] . ':' . $normalized['object_id'];
			if ( isset( $seen[ $signature ] ) ) {
				return self::error( 'spdb_calendar_duplicate_item', 'The provider returned a duplicate calendar item.' );
			}
			$seen[ $signature ] = true;
			$items[] = $normalized;
		}
		return array(
			'provider_key'   => $provider_key,
			'provider_name'  => $provider_name,
			'items'          => $items,
			'reported_total' => $envelope['total'],
			'has_more'       => $envelope['has_more'],
		);
	}

	/** @return array<string,mixed>|WP_Error */
	private static function normalize_envelope( $raw, string $surface ) {
		if ( ! is_array( $raw ) || ! isset( $raw['items'] ) || ! self::is_list( $raw['items'] ) || count( $raw['items'] ) > self::MAX_PROVIDER_ITEMS ) {
			return self::error( 'spdb_' . $surface . '_projection_invalid', 'The provider returned a malformed or oversized projection.' );
		}
		$total = self::strict_nonnegative_integer( $raw['total'] ?? count( $raw['items'] ), 'spdb_' . $surface . '_total_invalid' );
		if ( is_wp_error( $total ) ) {
			return $total;
		}
		$has_more = $raw['has_more'] ?? false;
		if ( ! is_bool( $has_more ) ) {
			return self::error( 'spdb_' . $surface . '_has_more_invalid', 'The provider returned an invalid continuation flag.' );
		}
		return array( 'items' => $raw['items'], 'total' => $total, 'has_more' => $has_more );
	}

	/** @return array<string,mixed>|WP_Error */
	private static function normalize_review_item( $raw, string $provider_key, array $metadata, array $context ) {
		if ( ! is_array( $raw ) ) {
			return self::error( 'spdb_review_item_invalid', 'A review item is malformed.' );
		}
		$reference = self::reference( $raw, $provider_key, $metadata, $context );
		if ( is_wp_error( $reference ) ) {
			return $reference;
		}
		$state = self::exact_value( $raw['review_state'] ?? '', self::review_states() );
		if ( '' === $state ) {
			return self::error( 'spdb_review_state_invalid', 'A review state is invalid.' );
		}
		$reviewer_id = self::strict_nonnegative_integer( $raw['assigned_reviewer_id'] ?? 0, 'spdb_review_reviewer_invalid' );
		if ( is_wp_error( $reviewer_id ) ) {
			return $reviewer_id;
		}
		$is_founder = ! empty( $context['is_founder'] );
		$user_id    = (int) ( $context['user_id'] ?? 0 );
		if ( ! $is_founder && 0 !== $reviewer_id && $reviewer_id !== $user_id ) {
			return self::error( 'spdb_review_assignment_forbidden', 'A review item is assigned to another reviewer.', 403 );
		}
		$reviewer_name = self::optional_text( $raw['assigned_reviewer_name'] ?? '', 120, 'spdb_review_reviewer_name_invalid' );
		if ( is_wp_error( $reviewer_name ) ) {
			return $reviewer_name;
		}
		$due_at = self::optional_timestamp( $raw['due_at'] ?? '' );
		if ( is_wp_error( $due_at ) ) {
			return $due_at;
		}
		$url = SPDB_Safe_Destination::normalize( $raw['native_review_url'] ?? '' );
		if ( is_wp_error( $url ) ) {
			return $url;
		}
		$operations = self::normalize_operations( $raw['allowed_operations'] ?? array(), self::review_operations(), $metadata );
		if ( is_wp_error( $operations ) ) {
			return $operations;
		}
		$flags = array();
		foreach ( array( 'privacy_flags', 'safety_flags', 'source_flags', 'copyright_flags' ) as $flag_key ) {
			$normalized_flags = self::normalize_flags( $raw[ $flag_key ] ?? array() );
			if ( is_wp_error( $normalized_flags ) ) {
				return $normalized_flags;
			}
			$flags[ $flag_key ] = $normalized_flags;
		}
		if ( ! array_key_exists( 'separation_required', $raw ) || ! is_bool( $raw['separation_required'] ) ) {
			return self::error( 'spdb_review_separation_flag_invalid', 'The review separation-of-duties flag is missing or invalid.' );
		}
		$separation_required = $raw['separation_required'];
		if ( (int) $reference['author_id'] === $user_id ) {
			$operations = array_values( array_diff( $operations, array( 'approve_review', 'reject_review' ) ) );
		}
		return array_merge(
			$reference,
			array(
				'review_state'          => $state,
				'assigned_reviewer_id'  => $reviewer_id,
				'assigned_reviewer_name'=> $reviewer_name,
				'due_at'                => $due_at,
				'privacy_flags'         => $flags['privacy_flags'],
				'safety_flags'          => $flags['safety_flags'],
				'source_flags'          => $flags['source_flags'],
				'copyright_flags'       => $flags['copyright_flags'],
				'native_review_url'     => $url,
				'allowed_operations'    => $operations,
				'separation_required'   => $separation_required,
			)
		);
	}

	/** @return array<string,mixed>|WP_Error */
	private static function normalize_calendar_item( $raw, string $provider_key, array $metadata, array $context ) {
		if ( ! is_array( $raw ) ) {
			return self::error( 'spdb_calendar_item_invalid', 'A calendar item is malformed.' );
		}
		$reference = self::reference( $raw, $provider_key, $metadata, $context );
		if ( is_wp_error( $reference ) ) {
			return $reference;
		}
		$status = self::exact_value( $raw['status'] ?? '', self::calendar_states() );
		if ( '' === $status ) {
			return self::error( 'spdb_calendar_state_invalid', 'A calendar state is invalid.' );
		}
		$scheduled_at = self::timestamp( $raw['scheduled_at_utc'] ?? '' );
		if ( is_wp_error( $scheduled_at ) ) {
			return $scheduled_at;
		}
		$timezone = is_scalar( $raw['native_timezone'] ?? null ) ? trim( (string) $raw['native_timezone'] ) : '';
		if ( ! self::valid_timezone( $timezone ) ) {
			return self::error( 'spdb_calendar_timezone_invalid', 'The native schedule time zone is invalid.' );
		}
		$url = SPDB_Safe_Destination::normalize( $raw['native_edit_url'] ?? '' );
		if ( is_wp_error( $url ) ) {
			return $url;
		}
		$operations = self::normalize_operations( $raw['allowed_operations'] ?? array(), self::calendar_operations(), $metadata );
		if ( is_wp_error( $operations ) ) {
			return $operations;
		}
		$conflicts = self::normalize_flags( $raw['conflicts'] ?? array() );
		if ( is_wp_error( $conflicts ) ) {
			return $conflicts;
		}
		return array_merge(
			$reference,
			array(
				'status'             => $status,
				'scheduled_at_utc'   => $scheduled_at,
				'native_timezone'    => $timezone,
				'conflicts'          => $conflicts,
				'native_edit_url'    => $url,
				'allowed_operations' => $operations,
			)
		);
	}

	/** @return array<string,mixed>|WP_Error */
	private static function reference( array $raw, string $provider_key, array $metadata, array $context ) {
		$object_type = is_scalar( $raw['object_type'] ?? null ) ? trim( (string) $raw['object_type'] ) : '';
		$object_id   = is_scalar( $raw['object_id'] ?? null ) ? trim( (string) $raw['object_id'] ) : '';
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! in_array( $object_type, $metadata['object_types'] ?? array(), true ) ) {
			return self::error( 'spdb_projection_object_type_invalid', 'The projected object type is invalid.' );
		}
		if ( ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) {
			return self::error( 'spdb_projection_object_id_invalid', 'The projected object identifier is invalid.' );
		}
		$author_id = self::strict_positive_integer( $raw['author_id'] ?? 0, 'spdb_projection_author_invalid' );
		if ( is_wp_error( $author_id ) ) {
			return $author_id;
		}
		$scope = self::exact_value( $raw['scope'] ?? '', array( 'own', 'institution' ) );
		if ( '' === $scope ) {
			return self::error( 'spdb_projection_scope_invalid', 'The projected scope is invalid.' );
		}
		$user_id = (int) ( $context['user_id'] ?? 0 );
		if ( 'own' === $scope && $user_id !== $author_id && empty( $context['can_review'] ) ) {
			return self::error( 'spdb_projection_owner_mismatch', 'The projected object is outside the current user scope.', 403 );
		}
		if ( 'institution' === $scope && empty( $context['is_founder'] ) && empty( $context['can_review'] ) ) {
			return self::error( 'spdb_projection_institution_forbidden', 'Institution scope is not authorized.', 403 );
		}
		$version = is_scalar( $raw['native_version'] ?? null ) ? trim( (string) $raw['native_version'] ) : '';
		if ( '' === $version || strlen( $version ) > 191 || preg_match( '/[\x00-\x1F\x7F]/', $version ) ) {
			return self::error( 'spdb_projection_version_invalid', 'The native object version is invalid.' );
		}
		$last_synced = self::timestamp( $raw['last_synced_at'] ?? '' );
		if ( is_wp_error( $last_synced ) ) {
			return $last_synced;
		}
		$title = self::required_text( $raw['title'] ?? '', 200, 'spdb_projection_title_invalid' );
		if ( is_wp_error( $title ) ) {
			return $title;
		}
		$author_name = self::required_text( $raw['author_name'] ?? '', 120, 'spdb_projection_author_name_invalid' );
		if ( is_wp_error( $author_name ) ) {
			return $author_name;
		}
		return array(
			'provider_key'   => $provider_key,
			'object_type'    => $object_type,
			'object_id'      => $object_id,
			'title'          => $title,
			'author_id'      => $author_id,
			'author_name'    => $author_name,
			'scope'          => $scope,
			'native_version' => $version,
			'last_synced_at' => $last_synced,
		);
	}

	/** @return string[]|WP_Error */
	private static function normalize_operations( $raw, array $allowed, array $metadata ) {
		if ( ! self::is_list( $raw ) || count( $raw ) > self::MAX_OPERATIONS ) {
			return self::error( 'spdb_projection_operations_invalid', 'The projected operations are malformed or oversized.' );
		}
		$declared = array_keys( is_array( $metadata['operation_definitions'] ?? null ) ? $metadata['operation_definitions'] : array() );
		$result   = array();
		foreach ( $raw as $operation ) {
			$operation = is_scalar( $operation ) ? (string) $operation : '';
			if ( ! in_array( $operation, $allowed, true ) || ! in_array( $operation, $declared, true ) || in_array( $operation, $result, true ) ) {
				return self::error( 'spdb_projection_operation_invalid', 'The provider projected a duplicate, undeclared, or unsupported operation.' );
			}
			$result[] = $operation;
		}
		return $result;
	}

	/** @return string[]|WP_Error */
	private static function normalize_flags( $raw ) {
		if ( ! self::is_list( $raw ) || count( $raw ) > self::MAX_FLAGS ) {
			return self::error( 'spdb_projection_flags_invalid', 'A projected flag list is malformed or oversized.' );
		}
		$flags = array();
		foreach ( $raw as $flag ) {
			$flag = is_scalar( $flag ) ? (string) $flag : '';
			if ( ! SPDB_Adapter_Registry::is_canonical_key( $flag ) || in_array( $flag, $flags, true ) ) {
				return self::error( 'spdb_projection_flag_invalid', 'A projected flag is invalid or duplicated.' );
			}
			$flags[] = $flag;
		}
		return $flags;
	}

	/** @return array<string,mixed>|null */
	public static function operation_contract( string $operation ): ?array {
		$contracts = array(
			'approve_review'  => array( 'surface' => 'review', 'capability' => 'spdb_review_assigned_content', 'founder_only' => false, 'states' => array( 'awaiting_review', 'under_review' ) ),
			'request_changes' => array( 'surface' => 'review', 'capability' => 'spdb_review_assigned_content', 'founder_only' => false, 'states' => array( 'awaiting_review', 'under_review' ) ),
			'reject_review'   => array( 'surface' => 'review', 'capability' => 'spdb_review_assigned_content', 'founder_only' => false, 'states' => array( 'awaiting_review', 'under_review' ) ),
			'assign_reviewer' => array( 'surface' => 'review', 'capability' => 'spdb_review_assigned_content', 'founder_only' => true, 'states' => array( 'awaiting_review', 'under_review', 'on_hold' ) ),
			'schedule'        => array( 'surface' => 'calendar', 'capability' => 'spdb_manage_schedule', 'founder_only' => false, 'states' => array( 'unscheduled', 'failed', 'on_hold' ) ),
			'reschedule'      => array( 'surface' => 'calendar', 'capability' => 'spdb_manage_schedule', 'founder_only' => false, 'states' => array( 'scheduled', 'failed', 'on_hold' ) ),
			'unschedule'      => array( 'surface' => 'calendar', 'capability' => 'spdb_manage_schedule', 'founder_only' => false, 'states' => array( 'scheduled', 'failed', 'on_hold' ) ),
		);
		return $contracts[ $operation ] ?? null;
	}

	/** @return string[] */
	public static function review_operations(): array {
		return array( 'approve_review', 'request_changes', 'reject_review', 'assign_reviewer' );
	}

	/** @return string[] */
	public static function calendar_operations(): array {
		return array( 'schedule', 'reschedule', 'unschedule' );
	}

	/** @return string[] */
	public static function review_states(): array {
		return array( 'not_required', 'awaiting_review', 'under_review', 'changes_requested', 'approved', 'rejected', 'on_hold', 'unknown' );
	}

	/** @return string[] */
	public static function calendar_states(): array {
		return array( 'unscheduled', 'scheduled', 'processing', 'published', 'failed', 'on_hold', 'unknown' );
	}

	/** @return string|WP_Error */
	public static function normalize_utc_timestamp( $raw ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		if ( '' === $value || 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value ) ) {
			return self::error( 'spdb_schedule_timestamp_invalid', 'The schedule timestamp must be absolute UTC RFC 3339.' );
		}
		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Throwable $throwable ) {
			return self::error( 'spdb_schedule_timestamp_invalid', 'The schedule timestamp is invalid.' );
		}
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ) === $value
			? $value
			: self::error( 'spdb_schedule_timestamp_invalid', 'The schedule timestamp is not canonical UTC.' );
	}

	public static function valid_timezone( string $timezone ): bool {
		return '' !== $timezone && in_array( $timezone, timezone_identifiers_list(), true );
	}

	public static function sensitive_text( string $value ): bool {
		return 1 === preg_match( '/(?:https?:\/\/|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\b(?:\+?92|0)?3\d{9}\b|\b\d{5}-\d{7}-\d\b)/i', $value );
	}

	/** @return string|WP_Error */
	private static function required_text( $raw, int $max, string $code ) {
		$value = self::text_value( $raw, $max );
		return null === $value || '' === $value ? self::error( $code, 'A required projection text field is invalid.' ) : $value;
	}

	/** @return string|WP_Error */
	private static function optional_text( $raw, int $max, string $code ) {
		if ( null === $raw || '' === $raw ) {
			return '';
		}
		$value = self::text_value( $raw, $max );
		return null === $value ? self::error( $code, 'An optional projection text field is invalid.' ) : $value;
	}

	private static function text_value( $raw, int $max ): ?string {
		if ( ! is_scalar( $raw ) ) {
			return null;
		}
		$original = trim( (string) $raw );
		$value    = trim( wp_strip_all_tags( $original ) );
		if ( $value !== $original || strlen( $value ) > $max || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value ) || self::sensitive_text( $value ) ) {
			return null;
		}
		return $value;
	}

	/** @return string|WP_Error */
	private static function timestamp( $raw ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		if ( '' === $value || 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $value ) ) {
			return self::error( 'spdb_projection_timestamp_invalid', 'A projection timestamp must be absolute RFC 3339.' );
		}
		try {
			new DateTimeImmutable( $value );
		} catch ( Throwable $throwable ) {
			return self::error( 'spdb_projection_timestamp_invalid', 'A projection timestamp is invalid.' );
		}
		return $value;
	}

	/** @return string|WP_Error */
	private static function optional_timestamp( $raw ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		return '' === $value ? '' : self::timestamp( $value );
	}

	private static function valid_date( string $value ): bool {
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		return $date instanceof DateTimeImmutable && $date->format( 'Y-m-d' ) === $value;
	}

	private static function exact_value( $raw, array $allowed ): string {
		$value = is_scalar( $raw ) ? (string) $raw : '';
		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/** @return int|WP_Error */
	private static function query_integer( $raw, int $minimum, int $maximum ) {
		if ( ! is_scalar( $raw ) || 1 !== preg_match( '/^(?:0|[1-9]\d*)$/', (string) $raw ) ) {
			return self::error( 'spdb_projection_query_integer_invalid', 'Pagination values must be canonical positive integers.', 400 );
		}
		$value = (int) $raw;
		return $value >= $minimum && $value <= $maximum
			? $value
			: self::error( 'spdb_projection_query_integer_invalid', 'A pagination value is outside the accepted range.', 400 );
	}

	/** @return int|WP_Error */
	private static function strict_nonnegative_integer( $raw, string $code ) {
		if ( is_int( $raw ) ) {
			$value = $raw;
		} elseif ( is_string( $raw ) && 1 === preg_match( '/^(?:0|[1-9]\d*)$/', $raw ) && strlen( $raw ) <= 10 ) {
			$value = (int) $raw;
		} else {
			return self::error( $code, 'A projected numeric value is invalid.' );
		}
		return $value >= 0 && $value <= self::MAX_REPORTED_TOTAL
			? $value
			: self::error( $code, 'A projected numeric value is outside the accepted range.' );
	}

	/** @return int|WP_Error */
	private static function strict_positive_integer( $raw, string $code ) {
		$value = self::strict_nonnegative_integer( $raw, $code );
		return is_wp_error( $value ) || $value < 1 ? self::error( $code, 'A projected user identifier is invalid.' ) : $value;
	}

	private static function is_list( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}
		$index = 0;
		foreach ( $value as $key => $unused ) {
			if ( $key !== $index++ ) {
				return false;
			}
		}
		return true;
	}

	private static function error( string $code, string $message, int $status = 422 ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) );
	}
}
