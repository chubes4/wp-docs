---
schema_version: 1
id: developer-script-modules-getting-started
slug: /developer/script-modules/getting-started/
title: Getting Started With Script Modules
section: Developer
topic: Script Modules
audience: plugin, theme, and block developers
status: source-backed-draft
review_state: needs-human-review
source_set: script-modules-pilot
source_commit: 58f02a0278b49f35436ecd14a73a9701f1a115ef
source_paths:
  - src/wp-includes/script-modules.php
  - src/wp-includes/class-wp-script-modules.php
generated_by: technical-docs-agent-compatible pilot slice
generation_note: Prepared from source-backed page specs; not a full WP Codebox runtime corpus run.
generated_at: 2026-06-16T16:38:25Z
nav:
  label: Getting Started
  parent: /developer/script-modules/
  order: 20
search:
  weight: 0.9
  keywords:
    - wp_enqueue_script_module
    - wp_register_script_module
    - module identifier
---

# Getting Started With Script Modules

The fastest way to load a Script Module is to enqueue it with a unique module identifier and a source URL.

```php
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_script_module(
        'my-plugin/view',
        plugins_url( 'assets/view.js', __FILE__ ),
        array(),
        '1.0.0'
    );
} );
```

The identifier is what other modules use as a dependency. Choose an identifier that is stable and namespaced to your project, such as `my-plugin/view`.

## Register First, Enqueue Later

Register a module when multiple parts of your code may depend on it, but only some requests should load it.

```php
add_action( 'wp_enqueue_scripts', function () {
    wp_register_script_module(
        'my-plugin/gallery',
        plugins_url( 'assets/gallery.js', __FILE__ ),
        array(),
        '1.0.0'
    );

    if ( is_singular() ) {
        wp_enqueue_script_module( 'my-plugin/gallery' );
    }
} );
```

If you call `wp_enqueue_script_module()` with a source URL for a module that has not been registered, WordPress registers it before enqueueing it.

## Version Behavior

The version argument controls cache busting on the module URL.

- `false` uses the installed WordPress version.
- `null` omits the version query string.
- A string uses that string as the version query value.

## Footer And Fetch Priority

The optional `$args` array accepts `in_footer` and `fetchpriority`.

```php
wp_enqueue_script_module(
    'my-plugin/view',
    plugins_url( 'assets/view.js', __FILE__ ),
    array(),
    '1.0.0',
    array(
        'in_footer'     => true,
        'fetchpriority' => 'low',
    )
);
```

`fetchpriority` accepts `auto`, `low`, or `high`. Invalid values are ignored and trigger a developer warning.

## Common Mistakes

- Use a non-empty module identifier. Empty identifiers trigger a developer warning.
- Register dependencies before the module graph is printed. Missing dependencies prevent that branch from being sorted and trigger a developer warning.
- Use Script Modules for module code. Classic scripts are still the right API for non-module scripts.
