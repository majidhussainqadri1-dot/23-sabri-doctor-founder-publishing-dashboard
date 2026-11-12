<?php
/**
 * File23 V12.1 WP-12: signed multi-job retention continuation integration.
 *
 * Simulates a File23-owned in-memory DB, WordPress job repository and local
 * private export artifacts. It does not prove Hostinger storage or cron.
 */
require_once __DIR__ . '/bootstrap.php';
function wp_salt( string $scheme = 'auth' ): string { return 'file23-job-test-secret-' . $scheme; }
$GLOBALS['spdb_retention_chain_root'] = sys_get_temp_dir() . '/spdb-retention-chain-' . bin2hex( random_bytes( 7 ) );
function wp_upload_dir( $time = null, $create_dir = true ): array { return array( 'basedir' => $GLOBALS['spdb_retention_chain_root'], 'error' => false ); }
function wp_mkdir_p( string $path ): bool { return is_dir( $path ) || mkdir( $path, 0700, true ); }
function trailingslashit( string $path ): string { return rtrim( $path, '/\\' ) . '/'; }

class SPDB_Continuation_Test_DB {
	public string $prefix = 'wp_';
	public array $exports = array();
	public array $jobs = array();
	public array $audit = array();
	public bool $fail_insert = false;
	public bool $fail_read = false;
	public int $purges = 0;
	public array $deleted_cutoffs = array();

	public function prepare( string $sql, ...$values ): string {
		foreach ( $values as $value ) {
			$pos = strpos( $sql, '%s' );
			$numeric = false;
			if ( false === $pos ) { $pos = strpos( $sql, '%d' ); $numeric = true; }
			if ( false === $pos ) { break; }
			$fragment = $numeric ? (string) (int) $value : "'" . str_replace( "'", "''", (string) $value ) . "'";
			$sql = substr_replace( $sql, $fragment, $pos, 2 );
		}
		return $sql;
	}
	public function get_results( string $sql, $mode = null ) {
		if ( $this->fail_read ) { return false; }
		if ( ! preg_match( "/expires_at_gmt <= '([^']+)' AND id > (\\d+) ORDER BY id ASC LIMIT (\\d+)/", $sql, $parts ) ) {
			return array();
		}
		$cutoff = $parts[1]; $cursor = (int) $parts[2]; $limit = (int) $parts[3];
		$rows = array();
		foreach ( $this->exports as $row ) {
			if ( $row['expires_at_gmt'] <= $cutoff && $row['id'] > $cursor ) {
				$rows[] = array( 'id' => $row['id'], 'storage_ref' => $row['storage_ref'], 'status' => $row['status'] );
			}
			if ( count( $rows ) >= $limit ) { break; }
		}
		return $rows;
	}
	public function get_row( string $sql, $mode = null ) {
		if ( preg_match( "/idempotency_key = '([^']+)'/", $sql, $m ) ) {
			foreach ( $this->jobs as $job ) { if ( $job['idempotency_key'] === $m[1] ) { return $job; } }
		}
		if ( preg_match( "/job_id = '([^']+)'/", $sql, $m ) ) {
			foreach ( $this->jobs as $job ) { if ( $job['job_id'] === $m[1] ) { return $job; } }
		}
		return null;
	}
	public function insert( string $table, array $row ) {
		if ( $this->fail_insert ) { return false; }
		if ( str_contains( $table, 'background_jobs' ) ) { $this->jobs[] = $row; return 1; }
		if ( str_contains( $table, 'dashboard_audit' ) ) { $this->audit[] = $row; return 1; }
		return false;
	}
	public function get_var( string $sql ) {
		if ( str_contains( $sql, '@@in_transaction' ) ) { return '0'; }
		if ( str_contains( $sql, 'GET_LOCK(' ) || str_contains( $sql, 'RELEASE_LOCK(' ) ) { return '1'; }
		return null;
	}
	public function query( string $sql ) {
		if ( preg_match( "/DELETE FROM .*export_jobs WHERE expires_at_gmt <= '([^']+)'/", $sql, $m ) ) {
			$old = count( $this->exports );
			$this->exports = array_values( array_filter( $this->exports, static fn( array $row ): bool => $row['expires_at_gmt'] > $m[1] ) );
			$this->purges += $old - count( $this->exports );
			$this->deleted_cutoffs[] = $m[1];
			return $old - count( $this->exports );
		}
		return 1;
	}
}
$GLOBALS['wpdb'] = new SPDB_Continuation_Test_DB();
$cutoff = '2026-07-31 00:21:00';
for ( $id = 1; $id <= 401; ++$id ) {
	$GLOBALS['wpdb']->exports[] = array( 'id' => $id, 'status' => 'ready', 'storage_ref' => '', 'expires_at_gmt' => $cutoff );
}
$GLOBALS['wpdb']->exports[] = array( 'id' => 402, 'status' => 'ready', 'storage_ref' => '', 'expires_at_gmt' => '2026-08-01 00:21:00' );
$repo = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$exports = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$runner = ( new ReflectionClass( SPDB_Background_Jobs::class ) )->newInstanceWithoutConstructor();
$rp = new ReflectionProperty( SPDB_Background_Jobs::class, 'repository' ); $rp->setAccessible( true ); $rp->setValue( $runner, $repo );
$ep = new ReflectionProperty( SPDB_Background_Jobs::class, 'exports' ); $ep->setAccessible( true ); $ep->setValue( $runner, $exports );
$run = new ReflectionMethod( SPDB_Background_Jobs::class, 'run_retention_cleanup' ); $run->setAccessible( true );
$directory = $GLOBALS['spdb_retention_chain_root'] . '/spdb-private-exports';
$tests=0; $failed=0;
$assert=static function(bool $pass,string $message)use(&$tests,&$failed):void{++$tests;if(!$pass){++$failed;fwrite(STDERR,"FAIL: {$message}\n");}};
try {
	$first='export_'.str_repeat('a',32).'.spdb';
	$last='export_'.str_repeat('b',32).'.spdb';
	$future='export_'.str_repeat('c',32).'.spdb';
	$init=new ReflectionMethod(SPDB_Export_Service::class,'private_directory');$init->setAccessible(true);$init->invoke($exports);
	$GLOBALS['wpdb']->exports[0]['storage_ref']=$first;
	$GLOBALS['wpdb']->exports[400]['storage_ref']=$last;
	$GLOBALS['wpdb']->exports[401]['storage_ref']=$future;
	file_put_contents($directory.'/'.$first,'sealed-first');
	file_put_contents($directory.'/'.$last,'sealed-last');
	file_put_contents($directory.'/'.$future,'sealed-future');
	$job=array('job_id'=>'job_'.str_repeat('d',32),'job_type'=>'retention_cleanup','owner_user_id'=>0,'payload'=>array(),'status'=>'processing');
	$steps=0;
	do {
		$result=$run->invoke($runner,$job);
		$assert(true===$result,'Each signed maintenance continuation must succeed.');
		++$steps;
		if($GLOBALS['wpdb']->purges>0){break;}
		$assert(count($GLOBALS['wpdb']->jobs)===$steps,'Exactly one new continuation queued for the next bounded step.');
		$last_job=end($GLOBALS['wpdb']->jobs);
		$payload=json_decode($last_job['payload_json'],true);
		$assert(is_array($payload)&&is_string($payload['signature']??null),'Continuation is server-signed.');
		$job=array('job_id'=>$last_job['job_id'],'job_type'=>'retention_cleanup','owner_user_id'=>0,'payload'=>$payload,'status'=>'processing');
		$assert(0===$GLOBALS['wpdb']->purges,'Metadata cannot be purged until the independent verification pass ends.');
		if($steps>10){break;}
	}while(true);
	$assert(6===$steps && 401===$GLOBALS['wpdb']->purges,'Two complete 3-page passes precede one frozen-cutoff DB purge.');
	$assert(1===count($GLOBALS['wpdb']->exports)&&402===$GLOBALS['wpdb']->exports[0]['id'],'Future cutoff record remains in database.');
	$assert(is_file($directory.'/'.$future)&&!file_exists($directory.'/'.$first)&&!file_exists($directory.'/'.$last),'Only expired ciphertexts deleted.');
	$assert($GLOBALS['wpdb']->deleted_cutoffs===array($cutoff),'Metadata purge uses the originally frozen UTC cutoff.');

	$bad=array('job_id'=>'job_'.str_repeat('e',32),'payload'=>array('root_job_id'=>'job_'.str_repeat('d',32),'cutoff'=>$cutoff,'phase'=>'verify','cursor'=>999999,'hop'=>5,'signature'=>str_repeat('0',64)));
	$invalid=$run->invoke($runner,$bad);
	$assert(is_wp_error($invalid)&&'spdb_retention_continuation_invalid'===$invalid->get_error_code(),'Forged high cursor cannot trigger metadata purge.');
	$assert(401===$GLOBALS['wpdb']->purges,'Forged continuation did not delete additional metadata.');

	// A new job must not falsely succeed when its next stage cannot be enqueued.
	$GLOBALS['wpdb']->exports[]=array('id'=>500,'status'=>'ready','storage_ref'=>'','expires_at_gmt'=>$cutoff);
	$GLOBALS['wpdb']->fail_insert=true;
	$root=array('job_id'=>'job_'.str_repeat('f',32),'job_type'=>'retention_cleanup','payload'=>array(),'owner_user_id'=>0);
	$failed_queue=$run->invoke($runner,$root);
	$assert(is_wp_error($failed_queue)&&401===$GLOBALS['wpdb']->purges,'Enqueue failure must block marking cleanup done or deleting metadata.');
} finally {
	if(is_dir($directory)){foreach(scandir($directory)?:array() as $entry){if('.'!==$entry&&'..'!==$entry){@unlink($directory.'/'.$entry);}}@rmdir($directory);}
	if(is_dir($GLOBALS['spdb_retention_chain_root'])){@rmdir($GLOBALS['spdb_retention_chain_root']);}
}
if($failed){fwrite(STDERR,"{$failed} of {$tests} continuation chain tests failed.\n");exit(1);}
echo "All {$tests} signed V12.1 retention continuation tests passed.\n";
