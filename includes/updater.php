<?php
/**
 * Auto-updater: comprueba nuevas versiones en GitHub Releases.
 *
 * Usa yahnis-elsts/plugin-update-checker (dependencia Composer de producción, viaja en
 * el vendor/ del ZIP de la Release). Se carga en todas las peticiones (WP-Cron y
 * MainWP también necesitan ver la actualización) si existe vendor/autoload.php: ver wpar_bootstrap_init() en wp-agent-ready.php.
 *
 * @package WpAgentReady
 */

use YahnisElsts\PluginUpdateChecker\v6\PucFactory;

/**
 * Register the GitHub-based update checker.
 */
function wpar_bootstrap_updater(): void {
	$update_checker = PucFactory::buildUpdateChecker(
		'https://github.com/PlaneaSoluciones/wp-agent-ready/',
		WPAR_PLUGIN_FILE,
		'wp-agent-ready'
	);

	// Use the wp-agent-ready.zip release asset (built by CI with the production vendor/),
	// not GitHub's auto-generated source zip. enableReleaseAssets() only exists on the
	// concrete GitHub API class, whose name lives in the library's internal versioned
	// namespace (v6p0), so it can't be narrowed without coupling to that version.
	$update_checker->getVcsApi()->enableReleaseAssets(); // @phpstan-ignore method.notFound

	$token = get_option( 'wpar_github_token' );
	if ( $token ) {
		$update_checker->setAuthentication( $token );
	}
}
wpar_bootstrap_updater();
