<?php
/**
 * Main REST request handler.
 *
 * Assembles query arguments, runs WP_Query, and returns paginated JSON.
 *
 * @package WpAgentReady
 */

/**
 * Handle GET /wp-json/wpar/v1/content.
 *
 * @param WP_REST_Request $request Incoming REST request.
 * @return WP_REST_Response|WP_Error JSON response with pagination headers, or rate-limit error.
 */
function wpar_handle_content_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$rate_check = wpar_check_rate_limit();
	if ( is_wp_error( $rate_check ) ) {
		return $rate_check;
	}

	$per_page       = absint( $request->get_param( 'per_page' ) );
	$page           = absint( $request->get_param( 'page' ) );
	$post_type      = (string) $request->get_param( 'post_type' );
	$modified_after = (string) $request->get_param( 'modified_after' );

	$args = array(
		'post_type'      => '' !== $post_type ? $post_type : 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page > 0 ? $per_page : 10,
		'paged'          => $page > 0 ? $page : 1,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'no_found_rows'  => false,
	);

	if ( '' !== $modified_after ) {
		$args['date_query'] = array(
			array(
				'column' => 'post_modified_gmt',
				'after'  => $modified_after,
			),
		);
	}

	$query = new WP_Query( $args );
	$items = array();

	foreach ( $query->posts as $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			continue;
		}
		$items[] = wpar_format_post( $post );
	}

	$response = new WP_REST_Response( $items, 200 );
	$response->header( 'X-WP-Total', (string) $query->found_posts );
	$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

	return $response;
}

/**
 * Format a single post into the API response structure.
 *
 * @param WP_Post $post Post object.
 * @return array<string, mixed> Formatted post data.
 */
function wpar_format_post( WP_Post $post ): array {
	$categories = get_the_terms( $post->ID, 'category' );
	$tags       = get_the_terms( $post->ID, 'post_tag' );

	return array(
		'id'         => $post->ID,
		'title'      => wpar_get_seo_title( $post ),
		'url'        => (string) get_permalink( $post ),
		'date'       => mysql_to_rfc3339( $post->post_date_gmt ),
		'modified'   => mysql_to_rfc3339( $post->post_modified_gmt ),
		'excerpt'    => wpar_get_meta_description( $post ),
		'content'    => wpar_sanitize_content( $post->post_content ),
		'categories' => wpar_format_terms( is_array( $categories ) ? $categories : array() ),
		'tags'       => wpar_format_terms( is_array( $tags ) ? $tags : array() ),
	);
}

/**
 * Map an array of WP_Term objects to an array of term names.
 *
 * @param WP_Term[] $terms Term objects.
 * @return string[]        Term names.
 */
function wpar_format_terms( array $terms ): array {
	return array_map(
		static fn( WP_Term $term ): string => $term->name,
		$terms
	);
}
