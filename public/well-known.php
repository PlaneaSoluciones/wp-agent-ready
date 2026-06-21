<?php
/**
 * Well-known endpoint handlers.
 *
 * Loaded on init via wpar_bootstrap_init(). Registers rewrite rules for
 * /.well-known/mcp.json and /llms.txt, serves both via template_redirect,
 * and exposes GET /wp-json/wpar/v1/manifest as a proper REST endpoint.
 *
 * @package WpAgentReady
 */

// Called directly — we are already inside the init hook when this file is required.
wpar_add_discovery_rewrite_rules();

add_filter( 'query_vars', 'wpar_discovery_query_vars' );
add_action( 'template_redirect', 'wpar_handle_discovery_requests' );
add_action( 'rest_api_init', 'wpar_register_manifest_route' );

/**
 * Register rewrite rules that map well-known paths to WordPress query vars.
 *
 * Also called from wpar_on_activation() to ensure rules are flushed on first install.
 */
function wpar_add_discovery_rewrite_rules(): void {
	add_rewrite_rule( '^\.well-known/mcp\.json$', 'index.php?wpar_manifest=1', 'top' );
	add_rewrite_rule( '^llms\.txt$', 'index.php?wpar_llms_txt=1', 'top' );
}

/**
 * Expose discovery query vars to WordPress.
 *
 * @param string[] $vars Registered query vars.
 * @return string[]
 */
function wpar_discovery_query_vars( array $vars ): array {
	$vars[] = 'wpar_manifest';
	$vars[] = 'wpar_llms_txt';

	return $vars;
}

/**
 * Intercept matched discovery requests before any template is rendered.
 */
function wpar_handle_discovery_requests(): void {
	if ( get_query_var( 'wpar_manifest' ) ) {
		wpar_serve_manifest();
	} elseif ( get_query_var( 'wpar_llms_txt' ) ) {
		wpar_serve_llms_txt();
	}
}

/**
 * Register GET /wp-json/wpar/v1/manifest.
 */
function wpar_register_manifest_route(): void {
	register_rest_route(
		'wpar/v1',
		'/manifest',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'wpar_handle_manifest_rest_request',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * Handle GET /wp-json/wpar/v1/manifest (REST).
 *
 * @return WP_REST_Response JSON manifest with cache headers.
 */
function wpar_handle_manifest_rest_request(): WP_REST_Response {
	$response = new WP_REST_Response( wpar_build_manifest(), 200 );
	$response->header( 'Cache-Control', 'public, max-age=3600, s-maxage=3600' );

	return $response;
}

/**
 * Serve /.well-known/mcp.json directly (rewrite rule path).
 *
 * Outputs JSON and exits.
 */
function wpar_serve_manifest(): void {
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600, s-maxage=3600' );
	header( 'X-Robots-Tag: noindex' );

	echo wp_json_encode( wpar_build_manifest(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

	exit;
}

/**
 * Build the MCP manifest array.
 *
 * @return array<string, mixed>
 */
function wpar_build_manifest(): array {
	$post_types = array_values( get_post_types( array( 'public' => true ) ) );

	return array(
		'name'             => get_bloginfo( 'name' ),
		'description'      => get_bloginfo( 'description' ),
		'url'              => home_url( '/' ),
		'api_version'      => 'wpar/v1',
		'content_endpoint' => rest_url( 'wpar/v1/content' ),
		'sync_endpoint'    => rest_url( 'wpar/v1/sync' ),
		'capabilities'     => array(
			'post_types' => $post_types,
			'filters'    => array( 'per_page', 'page', 'post_type', 'modified_after' ),
			'yoast_seo'  => defined( 'WPSEO_VERSION' ),
		),
	);
}

/**
 * Serve /llms.txt directly (rewrite rule path).
 *
 * Outputs plain text and exits.
 */
function wpar_serve_llms_txt(): void {
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600, s-maxage=3600' );
	header( 'X-Robots-Tag: noindex' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo wpar_build_llms_txt();

	exit;
}

/**
 * Build the llms.txt plain-text content.
 *
 * @return string Markdown-formatted content for LLM agents.
 */
function wpar_build_llms_txt(): string {
	$name        = wp_strip_all_tags( get_bloginfo( 'name' ) );
	$description = wp_strip_all_tags( get_bloginfo( 'description' ) );
	$url         = home_url( '/' );
	$endpoint    = rest_url( 'wpar/v1/content' );
	$manifest    = home_url( '/.well-known/mcp.json' );
	$post_types  = array_values( get_post_types( array( 'public' => true ) ) );

	$lines = array(
		"# {$name}",
		'',
	);

	if ( '' !== $description ) {
		$lines[] = "> {$description}";
		$lines[] = '';
	}

	$lines = array_merge(
		$lines,
		array(
			'This site exposes its content to AI agents via WP Agent Ready.',
			'',
			'## Content API',
			'',
			"- **Endpoint**: {$endpoint}",
			'- **Method**: GET',
			'- **Parameters**:',
			'  - `per_page` (integer, 1-100, default 10)',
			'  - `page` (integer, default 1)',
			'  - `post_type` (string, default "post")',
			'  - `modified_after` (ISO 8601 datetime)',
			'',
			'## Available Post Types',
			'',
		)
	);

	foreach ( $post_types as $type ) {
		$obj = get_post_type_object( $type );
		if ( null !== $obj ) {
			$lines[] = "- `{$type}`: {$obj->labels->name}";
		}
	}

	$lines = array_merge(
		$lines,
		array(
			'',
			'## Discovery',
			'',
			"- **Site URL**: {$url}",
			"- **MCP Manifest**: {$manifest}",
		)
	);

	return implode( "\n", $lines ) . "\n";
}
