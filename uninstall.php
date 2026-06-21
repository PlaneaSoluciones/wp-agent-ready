<?php
/**
 * Plugin uninstall handler.
 *
 * Runs when the user deletes the plugin from wp-admin > Plugins.
 * Only removes data from the database if the user opted in via settings.
 *
 * @package WpAgentReady
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! get_option( 'wpar_delete_on_uninstall' ) ) {
	return;
}

// Remove all plugin options.
$options = array(
	'wpar_mcp_url',
	'wpar_webhook_key',
	'wpar_post_types',
	'wpar_rate_limit',
	'wpar_llms_txt_enabled',
	'wpar_delete_on_uninstall',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove rate-limit transients (sliding window entries stored as wpar_rl_<md5>).
global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	"DELETE FROM {$wpdb->options}
	WHERE option_name LIKE '_transient_wpar_rl_%'
	   OR option_name LIKE '_transient_timeout_wpar_rl_%'"
);
