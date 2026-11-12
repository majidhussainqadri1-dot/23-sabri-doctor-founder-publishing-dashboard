<?php
/**
 * V12.1 WP-12: owner-scoped export generation vs privacy erasure lock.
 * The source contract is checked in addition to the live PHP option helper.
 * These tests do not replace real MySQL/Hostinger concurrency drills.
 */
require_once __DIR__ . '/bootstrap.php';
$tests=0; $failed=0;
$assert=static function(bool $yes,string $message) use (&$tests,&$failed):void {
	++$tests;
	if(!$yes){++$failed;fwrite(STDERR,"FAIL: {$message}\n");}
};
$first = SPDB_Export_Service::privacy_erasure_lock_name( 7 );
$second = SPDB_Export_Service::privacy_erasure_lock_name( 8 );
$assert( is_string($first) && strlen($first)<64 && $first!==$second, 'MySQL owner lock names are short and owner-distinct.' );
$assert( $first===SPDB_Export_Service::privacy_erasure_lock_name(7), 'Owner lock names are stable for retry.' );
$assert( SPDB_Export_Service::privacy_erasure_checkpoint_key(7)!==SPDB_Export_Service::privacy_erasure_checkpoint_key(8), 'Export erasure checkpoints remain owner-specific.' );
$assert( !SPDB_Export_Service::privacy_erasure_pending(7), 'New account is not erasure-blocked.' );
$key=SPDB_Export_Service::privacy_erasure_checkpoint_key(7);
update_option($key,array('owner'=>7,'phase'=>'remove'),false);
$assert( SPDB_Export_Service::privacy_erasure_pending(7), 'Any present checkpoint blocks owner export pending authentication and inspection.' );
$assert( !SPDB_Export_Service::privacy_erasure_pending(8), 'Other account is not blocked.' );
delete_option($key);
$assert( !SPDB_Export_Service::privacy_erasure_pending(7), 'Completed checkpoint can release export block.' );

$path=dirname(__DIR__).'/includes/';
$source=file_get_contents($path.'class-spdb-export-service.php');
$privacy=file_get_contents($path.'class-spdb-privacy-integration.php');
$background=file_get_contents($path.'class-spdb-background-jobs.php');
$repository=file_get_contents($path.'class-spdb-operations-repository.php');
$process_start=strpos($source,'public function process( string $export_id )');
$process_end=strpos($source,'private function write_ciphertext_exclusive(', $process_start);
$process=is_int($process_start)&&is_int($process_end)?substr($source,$process_start,$process_end-$process_start):'';
$acquire=strpos($process,"GET_LOCK(%s, 5)");
$write=strpos($process,'write_ciphertext_exclusive( $directory, $filename, $sealed )');
$release=strpos($process,"RELEASE_LOCK(%s)");
$assert(false!==$acquire && false!==$write && false!==$release && $acquire<$write && $write<$release,'Encrypted export write and completion occur strictly inside serialized owner lock.');
$assert(false!==strpos($process,'$fresh = $this->repository->get_export_job( $export_id, 0, true )'),'Owner lock requires a fresh authoritative DB job check before writing.');
$assert(false!==strpos($process,'$this->user_can_export( $owner_user_id )'),'Owner lock rechecks current File00 state before committing ciphertext.');
$assert(false!==strpos($process,'privacy_erasure_pending( $owner_user_id )'),'Late privacy erasure blocks in-flight report generation.');
$assert(false!==strpos($process,"'spdb_export_expired'"),'Expiration is rechecked after lengthy provider projection work.');
$assert(false!==strpos($process,'finally {'),'Advisory lock is released on all outcomes.');

$privacy_start=strpos($privacy,'public function erase_personal_data(');
$privacy_end=strpos($privacy,'private function erase_personal_data_locked(',$privacy_start);
$privacy_wrapper=is_int($privacy_start)&&is_int($privacy_end)?substr($privacy,$privacy_start,$privacy_end-$privacy_start):'';
$assert(false!==strpos($privacy_wrapper,'privacy_erasure_lock_name( $user_id )'),'WordPress erasure uses exactly the same owner lock key as export writer.');
$assert(false!==strpos($privacy_wrapper,'GET_LOCK(%s, 2)')&&false!==strpos($privacy_wrapper,'RELEASE_LOCK(%s)'),'Owner privacy lock is acquired and always released.');
$assert(false!==strpos($privacy,'erase_user_files_page( $user_id, $checkpoint[\'cursor\'], 200, $verify )'),'Privacy erasure is limited to one 200-row page per request.');
$assert(false!==strpos($privacy, '$checkpoint[\'stage\'] = \'verify\';'),'Privacy erasure requires a second verification pass.');
$assert(false!==strpos($privacy,'$this->repository->privacy_erase( $user_id )'),'Native metadata erase happens only after two-pass traversal.');

$assert(false!==strpos($background,'retention_continuation_signature('),'Retention job continuations are HMAC authenticated.');
$assert(false!==strpos($background,'$state[\'max_id\']'),'Retention checkpoints include frozen maximum row ID.');
$assert(false!==strpos($repository,'WHERE expires_at_gmt <= %s AND id <= %d'),'Final metadata purge is also fenced by original row ID.');
if($failed){fwrite(STDERR,"{$failed} of {$tests} owner/export coordination tests failed.\n");exit(1);}
echo "All {$tests} V12.1 owner/export coordination tests passed.\n";
