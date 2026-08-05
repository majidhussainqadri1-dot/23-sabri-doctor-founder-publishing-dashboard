<?php
/** Strict validator for native operational projections and aggregate metrics. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Operational_Projection_Validator {
	public const MAX_ITEMS = 100;
	private const DOMAINS = array(
		'sources',
		'media',
		'interactions',
		'gaps',
		'revisions',
		'notifications',
		'appointments',
		'messages',
		'reviews',
		'followers',
		'downloads',
		'support',
		'learning',
	);
	private const PRIVACY = array( 'public', 'restricted', 'private', 'clinical_sensitive' );

	public static function domains(): array { return self::DOMAINS; }
	public static function is_domain( string $domain ): bool { return in_array( $domain, self::DOMAINS, true ); }

	/** @param mixed $raw @return array<string,mixed>|WP_Error */
	public static function projection_page( $raw, string $provider_key, string $domain ) {
		if ( ! is_array( $raw ) || ! self::canonical_key( $provider_key ) || ! self::is_domain( $domain ) || ! isset( $raw['items'] ) || ! is_array( $raw['items'] ) || count( $raw['items'] ) > self::MAX_ITEMS ) {
			return self::error( 'spdb_projection_invalid_page', 'The provider returned an invalid operational projection.' );
		}
		$items = array();
		foreach ( $raw['items'] as $row ) {
			$validated = self::projection_item( $row, $provider_key, $domain );
			if ( is_wp_error( $validated ) ) {
				return self::error( 'spdb_projection_invalid_page_item', 'The provider projection contained an invalid item and was rejected.' );
			}
			$items[] = $validated;
		}
		if ( array_key_exists( 'total', $raw ) && ( ! is_int( $raw['total'] ) || $raw['total'] < count( $items ) || $raw['total'] > 1000000 ) ) {
			return self::error( 'spdb_projection_total_invalid', 'The provider returned an invalid projection total.' );
		}
		if ( array_key_exists( 'has_more', $raw ) && ! is_bool( $raw['has_more'] ) ) {
			return self::error( 'spdb_projection_pagination_invalid', 'The provider returned invalid projection pagination.' );
		}
		$total = $raw['total'] ?? count( $items );
		$has_more = true === ( $raw['has_more'] ?? false );
		if ( $has_more && $total <= count( $items ) ) {
			return self::error( 'spdb_projection_pagination_invalid', 'The provider returned inconsistent projection pagination.' );
		}
		$generated_at = self::timestamp( $raw['generated_at'] ?? gmdate( 'c' ) );
		if ( null === $generated_at ) {
			return self::error( 'spdb_projection_timestamp_invalid', 'The provider returned an invalid projection timestamp.' );
		}
		return array(
			'items' => $items,
			'total' => $total,
			'has_more' => $has_more,
			'generated_at' => $generated_at,
		);
	}

	/** @param mixed $row @return array<string,mixed>|WP_Error */
	public static function projection_item( $row, string $provider_key, string $domain ) {
		if ( ! is_array( $row ) || ! self::canonical_key( $provider_key ) || ! self::is_domain( $domain ) ) { return self::error( 'spdb_projection_invalid_item', 'An operational projection item is invalid.' ); }
		$required = array( 'object_type', 'object_id', 'native_version', 'title', 'status', 'updated_at', 'privacy_class' );
		foreach ( $required as $key ) { if ( ! array_key_exists( $key, $row ) || ! is_scalar( $row[ $key ] ) ) { return self::error( 'spdb_projection_missing_field', 'An operational projection item is incomplete.' ); } }
		$object_type_raw = trim( (string) $row['object_type'] );
		$object_type = sanitize_key( $object_type_raw );
		$object_id = trim( (string) $row['object_id'] );
		$native_version = trim( (string) $row['native_version'] );
		$title = trim( wp_strip_all_tags( (string) $row['title'] ) );
		$status_raw = trim( (string) $row['status'] );
		$status = sanitize_key( $status_raw );
		$privacy_raw = trim( (string) $row['privacy_class'] );
		$privacy = sanitize_key( $privacy_raw );
		$updated_at = self::timestamp( $row['updated_at'] );
		if ( $object_type !== $object_type_raw || $status !== $status_raw || $privacy !== $privacy_raw || ! self::canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) || ! self::bounded_text( $native_version, 191 ) || ! self::bounded_text( $title, 240 ) || ! self::canonical_key( $status ) || ! in_array( $privacy, self::PRIVACY, true ) || null === $updated_at ) {
			return self::error( 'spdb_projection_invalid_field', 'An operational projection item contains invalid fields.' );
		}
		$summary = isset( $row['summary'] ) && is_scalar( $row['summary'] ) ? trim( wp_strip_all_tags( (string) $row['summary'] ) ) : '';
		if ( '' !== $summary && ! self::valid_utf8( $summary ) ) { return self::error( 'spdb_projection_invalid_field', 'An operational projection item contains invalid fields.' ); }
		$severity_raw = isset( $row['severity'] ) && is_scalar( $row['severity'] ) ? trim( (string) $row['severity'] ) : 'information';
		$severity = sanitize_key( $severity_raw );
		if ( $severity !== $severity_raw || ! in_array( $severity, array( 'information', 'warning', 'high', 'critical' ), true ) ) { return self::error( 'spdb_projection_invalid_field', 'An operational projection item contains invalid fields.' ); }
		$destination = '';
		if ( isset( $row['destination_url'] ) && is_scalar( $row['destination_url'] ) ) {
			$destination = self::safe_destination( (string) $row['destination_url'] );
		}
		$metadata = array();
		if ( isset( $row['metadata'] ) && is_array( $row['metadata'] ) ) {
			foreach ( array_slice( $row['metadata'], 0, 20, true ) as $key => $value ) {
				$raw_key = trim( (string) $key );
				$key = sanitize_key( $raw_key );
				if ( '' === $key || $key !== $raw_key || ! is_scalar( $value ) || self::sensitive_key( $key ) ) { continue; }
				$text = trim( wp_strip_all_tags( (string) $value ) );
				if ( self::bounded_text( $text, 240, true ) ) { $metadata[ $key ] = $text; }
			}
		}
		return array(
			'provider_key' => $provider_key,
			'domain' => $domain,
			'object_type' => $object_type,
			'object_id' => $object_id,
			'native_version' => $native_version,
			'title' => $title,
			'summary' => self::text_substr( $summary, 0, 500 ),
			'status' => $status,
			'severity' => $severity,
			'privacy_class' => $privacy,
			'updated_at' => $updated_at,
			'destination_url' => $destination,
			'metadata' => $metadata,
		);
	}

	/** @param mixed $raw @return array<string,mixed>|WP_Error */
	public static function metrics( $raw, string $provider_key, int $configured_threshold ) {
		if ( ! is_array( $raw ) || ! self::canonical_key( $provider_key ) || $configured_threshold < 1 || ! isset( $raw['metrics'] ) || ! is_array( $raw['metrics'] ) || count( $raw['metrics'] ) > 100 ) { return self::error( 'spdb_metrics_invalid', 'The provider returned invalid aggregate analytics.' ); }
		$items = array();
		foreach ( $raw['metrics'] as $metric ) {
			if ( ! is_array( $metric ) ) { return self::error( 'spdb_metrics_invalid_item', 'The provider returned an invalid aggregate metric.' ); }
			$key_raw = trim( (string) ( $metric['metric_key'] ?? '' ) );
			$key = sanitize_key( $key_raw );
			$label = trim( wp_strip_all_tags( (string) ( $metric['label'] ?? '' ) ) );
			$definition = trim( wp_strip_all_tags( (string) ( $metric['definition'] ?? '' ) ) );
			$interval_raw = trim( (string) ( $metric['interval'] ?? '' ) );
			$interval = sanitize_key( $interval_raw );
			$cohort = isset( $metric['cohort_count'] ) && is_int( $metric['cohort_count'] ) ? max( 0, $metric['cohort_count'] ) : 0;
			$threshold = isset( $metric['privacy_threshold'] ) && is_int( $metric['privacy_threshold'] ) ? max( $configured_threshold, $metric['privacy_threshold'] ) : $configured_threshold;
			$unit_raw = trim( (string) ( $metric['unit'] ?? 'count' ) );
			$unit = sanitize_key( $unit_raw );
			$generated_at = self::timestamp( $metric['generated_at'] ?? gmdate( 'c' ) );
			if ( $key !== $key_raw || $interval !== $interval_raw || $unit !== $unit_raw || 1 !== preg_match( '/^[a-z0-9][a-z0-9_.-]{0,95}$/', $key ) || ! self::bounded_text( $label, 160 ) || ! self::bounded_text( $definition, 500 ) || ! self::canonical_key( $unit ) || ! in_array( $interval, array( 'realtime', 'hourly', 'daily', 'weekly', 'monthly', 'custom' ), true ) || null === $generated_at ) { return self::error( 'spdb_metrics_invalid_item', 'The provider returned an invalid aggregate metric.' ); }
			$value = null;
			$suppressed = $cohort < $threshold;
			if ( ! $suppressed && isset( $metric['value'] ) && ( is_int( $metric['value'] ) || is_float( $metric['value'] ) ) && is_finite( (float) $metric['value'] ) ) { $value = $metric['value']; }
			if ( ! $suppressed && null === $value ) { return self::error( 'spdb_metrics_invalid_value', 'The provider returned an invalid aggregate metric value.' ); }
			$items[] = array(
				'provider_key' => $provider_key,
				'metric_key' => $key,
				'label' => $label,
				'definition' => $definition,
				'value' => $value,
				'unit' => $unit,
				'interval' => $interval,
				'cohort_count' => $cohort,
				'privacy_threshold' => $threshold,
				'suppressed' => $suppressed,
				'generated_at' => $generated_at,
			);
		}
		$generated_at = self::timestamp( $raw['generated_at'] ?? gmdate( 'c' ) );
		return null === $generated_at
			? self::error( 'spdb_metrics_timestamp_invalid', 'The provider returned an invalid analytics timestamp.' )
			: array( 'metrics' => $items, 'generated_at' => $generated_at );
	}

	private static function timestamp( $value ): ?string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/', $value ) ) { return null; }
		try { $date = new DateTimeImmutable( $value ); } catch ( Throwable $e ) { return null; }
		$errors = DateTimeImmutable::getLastErrors();
		if ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) { return null; }
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'c' );
	}
	private static function safe_destination( string $url ): string {
		$normalized = SPDB_Safe_Destination::normalize( $url );
		return is_wp_error( $normalized ) ? '' : $normalized;
	}
	private static function canonical_key( string $value ): bool {
		return 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{0,63}$/', $value );
	}
	private static function bounded_text( string $value, int $limit, bool $allow_empty = false ): bool {
		return ( $allow_empty || '' !== $value ) && self::valid_utf8( $value ) && self::text_length( $value ) <= $limit && 1 !== preg_match( '/[\x00-\x1F\x7F]/u', $value );
	}
	private static function valid_utf8( string $value ): bool {
		return 1 === preg_match( '//u', $value );
	}
	private static function text_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}
	private static function text_substr( string $value, int $start, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, $start, $length, 'UTF-8' ) : substr( $value, $start, $length );
	}
	private static function sensitive_key( string $key ): bool {
		return 1 === preg_match( '/(?:patient|email|phone|address|message_body|message_text|conversation|recipient|sender|ip|token|secret|password|consent_document|appointment|clinical|diagnosis|prescription|payment|billing|national_id|passport|guardian)/', $key );
	}
	private static function error( string $code, string $message ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 502 ) ); }
}
