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
add_filter( 'robots_txt', 'wpar_append_to_robots_txt', 10, 2 );

/**
 * Register rewrite rules that map well-known paths to WordPress query vars.
 *
 * Also called from wpar_on_activation() to ensure rules are flushed on first install.
 */
function wpar_add_discovery_rewrite_rules(): void {
	add_rewrite_rule( '^\.well-known/mcp\.json$', 'index.php?wpar_manifest=1', 'top' );

	// Always register the rule. If a physical llms.txt exists in ABSPATH the web
	// server serves it directly before WordPress loads, so this rule is never
	// triggered (harmless entry in the rewrite table). If the file disappears
	// later (e.g. Yoast disables its llms.txt generation), the rule is already
	// present and starts working immediately without requiring a manual flush.
	add_rewrite_rule( '^llms\.txt$', 'index.php?wpar_llms_txt=1', 'bottom' );
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
		$enabled = (bool) apply_filters( 'wpar_serve_llms_txt', get_option( 'wpar_llms_txt_enabled', true ) );
		if ( ! $enabled ) {
			return;
		}
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
 * Derive the MCP server base URL (scheme://host[:port]) from the configured webhook URL.
 *
 * Returns an empty string when no MCP URL has been configured.
 *
 * @return string
 */
function wpar_get_mcp_base_url(): string {
	$webhook_url = (string) get_option( 'wpar_mcp_url', '' );
	if ( '' === $webhook_url ) {
		return '';
	}
	$parsed = wp_parse_url( $webhook_url );
	$base   = ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' );
	if ( ! empty( $parsed['port'] ) ) {
		$base .= ':' . $parsed['port'];
	}
	return rtrim( $base, '/' );
}

/**
 * Build the MCP manifest array.
 *
 * @return array<string, mixed>
 */
function wpar_build_manifest(): array {
	$post_types = array_values( (array) get_option( 'wpar_post_types', array( 'post', 'page' ) ) );

	$manifest = array(
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

	$mcp_base = wpar_get_mcp_base_url();
	if ( '' !== $mcp_base ) {
		$manifest['mcp_server'] = array(
			'url'      => $mcp_base . '/mcp',
			'manifest' => $mcp_base . '/manifest',
		);
	}

	return $manifest;
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
	$post_types  = array_values( (array) get_option( 'wpar_post_types', array( 'post', 'page' ) ) );

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

	$mcp_base = wpar_get_mcp_base_url();
	if ( '' !== $mcp_base ) {
		$lines = array_merge(
			$lines,
			array(
				'',
				'## MCP Server',
				'',
				"- **MCP Endpoint**: {$mcp_base}/mcp",
				"- **Manifest**: {$mcp_base}/manifest",
			)
		);
	}

	return implode( "\n", $lines ) . "\n";
}

/**
 * Append WP Agent Ready discovery hints to robots.txt.
 *
 * Hooks into the standard WordPress `robots_txt` filter so both vanilla
 * WordPress and Yoast SEO pick it up. The X- directives are informal
 * extensions (same pattern as the Sitemap: directive Yoast adds) and give
 * AI crawlers a pointer to llms.txt and the content API even if the
 * rewrite-based /llms.txt is handled by another plugin.
 *
 * @param string $output    Current robots.txt content.
 * @param bool   $is_public Whether the site allows indexing.
 * @return string
 */
function wpar_append_to_robots_txt( string $output, bool $is_public ): string {
	if ( ! $is_public ) {
		return $output;
	}

	$lines = array(
		'',
		'# WP Agent Ready',
		'X-llms-txt: ' . home_url( '/llms.txt' ),
		'X-Content-API: ' . rest_url( 'wpar/v1/content' ),
	);

	return $output . implode( "\n", $lines ) . "\n";
}
