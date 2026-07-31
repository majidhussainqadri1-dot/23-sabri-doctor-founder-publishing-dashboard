<?php
/** Executable tests for the Phase 23H resolver readiness bridge foundation. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-native-reference-readiness-bridge.php';

final class SPDB_Test_23H_Readiness implements SPDB_Native_Reference_Readiness {
	public bool $ready = false;
	public bool $available = true;
	public bool $throw = false;
	public bool $invalid_shape = false;
	public bool $reordered_shape = false;
	public string $source_code = 'provider-secret-code';
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;

	public function is_ready(): bool {
		++$this->ready_calls;
		if ( $this->throw ) { throw new RuntimeException( 'Private readiness exception.' ); }
		return $this->ready;
	}

	public function readiness_snapshot(): array {
		++$this->snapshot_calls;
		if ( $this->throw ) { throw new RuntimeException( 'Private readiness snapshot exception.' ); }
		$snapshot = $this->reordered_shape
			? array( 'code' => $this->source_code, 'ready' => $this->ready, 'available' => $this->available )
			: array( 'available' => $this->available, 'ready' => $this->ready, 'code' => $this->source_code );
		if ( $this->invalid_shape ) { $snapshot['private_detail'] = 'must-not-project'; }
		return $snapshot;
	}
}

final class SPDB_Test_23H_Resolver implements SPDB_Native_Reference_Resolver {
	public int $calls = 0;
	public bool $throw = false;
	public bool $return_error = false;
	public bool $invalid_response = false;
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		++$this->calls;
		if ( $this->throw ) { throw new RuntimeException( 'Private native resolver exception.' ); }
		if ( $this->return_error ) { return new WP_Error( 'provider_private_error', 'Patient and provider secret must not escape.', array( 'secret' => 'hidden' ) ); }
		if ( $this->invalid_response ) { return 'invalid-provider-response'; }
		return array(
			'provider_key' => $provider_key,
			'object_type' => $object_type,
			'object_id' => $object_id,
			'context' => $context,
		);
	}
}

$tests = 0;
$failed = 0;
function spdb_23h_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
function spdb_23h_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }

$resolver = new SPDB_Test_23H_Resolver();
$readiness = new SPDB_Test_23H_Readiness();
$bridge = new SPDB_Native_Reference_Readiness_Bridge( $resolver, $readiness );

$not_ready_snapshot = $bridge->readiness_snapshot();
spdb_23h_assert( array( 'available' => true, 'ready' => false, 'code' => 'resolver_not_ready' ) === $not_ready_snapshot, 'A present but unready resolver must project a bounded not-ready state.' );
$not_ready_result = $bridge->resolve_reference( 'provider_one', 'publication', 'post-101', array( 'scope' => 'own' ) );
spdb_23h_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23h_code( $not_ready_result ) && 0 === $resolver->calls, 'Not-ready resolution must fail before resolver execution.' );

$readiness->ready = true;
$ready_snapshot = $bridge->readiness_snapshot();
spdb_23h_assert( array( 'available' => true, 'ready' => true, 'code' => 'ready' ) === $ready_snapshot, 'Only consistent available and ready signals may produce readiness.' );
$resolved = $bridge->resolve_reference( 'provider_one', 'publication', 'post-101', array( 'scope' => 'own' ) );
spdb_23h_assert( is_array( $resolved ) && 'post-101' === $resolved['object_id'] && 1 === $resolver->calls, 'A ready bridge must delegate the exact reference once.' );
spdb_23h_assert( array_keys( $ready_snapshot ) === array( 'available', 'ready', 'code' ) && ! in_array( $readiness->source_code, $ready_snapshot, true ), 'The bridge must reconstruct a fixed non-sensitive readiness projection.' );

$readiness->reordered_shape = true;
$reordered = $bridge->readiness_snapshot();
spdb_23h_assert( array( 'available' => true, 'ready' => true, 'code' => 'ready' ) === $reordered, 'A valid readiness projection must not depend on associative key order.' );
$readiness->reordered_shape = false;

$readiness->available = false;
$unavailable = $bridge->readiness_snapshot();
spdb_23h_assert( array( 'available' => false, 'ready' => false, 'code' => 'resolver_unavailable' ) === $unavailable, 'Unavailable readiness must remain distinct from a present but unready resolver.' );
$readiness->available = true;

$readiness->invalid_shape = true;
$invalid = $bridge->readiness_snapshot();
spdb_23h_assert( array( 'available' => false, 'ready' => false, 'code' => 'readiness_invalid' ) === $invalid, 'Unknown readiness fields must invalidate the projection.' );
$invalid_result = $bridge->resolve_reference( 'provider_one', 'publication', 'post-102', array( 'scope' => 'own' ) );
spdb_23h_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23h_code( $invalid_result ) && 1 === $resolver->calls, 'Malformed readiness must prevent further resolver execution.' );
$readiness->invalid_shape = false;

$readiness->throw = true;
$exception_snapshot = $bridge->readiness_snapshot();
spdb_23h_assert( array( 'available' => false, 'ready' => false, 'code' => 'readiness_exception' ) === $exception_snapshot, 'Readiness exceptions must be isolated and reconstructed.' );
$exception_result = $bridge->resolve_reference( 'provider_one', 'publication', 'post-103', array( 'scope' => 'own' ) );
spdb_23h_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23h_code( $exception_result ) && 1 === $resolver->calls, 'Readiness exceptions must fail before resolver execution.' );
$readiness->throw = false;

$readiness->ready = true;
$resolver->throw = true;
$resolver_failure = $bridge->resolve_reference( 'provider_one', 'publication', 'post-104', array( 'scope' => 'own' ) );
spdb_23h_assert( 'spdb_native_reference_resolver_failed' === spdb_23h_code( $resolver_failure ) && false === strpos( $resolver_failure->get_error_message(), 'Private' ), 'Resolver exceptions must be replaced with a bounded non-sensitive error.' );
$resolver->throw = false;

$resolver->return_error = true;
$provider_error = $bridge->resolve_reference( 'provider_one', 'publication', 'post-105', array( 'scope' => 'own' ) );
spdb_23h_assert( 'spdb_native_reference_resolver_error' === spdb_23h_code( $provider_error ) && false === strpos( $provider_error->get_error_message(), 'Patient' ) && array( 'status' => 503 ) === $provider_error->get_error_data(), 'Resolver WP_Error text and data must be replaced with a bounded bridge error.' );
$resolver->return_error = false;

$resolver->invalid_response = true;
$invalid_provider_response = $bridge->resolve_reference( 'provider_one', 'publication', 'post-106', array( 'scope' => 'own' ) );
spdb_23h_assert( 'spdb_native_reference_resolver_response_invalid' === spdb_23h_code( $invalid_provider_response ), 'A malformed resolver response must fail closed at the bridge.' );
$resolver->invalid_response = false;

$readiness->ready = false;
$transition_result = $bridge->resolve_reference( 'provider_one', 'publication', 'post-107', array( 'scope' => 'own' ) );
spdb_23h_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23h_code( $transition_result ) && 4 === $resolver->calls, 'Readiness loss after successful or failed resolver calls must be enforced immediately.' );
spdb_23h_assert( 2 === $readiness->ready_calls - $readiness->snapshot_calls, 'Readiness declaration exceptions must short-circuit before snapshot evaluation without weakening denial.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23H readiness bridge tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Phase 23H readiness bridge tests passed.\n";
