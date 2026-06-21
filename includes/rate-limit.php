<?php
/**
 * Rate limiting.
 *
 * Enforces a per-IP limit of 60 requests per hour using WordPress transients.
 * Returns a WP_Error when the limit is exceeded.
 *
 * @package WpAgentReady
 */
