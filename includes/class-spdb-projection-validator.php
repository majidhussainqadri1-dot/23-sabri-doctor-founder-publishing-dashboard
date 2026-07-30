<?php
/**
 * Validate native provider projections before dashboard rendering.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Projection_Validator {
	/**
	 * @param mixed               $raw           Raw provider projection.
	 * @param string              $provider_key  Canonical registered provider.
	 * @param array<string,mixed> $metadata      Registry metadata.
	 * @param string              $expected_type Optional requested type.
	 * @param string              $expected_id   Optional requested ID.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function normalize_item( $raw, string $provider_key, array $metadata, string $expected_type = '', string $expected_id = '' ) {
		if ( ! is_array( $raw ) ) {
			return self::error( 'spdb_projection_invalid', 'The provider returned an invalid inventory projection.' );
		}

		$object_type_raw = self::scalar_string( $raw['object_type'] ?? '' );
		$object_id_raw   = self::scalar_string( $raw['object_id'] ?? '' );
		$title_raw       = self::scalar_string( $raw['title'] ?? '' );
		$version_raw     = self::scalar_string( $raw['object_version'] ?? '' );
		$privacy_raw     = self::scalar_string( $raw['privacy_class'] ?? '' );
		if ( is_wp_error( $object_type_raw ) || is_wp_error( $object_id_raw ) || is_wp_error( $title_raw ) || is_wp_error( $version_raw ) || is_wp_error( $privacy_raw ) ) {
			return self::error( 'spdb_projection_shape_invalid', 'The provider returned a projection field with an invalid shape.' );
		}

		$object_type = sanitize_key( $object_type_raw );
		$object_id   = trim( $object_id_raw );
		$title       = trim( sanitize_text_field( $title_raw ) );
		$version     = trim( sanitize_text_field( $version_raw ) );
		$privacy     = sanitize_key( $privacy_raw );

		if ( ! in_array( $object_type, $metadata['object_types'] ?? array(), true ) || ( '' !== $expected_type && $expected_type !== $object_type ) ) {
			return self::error( 'spdb_projection_object_type_invalid', 'The provider returned an unsupported object type.' );
		}
		if ( ! self::valid_object_id( $object_id ) || ( '' !== $expected_id && ! hash_equals( $expected_id, $object_id ) ) ) {
			return self::error( 'spdb_projection_object_id_invalid', 'The provider returned an invalid or mismatched object identifier.' );
		}
		if ( '' === $title || strlen( $title ) > 240 ) {
			return self::error( 'spdb_projection_title_invalid', 'The provider returned an invalid title.' );
		}
		if ( '' === $version || strlen( $version ) > 160 || preg_match( '/[\x00-\x1F\x7F]/', $version ) ) {
			return self::error( 'spdb_projection_version_invalid', 'The provider returned an invalid object version.' );
		}
		if ( ! in_array( $privacy, $metadata['privacy_classes'] ?? array(), true ) ) {
			return self::error( 'spdb_projection_privacy_invalid', 'The provider returned an undeclared privacy classification.' );
		}

		$states = array(
			'lifecycle_state'   => self::normalize_state( $raw['lifecycle_state'] ?? '', self::lifecycle_states() ),
			'review_state'      => self::normalize_state( $raw['review_state'] ?? '', self::review_states() ),
			'visibility_state'  => self::normalize_state( $raw['visibility_state'] ?? '', self::visibility_states() ),
			'operational_state' => self::normalize_state( $raw['operational_state'] ?? '', self::operational_states() ),
		);

		$destinations = self::normalize_destinations( $raw['destinations'] ?? array() );
		if ( is_wp_error( $destinations ) ) {
			return $destinations;
		}

		$canonical_url = self::normalize_same_origin_url( $raw['canonical_url'] ?? '' );
		if ( is_wp_error( $canonical_url ) ) {
			return $canonical_url;
		}

		$thumbnail_url = self::normalize_media_url( $raw['thumbnail_url'] ?? '' );
		if ( is_wp_error( $thumbnail_url ) ) {
			return $thumbnail_url;
		}

		$author = $raw['author'] ?? array();
		if ( null !== $author && ! is_array( $author ) ) {
			return self::error( 'spdb_projection_author_invalid', 'The provider returned an invalid author projection.' );
		}
		$author = is_array( $author ) ? $author : array();
		$author_id_raw = self::scalar_string( $author['id'] ?? '0' );
		$author_name_raw = self::scalar_string( $author['display_name'] ?? '' );
		if ( is_wp_error( $author_id_raw ) || is_wp_error( $author_name_raw ) || ! preg_match( '/^\d+$/', $author_id_raw ) ) {
			return self::error( 'spdb_projection_author_invalid', 'The provider returned an invalid author projection.' );
		}
		$author_id   = max( 0, (int) $author_id_raw );
		$author_name = trim( sanitize_text_field( $author_name_raw ) );
		if ( strlen( $author_name ) > 160 ) {
			$author_name = substr( $author_name, 0, 160 );
		}

		$summary = self::bounded_text( $raw['summary'] ?? '', 500 );
		$language = self::canonical_optional_key( $raw['language'] ?? '' );
		$topic = self::canonical_optional_key( $raw['topic'] ?? '' );
		$created_at = self::normalize_timestamp( $raw['created_at'] ?? '' );
		$modified_at = self::normalize_timestamp( $raw['modified_at'] ?? '' );
		$scheduled_at = self::normalize_timestamp( $raw['scheduled_at'] ?? '' );
		$published_at = self::normalize_timestamp( $raw['published_at'] ?? '' );
		foreach ( array( $summary, $language, $topic, $created_at, $modified_at, $scheduled_at, $published_at ) as $normalized_field ) {
			if ( is_wp_error( $normalized_field ) ) {
				return $normalized_field;
			}
		}

		$alerts = self::normalize_alerts( $raw['compliance_alerts'] ?? array() );
		if ( is_wp_error( $alerts ) ) {
			return $alerts;
		}

		$mapping_required = in_array( 'unknown', $states, true );
		if ( $mapping_required ) {
			$alerts[] = array(
				'key'     => 'state_mapping_required',
				'level'   => 'warning',
				'message' => __( 'One or more native states require an explicit adapter mapping.', 'sabri-publishing-dashboard' ),
			);
		}

		return array_merge(
			array(
				'provider_key'      => $provider_key,
				'provider_name'     => (string) ( $metadata['provider_name'] ?? $provider_key ),
				'provider_version'  => (string) ( $metadata['provider_version'] ?? '' ),
				'object_type'       => $object_type,
				'object_id'         => $object_id,
				'object_version'    => $version,
				'title'             => $title,
				'summary'           => $summary,
				'language'          => $language,
				'topic'             => $topic,
				'privacy_class'     => $privacy,
				'author'            => array( 'id' => $author_id, 'display_name' => $author_name ),
				'created_at'        => $created_at,
				'modified_at'       => $modified_at,
				'scheduled_at'      => $scheduled_at,
				'published_at'      => $published_at,
				'canonical_url'     => $canonical_url,
				'thumbnail_url'     => $thumbnail_url,
				'destinations'      => $destinations,
				'compliance_alerts' => array_slice( $alerts, 0, 10 ),
				'mapping_required'  => $mapping_required,
			),
			$states
		);
	}

	public static function valid_object_id( string $object_id ): bool {
		return 1 === preg_match( '/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/', $object_id );
	}

	/** @return string[] */
	public static function lifecycle_states(): array {
		return array( 'draft', 'submitted', 'published', 'archived', 'withdrawn', 'retracted', 'unknown' );
	}

	/** @return string[] */
	public static function review_states(): array {
		return array( 'not_required', 'awaiting_review', 'under_review', 'changes_requested', 'approved', 'rejected', 'on_hold', 'unknown' );
	}

	/** @return string[] */
	public static function visibility_states(): array {
		return array( 'private', 'restricted', 'public', 'hidden', 'embargoed', 'unknown' );
	}

	/** @return string[] */
	public static function operational_states(): array {
		return array( 'healthy', 'processing', 'scheduled', 'failed', 'adapter_unavailable', 'unknown' );
	}

	/**
	 * @param mixed    $value Raw state.
	 * @param string[] $allowed Allowed states.
	 */
	private static function normalize_state( $value, array $allowed ): string {
		$raw = self::scalar_string( $value );
		if ( is_wp_error( $raw ) ) {
			return 'unknown';
		}
		$value = sanitize_key( $raw );
		return in_array( $value, $allowed, true ) ? $value : 'unknown';
	}

	/**
	 * @param mixed $raw Raw destinations.
	 * @return array<string,string>|WP_Error
	 */
	private static function normalize_destinations( $raw ) {
		if ( null === $raw || array() === $raw || '' === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) ) {
			return self::error( 'spdb_projection_destinations_invalid', 'The provider returned invalid destination descriptors.' );
		}

		$clean = array();
		foreach ( array( 'edit', 'preview', 'public' ) as $key ) {
			if ( ! isset( $raw[ $key ] ) || '' === $raw[ $key ] ) {
				continue;
			}
			$url = self::normalize_same_origin_url( $raw[ $key ] );
			if ( is_wp_error( $url ) ) {
				return $url;
			}
			$clean[ $key ] = $url;
		}
		return $clean;
	}

	/**
	 * Require strict same-origin, non-secret dashboard destinations.
	 *
	 * @param mixed $raw Raw URL.
	 * @return string|WP_Error
	 */
	private static function normalize_same_origin_url( $raw ) {
		$raw_string = self::scalar_string( $raw );
		if ( is_wp_error( $raw_string ) ) {
			return self::error( 'spdb_projection_destination_invalid', 'A provider destination URL is invalid.' );
		}
		$url = trim( $raw_string );
		if ( '' === $url ) {
			return '';
		}
		if ( strlen( $url ) > 2048 || preg_match( '/[\x00-\x1F\x7F]/', $url ) ) {
			return self::error( 'spdb_projection_destination_invalid', 'A provider destination URL is invalid.' );
		}

		$parts = wp_parse_url( $url );
		$home  = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $parts ) || ! is_array( $home ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return self::error( 'spdb_projection_destination_invalid', 'A provider destination URL is invalid.' );
		}

		$scheme = strtolower( (string) $parts['scheme'] );
		$home_scheme = strtolower( (string) ( $home['scheme'] ?? 'https' ) );
		$port = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );
		$home_port = isset( $home['port'] ) ? (int) $home['port'] : ( 'https' === $home_scheme ? 443 : 80 );
		if ( $scheme !== $home_scheme || strtolower( (string) $parts['host'] ) !== strtolower( (string) ( $home['host'] ?? '' ) ) || $port !== $home_port ) {
			return self::error( 'spdb_projection_destination_origin_invalid', 'A provider destination must remain on the platform origin.' );
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return self::error( 'spdb_projection_destination_credentials', 'A provider destination must not contain embedded credentials.' );
		}

		if ( ! empty( $parts['query'] ) ) {
			parse_str( (string) $parts['query'], $query );
			foreach ( array_keys( $query ) as $query_key ) {
				$query_key = strtolower( (string) $query_key );
				if ( preg_match( '/(?:nonce|token|signature|secret|password|auth|expires|key)/', $query_key ) ) {
					return self::error( 'spdb_projection_destination_secret', 'A provider destination must not contain signed or secret-bearing query parameters.' );
				}
			}
		}

		return esc_url_raw( $url );
	}

	/**
	 * @param mixed $raw Raw media URL.
	 * @return string|WP_Error
	 */
	private static function normalize_media_url( $raw ) {
		$raw_string = self::scalar_string( $raw );
		if ( is_wp_error( $raw_string ) ) {
			return self::error( 'spdb_projection_thumbnail_invalid', 'The provider returned an invalid thumbnail URL.' );
		}
		$url = trim( $raw_string );
		if ( '' === $url ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		if ( strlen( $url ) > 2048 || ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return self::error( 'spdb_projection_thumbnail_invalid', 'The provider returned an invalid thumbnail URL.' );
		}
		return esc_url_raw( $url );
	}

	/**
	 * @param mixed $raw Raw alerts.
	 * @return array<int,array<string,string>>|WP_Error
	 */
	private static function normalize_alerts( $raw ) {
		if ( null === $raw || array() === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) || count( $raw ) > 10 ) {
			return self::error( 'spdb_projection_alerts_invalid', 'The provider returned invalid compliance alerts.' );
		}
		$clean = array();
		foreach ( $raw as $alert ) {
			if ( ! is_array( $alert ) ) {
				return self::error( 'spdb_projection_alert_invalid', 'The provider returned an invalid compliance alert.' );
			}
			$key_raw = self::scalar_string( $alert['key'] ?? '' );
			$level_raw = self::scalar_string( $alert['level'] ?? 'information' );
			$message = self::bounded_text( $alert['message'] ?? '', 240 );
			if ( is_wp_error( $key_raw ) || is_wp_error( $level_raw ) || is_wp_error( $message ) ) {
				return self::error( 'spdb_projection_alert_invalid', 'The provider returned an invalid compliance alert.' );
			}
			$key = sanitize_key( $key_raw );
			$level = sanitize_key( $level_raw );
			if ( '' === $key || '' === $message || ! in_array( $level, array( 'information', 'warning', 'critical' ), true ) ) {
				return self::error( 'spdb_projection_alert_invalid', 'The provider returned an invalid compliance alert.' );
			}
			$clean[] = array( 'key' => $key, 'level' => $level, 'message' => $message );
		}
		return $clean;
	}

	/**
	 * @param mixed $value Raw optional key.
	 * @return string|WP_Error
	 */
	private static function canonical_optional_key( $value ) {
		$raw = self::scalar_string( $value );
		if ( is_wp_error( $raw ) ) {
			return self::error( 'spdb_projection_key_invalid', 'The provider returned an invalid projection key.' );
		}
		$value = sanitize_key( $raw );
		return '' === $value || 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{0,63}$/', $value ) ? $value : '';
	}

	/**
	 * @param mixed $value Raw timestamp.
	 * @return string|WP_Error
	 */
	private static function normalize_timestamp( $value ) {
		$raw = self::scalar_string( $value );
		if ( is_wp_error( $raw ) ) {
			return self::error( 'spdb_projection_timestamp_invalid', 'The provider returned an invalid timestamp.' );
		}
		$value = trim( sanitize_text_field( $raw ) );
		if ( '' === $value ) {
			return '';
		}
		$timestamp = strtotime( $value );
		return false === $timestamp ? '' : gmdate( 'c', $timestamp );
	}

	/**
	 * @param mixed $value Raw text.
	 * @return string|WP_Error
	 */
	private static function bounded_text( $value, int $limit ) {
		$raw = self::scalar_string( $value );
		if ( is_wp_error( $raw ) ) {
			return self::error( 'spdb_projection_text_invalid', 'The provider returned invalid projection text.' );
		}
		$value = trim( sanitize_text_field( $raw ) );
		return strlen( $value ) > $limit ? substr( $value, 0, $limit ) : $value;
	}

	/**
	 * @param mixed $value Raw scalar.
	 * @return string|WP_Error
	 */
	private static function scalar_string( $value ) {
		if ( is_array( $value ) || is_object( $value ) || is_resource( $value ) ) {
			return self::error( 'spdb_projection_shape_invalid', 'The provider returned a projection field with an invalid shape.' );
		}
		return (string) $value;
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
	}
}
