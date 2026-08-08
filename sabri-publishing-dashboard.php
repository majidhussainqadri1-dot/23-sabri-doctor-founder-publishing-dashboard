<?php
/**
 * Plugin Name: Sabri Doctor and Founder Publishing Dashboard
 * Plugin URI:  https://www.sabrihomeopathy.com/
 * Description: A private, role-aware, federated publishing operations dashboard for the Founder and verified doctors of the Sabri Social Homeopathy Platform.
 * Version:     1.2.4
 * Author:      Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed
 * Text Domain: sabri-publishing-dashboard
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;
define( 'SPDB_VERSION', '1.2.4' );
define( 'SPDB_CONTRACT_VERSION', '2.0.0' );
define( 'SPDB_PLUGIN_FILE', __FILE__ );
define( 'SPDB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPDB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operational-mutation-guard.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-plugin.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-sensitive-session-guard.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-ai-teacher-oversight-rest.php';
register_activation_hook( __FILE__, array( 'SPDB_Operational_Mutation_Guard', 'activate' ) );
register_activation_hook( __FILE__, array( 'SPDB_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SPDB_Operational_Mutation_Guard', 'deactivate' ) );
register_deactivation_hook( __FILE__, array( 'SPDB_Plugin', 'deactivate' ) );
function spdb(): SPDB_Plugin { return SPDB_Plugin::instance(); }
/** @return string[] */
function spdb_get_capabilities(): array { return SPDB_Capabilities::all(); }
/** @return array<string,mixed> Sanitized File 24 assurance evidence. */
function spdb_get_assurance_manifest(): array { return spdb()->assurance_manifest(); }
/** @return array<int,array<string,mixed>> Complete File 00–26 discovery manifest. */
function spdb_get_dependency_manifest(): array { return spdb()->dependency_manifest(); }
/**
 * Revalidate the current File 00 state and File 23 report capability immediately
 * before an already-generated export is served. The export service still verifies
 * its owner-bound expiring HMAC, job ownership, expiry, hash and encrypted envelope.
 */
function spdb_export_download_authorization_gate(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}
	if ( SPDB_Membership_Guard::current_user_has_sensitive_session() && SPDB_Capabilities::current_user_can( 'spdb_export_reports' ) ) {
		return;
	}
	wp_die(
		esc_html__( 'The export is no longer authorized for the current account or session.', 'sabri-publishing-dashboard' ),
		esc_html__( 'Export unavailable', 'sabri-publishing-dashboard' ),
		array( 'response' => 403 )
	);
}
add_action( 'admin_post_spdb_download_export', 'spdb_export_download_authorization_gate', 1 );
SPDB_AI_Teacher_Oversight_REST::register();
SPDB_Operational_Mutation_Guard::register();
SPDB_Sensitive_Session_Guard::register();
spdb()->boot();
