<?php
/** Isolated dispatcher for provider-specific native resolver registration. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Native_Reference_Registration {
	public const HOOK = 'spdb/register_native_reference_resolvers';

	public static function dispatch( SPDB_Native_Reference_Registry $registry ): void {
		global $wp_filter;
		$hook = $wp_filter[ self::HOOK ] ?? null;
		$callbacks = null;
		if ( is_object( $hook ) && isset( $hook->callbacks ) && is_array( $hook->callbacks ) ) {
			$callbacks = $hook->callbacks;
		} elseif ( is_array( $hook ) ) {
			$callbacks = array( 10 => array_map( static fn( $function ) => array( 'function' => $function, 'accepted_args' => 1 ), $hook ) );
		}
		if ( ! is_array( $callbacks ) ) { return; }

		if ( function_exists( 'remove_all_actions' ) ) { remove_all_actions( self::HOOK ); }
		ksort( $callbacks, SORT_NUMERIC );
		foreach ( $callbacks as $priority_callbacks ) {
			if ( ! is_array( $priority_callbacks ) ) { continue; }
			foreach ( $priority_callbacks as $callback ) {
				$function = $callback['function'] ?? null;
				$accepted_args = isset( $callback['accepted_args'] ) ? (int) $callback['accepted_args'] : 1;
				if ( ! is_callable( $function ) ) {
					$registry->record_error( 'system', new WP_Error( 'spdb_native_resolver_registration_callback_invalid', __( 'A native resolver registration callback is invalid.', 'sabri-publishing-dashboard' ) ) );
					continue;
				}
				try {
					if ( $accepted_args < 1 ) { call_user_func( $function ); }
					else { call_user_func( $function, $registry ); }
				} catch ( Throwable $throwable ) {
					$registry->record_error( 'system', new WP_Error( 'spdb_native_resolver_registration_callback_exception', __( 'A native resolver registration callback failed and was isolated.', 'sabri-publishing-dashboard' ) ) );
				}
			}
		}
	}
}
