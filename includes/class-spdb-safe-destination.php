<?php
/**
 * Validate private dashboard destinations supplied by native adapters.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Safe_Destination {
	/**
	 * Require an absolute same-origin URL without credentials, fragments,
	 * secrets, signatures, expiring parameters, or nested redirect targets.
	 *
	 * @param mixed $raw Raw provider URL.
	 * @return string|WP_Error
	 */
	public static function normalize( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( 'spdb_workspace_destination_shape', 'A workspace destination has an invalid shape.' );
		}

		$url = trim( (string) $raw );
		if ( '' === $url || strlen( $url ) > 2048 || preg_match( '/[\x00-\x1F\x7F]/', $url ) ) {
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
		$host        = strtolower( rtrim( (string) $parts['host'], '.' ) );
		$home_host   = strtolower( rtrim( (string) ( $home['host'] ?? '' ), '.' ) );
		$port        = self::effective_port( $scheme, isset( $parts['port'] ) ? (int) $parts['port'] : null );
		$home_port   = self::effective_port( $home_scheme, isset( $home['port'] ) ? (int) $home['port'] : null );

		if ( $scheme !== $home_scheme || '' === $home_host || $host !== $home_host || $port !== $home_port ) {
			return self::error( 'spdb_workspace_destination_origin', 'A workspace destination must remain on the exact platform origin.' );
		}

		if ( ! empty( $parts['query'] ) ) {
			parse_str( (string) $parts['query'], $query );
			if ( ! is_array( $query ) || self::query_is_unsafe( $query ) ) {
				return self::error( 'spdb_workspace_destination_secret', 'A workspace destination must not contain secrets, signatures, expiry data, or nested redirect targets.' );
			}
		}

		$normalized = esc_url_raw( $url );
		return '' === $normalized ? self::error( 'spdb_workspace_destination_invalid', 'A workspace destination could not be normalized safely.' ) : $normalized;
	}

	private static function effective_port( string $scheme, ?int $port ): int {
		if ( null !== $port ) {
			return $port;
		}
		return 'https' === $scheme ? 443 : ( 'http' === $scheme ? 80 : 0 );
	}

	/**
	 * @param array<mixed> $query Parsed query.
	 */
	private static function query_is_unsafe( array $query ): bool {
		foreach ( $query as $key => $value ) {
			$key = strtolower( (string) $key );
			if ( preg_match( '/(?:nonce|token|signature|secret|password|passwd|auth|authorization|expires|expiry|jwt|session|key)/', $key ) ) {
				return true;
			}
			if ( is_array( $value ) ) {
				if ( self::query_is_unsafe( $value ) ) {
					return true;
				}
				continue;
			}
			if ( is_object( $value ) ) {
				return true;
			}
			$value = trim( (string) $value );
			if ( preg_match( '~(?:https?://|//[^/])~i', $value ) ) {
				return true;
			}
		}
		return false;
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
	}
}
