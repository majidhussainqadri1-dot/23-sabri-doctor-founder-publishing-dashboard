<?php
/** Optional privacy-safe aggregate analytics contract for File 23 providers. */
defined( 'ABSPATH' ) || exit;

interface SPDB_Analytics_Provider {
	/**
	 * Return metric definitions and aggregate values; never raw events or user-level tracking.
	 *
	 * @param array<string,mixed> $query Server-derived scope/date/provider context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_spdb_aggregate_metrics( array $query );
}
