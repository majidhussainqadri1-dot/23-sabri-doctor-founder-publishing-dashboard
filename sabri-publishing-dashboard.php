<?php
/**
 * Plugin Name: Sabri Doctor and Founder Publishing Dashboard
 * Plugin URI:  https://www.sabrihomeopathy.com/
 * Description: A private, role-aware, federated publishing operations dashboard for the Founder and verified doctors of the Sabri Social Homeopathy Platform.
 * Version:     0.6.2
 * Author:      Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed
 * Text Domain: sabri-publishing-dashboard
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;
define( 'SPDB_VERSION', '0.6.2' );
define( 'SPDB_CONTRACT_VERSION', '2.0.0' );
define( 'SPDB_PLUGIN_FILE', __FILE__ );
define( 'SPDB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPDB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-plugin.php';
register_activation_hook( __FILE__, array( 'SPDB_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SPDB_Plugin', 'deactivate' ) );
function spdb(): SPDB_Plugin { return SPDB_Plugin::instance(); }
/** @return string[] */
function spdb_get_capabilities(): array { return SPDB_Capabilities::all(); }
spdb()->boot();
