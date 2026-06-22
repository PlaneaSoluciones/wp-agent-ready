<?php
/**
 * Webhook emitter and receiver.
 *
 * Loaded on init (all contexts) so post-transition hooks fire in admin and cron.
 * The /sync REST endpoint is registered via a nested rest_api_init hook (fires only in REST).
 *
 * @package WpAgentReady
 */

// Register the sync REST endpoint when in REST context.
add_action( 'rest_api_init', 'wpar_register_sync_route' );

// Emit webhook on post status transitions (admin, front-end, REST edits).
add_action( 'transition_post_status', 'wpar_on_post_status_transition', 10, 3 );

// Emit a delete webhook before a published post is permanently removed.
add_action( 'before_delete_post', 'wpar_on_post_delete', 10, 2 );

// Handle scheduled retry deliveries.
add_action( 'wpar_retry_webhook', 'wpar_send_webhook_notification', 10, 3 );

/**
 * Register POST /wp-json/wpar/v1/sync.
 */
function wpar_register_sync_route(): void {
	register_rest_route(
		'wpar/v1',
		'/sync',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'wpar_handle_sync_request',
			'permission_callback' => 'wpar_verify_webhook_key',
			'args'                => array(
				'post_id' => array(
					'type'              => 'integer',
					'required'          => true,
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}

/**
 * Validate the Bearer API key from the Authorization header.
 *
 * @param WP_REST_Request $request Incoming request.
 * @return true|WP_Error True if authorized; WP_Error with appropriate status code otherwise.
 */
function wpar_verify_webhook_key( WP_REST_Request $request ): true|WP_Error {
	$stored_key = (string) get_option( 'wpar_webhook_key', '' );

	if ( '' === $stored_key ) {
		return new WP_Error(
			'wpar_no_webhook_key',
			__( 'Webhook key not configured.', 'wp-agent-ready' ),
			array( 'status' => 503 )
		);
	}

	$auth_header = $request->get_header( 'authorization' );

	if ( ! is_string( $auth_header ) || ! str_starts_with( $auth_header, 'Bearer ' ) ) {
		return new WP_Error(
			'wpar_missing_auth',
			__( 'Missing or invalid Authorization header.', 'wp-agent-ready' ),
			array( 'status' => 401 )
		);
	}

	$provided_key = substr( $auth_header, 7 );

	if ( ! hash_equals( $stored_key, $provided_key ) ) {
		return new WP_Error(
			'wpar_invalid_key',
			__( 'Invalid API key.', 'wp-agent-ready' ),
			array( 'status' => 403 )
		);
	}

	return true;
}

/**
 * Handle POST /wp-json/wpar/v1/sync.
 *
 * Triggers a fresh webhook emission to the MCP for the requested post.
 *
 * @param WP_REST_Request $request Incoming request.
 * @return WP_REST_Response|WP_Error
 */
function wpar_handle_sync_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$post_id = absint( $request->get_param( 'post_id' ) );
	$post    = get_post( $post_id );

	if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status ) {
		return new WP_Error(
			'wpar_post_not_found',
			__( 'Post not found or not published.', 'wp-agent-ready' ),
			array( 'status' => 404 )
		);
	}

	wpar_send_webhook_notification( $post_id, 'update', 1 );

	return new WP_REST_Response(
		array(
			'success' => true,
			'post_id' => $post_id,
		),
		200
	);
}

/**
 * Emit a webhook on post status transitions.
 *
 * Determines publish / update / delete based on old and new statuses.
 * Skips autosaves and revisions.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Previous post status.
 * @param WP_Post $post       Post object.
 */
function wpar_on_post_status_transition( string $new_status, string $old_status, WP_Post $post ): void {
	if ( wp_is_post_autosave( $post->ID ) || wp_is_post_revision( $post->ID ) ) {
		return;
	}

	if ( 'publish' === $new_status && 'publish' !== $old_status ) {
		wpar_send_webhook_notification( $post->ID, 'publish', 1 );
	} elseif ( 'publish' === $new_status ) {
		wpar_send_webhook_notification( $post->ID, 'update', 1 );
	} elseif ( 'publish' === $old_status ) {
		wpar_send_webhook_notification( $post->ID, 'delete', 1 );
	}
}

/**
 * Emit a delete webhook before a published post is permanently removed.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function wpar_on_post_delete( int $post_id, WP_Post $post ): void {
	if ( 'publish' === $post->post_status ) {
		wpar_send_webhook_notification( $post_id, 'delete', 1 );
	}
}

/**
 * Send a webhook notification to the configured MCP server.
 *
 * On failure, schedules up to 2 retries with exponential backoff (5 min, 15 min).
 *
 * @param int    $post_id Post ID.
 * @param string $action  Webhook action: publish|update|delete.
 * @param int    $attempt Current attempt number, 1-based (max 3).
 */
function wpar_send_webhook_notification( int $post_id, string $action, int $attempt = 1 ): void {
	$mcp_url = (string) get_option( 'wpar_mcp_url', '' );

	if ( '' === $mcp_url ) {
		return;
	}

	$post = get_post( $post_id );

	if ( ! ( $post instanceof WP_Post ) ) {
		return;
	}

	$payload = wp_json_encode(
		array(
			'post_id'   => $post_id,
			'url'       => (string) get_permalink( $post ),
			'action'    => $action,
			'timestamp' => gmdate( 'c' ),
		)
	);

	if ( false === $payload ) {
		return;
	}

	$response = wp_remote_post(
		$mcp_url,
		array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'X-WPAR-Secret' => (string) get_option( 'wpar_mcp_secret', '' ),
			),
			'body'    => $payload,
			'timeout' => 5,
		)
	);

	if ( is_wp_error( $response ) ) {
		$failed = true;
	} else {
		$code   = wp_remote_retrieve_response_code( $response );
		$failed = ! is_int( $code ) || $code >= 300;
	}

	if ( $failed && $attempt < 3 ) {
		// Exponential backoff: attempt 1 → 5 min, attempt 2 → 15 min.
		$delay = (int) ( 5 * MINUTE_IN_SECONDS * ( 3 ** ( $attempt - 1 ) ) );
		wp_schedule_single_event(
			time() + $delay,
			'wpar_retry_webhook',
			array( $post_id, $action, $attempt + 1 )
		);
	}
}
