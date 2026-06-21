<?php
/**
 * Yoast SEO integration.
 *
 * Reads Yoast metadata when available and falls back to native WordPress fields.
 *
 * @package WpAgentReady
 */

/**
 * Get the SEO-optimised description for a post.
 *
 * Returns the Yoast meta description when Yoast SEO is active and the field is set;
 * otherwise returns the native post excerpt.
 *
 * @param WP_Post $post Post object.
 * @return string Description text (never empty string — falls back to excerpt).
 */
function wpar_get_meta_description( WP_Post $post ): string {
	if ( wpar_is_yoast_active() ) {
		$meta = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
		if ( is_string( $meta ) && '' !== $meta ) {
			return $meta;
		}
	}

	return (string) get_the_excerpt( $post );
}

/**
 * Get the SEO-optimised title for a post.
 *
 * Returns the Yoast custom title when Yoast SEO is active and the field is set;
 * otherwise returns the native post title.
 *
 * @param WP_Post $post Post object.
 * @return string Title text.
 */
function wpar_get_seo_title( WP_Post $post ): string {
	if ( wpar_is_yoast_active() ) {
		$title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
		if ( is_string( $title ) && '' !== $title ) {
			return $title;
		}
	}

	return get_the_title( $post );
}

/**
 * Check whether Yoast SEO plugin is active.
 *
 * @return bool True if Yoast SEO is loaded.
 */
function wpar_is_yoast_active(): bool {
	return defined( 'WPSEO_VERSION' );
}
