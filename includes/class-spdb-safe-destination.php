<?php
/**
 * Validate private dashboard destinations supplied by native adapters.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Safe_Destination {
	private const MAX_URL_LENGTH = 2048;
	private const MAX_QUERY_PAIRS = 32;

	/**
	 * Require an absolute same-origin HTTP(S) URL without credentials,
	 * fragments, secrets, signatures, expiring values, nested targets, or
	 * ambiguous query serialization.
	 *
	 * @param mixed $raw Raw provider URL.
	 * @return string|WP_Error
	 */
	public static function normalize( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( 'spdb_workspace_destination_shape', 'A workspace destination has an invalid shape.' );
		}

		$url = trim( (string) $raw );
		if ( '' === $url || strlen( $url ) > self::MAX_URL_LENGTH || preg_match( '/[\x00-\x1F\x7F]/', $url ) ) {
			return self::error( 'spdb_workspace_destination_invalid', 'A workspace destination is invalid.' );
		}

		$parts = wp_parse_url( $url );
		$home  = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $parts ) || ! is_array( $home ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return self::error( 'spdb_workspace_destination_invalid', 'A workspace destination must be an absolute platform URL.' );
		}

		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) ) {
			return self::error( 'spdb_workspace_destination_private_component', 'A workspace destination must not contain credentials or a fragment.' );
		}

		$scheme      = strtolower( (string) $parts['scheme'] );
		$home_scheme = strtolower( (string) ( $home['scheme'] ?? 'https' ) );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || ! in_array( $home_scheme, array( 'http', 'https' ), true ) ) {
			return self::error( 'spdb_workspace_destination_scheme', 'A workspace destination must use HTTP or HTTPS.' );
		}

		$host      = strtolower( rtrim( (string) $parts['host'], '.' ) );
		$home_host = strtolower( rtrim( (string) ( $home['host'] ?? '' ), '.' ) );
		$port      = self::effective_port( $scheme, isset( $parts['port'] ) ? (int) $parts['port'] : null );
		$home_port = self::effective_port( $home_scheme, isset( $home['port'] ) ? (int) $home['port'] : null );

		if ( $scheme !== $home_scheme || '' === $home_host || $host !== $home_host || $port !== $home_port ) {
			return self::error( 'spdb_workspace_destination_origin', 'A workspace destination must remain on the exact platform origin.' );
		}

		if ( isset( $parts['query'] ) && '' !== (string) $parts['query'] ) {
			$query_state = self::validate_raw_query( (string) $parts['query'] );
			if ( is_wp_error( $query_state ) ) {
				return $query_state;
			}
		}

		$normalized = esc_url_raw( $url );
		if ( '' === $normalized ) {
			return self::error( 'spdb_workspace_destination_invalid', 'A workspace destination could not be normalized safely.' );
		}

		return $normalized;
	}

	private static function effective_port( string $scheme, ?int $port ): int {
		if ( null !== $port ) {
			return $port;
		}
		return 'https' === $scheme ? 443 : 80;
	}

	/** @return true|WP_Error */
	private static function validate_raw_query( string $raw_query ) {
		if ( strlen( $raw_query ) > 2048 || false !== strpos( $raw_query, ';' ) || preg_match( '/[\x00-\x1F\x7F]/', $raw_query ) ) {
			return self::error( 'spdb_workspace_destination_query', 'A workspace destination query is malformed or ambiguous.' );
		}

		$pairs = explode( '&', $raw_query );
		if ( count( $pairs ) > self::MAX_QUERY_PAIRS ) {
			return self::error( 'spdb_workspace_destination_query', 'A workspace destination contains too many query parameters.' );
		}

		$seen = array();
		foreach ( $pairs as $pair ) {
			if ( '' === $pair ) {
				return self::error( 'spdb_workspace_destination_query', 'A workspace destination query contains an empty parameter.' );
			}
			$parts = explode( '=', $pair, 2 );
			$raw_key   = $parts[0];
			$raw_value = $parts[1] ?? '';
			if ( '' === $raw_key || self::has_invalid_percent_encoding( $raw_key ) || self::has_invalid_percent_encoding( $raw_value ) ) {
				return self::error( 'spdb_workspace_destination_query', 'A workspace destination query contains invalid encoding.' );
			}

			$key = rawurldecode( $raw_key );
			if ( '' === $key || preg_match( '/[\[\]\x00-\x20\x7F]/', $key ) ) {
				return self::error( 'spdb_workspace_destination_query', 'A workspace destination query contains an invalid parameter name.' );
			}
			$canonical_key = strtolower( $key );
			if ( isset( $seen[ $canonical_key ] ) ) {
				return self::error( 'spdb_workspace_destination_query', 'A workspace destination query contains duplicate parameters.' );
			}
			$seen[ $canonical_key ] = true;

			if ( self::key_is_sensitive( $canonical_key ) ) {
				return self::error( 'spdb_workspace_destination_secret', 'A workspace destination must not contain secrets, signatures, or expiry data.' );
			}
			if ( self::value_is_unsafe( $raw_value ) ) {
				return self::error( 'spdb_workspace_destination_secret', 'A workspace destination must not contain secrets or nested redirect targets.' );
			}
		}

		return true;
	}

	private static function has_invalid_percent_encoding( string $value ): bool {
		return 1 === preg_match( '/%(?![0-9A-Fa-f]{2})/', $value );
	}

	private static function key_is_sensitive( string $key ): bool {
		$normalized = preg_replace( '/[^a-z0-9]+/', '_', $key ) ?? $key;
		$normalized = trim( $normalized, '_' );
		$sensitive  = array(
			'nonce', 'token', 'access_token', 'refresh_token', 'signature', 'sig', 'secret',
			'password', 'passwd', 'auth', 'authorization', 'expires', 'expiry', 'jwt',
			'session', 'session_id', 'api_key', 'access_key', 'private_key', 'key',
			'redirect', 'redirect_to', 'return_url', 'callback', 'continue',
		);
		return in_array( $normalized, $sensitive, true );
	}

	private static function value_is_unsafe( string $raw_value ): bool {
		$value = $raw_value;
		for ( $pass = 0; $pass < 4; ++$pass ) {
			$decoded = rawurldecode( $value );
			if ( preg_match( '/[\x00-\x1F\x7F]/', $decoded ) ) {
				return true;
			}
			$lower = strtolower( trim( $decoded ) );
			if (
				preg_match( '~(?:https?:|javascript:|data:|file:|ftp:|\\\\|//)~i', $lower )
				|| preg_match( '/(?:nonce|token|signature|secret|password|authorization|jwt|session)[\s:=_-]/i', $lower )
			) {
				return true;
			}
			if ( $decoded === $value ) {
				break;
			}
			$value = $decoded;
		}
		return false;
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
	}
}
