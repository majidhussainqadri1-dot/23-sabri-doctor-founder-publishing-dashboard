<?php
/**
 * WordPress privacy exporter and eraser for File 23-owned metadata only.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Privacy_Integration {
	private const LEGACY_SAVED_VIEWS_META = 'spdb_saved_views_v1';
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
		$page = max( 1, $page );
		$user = get_user_by( 'email', $email_address );
		if ( ! $user instanceof WP_User ) {
			return array( 'data' => array(), 'done' => true );
		}
		$per_page = 100;
		$data      = $this->repository->privacy_export( (int) $user->ID, $page, $per_page );
		$receipts  = SPDB_Operational_Mutation_Guard::privacy_export_receipts( (int) $user->ID, $page, $per_page );
		$done      = true === ( $data['_done'] ?? false ) && count( $receipts ) < $per_page;
		unset( $data['_done'] );
		$data['mutation_receipts'] = $receipts;

		/*
		 * Legacy saved views are no longer migrated as a side effect of GET.
		 * They therefore remain File 23-owned personal data until an explicit
		 * migration or erasure occurs and must be disclosed to the owner.
		 */
		if ( 1 === $page ) {
			$legacy_saved_views = get_user_meta( (int) $user->ID, self::LEGACY_SAVED_VIEWS_META, true );
			if ( is_array( $legacy_saved_views ) && ! empty( $legacy_saved_views ) ) {
				$data['legacy_saved_views'] = array_slice( $legacy_saved_views, 0, 25 );
			}
			$rate_limit_counters = SPDB_Rate_Limit_Privacy::export_for_user( (int) $user->ID );
			if ( ! empty( $rate_limit_counters ) ) {
				$data['rate_limit_counters'] = $rate_limit_counters;
			}
		}

		$items = array();
		foreach ( $data as $group => $value ) {
			if ( array() === $value ) { continue; }
			$items[] = array(
				'group_id'    => 'spdb-file23',
				'group_label' => __( 'Publishing Dashboard', 'sabri-publishing-dashboard' ),
				'item_id'     => 'spdb-' . sanitize_key( $group ) . '-' . (int) $user->ID . '-page-' . $page,
				'data'        => array(
					array(
						'name'  => ucwords( str_replace( '_', ' ', $group ) ),
						'value' => wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
					),
				),
			);
		}
		return array( 'data' => $items, 'done' => $done );
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
				'items_removed'  => $files > 0,
				'items_retained' => true,
				'messages'       => array( $result->get_error_message() ),
				'done'           => true,
			);
		}

		$legacy_raw     = get_user_meta( (int) $user->ID, self::LEGACY_SAVED_VIEWS_META, true );
		$legacy_removed = false;
		if ( is_array( $legacy_raw ) && ! empty( $legacy_raw ) ) {
			$legacy_removed = delete_user_meta( (int) $user->ID, self::LEGACY_SAVED_VIEWS_META );
		}

		$rate_deleted = SPDB_Rate_Limit_Privacy::erase_for_user( (int) $user->ID );
		if ( is_wp_error( $rate_deleted ) ) {
			return array(
				'items_removed'  => (int) $result['items_removed'] > 0 || $files > 0 || $legacy_removed,
				'items_retained' => true,
				'messages'       => array( $rate_deleted->get_error_message() ),
				'done'           => true,
			);
		}

		$receipts = SPDB_Operational_Mutation_Guard::erase_user_receipts( (int) $user->ID );
		return array(
			'items_removed'  => (int) $result['items_removed'] > 0 || $files > 0 || $receipts > 0 || $legacy_removed || $rate_deleted > 0,
			'items_retained' => true === $result['items_retained'],
			'messages'       => array( __( 'Institutional task and append-only audit evidence may be retained under the approved retention policy.', 'sabri-publishing-dashboard' ) ),
			'done'           => true,
		);
	}
}
