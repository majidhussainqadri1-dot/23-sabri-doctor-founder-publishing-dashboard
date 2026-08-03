<?php
/** Persistence contract for File 23-owned organizational metadata. */
defined( 'ABSPATH' ) || exit;

interface SPDB_Collections_Repository {
	/** @return array<string,mixed> */
	public function health_check(): array;
	/** @return array<string,mixed>|WP_Error */
	public function list_collections( array $query );
	/** @return array<string,mixed>|WP_Error */
	public function get_collection( string $collection_id );
	/** @return array<string,mixed>|WP_Error */
	public function create_collection( array $record );
	/** @return array<string,mixed>|WP_Error */
	public function update_collection( string $collection_id, int $expected_version, array $changes, array $operation );
	/** @return array<string,mixed>|WP_Error */
	public function archive_collection( string $collection_id, int $expected_version, array $operation );
	/** @return array<string,mixed>|WP_Error */
	public function list_collection_items( string $collection_id, array $query = array() );
	/** @return array<string,mixed>|WP_Error */
	public function get_collection_item( string $collection_id, string $item_id );
	/** @return array<string,mixed>|WP_Error */
	public function add_collection_item( string $collection_id, int $expected_collection_version, array $record );
	/** @return array<string,mixed>|WP_Error */
	public function update_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $changes, array $operation );
	/** @return array<string,mixed>|WP_Error */
	public function archive_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $operation );
	/** @return array<string,mixed>|WP_Error */
	public function list_knowledge_links( array $query );
	/** @return array<string,mixed>|WP_Error */
	public function get_knowledge_link( string $link_id );
	/** @return array<string,mixed>|WP_Error */
	public function create_knowledge_link( array $record );
	/** @return array<string,mixed>|WP_Error */
	public function update_knowledge_link( string $link_id, int $expected_version, array $changes, array $operation );
	/** @return array<string,mixed>|WP_Error */
	public function archive_knowledge_link( string $link_id, int $expected_version, array $operation );
}
