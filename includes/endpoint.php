<?php
/**
 * REST endpoint registration.
 *
 * Hooks into rest_api_init (priority 10) — fires after the bootstrap (priority 1)
 * has already required all dependency files.
 *
 * @package WpAgentReady
 */

add_action( 'rest_api_init', 'wpar_register_routes' );

/**
 * Register all WPAR REST routes.
 */
function wpar_register_routes(): void {
	register_rest_route(
		'wpar/v1',
		'/content',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'wpar_handle_content_request',
			'permission_callback' => '__return_true',
			'args'                => wpar_content_route_args(),
		)
	);
}

/**
 * Define and validate query parameters for the /content route.
 *
 * @return array<string, array<string, mixed>>
 */
function wpar_content_route_args(): array {
	return array(
		'per_page'       => array(
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
		),
		'page'           => array(
			'type'              => 'integer',
			'default'           => 1,
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
		),
		'post_type'      => array(
			'type'              => 'string',
			'default'           => 'post',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => static function ( string $value ): bool {
				$allowed = (array) get_option( 'wpar_post_types', array( 'post', 'page' ) );
				return in_array( $value, $allowed, true );
			},
		),
		'modified_after' => array(
			'type'              => 'string',
			'format'            => 'date-time',
			'sanitize_callback' => 'sanitize_text_field',
		),
	);
}
