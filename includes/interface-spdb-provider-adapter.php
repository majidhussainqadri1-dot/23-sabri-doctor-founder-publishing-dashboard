<?php
/**
 * Provider adapter contract for federated File 23 integrations.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

interface SPDB_Provider_Adapter {
	/**
	 * Return the stable provider key.
	 */
	public function get_provider_key(): string;

	/**
	 * Return the adapter contract version implemented by this provider.
	 */
	public function get_contract_version(): string;

	/**
	 * Return the current adapter maturity state.
	 */
	public function get_maturity_state(): string;

	/**
	 * Return supported native object types.
	 *
	 * @return string[]
	 */
	public function get_object_types(): array;

	/**
	 * Return a non-sensitive health snapshot.
	 *
	 * @return array<string,mixed>
	 */
	public function health_check(): array;

	/**
	 * Query a paginated, privacy-filtered list projection.
	 *
	 * @param array<string,mixed> $query Query arguments.
	 * @return array<string,mixed>|WP_Error
	 */
	public function list_items( array $query );

	/**
	 * Resolve one native object projection.
	 *
	 * @param string $object_type Native object type.
	 * @param string $object_id   Native object identifier.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_item( string $object_type, string $object_id );

	/**
	 * Return operations authorized for the current user and object.
	 *
	 * @param string $object_type Native object type.
	 * @param string $object_id   Native object identifier.
	 * @return array<string,array<string,mixed>>
	 */
	public function get_allowed_operations( string $object_type, string $object_id ): array;

	/**
	 * Execute one explicitly registered native operation.
	 *
	 * @param string              $operation_key Registered operation key.
	 * @param string              $object_type   Native object type.
	 * @param string              $object_id     Native object identifier.
	 * @param array<string,mixed> $payload       Validated operation payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute_operation( string $operation_key, string $object_type, string $object_id, array $payload );
}
