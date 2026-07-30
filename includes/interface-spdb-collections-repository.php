<?php
/**
 * Persistence contract for File 23-owned collection, campaign, and knowledge-link metadata.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

interface SPDB_Collections_Repository {
	/** @return array<string,mixed>|WP_Error */
	public function list_collections( array $query );

	/** @return array<string,mixed>|WP_Error */
	public function get_collection( string $collection_id );

	/** @return array<string,mixed>|WP_Error */
	public function create_collection( array $record );

	/** @return array<string,mixed>|WP_Error */
	public function update_collection( string $collection_id, int $expected_version, array $changes );

	/** @return array<string,mixed>|WP_Error */
	public function list_collection_items( string $collection_id );

	/** @return array<string,mixed>|WP_Error */
	public function add_collection_item( string $collection_id, int $expected_collection_version, array $record );

	/** @return array<string,mixed>|WP_Error */
	public function archive_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, string $idempotency_hash, int $actor_user_id, string $updated_at_gmt );

	/** @return array<string,mixed>|WP_Error */
	public function list_knowledge_links( array $query );

	/** @return array<string,mixed>|WP_Error */
	public function get_knowledge_link( string $link_id );

	/** @return array<string,mixed>|WP_Error */
	public function create_knowledge_link( array $record );

	/** @return array<string,mixed>|WP_Error */
	public function archive_knowledge_link( string $link_id, int $expected_version, string $idempotency_hash, int $actor_user_id, string $updated_at_gmt );
}
