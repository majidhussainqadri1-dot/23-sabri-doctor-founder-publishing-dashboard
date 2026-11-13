<?php
/**
 * V12.1 WP-12 Issue #31: disposable real MariaDB integration, never Hostinger.
 * Uses only synthetic File23 export metadata and files, with two full passes.
 */
require_once __DIR__ . '/bootstrap.php';
if ( ! extension_loaded( 'mysqli' ) || '1' !== getenv( 'FILE23_TEST_MARIADB' ) ) {
	fwrite( STDERR, "FAIL: Disposable MariaDB service was not explicitly enabled.\n" ); exit( 1 );
}
$GLOBALS['spdb_mariadb_root'] = sys_get_temp_dir() . '/file23-db-' . bin2hex( random_bytes( 6 ) );
function wp_upload_dir( $t = null, $c = true ): array { return array( 'basedir' => $GLOBALS['spdb_mariadb_root'], 'error' => false ); }
function wp_mkdir_p( string $p ): bool { return is_dir( $p ) || mkdir( $p, 0700, true ); }
function trailingslashit( string $s ): string { return rtrim( $s, '/\\' ) . '/'; }

final class SPDB_Real_Maria_WPDB {
	public string $prefix = 'wp_';
	public mysqli $db;
	public int $read_count = 0;
	public function __construct( mysqli $db ) { $this->db = $db; }
	public function prepare( string $sql, ...$args ): string {
		$index = 0;
		return (string) preg_replace_callback( '/%[ds]/', function ( array $m ) use ( &$index, $args ): string {
			$val = $args[ $index++ ] ?? null;
			return '%d' === $m[0] ? (string) (int) $val : "'" . $this->db->real_escape_string( (string) $val ) . "'";
		}, $sql );
	}
	public function get_results( string $sql, $type = null ) {
		++$this->read_count;
		$q = $this->db->query( $sql );
		return $q instanceof mysqli_result ? $q->fetch_all( MYSQLI_ASSOC ) : false;
	}
	public function get_var( string $sql ) {
		$q = $this->db->query( $sql );
		$r = $q instanceof mysqli_result ? $q->fetch_row() : null;
		return $r[0] ?? null;
	}
}
$db = new mysqli( '127.0.0.1', 'root', (string) getenv( 'FILE23_TEST_DB_PASSWORD' ), '', 3306 );
$db->set_charset( 'utf8mb4' );
$database = 'file23_ci_' . bin2hex( random_bytes( 5 ) );
if ( ! $db->query( 'CREATE DATABASE ' . $database . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' ) ) {
	throw new RuntimeException( 'Disposable CI database could not be created.' );
}
$db->select_db( $database );
$GLOBALS['wpdb'] = new SPDB_Real_Maria_WPDB( $db );
$table = SPDB_Operations_Schema::table( 'export_jobs' );
$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $label ) use ( &$tests, &$failed ): void {
	++$tests; if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$label}\n" ); }
};
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$folder = $GLOBALS['spdb_mariadb_root'] . '/spdb-private-exports';
$first = 'export_' . str_repeat( 'a', 32 ) . '.spdb';
$last = 'export_' . str_repeat( 'b', 32 ) . '.spdb';
$other = 'export_' . str_repeat( 'c', 32 ) . '.spdb';
$start = microtime( true );

try {
	$schema = 'CREATE TABLE ' . $table . ' (
		id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		export_id varchar(64) NOT NULL UNIQUE,
		owner_user_id bigint unsigned NOT NULL,
		report_key varchar(96) NOT NULL,
		format varchar(16) NOT NULL,
		scope varchar(16) NOT NULL,
		status varchar(24) NOT NULL,
		filters_json longtext NOT NULL,
		storage_ref varchar(255) NOT NULL,
		file_hash char(64) NOT NULL,
		row_count bigint unsigned NOT NULL DEFAULT 0,
		error_code varchar(96) NOT NULL,
		created_at_gmt datetime NOT NULL,
		updated_at_gmt datetime NOT NULL,
		expires_at_gmt datetime NOT NULL,
		KEY owner_status (owner_user_id,status),
		KEY expires_at_gmt (expires_at_gmt)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
	$assert( true === $db->query( $schema ), 'Actual MariaDB InnoDB export-job metadata schema is available.' );
	$columns = '(id,export_id,owner_user_id,report_key,format,scope,status,filters_json,storage_ref,file_hash,row_count,error_code,created_at_gmt,updated_at_gmt,expires_at_gmt)';
	for ( $batch = 1; $batch <= 20001; $batch += 500 ) {
		$values = array();
		for ( $id = $batch; $id < min( $batch + 500, 20002 ); ++$id ) {
			$ref = 1 === $id ? $first : ( 20001 === $id ? $last : '' );
			$eid = '' !== $ref ? substr( $ref, 0, -5 ) : 'export_' . str_pad( dechex( $id ), 32, '0', STR_PAD_LEFT );
			$values[] = '(' . $id . ",'" . $eid . "',7,'publication_history','pdf','own','ready','{}','" . $ref . "','',0,'','2026-07-01 00:00:00','2026-07-01 00:00:00','2026-07-30 00:00:00')";
		}
		$assert( true === $db->query( 'INSERT INTO ' . $table . ' ' . $columns . ' VALUES ' . implode( ',', $values ) ), 'Real MariaDB inserted a bounded metadata batch.' );
	}
	$assert( true === $db->query( 'INSERT INTO ' . $table . ' ' . $columns . " VALUES (20002,'" . substr( $other, 0, -5 ) . "',8,'publication_history','pdf','own','ready','{}','" . $other . "','',0,'','2026-07-01 00:00:00','2026-07-01 00:00:00','2027-01-01 00:00:00')" ), 'Later-expiring foreign-owner record inserted.' );
	$assert( '20002' === (string) $GLOBALS['wpdb']->get_var( 'SELECT COUNT(*) FROM ' . $table ), '20,002 synthetic rows exist in real SQL.' );
	$private = new ReflectionMethod( SPDB_Export_Service::class, 'private_directory' );
	$private->setAccessible( true );
	$assert( is_string( $private->invoke( $service ) ), 'Private guarded synthetic directory initialized.' );
	file_put_contents( $folder . '/' . $first, 'OWNER7_A' );
	file_put_contents( $folder . '/' . $last, 'OWNER7_B' );
	file_put_contents( $folder . '/' . $other, 'OWNER8_C' );

	$db->query( 'UPDATE ' . $table . " SET storage_ref='" . $other . "' WHERE id=1" );
	$bad = $service->erase_user_files_page( 7, 0, 200 );
	$assert( is_wp_error( $bad ) && 'spdb_export_erase_reference_invalid' === $bad->get_error_code(), 'Real DB incorrect owner/job artifact reference fails closed.' );
	$assert( 'OWNER8_C' === file_get_contents( $folder . '/' . $other ), 'Foreign encrypted artifact not deleted after bad reference.' );
	$db->query( 'UPDATE ' . $table . " SET storage_ref='" . $first . "' WHERE id=1" );

	$cursor = 0; $pages = 0; $deleted = 0; $finished = false;
	do {
		$result = $service->erase_user_files_page( 7, $cursor, 200 );
		$assert( is_array( $result ), 'Actual SQL keyset erasure page returns a valid result.' );
		if ( ! is_array( $result ) ) { break; }
		$assert( (int) $result['next_cursor'] > $cursor, 'Actual SQL cursor advances monotonically.' );
		$cursor = (int) $result['next_cursor'];
		$deleted += (int) $result['deleted'];
		$finished = true === $result['done'];
		++$pages;
	} while ( ! $finished && $pages <= 120 );
	$assert( $finished && 20001 === $cursor && $pages >= 101 && 2 === $deleted, 'Actual MariaDB erases two owned files across >20,000 rows.' );
	$assert( ! file_exists( $folder . '/' . $first ) && ! file_exists( $folder . '/' . $last ), 'Both matching owner ciphertexts removed.' );
	$assert( 'OWNER8_C' === file_get_contents( $folder . '/' . $other ), 'Other account ciphertext remains intact.' );

	$cursor = 0; $checks = 0; $finished = false;
	do {
		$result = $service->erase_user_files_page( 7, $cursor, 200, true );
		$assert( is_array( $result ), 'Independent DB-based read-only verification page succeeds.' );
		if ( ! is_array( $result ) ) { break; }
		$cursor = (int) $result['next_cursor'];
		$finished = true === $result['done'];
		++$checks;
	} while ( ! $finished && $checks <= 120 );
	$assert( $finished && 20001 === $cursor && $checks >= 101, 'Second real-SQL verification pass spans all owner rows.' );
	$assert( '20002' === (string) $GLOBALS['wpdb']->get_var( 'SELECT COUNT(*) FROM ' . $table ), 'Export metadata is retained until explicit authorized SQL cleanup.' );

	// The expiry-retention path must independently traverse >20k real
	// rows, delete only expired File23 artifacts and re-verify from zero.
	file_put_contents( $folder . '/' . $first, 'OWNER7_EXPIRED_1' );
	file_put_contents( $folder . '/' . $last, 'OWNER7_EXPIRED_20001' );
	$expiry_cursor = 0; $expiry_pages = 0; $expiry_deleted = 0; $expiry_done = false;
	do {
		$step = $service->cleanup_expired_files_page( '2026-07-30 00:00:00', $expiry_cursor, 200, false, 20002 );
		$assert( is_array( $step ), 'Actual SQL expiry page accepted the frozen cutoff and max row.' );
		if ( ! is_array( $step ) ) { break; }
		$expiry_cursor = (int) $step['next_cursor'];
		$expiry_deleted += (int) $step['deleted'];
		$expiry_done = true === $step['done'];
		++$expiry_pages;
	} while ( ! $expiry_done && $expiry_pages <= 120 );
	$assert( $expiry_done && 20001 === $expiry_cursor && $expiry_pages >= 101 && 2 === $expiry_deleted, 'Actual MariaDB expiry cleanup spans 20k+ rows and removes two eligible artifacts.' );
	$assert( 'OWNER8_C' === file_get_contents( $folder . '/' . $other ), 'Future, cross-owner artifact remains after expiry cleanup.' );

	$expiry_cursor = 0; $expiry_verify_pages = 0; $expiry_done = false;
	do {
		$step = $service->cleanup_expired_files_page( '2026-07-30 00:00:00', $expiry_cursor, 200, true, 20002 );
		$assert( is_array( $step ), 'Independent expiry verification page remains read-only.' );
		if ( ! is_array( $step ) ) { break; }
		$expiry_cursor = (int) $step['next_cursor'];
		$expiry_done = true === $step['done'];
		++$expiry_verify_pages;
	} while ( ! $expiry_done && $expiry_verify_pages <= 120 );
	$assert( $expiry_done && 20001 === $expiry_cursor && $expiry_verify_pages >= 101, 'Real MariaDB expiry verification finishes across all 20k+ expired rows.' );
	$assert( '20002' === (string) $GLOBALS['wpdb']->get_var( 'SELECT COUNT(*) FROM ' . $table ), 'Two expiry passes do not prematurely remove metadata.' );

	$db->query( 'UPDATE ' . $table . " SET status='processing' WHERE id=1" );
	file_put_contents( $folder . '/' . $first, 'ACTIVE EXPIRED JOB' );
	$active = $service->cleanup_expired_files_page( '2026-07-30 00:00:00', 0, 200, false, 20002 );
	$assert( is_wp_error( $active ) && 'spdb_export_cleanup_active_job' === $active->get_error_code(), 'Actual MariaDB expired processing job is retained for retry.' );
	$assert( 'ACTIVE EXPIRED JOB' === file_get_contents( $folder . '/' . $first ), 'Expired active ciphertext not deleted.' );
	@unlink( $folder . '/' . $first );
	$db->query( 'UPDATE ' . $table . " SET status='ready' WHERE id=1" );

	$db->begin_transaction();
	$db->query( 'DELETE FROM ' . $table . ' WHERE owner_user_id=7' );
	$assert( '1' === (string) $GLOBALS['wpdb']->get_var( 'SELECT COUNT(*) FROM ' . $table ), 'InnoDB owner-scoped metadata transaction is isolated.' );
	$db->rollback();
	$assert( '20002' === (string) $GLOBALS['wpdb']->get_var( 'SELECT COUNT(*) FROM ' . $table ), 'Real InnoDB rollback restores metadata without losses.' );
	$assert( microtime( true ) - $start < 150, 'CI-only MariaDB test finished within a 150-second safety budget, not a Hostinger performance SLO.' );
	echo 'MariaDB synthetic rows=20002, erase_pages=' . $pages . ', verify_pages=' . $checks . ', expiry_pages=' . $expiry_pages . ', expiry_verify_pages=' . $expiry_verify_pages . ', select_queries=' . $GLOBALS['wpdb']->read_count . "\n";
} finally {
	$db->query( 'DROP DATABASE IF EXISTS ' . $database );
	$db->close();
	if ( is_dir( $folder ) ) {
		foreach ( scandir( $folder ) ?: array() as $f ) {
			if ( '.' !== $f && '..' !== $f ) { @unlink( $folder . '/' . $f ); }
		}
		@rmdir( $folder );
	}
	@rmdir( $GLOBALS['spdb_mariadb_root'] );
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} real MariaDB File23 source checks failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 real MariaDB File23 checks passed.\n";
