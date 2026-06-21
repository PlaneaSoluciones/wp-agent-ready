<?php
/**
 * Plugin Name:       WP Agent Ready
 * Plugin URI:        https://github.com/PlaneaSoluciones/wp-agent-ready
 * Description:       Exposes WordPress published content to AI agents and LLMs via a clean REST API.
 * Version:           0.7.0
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

define( 'WPAR_VERSION', '0.7.0' );
define( 'WPAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAR_PLUGIN_FILE', __FILE__ );

// webhook.php must load on init (not just REST) so its hooks fire in admin/cron contexts too.
add_action( 'init', 'wpar_bootstrap_init' );

// Admin settings page — only in wp-admin and admin-ajax.php contexts.
add_action( 'init', 'wpar_maybe_bootstrap_admin' );

// REST-only components: rate limiting, sanitizer, Yoast, handler, content endpoint.
// webhook.php is safe here too — require_once prevents double-loading.
add_action( 'rest_api_init', 'wpar_bootstrap_rest', 1 );

register_activation_hook( WPAR_PLUGIN_FILE, 'wpar_on_activation' );
register_deactivation_hook( WPAR_PLUGIN_FILE, 'wpar_on_deactivation' );

/**
 * Load admin settings page only when in wp-admin or AJAX context.
 */
function wpar_maybe_bootstrap_admin(): void {
	if ( ! is_admin() ) {
		return;
	}
	require_once WPAR_PLUGIN_DIR . 'includes/admin.php';
}

/**
 * Load components needed on every request (admin, front-end, cron, REST).
 */
function wpar_bootstrap_init(): void {
	require_once WPAR_PLUGIN_DIR . 'includes/webhook.php';
	require_once WPAR_PLUGIN_DIR . 'public/well-known.php';
}

/**
 * Load REST-only components (conditional load — only active in REST context).
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
 * Generate the webhook API key and flush rewrite rules on first activation.
 */
function wpar_on_activation(): void {
	if ( '' === (string) get_option( 'wpar_webhook_key', '' ) ) {
		update_option( 'wpar_webhook_key', wp_generate_password( 48, false ), false );
	}

	// Register discovery rewrite rules before flushing so they persist in the DB.
	add_rewrite_rule( '^\.well-known/mcp\.json$', 'index.php?wpar_manifest=1', 'top' );
	add_rewrite_rule( '^llms\.txt$', 'index.php?wpar_llms_txt=1', 'top' );
	flush_rewrite_rules( false );
}

/**
 * Flush rewrite rules on deactivation to remove WPAR's custom rules.
 */
function wpar_on_deactivation(): void {
	flush_rewrite_rules( false );
}
