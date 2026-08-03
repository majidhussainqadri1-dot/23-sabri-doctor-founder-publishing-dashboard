<?php
/** Optional read-only operational projection contract for File 23 providers. */
defined( 'ABSPATH' ) || exit;

interface SPDB_Operational_Projection_Provider {
	/** @return string[] Canonical domains: sources, media, interactions, gaps, revisions, notifications. */
	public function get_operational_projection_domains(): array;

	/**
	 * Return a bounded, privacy-filtered projection owned by the native provider.
	 *
	 * @param string              $domain Canonical projection domain.
	 * @param array<string,mixed> $query  Server-derived query and viewer context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function list_operational_projections( string $domain, array $query );
}
