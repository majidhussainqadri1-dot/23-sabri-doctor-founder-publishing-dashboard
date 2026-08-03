<?php
/** Optional File 16 source-linked AI assistance contract. */
defined( 'ABSPATH' ) || exit;

interface SPDB_AI_Assistance_Provider {
	/**
	 * Return human-review-only suggestions with citations and safety status.
	 *
	 * @param array<string,mixed> $request Privacy-filtered request.
	 * @return array<string,mixed>|WP_Error
	 */
	public function request_spdb_assistance( array $request );
}
