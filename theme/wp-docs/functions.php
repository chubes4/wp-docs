<?php
/**
 * WP Docs theme runtime helpers.
 *
 * @package WPDocs
 */

add_action( 'init', 'wp_docs_register_docs_shell_block' );
add_action( 'wp_enqueue_scripts', 'wp_docs_enqueue_assets' );

/**
 * Register the dynamic docs shell used by block templates.
 */
function wp_docs_register_docs_shell_block(): void {
	register_block_type(
		'wp-docs/docs-shell',
		array(
			'render_callback' => 'wp_docs_render_docs_shell_block',
		)
	);
}

/**
 * Enqueue navigation, TOC, search, and rendering assets.
 */
function wp_docs_enqueue_assets(): void {
	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'wp-docs-navigation',
		get_theme_file_uri( 'assets/docs-navigation.css' ),
		array(),
		$theme_version
	);

	wp_enqueue_script(
		'wp-docs-rendering',
		get_theme_file_uri( 'assets/js/docs-rendering.js' ),
		array(),
		wp_docs_asset_version( 'assets/js/docs-rendering.js', $theme_version ),
		array( 'strategy' => 'defer' )
	);

	wp_enqueue_script(
		'wp-docs-navigation',
		get_theme_file_uri( 'assets/docs-navigation.js' ),
		array(),
		wp_docs_asset_version( 'assets/docs-navigation.js', $theme_version ),
		true
	);

	wp_localize_script(
		'wp-docs-navigation',
		'wpDocsSearchIndex',
		array(
			'entries' => wp_docs_get_search_entries(),
		)
	);
}

/**
 * Return a cache-busting asset version.
 */
function wp_docs_asset_version( string $relative_path, string $fallback ): string {
	$path = get_theme_file_path( $relative_path );

	return file_exists( $path ) ? (string) filemtime( $path ) : $fallback;
}

/**
 * Render the docs shell around post/page content.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Inner block content.
 * @return string Rendered block markup.
 */
function wp_docs_render_docs_shell_block( array $attributes, string $content ): string {
	unset( $attributes );

	ob_start();
	?>
	<div class="wp-docs-shell has-no-wp-docs-toc" data-wp-docs-shell>
		<aside class="wp-docs-sidebar" aria-label="Docs navigation" data-wp-docs-sidebar>
			<div class="wp-docs-sidebar__mobile-header">
				<strong>Docs</strong>
			</div>
			<div class="wp-docs-sidebar__header">
				<span class="wp-docs-sidebar__eyebrow">Docs</span>
				<button class="wp-docs-search-button" type="button" data-wp-docs-search-open aria-haspopup="dialog">
					<span>Search docs</span>
					<kbd>Cmd+K</kbd>
				</button>
			</div>
			<?php echo wp_docs_render_left_navigation(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</aside>

		<article class="wp-docs-content" id="content" data-wp-docs-content>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</article>

		<aside class="wp-docs-toc" aria-label="On this page" data-wp-docs-toc hidden>
			<p class="wp-docs-toc__title">On this page</p>
			<nav aria-label="Page sections" data-wp-docs-toc-list></nav>
		</aside>
	</div>

	<div class="wp-docs-search-modal" data-wp-docs-search hidden>
		<div class="wp-docs-search__backdrop" data-wp-docs-search-close></div>
		<div class="wp-docs-search__dialog" role="dialog" aria-modal="true" aria-labelledby="wp-docs-search-title">
			<div class="wp-docs-search__bar">
				<label class="screen-reader-text" for="wp-docs-search-input" id="wp-docs-search-title">Search documentation</label>
				<input id="wp-docs-search-input" type="search" placeholder="Search titles, headings, and content" autocomplete="off" data-wp-docs-search-input>
				<button type="button" data-wp-docs-search-close>Esc</button>
			</div>
			<div class="wp-docs-search__results" role="listbox" data-wp-docs-search-results></div>
			<p class="wp-docs-search__empty" data-wp-docs-search-empty>Start typing to search the pilot docs.</p>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render the left docs navigation from WordPress pages or fixture metadata.
 */
function wp_docs_render_left_navigation(): string {
	$pages = get_pages(
		array(
			'post_status' => 'publish',
			'sort_column' => 'menu_order,post_title',
			'sort_order'  => 'ASC',
		)
	);

	if ( ! empty( $pages ) && is_page() ) {
		$page_navigation = wp_docs_render_page_navigation( $pages );

		if ( '' !== $page_navigation ) {
			return $page_navigation;
		}
	}

	return wp_docs_render_fixture_navigation();
}

/**
 * Render navigation from the WordPress page hierarchy.
 *
 * @param WP_Post[] $pages Published pages.
 */
function wp_docs_render_page_navigation( array $pages ): string {
	$current_id = (int) get_queried_object_id();
	$page_ids   = array_map(
		static function ( WP_Post $page ): int {
			return (int) $page->ID;
		},
		$pages
	);

	if ( $current_id <= 0 || ! in_array( $current_id, $page_ids, true ) ) {
		return '';
	}

	$ancestor_ids = get_post_ancestors( $current_id );
	$root_id      = ! empty( $ancestor_ids ) ? (int) end( $ancestor_ids ) : $current_id;
	$root_page    = null;
	$children     = array();

	foreach ( $pages as $page ) {
		if ( (int) $page->ID === $root_id ) {
			$root_page = $page;
		}

		$children[ (int) $page->post_parent ][] = $page;
	}

	if ( ! $root_page instanceof WP_Post ) {
		return '';
	}

	$walker = static function ( int $parent_id ) use ( &$walker, $children, $current_id, $ancestor_ids ): string {
		if ( empty( $children[ $parent_id ] ) ) {
			return '';
		}

		$output = '<ul class="wp-docs-nav__list">';
		foreach ( $children[ $parent_id ] as $page ) {
			$is_current  = (int) $page->ID === $current_id;
			$is_ancestor = in_array( (int) $page->ID, $ancestor_ids, true );
			$classes     = array( 'wp-docs-nav__item' );

			if ( $is_current ) {
				$classes[] = 'is-current';
			}

			if ( $is_ancestor ) {
				$classes[] = 'is-ancestor';
			}

			$output .= sprintf(
				'<li class="%1$s"><a class="wp-docs-nav__link" href="%2$s"%3$s>%4$s</a>%5$s</li>',
				esc_attr( implode( ' ', $classes ) ),
				esc_url( get_permalink( $page ) ),
				$is_current ? ' aria-current="page"' : '',
				esc_html( get_the_title( $page ) ),
				$walker( (int) $page->ID )
			);
		}

		$output .= '</ul>';
		return $output;
	};

	$root_children = $walker( $root_id );
	$root_slug     = (string) get_post_field( 'post_name', $root_page );
	$is_current    = (int) $root_page->ID === $current_id;
	$classes       = array( 'wp-docs-nav__item', 'wp-docs-nav__item--root' );

	if ( $is_current ) {
		$classes[] = 'is-current';
	} else {
		$classes[] = 'is-ancestor';
	}

	$output  = wp_docs_render_root_links( $root_slug );
	$output .= '<nav class="wp-docs-nav" data-wp-docs-nav>';
	$output .= '<ul class="wp-docs-nav__list"><li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	$output .= sprintf(
		'<a class="wp-docs-nav__link" href="%1$s"%2$s>%3$s</a>',
		esc_url( get_permalink( $root_page ) ),
		$is_current ? ' aria-current="page"' : '',
		esc_html( get_the_title( $root_page ) )
	);
	$output .= $root_children;
	$output .= '</li></ul></nav>';

	return $output;
}

/**
 * Render navigation from the committed pilot metadata fixture.
 */
function wp_docs_render_fixture_navigation(): string {
	$entries = wp_docs_get_fixture_entries();

	if ( empty( $entries ) ) {
		return '<nav class="wp-docs-nav" data-wp-docs-nav><p class="wp-docs-nav__empty">Docs navigation will appear here after pages are imported.</p></nav>';
	}

	$root_key = wp_docs_get_current_fixture_root( $entries );
	$entries  = array_values(
		array_filter(
			$entries,
			static function ( array $entry ) use ( $root_key ): bool {
				return wp_docs_get_entry_root_key( $entry ) === $root_key;
			}
		)
	);

	usort(
		$entries,
		static function ( array $a, array $b ): int {
			return array( $a['sectionOrder'] ?? 999, $a['order'] ?? 999, $a['title'] ?? '' ) <=> array( $b['sectionOrder'] ?? 999, $b['order'] ?? 999, $b['title'] ?? '' );
		}
	);

	$by_section = array();
	foreach ( $entries as $entry ) {
		$section                  = (string) ( $entry['section'] ?? 'Docs' );
		$by_section[ $section ][] = $entry;
	}

	$output = wp_docs_render_root_links( $root_key );
	$output .= '<nav class="wp-docs-nav" data-wp-docs-nav><ul class="wp-docs-nav__list">';
	foreach ( $by_section as $section => $items ) {
		$output .= '<li class="wp-docs-nav__section"><span class="wp-docs-nav__section-title">' . esc_html( $section ) . '</span><ul class="wp-docs-nav__list">';
		$output .= wp_docs_render_fixture_tree( $items );
		$output .= '</ul></li>';
	}
	$output .= '</ul></nav>';

	return $output;
}

/**
 * Render configured links for a docs root above the navigation tree.
 */
function wp_docs_render_root_links( string $root_key ): string {
	$config = wp_docs_get_root_link_config();
	$links  = $config[ $root_key ] ?? $config['default'] ?? array();

	if ( empty( $links ) ) {
		return '';
	}

	$output = '<div class="wp-docs-root-links" aria-label="Related resources">';
	foreach ( $links as $link ) {
		$url   = isset( $link['url'] ) ? (string) $link['url'] : '';
		$label = isset( $link['label'] ) ? (string) $link['label'] : '';

		if ( '' === $url || '' === $label ) {
			continue;
		}

		$type    = isset( $link['type'] ) ? sanitize_html_class( (string) $link['type'] ) : 'resource';
		$output .= sprintf(
			'<a class="wp-docs-root-links__link wp-docs-root-links__link--%1$s" href="%2$s">%3$s</a>',
			$type,
			esc_url( $url ),
			esc_html( $label )
		);
	}
	$output .= '</div>';

	return $output;
}

/**
 * Central docs-root resource configuration.
 *
 * @return array<string,array<int,array{label:string,url:string,type:string}>>
 */
function wp_docs_get_root_link_config(): array {
	$config = array(
		'default'       => array(
			array(
				'label' => 'Build with WordPress.com',
				'url'   => 'https://wordpress.com/studio/',
				'type'  => 'commercial',
			),
			array(
				'label' => 'WordPress release notes',
				'url'   => 'https://wordpress.org/documentation/wordpress-version/version-history/',
				'type'  => 'open-source',
			),
		),
		'wordpress'     => array(
			array(
				'label' => 'Build with WordPress.com',
				'url'   => 'https://wordpress.com/studio/',
				'type'  => 'commercial',
			),
			array(
				'label' => 'WordPress release notes',
				'url'   => 'https://wordpress.org/documentation/wordpress-version/version-history/',
				'type'  => 'open-source',
			),
		),
		'wordpress-com' => array(
			array(
				'label' => 'WordPress.com Studio',
				'url'   => 'https://wordpress.com/studio/',
				'type'  => 'commercial',
			),
			array(
				'label' => 'WordPress release notes',
				'url'   => 'https://wordpress.org/documentation/wordpress-version/version-history/',
				'type'  => 'open-source',
			),
		),
	);

	/**
	 * Filters docs-root resource links shown above the sidebar tree.
	 *
	 * @param array<string,array<int,array{label:string,url:string,type:string}>> $config Link config keyed by docs root slug.
	 */
	$filtered_config = apply_filters( 'wp_docs_root_link_config', $config );

	return is_array( $filtered_config ) ? $filtered_config : $config;
}

/**
 * Render nested fixture entries for one section.
 *
 * @param array<int,array<string,mixed>> $items Fixture entries.
 */
function wp_docs_render_fixture_tree( array $items ): string {
	$by_parent = array();
	$ids       = array();

	foreach ( $items as $item ) {
		$id = isset( $item['id'] ) ? (string) $item['id'] : '';

		if ( '' !== $id ) {
			$ids[ $id ] = true;
		}
	}

	foreach ( $items as $item ) {
		$parent = isset( $item['parent'] ) ? (string) $item['parent'] : '';

		if ( '' !== $parent && ! isset( $ids[ $parent ] ) ) {
			$parent = '';
		}

		$by_parent[ $parent ][] = $item;
	}

	$walker = static function ( string $parent ) use ( &$walker, $by_parent ): string {
		if ( empty( $by_parent[ $parent ] ) ) {
			return '';
		}

		$output = '' === $parent ? '' : '<ul class="wp-docs-nav__list">';
		foreach ( $by_parent[ $parent ] as $item ) {
			$id         = isset( $item['id'] ) ? (string) $item['id'] : '';
			$url        = (string) ( $item['url'] ?? '#' );
			$is_current = wp_docs_is_current_fixture_url( $url );

			$output .= sprintf(
				'<li class="wp-docs-nav__item%1$s"><a class="wp-docs-nav__link" href="%2$s"%3$s>%4$s</a>%5$s</li>',
				$is_current ? ' is-current' : '',
				esc_url( $url ),
				$is_current ? ' aria-current="page"' : '',
				esc_html( (string) ( $item['title'] ?? 'Untitled' ) ),
				'' !== $id ? $walker( $id ) : ''
			);
		}
		$output .= '' === $parent ? '' : '</ul>';

		return $output;
	};

	return $walker( '' );
}

/**
 * Determine the fixture root for the current URL.
 *
 * @param array<int,array<string,mixed>> $entries Fixture entries.
 */
function wp_docs_get_current_fixture_root( array $entries ): string {
	foreach ( $entries as $entry ) {
		$url = isset( $entry['url'] ) ? (string) $entry['url'] : '';

		if ( '' !== $url && wp_docs_is_current_fixture_url( $url ) ) {
			return wp_docs_get_entry_root_key( $entry );
		}
	}

	$first = reset( $entries );

	return is_array( $first ) ? wp_docs_get_entry_root_key( $first ) : 'default';
}

/**
 * Return the configured root key for a fixture entry.
 */
function wp_docs_get_entry_root_key( array $entry ): string {
	if ( isset( $entry['root'] ) && '' !== (string) $entry['root'] ) {
		return sanitize_title( (string) $entry['root'] );
	}

	$url_path = isset( $entry['url'] ) ? wp_parse_url( (string) $entry['url'], PHP_URL_PATH ) : '';
	$parts    = is_string( $url_path ) ? array_values( array_filter( explode( '/', trim( $url_path, '/' ) ) ) ) : array();

	return ! empty( $parts ) ? sanitize_title( (string) $parts[0] ) : 'default';
}

/**
 * Determine whether a fixture URL describes the current request path.
 */
function wp_docs_is_current_fixture_url( string $url ): bool {
	$current_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$url_path     = wp_parse_url( $url, PHP_URL_PATH );

	return is_string( $current_path ) && is_string( $url_path ) && untrailingslashit( $current_path ) === untrailingslashit( $url_path );
}

/**
 * Build client-side search entries from WordPress content with fixture fallback.
 *
 * @return array<int,array<string,mixed>>
 */
function wp_docs_get_search_entries(): array {
	$posts = get_posts(
		array(
			'post_type'      => array( 'page', 'post' ),
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);

	$entries = array();
	foreach ( $posts as $post ) {
		$post_type_object = get_post_type_object( $post->post_type );

		$entries[] = array(
			'id'      => (string) $post->ID,
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'section' => $post_type_object ? $post_type_object->labels->singular_name : 'Docs',
			'excerpt' => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'body'    => wp_strip_all_tags( apply_filters( 'the_content', $post->post_content ) ),
		);
	}

	if ( count( $entries ) < 2 ) {
		return wp_docs_get_fixture_entries();
	}

	return $entries;
}

/**
 * Read committed pilot metadata used when WordPress content is not loaded yet.
 *
 * @return array<int,array<string,mixed>>
 */
function wp_docs_get_fixture_entries(): array {
	$path = get_theme_file_path( 'assets/docs-index.json' );

	if ( ! file_exists( $path ) ) {
		return array();
	}

	$decoded = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme fixture file.

	if ( ! is_array( $decoded ) || ! isset( $decoded['entries'] ) || ! is_array( $decoded['entries'] ) ) {
		return array();
	}

	return $decoded['entries'];
}
