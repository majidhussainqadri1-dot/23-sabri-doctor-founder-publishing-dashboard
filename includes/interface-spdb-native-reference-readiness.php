<?php
/**
 * Report whether an aggregate native-reference resolver is operationally ready.
 *
 * Readiness is distinct from object availability. Implementations must be
 * fail-closed, bounded, non-sensitive, and current for the active environment.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

interface SPDB_Native_Reference_Readiness {
	/**
	 * Return true only when the resolver can safely serve the current request.
	 */
	public function is_ready(): bool;

	/**
	 * Return a bounded non-sensitive readiness projection.
	 *
	 * Required keys: available, ready, code.
	 *
	 * @return array{available:bool,ready:bool,code:string}
	 */
	public function readiness_snapshot(): array;
}
