<?php
/**
 * Provider-specific native reference resolver contract.
 *
 * Each implementation belongs to one canonical native provider. File 23 owns
 * registration, acceptance, routing, health interpretation, and failure policy.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

interface SPDB_Native_Reference_Provider {
	/** Return the immutable canonical provider key. */
	public function get_provider_key(): string;

	/** Return the exact native provider version observed by this resolver. */
	public function get_provider_version(): string;

	/** Return the semantic version of this resolver implementation. */
	public function get_resolver_version(): string;

	/** @return string[] Canonical native object types this resolver supports. */
	public function get_object_types(): array;

	/** @return array<string,mixed> Non-sensitive technical health only. */
	public function health_check(): array;

	/**
	 * Resolve current native truth for one object owned by this provider.
	 *
	 * @param string              $object_type Canonical native object type.
	 * @param string              $object_id   Canonical native object identifier.
	 * @param array<string,mixed> $context     Server-derived current-user context.
	 * @return array<string,mixed>|WP_Error
	 */
	public function resolve_reference( string $object_type, string $object_id, array $context );
}
