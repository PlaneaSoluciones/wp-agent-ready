<?php
/**
 * Rate limiting.
 *
 * Enforces a per-IP sliding-window limit using WordPress transients.
 *
 * @package WpAgentReady
 */

/**
 * Check and increment the rate limit counter for the current request IP.
 *
 * The limit and window are configurable via wpar_rate_limit option (default: 60 req/hour).
 * Uses a sliding window: each successful request resets the TTL.
 *
 * @return true|WP_Error True if within limit; WP_Error 429 if exceeded.
 */
function wpar_check_rate_limit(): true|WP_Error {
	$limit = absint( get_option( 'wpar_rate_limit', 60 ) );
	$ip    = wpar_get_client_ip();
	$key   = 'wpar_rl_' . md5( $ip );
	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return new WP_Error(
			'wpar_rate_limit_exceeded',
			__( 'Rate limit exceeded. Try again later.', 'wp-agent-ready' ),
			array( 'status' => 429 )
		);
	}

	if ( 0 === $count ) {
		set_transient( $key, 1, HOUR_IN_SECONDS );
	} else {
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	return true;
}

/**
 * Resolve the client IP address from the server environment.
 *
 * @return string Validated IP address, or '0.0.0.0' if unavailable.
 */
function wpar_get_client_ip(): string {
	$raw_ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) )
		: '';

	return filter_var( $raw_ip, FILTER_VALIDATE_IP ) ? $raw_ip : '0.0.0.0';
}
