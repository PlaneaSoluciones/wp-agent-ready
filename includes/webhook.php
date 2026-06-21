<?php
/**
 * Webhook emitter and receiver.
 *
 * Emits a notification to the MCP server on save_post (publish/update/delete).
 * Receives POST /wp-json/wpar/v1/sync protected by API key stored in wpar_webhook_key option.
 * Retries failed deliveries via wp_schedule_single_event.
 *
 * @package WpAgentReady
 */
