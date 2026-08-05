<?php
/**
 * Isolated dispatcher for provider adapter registration callbacks.
 *
 * A failing provider callback must not prevent later providers from registering
 * or take down the dashboard request. The dispatcher snapshots the registered
 * callbacks and invokes each one behind an individual Throwable boundary.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Provider_Registration {
	public const HOOK = 'spdb/register_adapters';

	/** Request-scoped registry populated by the plugin service container. */
	private static ?SPDB_Adapter_Registry $registry = null;

	/**
	 * Dispatch all currently registered provider callbacks independently.
	 */
	public static function dispatch( SPDB_Adapter_Registry $registry ): void {
		global $wp_filter;

		// Keep only the request-scoped canonical registry created by File 23.
		// Consumers may inspect it, but providers never receive authority to replace it.
		self::$registry = $registry;

		$hook = $wp_filter[ self::HOOK ] ?? null;
		if ( ! is_object( $hook ) || ! isset( $hook->callbacks ) || ! is_array( $hook->callbacks ) ) {
			return;
		}

		$callbacks = $hook->callbacks;

		// Prevent an unrelated later do_action() call from running the same
		// registration callbacks a second time in this request.
		if ( function_exists( 'remove_all_actions' ) ) {
			remove_all_actions( self::HOOK );
		}

		ksort( $callbacks, SORT_NUMERIC );
		foreach ( $callbacks as $priority_callbacks ) {
			if ( ! is_array( $priority_callbacks ) ) {
				continue;
		}

			foreach ( $priority_callbacks as $callback ) {
				$function      = $callback['function'] ?? null;
				$accepted_args = isset( $callback['accepted_args'] ) ? (int) $callback['accepted_args'] : 1;

				if ( ! is_callable( $function ) ) {
					$registry->record_error(
						'system',
						new WP_Error( 'spdb_invalid_registration_callback', __( 'A provider registration callback is invalid.', 'sabri-publishing-dashboard' ) )
					);
					continue;
				}

				try {
					if ( $accepted_args < 1 ) {
						call_user_func( $function );
					} else {
						call_user_func( $function, $registry );
					}
				} catch ( Throwable $throwable ) {
					$registry->record_error(
						'system',
						new WP_Error( 'spdb_provider_registration_exception', __( 'A provider registration callback failed and was isolated.', 'sabri-publishing-dashboard' ) )
					);
				}
			}
		}
	}

	/**
	 * Return the canonical registry for this request after File 23 dispatch.
	 *
	 * A missing registry is a fail-closed state; callers must not create a
	 * substitute registry or infer provider acceptance from provider input.
	 */
	public static function registry(): ?SPDB_Adapter_Registry {
		return self::$registry;
	}
}
