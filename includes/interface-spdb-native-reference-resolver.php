<?php
/**
 * Resolve a canonical native object without importing it into File 23.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

interface SPDB_Native_Reference_Resolver {
	/**
	 * Resolve current native truth for one canonical reference.
	 *
	 * The response must include the exact provider/object reference, current
	 * existence, current-user visibility, current-user permission to reference
	 * the object, current owner ID, native version, and an optional safe current
	 * destination. A stale cached result is not sufficient for mutation.
	 *
	 * @param string              $provider_key Canonical provider key.
	 * @param string              $object_type  Canonical native object type.
	 * @param string              $object_id    Canonical native object ID.
	 * @param array<string,mixed> $context      Server-derived current-user context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context );
}
