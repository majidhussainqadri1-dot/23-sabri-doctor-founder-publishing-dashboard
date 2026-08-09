<?php
/** Privacy export/erasure bridge for File 23 authenticated rate-limit counters. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Rate_Limit_Privacy {
	private const TABLE_SUFFIX = 'spdb_rest_rate_limits';

	/** @return array<int,array<string,mixed>> */
	public static function export_for_user( int $user_id ): array {
		global $wpdb;
		if ( $user_id < 1 || ! is_object( $wpdb ) || empty( $wpdb->prefix ) ) { return array(); }
		$table = (string) $wpdb->prefix . self::TABLE_SUFFIX;
		if ( 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) { return array(); }
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT policy_key, request_count, window_started_at_gmt, reset_at_gmt, updated_at_gmt FROM `{$table}` WHERE actor_user_id = %d ORDER BY updated_at_gmt DESC LIMIT 25",
				$user_id
			),
			defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
		);
		$out = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$out[] = array(
				'policy_key'            => sanitize_key( (string) ( $row['policy_key'] ?? '' ) ),
				'request_count'         => max( 0, (int) ( $row['request_count'] ?? 0 ) ),
				'window_started_at_gmt' => sanitize_text_field( (string) ( $row['window_started_at_gmt'] ?? '' ) ),
				'reset_at_gmt'          => sanitize_text_field( (string) ( $row['reset_at_gmt'] ?? '' ) ),
				'updated_at_gmt'        => sanitize_text_field( (string) ( $row['updated_at_gmt'] ?? '' ) ),
			);
		}
		return $out;
	}

	/** @return int|WP_Error Number of deleted counters. */
	public static function erase_for_user( int $user_id ) {
		global $wpdb;
		if ( $user_id < 1 || ! is_object( $wpdb ) || empty( $wpdb->prefix ) ) {
			return new WP_Error( 'spdb_rate_limit_privacy_invalid', __( 'Rate-limit privacy data could not be addressed.', 'sabri-publishing-dashboard' ), array( 'status' => 500 ) );
		}
		$table = (string) $wpdb->prefix . self::TABLE_SUFFIX;
		if ( 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
			return new WP_Error( 'spdb_rate_limit_privacy_invalid', __( 'Rate-limit privacy data could not be addressed.', 'sabri-publishing-dashboard' ), array( 'status' => 500 ) );
		}
		$deleted = $wpdb->delete( $table, array( 'actor_user_id' => $user_id ), array( '%d' ) );
		return false === $deleted
			? new WP_Error( 'spdb_rate_limit_privacy_erase_failed', __( 'Rate-limit privacy data could not be erased.', 'sabri-publishing-dashboard' ), array( 'status' => 500 ) )
			: (int) $deleted;
	}
}
