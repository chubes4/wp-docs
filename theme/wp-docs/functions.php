<?php
/**
 * WP Docs theme runtime helpers.
 *
 * @package WPDocs
 */

add_action( 'init', 'wp_docs_register_source_content_types' );
add_action( 'init', 'wp_docs_register_dynamic_blocks' );
add_action( 'wp_enqueue_scripts', 'wp_docs_enqueue_assets' );

/**
 * Register source-shaped documentation content types for the docs scratchpad.
 */
function wp_docs_register_source_content_types(): void {
	register_post_type(
		'helphub_article',
		array(
			'labels'             => array(
				'name'          => __( 'WordPress.org Articles', 'wp-docs' ),
				'singular_name' => __( 'WordPress.org Article', 'wp-docs' ),
			),
			'description'        => __( 'Mirror of wordpress.org/documentation HelpHub articles.', 'wp-docs' ),
			'public'             => true,
			'show_in_rest'       => true,
			'hierarchical'       => false,
			'has_archive'        => 'wordpress-org-documentation',
			'menu_icon'          => 'dashicons-media-document',
			'rewrite'            => array(
				'slug'       => 'wordpress-org-documentation/article',
				'with_front' => false,
			),
			'supports'           => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields' ),
			'taxonomies'         => array( 'category' ),
			'delete_with_user'   => false,
			'publicly_queryable' => true,
		)
	);

	register_post_type(
		'documentation',
		array(
			'labels'             => array(
				'name'          => __( 'WordPress.com Docs', 'wp-docs' ),
				'singular_name' => __( 'WordPress.com Doc', 'wp-docs' ),
			),
			'description'        => __( 'Mirror of developer.wordpress.com documentation posts.', 'wp-docs' ),
			'public'             => true,
			'show_in_rest'       => true,
			'hierarchical'       => true,
			'has_archive'        => 'developer-wordpress-com-documentation',
			'menu_icon'          => 'dashicons-book',
			'rewrite'            => array(
				'slug'       => 'developer-wordpress-com-documentation',
				'with_front' => false,
			),
			'supports'           => array( 'title', 'editor', 'page-attributes', 'excerpt', 'revisions', 'custom-fields' ),
			'taxonomies'         => array( 'category', 'post_tag' ),
			'delete_with_user'   => false,
			'publicly_queryable' => true,
		)
	);

	register_taxonomy_for_object_type( 'category', 'helphub_article' );
	register_taxonomy_for_object_type( 'category', 'documentation' );
	register_taxonomy_for_object_type( 'post_tag', 'documentation' );
}

/**
 * Register dynamic blocks used by block templates.
 */
function wp_docs_register_dynamic_blocks(): void {
	register_block_type(
		'wp-docs/header',
		array(
			'render_callback' => 'wp_docs_render_header_block',
		)
	);

	register_block_type(
		'wp-docs/root-nav',
		array(
			'render_callback' => 'wp_docs_render_root_nav_block',
		)
	);

	register_block_type(
		'wp-docs/docs-shell',
		array(
			'render_callback' => 'wp_docs_render_docs_shell_block',
		)
	);
}

/**
 * Render the site header without block layout wrappers.
 *
 * @param array $attributes Block attributes.
 */
function wp_docs_render_header_block( array $attributes ): string {
	unset( $attributes );

	ob_start();
	?>
	<div class="wp-docs-header__inner">
		<div class="wp-docs-header__brand">
			<button class="wp-docs-mobile-menu-button" type="button" data-wp-docs-sidebar-toggle aria-expanded="false" aria-controls="wp-docs-sidebar">Menu</button>
			<a class="wp-docs-brand-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<span class="wp-docs-wordpress-mark" aria-hidden="true"><?php echo wp_docs_get_wordpress_logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="wp-docs-brand-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>
		</div>

		<?php echo wp_docs_render_root_nav_block( array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="wp-docs-header__actions">
			<a class="wp-docs-header-button" href="#search" data-wp-docs-search-open>Search</a>
			<a class="wp-docs-header-link" href="https://github.com/chubes4/wp-docs">GitHub</a>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Return the WordPress logo SVG used in the header.
 */
function wp_docs_get_wordpress_logo_svg(): string {
	return '<svg viewBox="0 0 28 28" focusable="false" aria-hidden="true"><path d="M20.3643 24.8789L24.2153 13.7636C24.9351 11.9678 25.1745 10.5322 25.1745 9.25543C25.1745 8.79189 25.1439 8.36182 25.0895 7.96094C26.0738 9.75362 26.634 11.8109 26.634 13.9995C26.634 18.6429 24.113 22.6975 20.3643 24.8789ZM15.7627 8.09923C16.5218 8.05945 17.2058 7.97972 17.2058 7.97972C17.8851 7.89965 17.8052 6.90274 17.1255 6.94252C17.1255 6.94252 15.0832 7.1025 13.7647 7.1025C12.5255 7.1025 10.4436 6.94252 10.4436 6.94252C9.76376 6.90274 9.68406 7.9396 10.3639 7.97972C10.3639 7.97972 11.007 8.05945 11.6862 8.09923L13.6506 13.4723L10.8906 21.7332L6.29941 8.09923C7.05915 8.05945 7.74227 7.97972 7.74227 7.97972C8.42163 7.89965 8.34125 6.90274 7.66172 6.94252C7.66172 6.94252 5.6197 7.1025 4.30118 7.1025C4.06481 7.1025 3.78584 7.09652 3.48927 7.08713C5.74422 3.67045 9.61958 1.41406 14.0248 1.41406C17.3075 1.41406 20.2966 2.66689 22.5397 4.71874C22.4857 4.71533 22.4323 4.7085 22.3766 4.7085C21.1377 4.7085 20.2589 5.78548 20.2589 6.94252C20.2589 7.97972 20.8584 8.85711 21.4976 9.89465C21.977 10.7329 22.537 11.8097 22.537 13.3656C22.537 14.4435 22.217 15.7991 21.5776 17.435L20.3197 21.6297L15.7627 8.09923ZM14.0257 26.5886C12.7881 26.5886 11.5934 26.4069 10.4637 26.0759L14.2474 15.1016L18.1229 25.7013C18.1484 25.7631 18.1796 25.8201 18.2133 25.8746C16.9026 26.3347 15.4943 26.5886 14.0257 26.5886ZM1.41309 13.9976C1.41309 12.1727 1.80528 10.4401 2.50517 8.875L8.51986 25.3255C4.31303 23.2856 1.41309 18.9801 1.41309 13.9976ZM14.0245 0C6.2916 0 0 6.28002 0 13.9993C0 21.7193 6.2916 28 14.0245 28C21.7576 28 28.0494 21.7193 28.0494 13.9993C28.0494 6.28002 21.7576 0 14.0245 0Z" fill="currentColor" /></svg>';
}

/**
 * Render the product/root navigation used by the top header.
 *
 * @param array $attributes Block attributes.
 */
function wp_docs_render_root_nav_block( array $attributes ): string {
	unset( $attributes );

	$items = wp_docs_get_root_nav_items();

	if ( empty( $items ) ) {
		return '';
	}

	$current_root = wp_docs_get_current_root_slug();
	$list         = '';

	foreach ( $items as $item ) {
		$is_current = $current_root && $current_root === $item['slug'];
		$list      .= sprintf(
			'<li class="wp-docs-root-nav__item%1$s"><a class="wp-docs-root-nav__link" href="%2$s"%3$s>%4$s</a></li>',
			$is_current ? ' is-current' : '',
			esc_url( $item['url'] ),
			$is_current ? ' aria-current="page"' : '',
			esc_html( $item['label'] )
		);
	}

	return sprintf(
		'<nav class="wp-docs-root-nav" aria-label="Documentation roots"><ul class="wp-docs-root-nav__list">%1$s</ul></nav>',
		$list
	);
}

/**
 * Return top-level docs roots for the product nav.
 *
 * Known roots keep a stable display order when matching pages exist, then any
 * additional published top-level pages are appended without template edits.
 *
 * @return array<int,array{slug:string,label:string,url:string,order:int}>
 */
function wp_docs_get_root_nav_items(): array {
	$known_roots  = array(
		'wordpress-org' => array(
			'label' => 'WordPress.org',
			'order' => 10,
		),
		'wordpress-com' => array(
			'label' => 'WordPress.com',
			'order' => 20,
		),
		'woocommerce'   => array(
			'label' => 'WooCommerce',
			'order' => 30,
		),
		'jetpack'       => array(
			'label' => 'Jetpack',
			'order' => 40,
		),
		'studio'        => array(
			'label' => 'Studio',
			'order' => 50,
		),
		'playground'    => array(
			'label' => 'Playground',
			'order' => 60,
		),
		'wp-cli'        => array(
			'label' => 'WP-CLI',
			'order' => 70,
		),
	);
	$items        = array();
	$excluded_ids = array_filter(
		array(
			(int) get_option( 'page_on_front' ),
			(int) get_option( 'wp_page_for_privacy_policy' ),
		)
	);

	$pages = get_pages(
		array(
			'parent'      => 0,
			'post_status' => 'publish',
			'sort_column' => 'menu_order,post_title',
			'sort_order'  => 'ASC',
		)
	);
	$pages = is_array( $pages ) ? $pages : array();

	foreach ( $pages as $page ) {
		if ( in_array( (int) $page->ID, $excluded_ids, true ) ) {
			continue;
		}

		$slug_source = '' !== $page->post_name ? $page->post_name : $page->post_title;
		$slug        = sanitize_title( $slug_source );

		$items[ $slug ] = array(
			'slug'  => $slug,
			'label' => isset( $known_roots[ $slug ] ) ? $known_roots[ $slug ]['label'] : get_the_title( $page ),
			'url'   => get_permalink( $page ),
			'order' => isset( $known_roots[ $slug ] ) ? $known_roots[ $slug ]['order'] : 1000 + (int) $page->menu_order,
		);
	}

	foreach ( wp_docs_get_docs_post_types() as $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object ) {
			continue;
		}

		$count = (int) ( new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		) )->found_posts;

		if ( 0 === $count ) {
			continue;
		}

		$slug = wp_docs_get_post_type_root_slug( $post_type );
		$items[ $slug ] = array(
			'slug'  => $slug,
			'label' => $post_type_object->labels->name,
			'url'   => get_post_type_archive_link( $post_type ) ?: home_url( '/' . $slug . '/' ),
			'order' => 100 + count( $items ),
		);
	}

	usort(
		$items,
		static function ( array $a, array $b ): int {
			return array( $a['order'], $a['label'] ) <=> array( $b['order'], $b['label'] );
		}
	);

	return $items;
}

/**
 * Infer the active docs root from the queried page or current path.
 */
function wp_docs_get_current_root_slug(): string {
	$current_id = (int) get_queried_object_id();

	if ( $current_id > 0 && 'page' === get_post_type( $current_id ) ) {
		$ancestor_ids = get_post_ancestors( $current_id );
		$root_id      = empty( $ancestor_ids ) ? $current_id : (int) end( $ancestor_ids );
		$root         = get_post( $root_id );

		if ( $root instanceof WP_Post ) {
			$slug_source = '' !== $root->post_name ? $root->post_name : $root->post_title;

			return sanitize_title( $slug_source );
		}
	}

	if ( $current_id > 0 ) {
		$post_type = get_post_type( $current_id );
		if ( is_string( $post_type ) && in_array( $post_type, wp_docs_get_docs_post_types(), true ) ) {
			return wp_docs_get_post_type_root_slug( $post_type );
		}
	}

	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
		if ( is_string( $post_type ) && '' !== $post_type ) {
			return wp_docs_get_post_type_root_slug( $post_type );
		}
	}

	$current_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';

	if ( ! is_string( $current_path ) ) {
		return '';
	}

	$segments = array_values( array_filter( explode( '/', trim( $current_path, '/' ) ) ) );

	return isset( $segments[0] ) ? sanitize_title( $segments[0] ) : '';
}

/**
 * Return public imported documentation post types, without naming sources here.
 *
 * @return string[]
 */
function wp_docs_get_docs_post_types(): array {
	$post_types = array();

	foreach ( get_post_types( array( 'public' => true ), 'names' ) as $post_type ) {
		if ( in_array( $post_type, array( 'post', 'page', 'attachment' ), true ) ) {
			continue;
		}

		$has_imports = ( new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_wp_docs_import_bucket',
						'compare' => 'EXISTS',
					),
				),
			)
		) )->have_posts();

		if ( $has_imports ) {
			$post_types[] = $post_type;
		}
	}

	return $post_types;
}

/**
 * Return the stable docs root slug for a post type.
 */
function wp_docs_get_post_type_root_slug( string $post_type ): string {
	$post_type_object = get_post_type_object( $post_type );
	$archive          = $post_type_object ? $post_type_object->has_archive : '';

	if ( is_string( $archive ) && '' !== $archive ) {
		return sanitize_title( $archive );
	}

	return sanitize_title( $post_type );
}

/**
 * Enqueue navigation, TOC, search, and rendering assets.
 */
function wp_docs_enqueue_assets(): void {
	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'wp-docs-style',
		get_stylesheet_uri(),
		array(),
		$theme_version
	);

	wp_enqueue_style(
		'wp-docs-navigation',
		get_theme_file_uri( 'assets/docs-navigation.css' ),
		array( 'wp-docs-style' ),
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
		<aside class="wp-docs-sidebar" id="wp-docs-sidebar" aria-label="Docs navigation" data-wp-docs-sidebar>
			<div class="wp-docs-sidebar__mobile-header">
				<strong>Docs</strong>
				<button type="button" data-wp-docs-sidebar-toggle aria-expanded="false" aria-controls="wp-docs-sidebar">Close</button>
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
	$current_id = (int) get_queried_object_id();
	if ( $current_id > 0 ) {
		$post_type = get_post_type( $current_id );
		if ( is_string( $post_type ) && in_array( $post_type, wp_docs_get_docs_post_types(), true ) ) {
			$navigation = wp_docs_render_post_type_navigation( $post_type, $current_id );
			if ( '' !== $navigation ) {
				return $navigation;
			}
		}
	}

	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
		if ( is_string( $post_type ) && in_array( $post_type, wp_docs_get_docs_post_types(), true ) ) {
			$navigation = wp_docs_render_post_type_navigation( $post_type, 0 );
			if ( '' !== $navigation ) {
				return $navigation;
			}
		}
	}

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
 * Render navigation for an imported source post type using its native structure.
 */
function wp_docs_render_post_type_navigation( string $post_type, int $current_id ): string {
	$post_type_object = get_post_type_object( $post_type );
	if ( ! $post_type_object ) {
		return '';
	}

	$root_slug = wp_docs_get_post_type_root_slug( $post_type );
	$output    = wp_docs_render_root_links( $root_slug );

	if ( is_post_type_hierarchical( $post_type ) ) {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			)
		);

		return $output . wp_docs_render_hierarchical_post_navigation( $posts, $current_id, $post_type_object->labels->name );
	}

	$taxonomy = wp_docs_get_primary_hierarchical_taxonomy( $post_type );
	if ( '' !== $taxonomy ) {
		return $output . wp_docs_render_taxonomy_post_navigation( $post_type, $taxonomy, $current_id, $post_type_object->labels->name );
	}

	return $output . wp_docs_render_flat_post_navigation( $post_type, $current_id, $post_type_object->labels->name );
}

/**
 * Pick the first hierarchical taxonomy attached to a post type.
 */
function wp_docs_get_primary_hierarchical_taxonomy( string $post_type ): string {
	foreach ( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy ) {
		if ( $taxonomy->hierarchical && $taxonomy->public ) {
			return $taxonomy->name;
		}
	}

	return '';
}

/**
 * Render a post-parent tree for hierarchical post types.
 *
 * @param WP_Post[] $posts Posts in the current post type.
 */
function wp_docs_render_hierarchical_post_navigation( array $posts, int $current_id, string $root_label ): string {
	$children     = array();
	$ancestor_ids = get_post_ancestors( $current_id );

	foreach ( $posts as $post ) {
		$children[ (int) $post->post_parent ][] = $post;
	}

	$walker = static function ( int $parent_id ) use ( &$walker, $children, $current_id, $ancestor_ids ): string {
		if ( empty( $children[ $parent_id ] ) ) {
			return '';
		}

		$output = '<ul class="wp-docs-nav__list">';
		foreach ( $children[ $parent_id ] as $post ) {
			$is_current  = (int) $post->ID === $current_id;
			$is_ancestor = in_array( (int) $post->ID, $ancestor_ids, true );
			$output     .= wp_docs_render_page_nav_item( $post, $walker( (int) $post->ID ), $is_current, $is_ancestor );
		}
		$output .= '</ul>';

		return $output;
	};

	return '<nav class="wp-docs-nav" data-wp-docs-nav><ul class="wp-docs-nav__list"><li class="wp-docs-nav__section"><span class="wp-docs-nav__section-title">' . esc_html( $root_label ) . '</span>' . $walker( 0 ) . '</li></ul></nav>';
}

/**
 * Render taxonomy hierarchy with posts grouped under terms for flat post types.
 */
function wp_docs_render_taxonomy_post_navigation( string $post_type, string $taxonomy, int $current_id, string $root_label ): string {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return wp_docs_render_flat_post_navigation( $post_type, $current_id, $root_label );
	}

	$terms_by_parent = array();
	foreach ( $terms as $term ) {
		$terms_by_parent[ (int) $term->parent ][] = $term;
	}

	$current_term_ids = wp_get_object_terms( $current_id, $taxonomy, array( 'fields' => 'ids' ) );
	$current_term_ids = is_wp_error( $current_term_ids ) ? array() : array_map( 'intval', $current_term_ids );

	$term_ancestor_ids = array();
	foreach ( $current_term_ids as $term_id ) {
		$term_ancestor_ids = array_merge( $term_ancestor_ids, get_ancestors( $term_id, $taxonomy, 'taxonomy' ) );
	}

	$walker = static function ( int $parent_id ) use ( &$walker, $terms_by_parent, $taxonomy, $post_type, $current_id, $current_term_ids, $term_ancestor_ids ): string {
		if ( empty( $terms_by_parent[ $parent_id ] ) ) {
			return '';
		}

		$output = '<ul class="wp-docs-nav__list">';
		foreach ( $terms_by_parent[ $parent_id ] as $term ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'tax_query'      => array(
						array(
							'taxonomy'         => $taxonomy,
							'terms'            => array( (int) $term->term_id ),
							'include_children' => false,
						),
					),
				)
			);

			$child_list = $walker( (int) $term->term_id );
			if ( ! empty( $posts ) ) {
				$child_list .= '<ul class="wp-docs-nav__list">';
				foreach ( $posts as $post ) {
					$child_list .= wp_docs_render_page_nav_item( $post, '', (int) $post->ID === $current_id, false );
				}
				$child_list .= '</ul>';
			}

			$is_current_term = in_array( (int) $term->term_id, $current_term_ids, true );
			$is_ancestor     = in_array( (int) $term->term_id, $term_ancestor_ids, true );
			$output         .= wp_docs_render_term_nav_item( $term, $child_list, $is_current_term, $is_ancestor );
		}
		$output .= '</ul>';

		return $output;
	};

	return '<nav class="wp-docs-nav" data-wp-docs-nav><ul class="wp-docs-nav__list"><li class="wp-docs-nav__section"><span class="wp-docs-nav__section-title">' . esc_html( $root_label ) . '</span>' . $walker( 0 ) . '</li></ul></nav>';
}

/**
 * Render flat post navigation when no hierarchy source exists.
 */
function wp_docs_render_flat_post_navigation( string $post_type, int $current_id, string $root_label ): string {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$output = '<nav class="wp-docs-nav" data-wp-docs-nav><ul class="wp-docs-nav__list"><li class="wp-docs-nav__section"><span class="wp-docs-nav__section-title">' . esc_html( $root_label ) . '</span><ul class="wp-docs-nav__list">';
	foreach ( $posts as $post ) {
		$output .= wp_docs_render_page_nav_item( $post, '', (int) $post->ID === $current_id, false );
	}
	$output .= '</ul></li></ul></nav>';

	return $output;
}

/**
 * Render a taxonomy term navigation item.
 */
function wp_docs_render_term_nav_item( WP_Term $term, string $child_list, bool $is_current, bool $is_ancestor ): string {
	$has_children = '' !== $child_list;
	$is_expanded  = $has_children && ( $is_current || $is_ancestor );
	$classes      = array( 'wp-docs-nav__item', 'wp-docs-nav__item--term' );
	$children_id  = 'wp-docs-nav-term-children-' . (int) $term->term_id;

	if ( $has_children ) {
		$classes[] = 'has-children';
	}
	if ( $is_current ) {
		$classes[] = 'is-current';
	}
	if ( $is_ancestor ) {
		$classes[] = 'is-ancestor';
	}

	$link = sprintf(
		'<a class="wp-docs-nav__link" href="%1$s">%2$s</a>',
		esc_url( get_term_link( $term ) ),
		esc_html( $term->name )
	);

	if ( $has_children ) {
		$link      .= sprintf(
			'<button class="wp-docs-nav__toggle" type="button" aria-expanded="%1$s" aria-controls="%2$s" data-wp-docs-nav-toggle><span class="screen-reader-text">Toggle %3$s</span></button>',
			$is_expanded ? 'true' : 'false',
			esc_attr( $children_id ),
			esc_html( $term->name )
		);
		$child_list = preg_replace( '/^<ul class="wp-docs-nav__list">/', '<ul class="wp-docs-nav__list" id="' . esc_attr( $children_id ) . '"' . ( $is_expanded ? '' : ' hidden' ) . '>', $child_list, 1 ) ?? $child_list;
	}

	return sprintf( '<li class="%1$s"><div class="wp-docs-nav__row">%2$s</div>%3$s</li>', esc_attr( implode( ' ', $classes ) ), $link, $child_list );
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

	$ancestor_ids  = get_post_ancestors( $current_id );
	$root_id       = ! empty( $ancestor_ids ) ? (int) end( $ancestor_ids ) : $current_id;
	$root_page     = null;
	$children      = array();
	$pages_by_slug = array();

	foreach ( $pages as $page ) {
		if ( (int) $page->ID === $root_id ) {
			$root_page = $page;
		}

		$children[ (int) $page->post_parent ][]     = $page;
		$pages_by_slug[ (string) $page->post_name ] = $page;
	}

	if ( ! $root_page instanceof WP_Post ) {
		return '';
	}

	$root_slug_field = get_post_field( 'post_name', $root_page );
	$root_slug       = is_string( $root_slug_field ) ? $root_slug_field : '';

	if ( 'developer-wordpress-com-documentation' === $root_slug ) {
		$wpcom_navigation = wp_docs_render_wpcom_page_navigation( $root_page, $pages_by_slug, $current_id, $ancestor_ids );

		if ( '' !== $wpcom_navigation ) {
			return wp_docs_render_root_links( $root_slug ) . $wpcom_navigation;
		}
	}

	$walker = static function ( int $parent_id ) use ( &$walker, $children, $current_id, $ancestor_ids ): string {
		if ( empty( $children[ $parent_id ] ) ) {
			return '';
		}

		$output = '<ul class="wp-docs-nav__list">';
		foreach ( $children[ $parent_id ] as $page ) {
			$is_current  = (int) $page->ID === $current_id;
			$is_ancestor = in_array( (int) $page->ID, $ancestor_ids, true );
			$child_list  = $walker( (int) $page->ID );

			$output .= wp_docs_render_page_nav_item( $page, $child_list, $is_current, $is_ancestor );
		}

		$output .= '</ul>';
		return $output;
	};

	$root_children = $walker( $root_id );
	$is_current    = (int) $root_page->ID === $current_id;

	$output  = wp_docs_render_root_links( $root_slug );
	$output .= '<nav class="wp-docs-nav" data-wp-docs-nav>';
	$output .= '<ul class="wp-docs-nav__list">';
	$output .= wp_docs_render_page_nav_item( $root_page, $root_children, $is_current, ! $is_current );
	$output .= '</ul></nav>';

	return $output;
}

/**
 * Render one page navigation item with an optional disclosure control.
 */
function wp_docs_render_page_nav_item( WP_Post $page, string $child_list, bool $is_current, bool $is_ancestor ): string {
	$has_children = '' !== $child_list;
	$is_expanded  = $has_children && ( $is_current || $is_ancestor );
	$classes      = array( 'wp-docs-nav__item' );
	$children_id  = 'wp-docs-nav-children-' . (int) $page->ID;

	if ( $has_children ) {
		$classes[] = 'has-children';
	}

	if ( $is_current ) {
		$classes[] = 'is-current';
	}

	if ( $is_ancestor ) {
		$classes[] = 'is-ancestor';
	}

	$link = sprintf(
		'<a class="wp-docs-nav__link" href="%1$s"%2$s>%3$s</a>',
		esc_url( get_permalink( $page ) ),
		$is_current ? ' aria-current="page"' : '',
		esc_html( get_the_title( $page ) )
	);

	if ( $has_children ) {
		$link      .= sprintf(
			'<button class="wp-docs-nav__toggle" type="button" aria-expanded="%1$s" aria-controls="%2$s" data-wp-docs-nav-toggle><span class="screen-reader-text">Toggle %3$s</span></button>',
			$is_expanded ? 'true' : 'false',
			esc_attr( $children_id ),
			esc_html( get_the_title( $page ) )
		);
		$child_list = preg_replace( '/^<ul class="wp-docs-nav__list">/', '<ul class="wp-docs-nav__list" id="' . esc_attr( $children_id ) . '"' . ( $is_expanded ? '' : ' hidden' ) . '>', $child_list, 1 ) ?? $child_list;
	}

	return sprintf(
		'<li class="%1$s"><div class="wp-docs-nav__row">%2$s</div>%3$s</li>',
		esc_attr( implode( ' ', $classes ) ),
		$link,
		$child_list
	);
}

/**
 * Render the WordPress.com docs sidebar in the same order as the live docs site.
 *
 * @param array<string,WP_Post> $pages_by_slug Imported pages keyed by slug.
 * @param int[]                 $ancestor_ids  Current page ancestors.
 */
function wp_docs_render_wpcom_page_navigation( WP_Post $root_page, array $pages_by_slug, int $current_id, array $ancestor_ids ): string {
	$tree = wp_docs_get_wpcom_navigation_tree();

	$render_nodes = static function ( array $nodes ) use ( &$render_nodes, $pages_by_slug, $current_id, $ancestor_ids ): string {
		$items = '';

		foreach ( $nodes as $slug => $children ) {
			if ( ! isset( $pages_by_slug[ $slug ] ) ) {
				continue;
			}

			$page        = $pages_by_slug[ $slug ];
			$child_list  = is_array( $children ) && ! empty( $children ) ? $render_nodes( $children ) : '';
			$is_current  = (int) $page->ID === $current_id;
			$is_ancestor = in_array( (int) $page->ID, $ancestor_ids, true ) || wp_docs_nav_contains_current_page( $children, $pages_by_slug, $current_id );

			$items .= wp_docs_render_page_nav_item( $page, $child_list, $is_current, $is_ancestor );
		}

		if ( '' === $items ) {
			return '';
		}

		return '<ul class="wp-docs-nav__list">' . $items . '</ul>';
	};

	$is_current = (int) $root_page->ID === $current_id;
	$output     = '<nav class="wp-docs-nav" data-wp-docs-nav><ul class="wp-docs-nav__list">';
	$output    .= wp_docs_render_page_nav_item( $root_page, $render_nodes( $tree ), $is_current, ! $is_current );
	$output    .= '</ul></nav>';

	return $output;
}

/**
 * Determine whether a configured nav branch contains the current page.
 *
 * @param array<string,mixed>|mixed $children Child branch config.
 * @param array<string,WP_Post>     $pages_by_slug Imported pages keyed by slug.
 */
function wp_docs_nav_contains_current_page( $children, array $pages_by_slug, int $current_id ): bool {
	if ( ! is_array( $children ) ) {
		return false;
	}

	foreach ( $children as $slug => $grandchildren ) {
		if ( isset( $pages_by_slug[ $slug ] ) && (int) $pages_by_slug[ $slug ]->ID === $current_id ) {
			return true;
		}

		if ( wp_docs_nav_contains_current_page( $grandchildren, $pages_by_slug, $current_id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Return live WordPress.com developer docs menu order keyed by imported slugs.
 *
 * @return array<string,mixed>
 */
function wp_docs_get_wpcom_navigation_tree(): array {
	// phpcs:disable WordPress.Arrays.MultipleStatementAlignment -- Preserve the live menu order map without column churn.
	return array(
		'developer-wordpress-com-glance' => array(
			'developer-wordpress-com-wordpress-and-wordpress-com' => array(),
			'developer-wordpress-com-tech-stack' => array(),
			'developer-wordpress-com-glossary' => array(),
			'developer-wordpress-com-interface-styles' => array(),
			'developer-wordpress-com-support' => array(),
		),
		'developer-wordpress-com-get-started' => array(
			'developer-wordpress-com-create-site' => array(),
			'developer-wordpress-com-local-environment-setup' => array(),
			'developer-wordpress-com-github' => array(),
			'developer-wordpress-com-develop-locally' => array(),
			'developer-wordpress-com-deploy' => array(),
		),
		'developer-wordpress-com-studio' => array(
			'developer-wordpress-com-sites' => array(),
			'developer-wordpress-com-cli' => array(),
			'developer-wordpress-com-studio-code' => array(),
			'developer-wordpress-com-agent-skills-wordpress-studio' => array(),
			'developer-wordpress-com-mcp-on-studio' => array(),
			'developer-wordpress-com-blueprints' => array(
				'developer-wordpress-com-open-in-wordpress-studio-button' => array(),
				'developer-wordpress-com-how-to-create-custom-blueprints' => array(),
			),
			'developer-wordpress-com-preview-sites' => array(),
			'developer-wordpress-com-sync' => array(),
			'developer-wordpress-com-assistant' => array(),
			'developer-wordpress-com-import-export' => array(),
			'developer-wordpress-com-ssl-in-studio' => array(),
			'developer-wordpress-com-debugging' => array(
				'developer-wordpress-com-xdebug' => array(),
			),
			'developer-wordpress-com-frequently-asked-questions' => array(),
			'developer-wordpress-com-changelog' => array(),
			'developer-wordpress-com-roadmap' => array(
				'developer-wordpress-com-beta-features' => array(),
			),
		),
		'developer-wordpress-com-agent-skills' => array(),
		'developer-wordpress-com-mcp' => array(
			'developer-wordpress-com-tools-reference' => array(),
			'developer-wordpress-com-connect-custom-mcp-client' => array(),
		),
		'developer-wordpress-com-developer-tools' => array(
			'developer-wordpress-com-wp-cli' => array(
				'developer-wordpress-com-overview' => array(),
				'developer-wordpress-com-platform-commands' => array(),
				'developer-wordpress-com-common-commands' => array(),
				'developer-wordpress-com-troubleshooting' => array(),
			),
			'developer-wordpress-com-api' => array(
				'developer-wordpress-com-getting-started' => array(),
				'developer-wordpress-com-rest-api-reference' => array(),
				'developer-wordpress-com-namespaces-versions' => array(),
				'developer-wordpress-com-oauth2' => array(),
				'developer-wordpress-com-wpcc' => array(),
				'developer-wordpress-com-rest-api-javascript' => array(),
				'developer-wordpress-com-guidelines-for-responsible-use-of-automattics-apis' => array(),
			),
			'developer-wordpress-com-site-accelerator-api' => array(),
		),
		'developer-wordpress-com-platform-features' => array(
			'developer-wordpress-com-site-performance' => array(),
			'developer-wordpress-com-domain-management' => array(),
			'developer-wordpress-com-user-management' => array(),
			'developer-wordpress-com-real-time-backup-restore' => array(),
			'developer-wordpress-com-storage' => array(),
			'developer-wordpress-com-sitemaps' => array(),
			'developer-wordpress-com-jetpack-scan' => array(),
			'developer-wordpress-com-account-security' => array(),
		),
		'developer-wordpress-com-guides' => array(
			'developer-wordpress-com-add-http-headers' => array(),
			'developer-wordpress-com-block-patterns' => array(),
			'developer-wordpress-com-manage-permissions' => array(),
			'developer-wordpress-com-manually-restore-backup' => array(),
			'developer-wordpress-com-symlinked-files-folders' => array(),
			'developer-wordpress-com-oembed-provider-api' => array(),
			'developer-wordpress-com-wp-cron-on-wordpress-com' => array(),
		),
		'developer-wordpress-com-troubleshooting-2' => array(
			'developer-wordpress-com-wp-debug' => array(),
			'developer-wordpress-com-jetpack-activity-log' => array(),
		),
	);
	// phpcs:enable WordPress.Arrays.MultipleStatementAlignment
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

	$output  = wp_docs_render_root_links( $root_key );
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
		$url   = (string) $link['url'];
		$label = (string) $link['label'];

		if ( '' === $url || '' === $label ) {
			continue;
		}

		$type    = sanitize_html_class( (string) $link['type'] );
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
	/** @var mixed $filtered_config */
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

	$walker = static function ( string $fixture_parent ) use ( &$walker, $by_parent ): string {
		if ( empty( $by_parent[ $fixture_parent ] ) ) {
			return '';
		}

		$output = '' === $fixture_parent ? '' : '<ul class="wp-docs-nav__list">';
		foreach ( $by_parent[ $fixture_parent ] as $item ) {
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
		$output .= '' === $fixture_parent ? '' : '</ul>';

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
