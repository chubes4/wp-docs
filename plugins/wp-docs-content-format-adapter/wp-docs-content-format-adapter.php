<?php
/**
 * Plugin Name: WP Docs Content Format Adapter
 * Description: Connects WP Docs markdown storage import/export hooks to Data Machine's generic content-format conversion seam.
 * Version: 0.1.0
 * Author: Extra Chill
 * Text Domain: wp-docs
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'markdown_db_import_post_content', 'wp_docs_content_format_import_markdown_body', 10, 4 );
add_filter( 'markdown_db_export_post_content', 'wp_docs_content_format_export_markdown_body', 10, 4 );

/**
 * Convert markdown file bodies into the configured WordPress storage format.
 *
 * @param string $content     Markdown file body.
 * @param array  $context     MDI transform context.
 * @param array  $postarr     Post array about to be inserted.
 * @param object $source_post Source markdown post object.
 * @return string Converted content or original content.
 */
function wp_docs_content_format_import_markdown_body( string $content, array $context, array $postarr, object $source_post ): string {
	$post_type = wp_docs_content_format_post_type( $postarr['post_type'] ?? $context['post_type'] ?? $source_post->post_type ?? 'page' );
	$target    = wp_docs_content_format_stored_format( $post_type, $context, $source_post );

	return wp_docs_content_format_convert_or_original( $content, 'markdown', $target, $context, $source_post );
}

/**
 * Convert stored WordPress content back into markdown file bodies.
 *
 * @param string $content     Stored post content.
 * @param array  $context     MDI transform context.
 * @param object $export_post Cloned post object being exported.
 * @param object $source_post Original WordPress post object.
 * @return string Converted content or original content.
 */
function wp_docs_content_format_export_markdown_body( string $content, array $context, object $export_post, object $source_post ): string {
	$post_type = wp_docs_content_format_post_type( $export_post->post_type ?? $context['post_type'] ?? $source_post->post_type ?? 'page' );
	$source    = wp_docs_content_format_stored_format( $post_type, $context, $source_post );

	return wp_docs_content_format_convert_or_original( $content, $source, 'markdown', $context, $source_post );
}

/**
 * Return WP Docs' stored content format for imported posts.
 *
 * @param string $post_type   Post type slug.
 * @param array  $context     MDI transform context.
 * @param object $source_post Source post object.
 * @return string Format slug.
 */
function wp_docs_content_format_stored_format( string $post_type, array $context, object $source_post ): string {
	$format = apply_filters( 'wp_docs_content_format_stored_format', 'blocks', $post_type, $context, $source_post );

	return sanitize_key( is_string( $format ) && '' !== $format ? $format : 'blocks' );
}

/**
 * Normalize a post type value.
 *
 * @param mixed $post_type Raw post type value.
 * @return string Post type slug.
 */
function wp_docs_content_format_post_type( $post_type ): string {
	$post_type = is_string( $post_type ) && '' !== $post_type ? $post_type : 'page';

	return sanitize_key( $post_type );
}

/**
 * Convert content through the generic Data Machine conversion seam.
 *
 * @param string $content     Source content.
 * @param string $from        Source format slug.
 * @param string $to          Target format slug.
 * @param array  $context     MDI transform context.
 * @param object $source_post Source post object.
 * @return string Converted content or original content.
 */
function wp_docs_content_format_convert_or_original( string $content, string $from, string $to, array $context, object $source_post ): string {
	$converted = apply_filters(
		'datamachine_content_format_convert',
		null,
		$content,
		$from,
		$to,
		array(
			'integration' => 'wp-docs-content-format-adapter',
			'source'      => 'markdown-database-integration',
			'context'     => $context,
			'post'        => $source_post,
		)
	);

	if ( is_wp_error( $converted ) ) {
		do_action( 'wp_docs_content_format_conversion_failed', $converted, $context, $source_post );
		return $content;
	}

	return is_string( $converted ) ? $converted : $content;
}
