<?php
/**
 * Pure-PHP smoke test for WP Docs content format adapter.
 *
 * Run with: php plugins/wp-docs-content-format-adapter/tests/smoke.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$failed = 0;
$total  = 0;

function assert_wp_docs_adapter( string $name, bool $condition ): void {
	global $failed, $total;
	++$total;
	if ( $condition ) {
		echo "  PASS: {$name}\n";
		return;
	}

	echo "  FAIL: {$name}\n";
	++$failed;
}

$GLOBALS['__wp_docs_adapter_filters'] = array();
$GLOBALS['__wp_docs_adapter_actions'] = array();

function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
	$GLOBALS['__wp_docs_adapter_filters'][ $hook ][ $priority ][] = array( $callback, $accepted_args );
}

function apply_filters( string $hook, $value, ...$args ) {
	if ( empty( $GLOBALS['__wp_docs_adapter_filters'][ $hook ] ) ) {
		return $value;
	}

	ksort( $GLOBALS['__wp_docs_adapter_filters'][ $hook ] );
	foreach ( $GLOBALS['__wp_docs_adapter_filters'][ $hook ] as $callbacks ) {
		foreach ( $callbacks as $registered_callback ) {
			list( $callback, $accepted_args ) = $registered_callback;
			$value                            = $callback( ...array_slice( array_merge( array( $value ), $args ), 0, $accepted_args ) );
		}
	}

	return $value;
}

function do_action( string $hook, ...$args ): void {
	$GLOBALS['__wp_docs_adapter_actions'][] = array( $hook, $args );
}

function sanitize_key( $key ): string {
	return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key ) );
}

function is_wp_error( $thing ): bool {
	return $thing instanceof WP_Error;
}

class WP_Error {
	public function __construct( public string $code = '', public string $message = '' ) {}
}

require_once dirname( __DIR__ ) . '/wp-docs-content-format-adapter.php';

add_filter(
	'datamachine_content_format_convert',
	static function ( $converted, string $content, string $from, string $to ): string {
		unset( $converted );
		return "[{$from}:{$to}]{$content}";
	},
	10,
	4
);

$source_post = (object) array( 'post_type' => 'page' );
$imported    = apply_filters(
	'markdown_db_import_post_content',
	'# Hello',
	array( 'post_type' => 'page' ),
	array( 'post_type' => 'page' ),
	$source_post
);

assert_wp_docs_adapter( 'import-calls-generic-conversion-seam', '[markdown:blocks]# Hello' === $imported );

$exported = apply_filters(
	'markdown_db_export_post_content',
	'<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->',
	array( 'post_type' => 'page' ),
	(object) array( 'post_type' => 'page' ),
	$source_post
);

assert_wp_docs_adapter( 'export-calls-generic-conversion-seam', '[blocks:markdown]<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->' === $exported );

echo "\nWP Docs content format adapter smoke: {$total} assertions, {$failed} failures.\n";

exit( min( 1, $failed ) );
