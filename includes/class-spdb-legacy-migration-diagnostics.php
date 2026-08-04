<?php
/**
 * Read-only File 04 migration/cutover diagnostics.
 *
 * File 23 never mutates legacy or canonical publication records. File 04 and
 * File 21 expose privacy-minimized counts and evidence through filters; File 23
 * merely normalizes that evidence for dry-run, reconciliation, cutover, and
 * rollback review.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Legacy_Migration_Diagnostics {
	/** @return array<string,mixed> */
	public static function snapshot(): array {
		$inventory = apply_filters( 'spdb/file04_migration_inventory', array() );
		$mapping   = apply_filters( 'spdb/file04_migration_mapping', array() );
		$state     = apply_filters( 'spdb/file04_migration_state', array() );

		$inventory = is_array( $inventory ) ? $inventory : array();
		$mapping   = is_array( $mapping ) ? $mapping : array();
		$state     = is_array( $state ) ? $state : array();

		$total       = self::count( $inventory, 'total_records' );
		$candidates  = min( $total, self::count( $inventory, 'eligible_candidates' ) );
		$migrated    = min( $candidates, self::count( $mapping, 'migrated_records' ) );
		$mapped      = min( $candidates, self::count( $mapping, 'mapped_records' ) );
		$duplicates  = self::count( $mapping, 'duplicate_records' );
		$orphans     = self::count( $mapping, 'orphaned_records' );
		$failed      = self::count( $mapping, 'failed_records' );
		$dry_run     = true === ( $state['dry_run_completed'] ?? false );
		$write_state = sanitize_key( (string) ( $state['legacy_write_state'] ?? 'unknown' ) );
		if ( ! in_array( $write_state, array( 'unknown', 'enabled', 'suppressed', 'disabled' ), true ) ) {
			$write_state = 'unknown';
		}
		$rollback_evidence = self::bounded_id( $state['rollback_evidence_id'] ?? '' );
		$reconciliation_id = self::bounded_id( $state['reconciliation_evidence_id'] ?? '' );
		$provider_version  = self::bounded_version( $state['provider_version'] ?? '' );
		$last_checked      = self::timestamp( $state['last_checked_at_gmt'] ?? '' );

		$available = ! empty( $inventory ) || ! empty( $mapping ) || ! empty( $state );
		$mapping_complete = $candidates > 0 && $mapped >= $candidates && 0 === $failed && 0 === $orphans;
		$cutover_ready = $available
			&& $dry_run
			&& $mapping_complete
			&& 0 === $duplicates
			&& in_array( $write_state, array( 'suppressed', 'disabled' ), true )
			&& '' !== $rollback_evidence
			&& '' !== $reconciliation_id;

		return array(
			'available'                  => $available,
			'migration_only'             => true,
			'mutation_supported'         => false,
			'legacy_owner'               => 'file04',
			'canonical_owner'            => 'file21',
			'provider_version'           => $provider_version,
			'total_records'              => $total,
			'eligible_candidates'        => $candidates,
			'migrated_records'           => $migrated,
			'mapped_records'             => $mapped,
			'duplicate_records'          => $duplicates,
			'orphaned_records'           => $orphans,
			'failed_records'             => $failed,
			'dry_run_completed'          => $dry_run,
			'legacy_write_state'         => $write_state,
			'mapping_complete'           => $mapping_complete,
			'cutover_ready'              => $cutover_ready,
			'rollback_evidence_id'       => $rollback_evidence,
			'reconciliation_evidence_id' => $reconciliation_id,
			'last_checked_at_gmt'        => $last_checked,
			'code'                       => ! $available ? 'provider_unavailable' : ( $cutover_ready ? 'cutover_ready' : 'evidence_incomplete' ),
		);
	}

	private static function count( array $source, string $key ): int {
		$value = $source[ $key ] ?? 0;
		return is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) )
			? min( 100000000, max( 0, (int) $value ) )
			: 0;
	}

	private static function bounded_id( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = sanitize_text_field( (string) $value );
		return strlen( $value ) <= 191 ? $value : '';
	}

	private static function bounded_version( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = trim( (string) $value );
		return 1 === preg_match( '/^[0-9A-Za-z][0-9A-Za-z.+-]{0,63}$/', $value ) ? $value : '';
	}

	private static function timestamp( $value ): string {
		if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
			return '';
		}
		try {
			$date = new DateTimeImmutable( (string) $value );
		} catch ( Throwable $exception ) {
			return '';
		}
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'c' );
	}
}
