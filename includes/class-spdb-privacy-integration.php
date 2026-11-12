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
		$items = array();
		foreach ( $data as $group => $value ) {
			if ( array() === $value ) {
				continue;
			}
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
	/**
	 * WordPress erasure paginates with a durable server-side checkpoint, not
	 * the caller's page number. The full independent verification pass is
	 * compulsory before File23-owned export-job metadata is deleted.
	 *
	 * @return array<string,mixed>
	 */
	public function erase_personal_data( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user instanceof WP_User ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		$user_id = (int) $user->ID;
		$checkpoint = $this->read_erasure_checkpoint( $user_id );
		if ( is_wp_error( $checkpoint ) ) {
			return $this->erasure_error_response( $checkpoint );
		}
		if ( null === $checkpoint ) {
			if ( $page > 1 ) {
				return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
			}
			$checkpoint = array(
				'user_id' => $user_id,
				'stage' => 'remove',
				'cursor' => 0,
				'removed' => 0,
				'started_at_unix' => time(),
			);
			$saved = $this->save_erasure_checkpoint( $checkpoint, true );
			if ( is_wp_error( $saved ) ) {
				return $this->erasure_error_response( $saved );
			}
			// Another erasure call may have created the checkpoint concurrently.
			$checkpoint = $this->read_erasure_checkpoint( $user_id );
			if ( is_wp_error( $checkpoint ) || ! is_array( $checkpoint ) ) {
				return $this->erasure_error_response( is_wp_error( $checkpoint ) ? $checkpoint : new WP_Error( 'spdb_erasure_checkpoint_missing', __( 'The privacy-erasure checkpoint is unavailable.', 'sabri-publishing-dashboard' ) ) );
			}
		}

		$verify = 'verify' === $checkpoint['stage'];
		$step = $this->exports->erase_user_files_page( $user_id, $checkpoint['cursor'], 200, $verify );
		if ( is_wp_error( $step ) ) {
			return $this->erasure_error_response( $step );
		}
		$checkpoint['removed'] += $step['deleted'];
		if ( empty( $step['done'] ) ) {
			$checkpoint['cursor'] = $step['next_cursor'];
			$saved = $this->save_erasure_checkpoint( $checkpoint );
			return is_wp_error( $saved )
				? $this->erasure_error_response( $saved )
				: array( 'items_removed' => $checkpoint['removed'] > 0, 'items_retained' => true, 'messages' => array(), 'done' => false );
		}

		if ( ! $verify ) {
			// Recheck every owner row from the beginning in a fresh pass. No
			// export may be generated while the durable lock exists.
			$checkpoint['stage'] = 'verify';
			$checkpoint['cursor'] = 0;
			$saved = $this->save_erasure_checkpoint( $checkpoint );
			return is_wp_error( $saved )
				? $this->erasure_error_response( $saved )
				: array( 'items_removed' => $checkpoint['removed'] > 0, 'items_retained' => true, 'messages' => array(), 'done' => false );
		}

		// A complete second scan has confirmed all known owner-referenced
		// ciphertext files absent. Any DB failure retains the checkpoint.
		$result = $this->repository->privacy_erase( $user_id );
		if ( is_wp_error( $result ) ) {
			return $this->erasure_error_response( $result );
		}
		$receipts = SPDB_Operational_Mutation_Guard::erase_user_receipts( $user_id );
		$key = SPDB_Export_Service::privacy_erasure_checkpoint_key( $user_id );
		if ( ! delete_option( $key ) && false !== get_option( $key, false ) ) {
			return $this->erasure_error_response( new WP_Error( 'spdb_erasure_checkpoint_release_failed', __( 'The privacy-erasure lock could not be released after metadata removal.', 'sabri-publishing-dashboard' ) ) );
		}
		return array(
			'items_removed' => (int) ( $result['items_removed'] ?? 0 ) > 0 || $checkpoint['removed'] > 0 || $receipts > 0,
			'items_retained' => true === ( $result['items_retained'] ?? false ),
			'messages' => array( __( 'Institutional task and append-only audit evidence may be retained under the approved retention policy.', 'sabri-publishing-dashboard' ) ),
			'done' => true,
		);
	}

	/** @return array<string,mixed>|WP_Error|null */
	private function read_erasure_checkpoint( int $user_id ) {
		$key = SPDB_Export_Service::privacy_erasure_checkpoint_key( $user_id );
		$raw = get_option( $key, false );
		if ( false === $raw ) {
			return null;
		}
		if ( ! is_array( $raw ) || ! function_exists( 'wp_salt' ) ||
			$user_id !== ( $raw['user_id'] ?? null ) ||
			! in_array( $raw['stage'] ?? null, array( 'remove', 'verify' ), true ) ||
			! is_int( $raw['cursor'] ?? null ) || $raw['cursor'] < 0 ||
			! is_int( $raw['removed'] ?? null ) || $raw['removed'] < 0 ||
			! is_int( $raw['started_at_unix'] ?? null ) || $raw['started_at_unix'] < 1 ||
			! is_string( $raw['signature'] ?? null ) ) {
			return new WP_Error( 'spdb_erasure_checkpoint_invalid', __( 'The private export erasure checkpoint failed validation.', 'sabri-publishing-dashboard' ) );
		}
		$expected = $this->erasure_checkpoint_signature( $raw );
		return hash_equals( $expected, $raw['signature'] )
			? $raw
			: new WP_Error( 'spdb_erasure_checkpoint_invalid', __( 'The private export erasure checkpoint failed authentication.', 'sabri-publishing-dashboard' ) );
	}

	/** @param array<string,mixed> $state @return true|WP_Error */
	private function save_erasure_checkpoint( array $state, bool $create = false ) {
		if ( ! function_exists( 'wp_salt' ) ) {
			return new WP_Error( 'spdb_erasure_key_unavailable', __( 'The privacy-erasure checkpoint signing key is unavailable.', 'sabri-publishing-dashboard' ) );
		}
		$key = SPDB_Export_Service::privacy_erasure_checkpoint_key( (int) $state['user_id'] );
		$state['signature'] = $this->erasure_checkpoint_signature( $state );
		if ( $create && function_exists( 'add_option' ) ) {
			if ( add_option( $key, $state, '', false ) ) {
				return true;
			}
			$existing = $this->read_erasure_checkpoint( (int) $state['user_id'] );
			return is_array( $existing )
				? true
				: new WP_Error( 'spdb_erasure_checkpoint_conflict', __( 'The privacy-erasure checkpoint cannot be created safely.', 'sabri-publishing-dashboard' ) );
		}
		if ( update_option( $key, $state, false ) || get_option( $key, false ) === $state ) {
			return true;
		}
		return new WP_Error( 'spdb_erasure_checkpoint_write_failed', __( 'The private export erasure progress could not be saved.', 'sabri-publishing-dashboard' ) );
	}

	/** @param array<string,mixed> $state */
	private function erasure_checkpoint_signature( array $state ): string {
		$fields = array( 'file23-privacy-export-v1', (string) $state['user_id'], (string) $state['stage'], (string) $state['cursor'], (string) $state['removed'], (string) $state['started_at_unix'] );
		return hash_hmac( 'sha256', implode( '|', $fields ), wp_salt( 'auth' ) );
	}

	/** @return array<string,mixed> */
	private function erasure_error_response( WP_Error $error ): array {
		return array(
			'items_removed' => false,
			'items_retained' => true,
			'messages' => array( $error->get_error_message() ),
			'done' => true,
		);
	}

}
