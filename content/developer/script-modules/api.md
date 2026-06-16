---
schema_version: 1
id: developer-script-modules-api
slug: /developer/script-modules/api/
title: Script Modules API Reference
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
  label: API Reference
  parent: /developer/script-modules/
  order: 50
search:
  weight: 0.95
  keywords:
    - wp_script_modules
    - wp_register_script_module
    - wp_enqueue_script_module
    - wp_dequeue_script_module
    - wp_deregister_script_module
    - wp_set_script_module_translations
---

# Script Modules API Reference

This reference covers the public Script Modules functions in WordPress Core.

## `wp_script_modules()`

Returns the main `WP_Script_Modules` instance, creating it if needed.

```php
$script_modules = wp_script_modules();
```

## `wp_register_script_module( string $id, string $src, array $deps = array(), $version = false, array $args = array() )`

Registers a module by identifier without enqueueing it.

Parameters:

- `$id`: Unique module identifier used in import maps and dependency declarations.
- `$src`: Full URL or WordPress-root-relative path to the module file.
- `$deps`: Dependency list. Entries can be strings or arrays with `id` and optional `import`.
- `$version`: `false` for the installed WordPress version, `null` for no version, or a string version.
- `$args`: Optional `in_footer` boolean and `fetchpriority` value.

## `wp_enqueue_script_module( string $id, string $src = '', array $deps = array(), $version = false, array $args = array() )`

Marks a module to be loaded on the page. If `$src` is provided and the module is not registered, WordPress registers it first.

```php
wp_enqueue_script_module(
    'my-plugin/view',
    plugins_url( 'assets/view.js', __FILE__ ),
    array( '@wordpress/interactivity' ),
    '1.0.0'
);
```

## `wp_dequeue_script_module( string $id )`

Removes a module from the queue for the current page. The module remains registered.

```php
wp_dequeue_script_module( 'my-plugin/view' );
```

## `wp_deregister_script_module( string $id )`

Dequeues and unregisters a module.

```php
wp_deregister_script_module( 'my-plugin/view' );
```

## `wp_set_script_module_translations( string $id, string $domain = 'default', string $path = '' ): bool`

Overrides the text domain and translations path for a registered module. Returns `true` when the module is registered and the translation metadata is stored; returns `false` when the module is not registered.

```php
wp_set_script_module_translations( 'my-plugin/view', 'my-plugin', __DIR__ . '/languages' );
```

Use this only when the module's text domain differs from `default` or translation files live outside the standard location.

## Related Class Methods

Most public functions delegate to `WP_Script_Modules` methods:

- `register()` stores module source, version, normalized dependencies, footer behavior, and fetch priority.
- `enqueue()` adds the module ID to the queue and can register it when a source URL is supplied.
- `dequeue()` removes a module ID from the queue.
- `deregister()` dequeues and removes a registered module.
- `set_translations()` stores text domain and translations path metadata.
- `get_queue()` returns queued module IDs.
- `get_registered()` returns data for one registered module or `null`.
