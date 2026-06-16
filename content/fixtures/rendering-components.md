# Rendering Components Fixture

This fixture exercises the generated-content markup that the WP Docs theme styles. It is intentionally small enough for reviewers to import or paste into a preview page without running a full corpus generation.

## Inline Code And Anchors

Use inline code for identifiers such as `wp_register_script_module()`, package names such as `@wordpress/interactivity`, and command fragments such as `wp option get siteurl`. Headings should receive stable deep-link anchors in the rendered page.

## Code Blocks

PHP examples should preserve indentation and stay readable on narrow screens.

```php
<?php
wp_register_script_module(
    '@wp-docs/example-view',
    plugin_dir_url( __FILE__ ) . 'view.js',
    array( '@wordpress/interactivity' ),
    '1.0.0'
);
```

JavaScript examples should copy cleanly without including button text.

```js
import { store } from '@wordpress/interactivity';

store( 'wp-docs/example', {
    actions: {
        toggle() {
            const context = getContext();
            context.open = ! context.open;
        },
    },
} );
```

JSON examples should support long keys and nested values.

```json
{
  "handle": "@wp-docs/example-view",
  "dependencies": [ "@wordpress/interactivity" ],
  "version": "1.0.0"
}
```

Shell examples should keep prompts and flags aligned.

```shell
wp plugin activate wp-docs-example
wp option get siteurl --format=json
```

HTML and CSS examples should render as code, not markup.

```html
<div data-wp-interactive="wp-docs/example">
    <button data-wp-on--click="actions.toggle">Toggle</button>
</div>
```

```css
.wp-docs-example {
    display: grid;
    gap: 1rem;
}
```

## API Reference Table

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| `handle` | `string` | Yes | Unique script module identifier. |
| `src` | `string` | Yes | URL for the script module entrypoint. |
| `deps` | `array` | No | Script module dependencies loaded before execution. |
| `version` | `string|null` | No | Cache-busting version string for the module URL. |

## Callouts

<div class="wp-docs-callout wp-docs-callout--note">
<strong class="wp-docs-callout__title">Note</strong>
Generated pages can use callouts for important context that belongs in the reading flow.
</div>

<div class="wp-docs-callout wp-docs-callout--tip">
<strong class="wp-docs-callout__title">Tip</strong>
Place the shortest working example before advanced configuration details.
</div>

<div class="wp-docs-callout wp-docs-callout--warning">
<strong class="wp-docs-callout__title">Warning</strong>
Call out behavior changes, compatibility constraints, and migration hazards before the reader copies code.
</div>

## Provenance

<aside class="wp-docs-provenance" aria-label="Source provenance">
<strong class="wp-docs-provenance__title">Source notes</strong>
<ul>
<li>Primary source: `wordpress-develop:src/wp-includes/script-modules.php`.</li>
<li>Reviewed against WordPress core revision `example-sha`.</li>
</ul>
</aside>

<p class="wp-docs-source-note">Last generated from the Script Modules pilot source map. Keep detailed run logs in WP Codebox artifacts.</p>
