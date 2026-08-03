<?php
/**
 * WordPress privacy exporter and eraser for File 23-owned metadata only.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Privacy_Integration {
	private SPDB_Operations_Repository $repository;
	private SPDB_Export_Service $exports;

	public function __construct( SPDB_Operations_Repository $repository, SPDB_Export_Service $exports ) {
		$this->repository = $repository;
		$this->exports    = $exports;
	}

	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );
	}

	/** @param array<string,array<string,mixed>> $exporters @return array<string,array<string,mixed>> */
	public function exporters( array $exporters ): array {
		$exporters['spdb-file23'] = array(
			'exporter_friendly_name' => __( 'Publishing Dashboard operational metadata', 'sabri-publishing-dashboard' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);
		return $exporters;
	}

	/** @param array<string,array<string,mixed>> $erasers @return array<string,array<string,mixed>> */
	public function erasers( array $erasers ): array {
		$erasers['spdb-file23'] = array(
			'eraser_friendly_name' => __( 'Publishing Dashboard personal metadata', 'sabri-publishing-dashboard' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/** @return array<string,mixed> */
	public function export_personal_data( string $email_address, int $page = 1 ): array {
		if ( $page > 1 ) {
			return array( 'data' => array(), 'done' => true );
		}
		$user = get_user_by( 'email', $email_address );
		if ( ! $user instanceof WP_User ) {
			return array( 'data' => array(), 'done' => true );
		}
		$data  = $this->repository->privacy_export( (int) $user->ID );
		$items = array();
		foreach ( $data as $group => $value ) {
			$items[] = array(
				'group_id'    => 'spdb-file23',
				'group_label' => __( 'Publishing Dashboard', 'sabri-publishing-dashboard' ),
				'item_id'     => 'spdb-' . sanitize_key( $group ) . '-' . (int) $user->ID,
				'data'        => array(
					array(
						'name'  => ucwords( str_replace( '_', ' ', $group ) ),
						'value' => wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
					),
				),
			);
		}
		return array( 'data' => $items, 'done' => true );
	}

	/** @return array<string,mixed> */
	public function erase_personal_data( string $email_address, int $page = 1 ): array {
		if ( $page > 1 ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		$user = get_user_by( 'email', $email_address );
		if ( ! $user instanceof WP_User ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		$files  = $this->exports->erase_user_files( (int) $user->ID );
		$result = $this->repository->privacy_erase( (int) $user->ID );
		if ( is_wp_error( $result ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => true,
				'messages'       => array( $result->get_error_message() ),
				'done'           => true,
			);
		}
		return array(
			'items_removed'  => (int) $result['items_removed'] > 0 || $files > 0,
			'items_retained' => true === $result['items_retained'],
			'messages'       => array( __( 'Institutional task and append-only audit evidence may be retained under the approved retention policy.', 'sabri-publishing-dashboard' ) ),
			'done'           => true,
		);
	}
}
