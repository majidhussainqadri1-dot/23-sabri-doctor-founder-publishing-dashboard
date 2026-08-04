<?php
/** Strict validator for native operational projections and aggregate metrics. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Operational_Projection_Validator {
	public const MAX_ITEMS = 100;
	private const DOMAINS = array( 'sources', 'media', 'interactions', 'gaps', 'revisions', 'notifications' );
	private const PRIVACY = array( 'public', 'restricted', 'private', 'clinical_sensitive' );

	public static function domains(): array { return self::DOMAINS; }
	public static function is_domain( string $domain ): bool { return in_array( $domain, self::DOMAINS, true ); }

	/** @param mixed $raw @return array<string,mixed>|WP_Error */
	public static function projection_page( $raw, string $provider_key, string $domain ) {
		if ( ! is_array( $raw ) || ! self::is_domain( $domain ) || ! isset( $raw['items'] ) || ! is_array( $raw['items'] ) ) {
			return self::error( 'spdb_projection_invalid_page', 'The provider returned an invalid operational projection.' );
		}
		$items = array();
		foreach ( array_slice( $raw['items'], 0, self::MAX_ITEMS ) as $row ) {
			$validated = self::projection_item( $row, $provider_key, $domain );
			if ( is_wp_error( $validated ) ) { continue; }
			$items[] = $validated;
		}
		$total = isset( $raw['total'] ) && is_int( $raw['total'] ) && $raw['total'] >= count( $items ) ? min( 1000000, $raw['total'] ) : count( $items );
		return array(
			'items' => $items,
			'total' => $total,
			'has_more' => true === ( $raw['has_more'] ?? false ),
			'generated_at' => self::timestamp( $raw['generated_at'] ?? gmdate( 'c' ) ),
		);
	}

	/** @param mixed $row @return array<string,mixed>|WP_Error */
	public static function projection_item( $row, string $provider_key, string $domain ) {
		if ( ! is_array( $row ) ) { return self::error( 'spdb_projection_invalid_item', 'An operational projection item is invalid.' ); }
		$required = array( 'object_type', 'object_id', 'native_version', 'title', 'status', 'updated_at', 'privacy_class' );
		foreach ( $required as $key ) { if ( ! array_key_exists( $key, $row ) || ! is_scalar( $row[ $key ] ) ) { return self::error( 'spdb_projection_missing_field', 'An operational projection item is incomplete.' ); } }
		$object_type = sanitize_key( (string) $row['object_type'] );
		$object_id = trim( (string) $row['object_id'] );
		$native_version = trim( (string) $row['native_version'] );
		$title = trim( wp_strip_all_tags( (string) $row['title'] ) );
		$status = sanitize_key( (string) $row['status'] );
		$privacy = sanitize_key( (string) $row['privacy_class'] );
		if ( 1 !== preg_match( '/^[a-z0-9][a-z0-9_-]{0,63}$/', $object_type ) || '' === $object_id || strlen( $object_id ) > 128 || '' === $native_version || strlen( $native_version ) > 191 || '' === $title || strlen( $title ) > 240 || 1 !== preg_match( '/^[a-z0-9][a-z0-9_-]{0,63}$/', $status ) || ! in_array( $privacy, self::PRIVACY, true ) ) {
			return self::error( 'spdb_projection_invalid_field', 'An operational projection item contains invalid fields.' );
		}
		$summary = isset( $row['summary'] ) && is_scalar( $row['summary'] ) ? trim( wp_strip_all_tags( (string) $row['summary'] ) ) : '';
		$severity = isset( $row['severity'] ) && is_scalar( $row['severity'] ) ? sanitize_key( (string) $row['severity'] ) : 'information';
		if ( ! in_array( $severity, array( 'information', 'warning', 'high', 'critical' ), true ) ) { $severity = 'information'; }
		$destination = '';
		if ( isset( $row['destination_url'] ) && is_scalar( $row['destination_url'] ) ) {
			$destination = self::safe_destination( (string) $row['destination_url'] );
		}
		$metadata = array();
		if ( isset( $row['metadata'] ) && is_array( $row['metadata'] ) ) {
			foreach ( array_slice( $row['metadata'], 0, 20, true ) as $key => $value ) {
				$key = sanitize_key( (string) $key );
				if ( '' === $key || ! is_scalar( $value ) || self::sensitive_key( $key ) ) { continue; }
				$text = trim( wp_strip_all_tags( (string) $value ) );
				if ( strlen( $text ) <= 240 ) { $metadata[ $key ] = $text; }
			}
		}
		return array(
			'provider_key' => $provider_key,
			'domain' => $domain,
			'object_type' => $object_type,
			'object_id' => $object_id,
			'native_version' => $native_version,
			'title' => $title,
			'summary' => substr( $summary, 0, 500 ),
			'status' => $status,
			'severity' => $severity,
			'privacy_class' => $privacy,
			'updated_at' => self::timestamp( $row['updated_at'] ),
			'destination_url' => $destination,
			'metadata' => $metadata,
		);
	}

	/** @param mixed $raw @return array<string,mixed>|WP_Error */
	public static function metrics( $raw, string $provider_key, int $configured_threshold ) {
		if ( ! is_array( $raw ) || ! isset( $raw['metrics'] ) || ! is_array( $raw['metrics'] ) ) { return self::error( 'spdb_metrics_invalid', 'The provider returned invalid aggregate analytics.' ); }
		$items = array();
		foreach ( array_slice( $raw['metrics'], 0, 100 ) as $metric ) {
			if ( ! is_array( $metric ) ) { continue; }
			$key = sanitize_key( (string) ( $metric['metric_key'] ?? '' ) );
			$label = trim( wp_strip_all_tags( (string) ( $metric['label'] ?? '' ) ) );
			$definition = trim( wp_strip_all_tags( (string) ( $metric['definition'] ?? '' ) ) );
			$interval = sanitize_key( (string) ( $metric['interval'] ?? '' ) );
			$cohort = isset( $metric['cohort_count'] ) && is_int( $metric['cohort_count'] ) ? max( 0, $metric['cohort_count'] ) : 0;
			$threshold = isset( $metric['privacy_threshold'] ) && is_int( $metric['privacy_threshold'] ) ? max( $configured_threshold, $metric['privacy_threshold'] ) : $configured_threshold;
			if ( 1 !== preg_match( '/^[a-z0-9][a-z0-9_.-]{0,95}$/', $key ) || '' === $label || strlen( $label ) > 160 || '' === $definition || strlen( $definition ) > 500 || ! in_array( $interval, array( 'realtime', 'hourly', 'daily', 'weekly', 'monthly', 'custom' ), true ) ) { continue; }
			$value = null;
			$suppressed = $cohort < $threshold;
			if ( ! $suppressed && isset( $metric['value'] ) && ( is_int( $metric['value'] ) || is_float( $metric['value'] ) ) && is_finite( (float) $metric['value'] ) ) { $value = $metric['value']; }
			$items[] = array(
				'provider_key' => $provider_key,
				'metric_key' => $key,
				'label' => $label,
				'definition' => $definition,
				'value' => $value,
				'unit' => sanitize_key( (string) ( $metric['unit'] ?? 'count' ) ),
				'interval' => $interval,
				'cohort_count' => $cohort,
				'privacy_threshold' => $threshold,
				'suppressed' => $suppressed,
				'generated_at' => self::timestamp( $metric['generated_at'] ?? gmdate( 'c' ) ),
			);
		}
		return array( 'metrics' => $items, 'generated_at' => self::timestamp( $raw['generated_at'] ?? gmdate( 'c' ) ) );
	}

	private static function timestamp( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		try { $date = new DateTimeImmutable( $value ); } catch ( Throwable $e ) { return gmdate( 'c' ); }
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'c' );
	}
	private static function safe_destination( string $url ): string {
		$url = esc_url_raw( trim( $url ), array( 'http', 'https' ) );
		if ( '' === $url ) { return ''; }
		$home = wp_parse_url( home_url( '/' ) ); $target = wp_parse_url( $url );
		if ( ! is_array( $home ) || ! is_array( $target ) || empty( $target['host'] ) || empty( $home['host'] ) || strtolower( (string) $target['host'] ) !== strtolower( (string) $home['host'] ) || isset( $target['user'] ) || isset( $target['pass'] ) || isset( $target['fragment'] ) ) { return ''; }
		return $url;
	}
	private static function sensitive_key( string $key ): bool { return 1 === preg_match( '/(?:patient|email|phone|address|message_body|ip|token|secret|password|consent_document|appointment)/', $key ); }
	private static function error( string $code, string $message ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 502 ) ); }
}
