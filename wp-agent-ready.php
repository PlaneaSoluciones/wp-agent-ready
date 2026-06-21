<?php
/**
 * Plugin Name:       WP Agent Ready
 * Plugin URI:        https://github.com/PlaneaSoluciones/wp-agent-ready
 * Description:       Exposes WordPress published content to AI agents and LLMs via a clean REST API.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.4
 * Author:            Planea Soluciones Informáticas
 * Author URI:        https://planeasoluciones.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-agent-ready
 * Domain Path:       /languages
 *
 * @package WpAgentReady
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPAR_VERSION', '0.1.0' );
define( 'WPAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAR_PLUGIN_FILE', __FILE__ );

add_action( 'rest_api_init', 'wpar_bootstrap_rest' );
add_action( 'init', 'wpar_bootstrap_rewrite' );

/**
 * Load REST API components.
 *
 * Conditional load: only active in REST context to avoid overhead on regular page requests.
 */
function wpar_bootstrap_rest(): void {
	require_once WPAR_PLUGIN_DIR . 'includes/rate-limit.php';
	require_once WPAR_PLUGIN_DIR . 'includes/sanitizer.php';
	require_once WPAR_PLUGIN_DIR . 'includes/yoast.php';
	require_once WPAR_PLUGIN_DIR . 'includes/handler.php';
	require_once WPAR_PLUGIN_DIR . 'includes/endpoint.php';
	require_once WPAR_PLUGIN_DIR . 'includes/webhook.php';
}

/**
 * Load rewrite rules for well-known endpoints.
 */
function wpar_bootstrap_rewrite(): void {
	require_once WPAR_PLUGIN_DIR . 'public/well-known.php';
}
