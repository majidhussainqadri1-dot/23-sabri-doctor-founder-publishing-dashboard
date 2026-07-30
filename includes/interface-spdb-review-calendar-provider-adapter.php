<?php
/**
 * Optional Phase 23E contract for native review and schedule projections.
 *
 * Native modules retain ownership of review decisions, reviewer assignments,
 * schedules, time zones, cron reconciliation, conflicts, and audit records.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

interface SPDB_Review_Calendar_Provider_Adapter {
	/**
	 * Return a bounded, privacy-filtered native review queue projection.
	 *
	 * @param array<string,mixed> $context Server-derived authority context.
	 * @param array<string,mixed> $query   Normalized read-only queue filters.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_review_queue( array $context, array $query );

	/**
	 * Return bounded native scheduled-object projections.
	 *
	 * @param array<string,mixed> $context Server-derived authority context.
	 * @param array<string,mixed> $query   Normalized read-only calendar filters.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_calendar_entries( array $context, array $query );
}
