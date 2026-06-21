# WP Agent Ready

WordPress plugin that exposes published content to AI agents and LLMs via a clean, structured REST API.

## What it does

- REST endpoint `/wp-json/wpar/v1/content` returning clean, HTML-stripped content
- Optional Yoast SEO integration (meta description, SEO title)
- Basic rate limiting (60 req/hour per IP) via transients
- Webhook receiver for re-indexing from an external MCP server
- Generates `llms.txt` at the site root
- Exposes `/.well-known/mcp.json` for discoverability

## What it does NOT do

- Generate AI content
- Modify existing content
- Depend on external APIs

## Requirements

- WordPress 6.0+
- PHP 8.4+

## Installation

1. Upload the `wp-agent-ready/` folder to `/wp-content/plugins/`
2. Activate the plugin from the WordPress admin
3. The REST endpoint is immediately available at `/wp-json/wpar/v1/content`

## Project structure

```
wp-agent-ready/
├── wp-agent-ready.php   ← plugin header + bootstrap
├── includes/
│   ├── endpoint.php     ← REST route registration
│   ├── handler.php      ← main request handler
│   ├── sanitizer.php    ← HTML cleaning
│   ├── yoast.php        ← optional Yoast SEO integration
│   ├── rate-limit.php   ← IP-based rate limiting
│   └── webhook.php      ← webhook emitter/receiver
├── public/
│   └── well-known.php   ← /.well-known/mcp.json + llms.txt
└── languages/           ← i18n ready
```

## Development

```bash
composer install
composer run lint       # PHPCS
composer run analyse    # PHPStan
composer run check      # both
```

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)
