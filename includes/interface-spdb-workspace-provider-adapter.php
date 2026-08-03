<?php
/**
 * Optional Phase 23D adapter contract for role-specific workspace projections.
 *
 * This interface supplements Adapter Contract 2.0.0 without changing the base
 * provider contract. Native modules remain authoritative for every count,
 * destination, profile field, knowledge relation, and publishing operation.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

interface SPDB_Workspace_Provider_Adapter {
	/**
	 * Return a bounded role-workspace projection for the current user.
	 *
	 * The context is constructed by File 23. Implementations must not accept a
	 * user, role, Founder flag, scope, verification state, or environment from
	 * a browser request.
	 *
	 * @param array<string,mixed> $context Server-derived workspace context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_workspace_projection( array $context );
}
