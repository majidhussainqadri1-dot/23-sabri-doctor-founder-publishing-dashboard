<?php
/**
 * Optional contract for registered adapters that expose a One-Stop Doctor surface.
 *
 * The adapter remains the native module boundary. File 23 validates registry
 * identity, exact versions, acceptance, actor capability and destination origin
 * before presenting the returned destination.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

interface SPDB_Professional_Surface_Provider {
	/**
	 * Return a privacy-minimized professional surface contract for the actor.
	 *
	 * Returning an empty array or enabled=false hides the surface. The contract
	 * must never contain native records, patient data, message bodies, secrets,
	 * payment data or any self-asserted provider acceptance state.
	 *
	 * @param string $surface One of the supported professional surface keys.
	 * @param int    $user_id Current authenticated user identifier.
	 * @return array<string,mixed>
	 */
	public function get_professional_surface_contract( string $surface, int $user_id ): array;
}
