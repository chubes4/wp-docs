---
schema_version: 1
id: developer-script-modules-overview
slug: /developer/script-modules/
title: Script Modules
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
  - src/wp-includes/assets/script-modules-packages.php
generated_by: technical-docs-agent-compatible pilot slice
generation_note: Prepared from the committed Script Modules pilot recipe and public WordPress Core source; not a full WP Codebox runtime corpus run.
generated_at: 2026-06-16T16:38:25Z
nav:
  label: Script Modules
  order: 10
search:
  weight: 1.0
  keywords:
    - script modules
    - ES modules
    - import map
    - modulepreload
---

# Script Modules

Script Modules are WordPress's native API for loading JavaScript ES modules. Use them when your code is authored as modules, imports other modules by identifier, or needs WordPress to build the page's import map and module preload tags.

Classic scripts and Script Modules are different loading systems. Classic scripts are registered with script handles and printed as normal script tags. Script Modules are registered with module identifiers and printed as `type="module"` scripts, import maps, and `rel="modulepreload"` links.

## When To Use Script Modules

Use Script Modules for JavaScript that imports or exports ES modules, depends on a WordPress module such as `@wordpress/interactivity`, or should participate in the browser module graph.

Use classic scripts for JavaScript that expects the traditional WordPress script dependency system, global variables, or non-module script loading.

## The Core Workflow

Register a module with `wp_register_script_module()` when you want WordPress to know about the module without loading it immediately. Enqueue a module with `wp_enqueue_script_module()` when the page needs that module. WordPress then resolves dependencies, prints import-map entries for dependencies, preloads static dependencies, and prints enqueued modules as module scripts.

```php
add_action( 'wp_enqueue_scripts', function () {
    wp_register_script_module(
        'my-plugin/view',
        plugins_url( 'assets/view.js', __FILE__ ),
        array(
            array( 'id' => '@wordpress/interactivity', 'import' => 'static' ),
        ),
        '1.0.0'
    );

    wp_enqueue_script_module( 'my-plugin/view' );
} );
```

## What WordPress Prints

For enqueued modules, WordPress can print:

- A `type="importmap"` script with dependency identifiers and URLs.
- `rel="modulepreload"` links for static dependencies that are not directly enqueued.
- `type="module"` script tags for enqueued modules.
- Inline translation and data scripts for modules that need them.

## Pilot Pages

- [Getting started](getting-started.md)
- [Dependencies and imports](dependencies-and-imports.md)
- [Loading, rendering, and data](loading-rendering-and-data.md)
- [API reference](api.md)

## Review Status

This pilot section is a source-backed draft. It is intentionally small so reviewers can check the information architecture, navigation metadata, search metadata, source mapping, and rendering shape before the corpus expands.

Source evidence is recorded in `content/_meta/source-map.json`.
