<?php
/**
 * Theme setup for WP Docs.
 *
 * @package wp-docs
 */

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$asset_path = get_theme_file_path( 'assets/js/docs-rendering.js' );

		wp_enqueue_script(
			'wp-docs-rendering',
			get_theme_file_uri( 'assets/js/docs-rendering.js' ),
			array(),
			file_exists( $asset_path ) ? (string) filemtime( $asset_path ) : '0.1.0',
			array( 'strategy' => 'defer' )
		);
	}
);
