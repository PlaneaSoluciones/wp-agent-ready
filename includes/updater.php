<?php
/**
 * Auto-updater: comprueba nuevas versiones en GitHub Releases.
 *
 * Vendorizado desde YahnisElsts/plugin-update-checker v5.7 (includes/lib/plugin-update-checker/).
 * Loaded on init (all contexts) so the library's own hooks also fire during WP-Cron,
 * not just when an admin is browsing wp-admin.
 *
 * @package WpAgentReady
 */

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

require_once WPAR_PLUGIN_DIR . 'includes/lib/plugin-update-checker/plugin-update-checker.php';

/**
 * Register the GitHub-based update checker.
 */
function wpar_bootstrap_updater(): void {
	$update_checker = PucFactory::buildUpdateChecker(
		'https://github.com/PlaneaSoluciones/wp-agent-ready/',
		WPAR_PLUGIN_FILE,
		'wp-agent-ready'
	);

	// Use the wp-agent-ready.zip release asset (built clean by CI), not GitHub's
	// auto-generated source zip, which would include dev-only files.
	$update_checker->getVcsApi()->enableReleaseAssets();

	$token = get_option( 'wpar_github_token' );
	if ( $token ) {
		$update_checker->setAuthentication( $token );
	}
}
wpar_bootstrap_updater();
