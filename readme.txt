=== WP Agent Ready ===
Contributors: planeasoluciones
Tags: ai, llm, rest-api, agents, mcp
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 0.8.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Exposes WordPress published content to AI agents and LLMs via a clean REST API.

== Description ==

WP Agent Ready makes your WordPress site readable by AI agents, LLMs, and external automation tools by exposing published content through a structured REST API.

**Main features:**

* `GET /wp-json/wpar/v1/content` — paginated content endpoint with filters (`per_page`, `page`, `post_type`, `modified_after`)
* Clean content output — HTML tags and unexpanded shortcodes are stripped automatically
* Optional Yoast SEO integration — exposes meta description and SEO title if Yoast is active
* Rate limiting — configurable requests per hour per IP (default: 60)
* Webhook emitter — notifies your MCP server on publish, update, or delete events
* `POST /wp-json/wpar/v1/sync` — authenticated endpoint to trigger re-sync on demand
* Discoverability — `/.well-known/mcp.json` manifest and `/llms.txt` for LLM agents
* Admin settings page — configure MCP server URL, API key, allowed post types, rate limit, and access control
* Public access toggle — disable the content endpoint independently of search engine visibility

== Installation ==

1. Upload the `wp-agent-ready` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings › WP Agent Ready** to configure your MCP server URL and API key.

== Frequently Asked Questions ==

= Does this plugin require an MCP server? =

No. The content endpoint `/wp-json/wpar/v1/content` works without any external server. The MCP URL and webhook are only needed if you want real-time sync notifications.

= Is the content endpoint public? =

By default yes — any HTTP client can read it. You can disable public access from **Settings › WP Agent Ready › Contenido** without deactivating the plugin.

= Does it work with custom post types? =

Yes. Any public post type can be enabled from the settings page.

= What happens to my data if I uninstall the plugin? =

By default nothing is deleted. If you want to remove all plugin data from the database, enable **Borrar datos al desinstalar** in **Settings › WP Agent Ready › Avanzado** before uninstalling.

== Changelog ==

= 0.7.0 =
* Added robots.txt integration: X-llms-txt and X-Content-API directives are automatically appended, improving AI crawler discoverability even when /llms.txt is managed by another plugin.
* Added `wpar_serve_llms_txt` filter for programmatic control of /llms.txt generation.
* Changed /llms.txt rewrite rule priority to bottom for better compatibility with Yoast SEO and other plugins.

= 0.6.0 =
* Added public access toggle for the content endpoint, independent of search engine visibility settings.

= 0.5.1 =
* Fixed: development files (`phpstan-bootstrap.php`, brief document) no longer included in release ZIP.

= 0.5.0 =
* Added option to delete all plugin data on uninstall (opt-in, disabled by default).

= 0.4.0 =
* Added admin settings page (Settings > WP Agent Ready) with MCP connection, content, and discoverability sections.

= 0.3.0 =
* Added `/.well-known/mcp.json` manifest and `/llms.txt` for LLM discoverability.
* Added `GET /wp-json/wpar/v1/manifest` REST endpoint.

= 0.2.0 =
* Added webhook emitter and `POST /wp-json/wpar/v1/sync` endpoint with Bearer auth and exponential backoff retry.

== Upgrade Notice ==

= 0.6.0 =
New setting added. No action required — the content endpoint remains public by default.
