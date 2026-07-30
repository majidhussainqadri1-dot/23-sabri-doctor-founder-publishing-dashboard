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

	/** @return array<string,mixed> */
	public static function normalize_query( array $input, string $surface ): array {
		$allowed = 'review' === $surface
			? array( 'provider', 'review_state', 'assigned', 'due_from', 'due_to', 'page', 'per_page' )
			: array( 'provider', 'status', 'date_from', 'date_to', 'timezone', 'page', 'per_page' );
		$query = array();
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $input ) || is_array( $input[ $key ] ) || is_object( $input[ $key ] ) ) {
				continue;
			}
			$value = trim( (string) $input[ $key ] );
			if ( '' !== $value ) {
				$query[ $key ] = $value;
			}
		}
		$query['page']     = self::bounded_integer( $query['page'] ?? '1', 1, 1000, 1 );
		$query['per_page'] = self::bounded_integer( $query['per_page'] ?? '25', 1, 50, 25 );
		if ( isset( $query['provider'] ) && ! SPDB_Adapter_Registry::is_canonical_key( $query['provider'] ) ) {
			unset( $query['provider'] );
		}
		if ( isset( $query['assigned'] ) && ! in_array( $query['assigned'], array( 'me', 'unassigned', 'all' ), true ) ) {
			unset( $query['assigned'] );
		}
		if ( isset( $query['review_state'] ) && ! in_array( $query['review_state'], self::review_states(), true ) ) {
			unset( $query['review_state'] );
		}
		if ( isset( $query['status'] ) && ! in_array( $query['status'], self::calendar_states(), true ) ) {
			unset( $query['status'] );
		}
		foreach ( array( 'due_from', 'due_to', 'date_from', 'date_to' ) as $date_key ) {
			if ( isset( $query[ $date_key ] ) && ! self::valid_date( $query[ $date_key ] ) ) {
				unset( $query[ $date_key ] );
			}
		}
		if ( isset( $query['timezone'] ) && ! self::valid_timezone( $query['timezone'] ) ) {
			unset( $query['timezone'] );
		}
		return $query;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function normalize_review_queue( $raw, string $provider_key, array $metadata, array $context ) {
		if ( ! is_array( $raw ) || ! isset( $raw['items'] ) || ! is_array( $raw['items'] ) || count( $raw['items'] ) > self::MAX_PROVIDER_ITEMS ) {
			return self::error( 'spdb_review_queue_invalid', 'The provider returned a malformed or oversized review queue.' );
		}
		$total = self::strict_nonnegative_integer( $raw['total'] ?? count( $raw['items'] ), 'spdb_review_total_invalid' );
		if ( is_wp_error( $total ) ) {
			return $total;
		}
		$provider_name = self::required_text( $metadata['provider_name'] ?? $provider_key, 160, 'spdb_review_provider_name_invalid' );
		if ( is_wp_error( $provider_name ) ) {
			return $provider_name;
		}
		$items = array();
		$seen  = array();
		foreach ( $raw['items'] as $item ) {
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
			'reported_total' => $total,
			'has_more'       => true === ( $raw['has_more'] ?? false ),
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public static function normalize_calendar( $raw, string $provider_key, array $metadata, array $context ) {
		if ( ! is_array( $raw ) || ! isset( $raw['items'] ) || ! is_array( $raw['items'] ) || count( $raw['items'] ) > self::MAX_PROVIDER_ITEMS ) {
			return self::error( 'spdb_calendar_invalid', 'The provider returned a malformed or oversized calendar projection.' );
		}
		$total = self::strict_nonnegative_integer( $raw['total'] ?? count( $raw['items'] ), 'spdb_calendar_total_invalid' );
		if ( is_wp_error( $total ) ) {
			return $total;
		}
		$provider_name = self::required_text( $metadata['provider_name'] ?? $provider_key, 160, 'spdb_calendar_provider_name_invalid' );
		if ( is_wp_error( $provider_name ) ) {
			return $provider_name;
		}
		$items = array();
		$seen  = array();
		foreach ( $raw['items'] as $item ) {
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
			'reported_total' => $total,
			'has_more'       => true === ( $raw['has_more'] ?? false ),
		);
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
			return self::error( 'spdb_review_assignment_forbidden', 'A review item is assigned to another reviewer.' );
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
		$separation_required = true === ( $raw['separation_required'] ?? false );
		if ( $separation_required && (int) $reference['author_id'] === $user_id ) {
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
		if ( '' === $object_id || strlen( $object_id ) > 191 || preg_match( '/[\x00-\x1F\x7F]/', $object_id ) ) {
			return self::error( 'spdb_projection_object_id_invalid', 'The projected object identifier is invalid.' );
		}
		$author_id = self::strict_positive_integer( $raw['author_id'] ?? 0, 'spdb_projection_author_invalid' );
		if ( is_wp_error( $author_id ) ) {
			return $author_id;
		}
		$scope = self::exact_value( $raw['scope'] ?? 'own', array( 'own', 'institution' ) );
		if ( '' === $scope ) {
			return self::error( 'spdb_projection_scope_invalid', 'The projected scope is invalid.' );
		}
		if ( 'own' === $scope && (int) ( $context['user_id'] ?? 0 ) !== $author_id && empty( $context['can_review'] ) ) {
			return self::error( 'spdb_projection_owner_mismatch', 'The projected object is outside the current user scope.' );
		}
		if ( 'institution' === $scope && empty( $context['is_founder'] ) && empty( $context['can_review'] ) ) {
			return self::error( 'spdb_projection_institution_forbidden', 'Institution scope is not authorized.' );
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
			'provider_key'  => $provider_key,
			'object_type'   => $object_type,
			'object_id'     => $object_id,
			'title'         => $title,
			'author_id'     => $author_id,
			'author_name'   => $author_name,
			'scope'         => $scope,
			'native_version'=> $version,
			'last_synced_at'=> $last_synced,
		);
	}

	/** @return string[]|WP_Error */
	private static function normalize_operations( $raw, array $allowed, array $metadata ) {
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_OPERATIONS ) {
			return self::error( 'spdb_projection_operations_invalid', 'The projected operations are malformed or oversized.' );
		}
		$declared = array_keys( is_array( $metadata['operation_definitions'] ?? null ) ? $metadata['operation_definitions'] : array() );
		$result   = array();
		foreach ( $raw as $operation ) {
			$operation = is_scalar( $operation ) ? (string) $operation : '';
			if ( ! in_array( $operation, $allowed, true ) || ! in_array( $operation, $declared, true ) ) {
				return self::error( 'spdb_projection_operation_invalid', 'The provider projected an undeclared or unsupported operation.' );
			}
			$result[] = $operation;
		}
		return array_values( array_unique( $result ) );
	}

	/** @return string[]|WP_Error */
	private static function normalize_flags( $raw ) {
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_FLAGS ) {
			return self::error( 'spdb_projection_flags_invalid', 'A projected flag list is malformed or oversized.' );
		}
		$flags = array();
		foreach ( $raw as $flag ) {
			$flag = is_scalar( $flag ) ? (string) $flag : '';
			if ( ! SPDB_Adapter_Registry::is_canonical_key( $flag ) ) {
				return self::error( 'spdb_projection_flag_invalid', 'A projected flag is invalid.' );
			}
			$flags[] = $flag;
		}
		return array_values( array_unique( $flags ) );
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
		return array( 'scheduled', 'processing', 'published', 'failed', 'on_hold', 'unknown' );
	}

	/** @return string|WP_Error */
	private static function required_text( $raw, int $max, string $code ) {
		$value = self::text_value( $raw, $max );
		if ( null === $value || '' === $value ) {
			return self::error( $code, 'A required projection text field is invalid.' );
		}
		return $value;
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
		$value = trim( wp_strip_all_tags( (string) $raw ) );
		if ( strlen( $value ) > $max || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value ) ) {
			return null;
		}
		if ( preg_match( '/(?:https?:\/\/|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\b(?:\+?92|0)?3\d{9}\b|\b\d{5}-\d{7}-\d\b)/i', $value ) ) {
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

	private static function valid_timezone( string $timezone ): bool {
		return '' !== $timezone && in_array( $timezone, timezone_identifiers_list(), true );
	}

	private static function valid_date( string $value ): bool {
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		return $date instanceof DateTimeImmutable && $date->format( 'Y-m-d' ) === $value;
	}

	private static function exact_value( $raw, array $allowed ): string {
		$value = is_scalar( $raw ) ? (string) $raw : '';
		return in_array( $value, $allowed, true ) ? $value : '';
	}

	private static function bounded_integer( $raw, int $minimum, int $maximum, int $default ): int {
		if ( ! is_scalar( $raw ) || 1 !== preg_match( '/^\d+$/', (string) $raw ) ) {
			return $default;
		}
		$value = (int) $raw;
		return max( $minimum, min( $maximum, $value ) );
	}

	/** @return int|WP_Error */
	private static function strict_nonnegative_integer( $raw, string $code ) {
		if ( is_int( $raw ) && $raw >= 0 ) {
			return $raw;
		}
		if ( is_string( $raw ) && 1 === preg_match( '/^(?:0|[1-9]\d*)$/', $raw ) ) {
			return (int) $raw;
		}
		return self::error( $code, 'A projected numeric value is invalid.' );
	}

	/** @return int|WP_Error */
	private static function strict_positive_integer( $raw, string $code ) {
		$value = self::strict_nonnegative_integer( $raw, $code );
		if ( is_wp_error( $value ) || $value < 1 ) {
			return self::error( $code, 'A projected user identifier is invalid.' );
		}
		return $value;
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
	}
}
