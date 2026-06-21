<?php
/**
 * Content sanitizer.
 *
 * Converts raw WordPress post content into clean plain text for LLM consumption.
 *
 * @package WpAgentReady
 */

/**
 * Strip HTML tags and unexpanded shortcodes from post content.
 *
 * Expands shortcodes first so their rendered output is preserved,
 * then strips any remaining placeholders and all HTML markup.
 *
 * @param string $raw_content Raw post content with HTML and shortcodes.
 * @return string Clean, normalized plain text.
 */
function wpar_sanitize_content( string $raw_content ): string {
	$content = do_shortcode( $raw_content );
	$content = strip_shortcodes( $content );
	$content = wp_strip_all_tags( $content );
	$content = preg_replace( '/\s+/', ' ', $content ) ?? $content;

	return trim( $content );
}
