---
schema_version: 1
id: developer-script-modules-loading-rendering-and-data
slug: /developer/script-modules/loading-rendering-and-data/
title: Loading, Rendering, And Data
section: Developer
topic: Script Modules
audience: plugin, theme, and block developers
status: source-backed-draft
review_state: needs-human-review
source_set: script-modules-pilot
source_commit: 58f02a0278b49f35436ecd14a73a9701f1a115ef
source_paths:
  - src/wp-includes/class-wp-script-modules.php
generated_by: technical-docs-agent-compatible pilot slice
generation_note: Prepared from source-backed page specs; not a full WP Codebox runtime corpus run.
generated_at: 2026-06-16T16:38:25Z
nav:
  label: Loading, Rendering, And Data
  parent: /developer/script-modules/
  order: 40
search:
  weight: 0.8
  keywords:
    - wp-importmap
    - script_module_data
    - translations
    - fetchpriority
---

# Loading, Rendering, And Data

`WP_Script_Modules` attaches rendering callbacks to WordPress hooks. The hook placement depends on the theme type because block themes know more about the rendered block template earlier than classic themes do.

## Import Maps And Module Scripts

WordPress prints import maps with an inline script tag using `type="importmap"` and the ID `wp-importmap`. Enqueued modules are printed as script tags with `type="module"` and an ID based on the module identifier.

The import map includes registered dependencies that need identifier-to-URL mappings. Enqueued modules themselves are printed as module scripts instead of import-map entries.

## Preloads

WordPress prints `rel="modulepreload"` links for static dependencies of enqueued modules. A module that is directly enqueued is not also preloaded.

Fetch priority is calculated from the enqueued dependents. If the resolved priority is not `auto`, WordPress prints a `fetchpriority` attribute. If the printed priority differs from the module's own priority, WordPress also prints `data-wp-fetchpriority`.

## Block Themes And Classic Themes

In block themes, WordPress can print import maps and eligible head modules in `wp_head`. Modules marked for footer output are still printed in the footer.

In classic themes, modules used by rendered blocks may not be known when `wp_head` runs. WordPress therefore prints the import map, preloads, and enqueued modules in the footer for the frontend classic-theme path.

## Module Data

Use the `script_module_data_{$module_id}` filter to attach essential initialization data to a module. If the filter returns a non-empty array, WordPress serializes it into a JSON script tag whose ID is `wp-script-module-data-{$module_id}`.

```php
add_filter( 'script_module_data_my-plugin/view', function ( array $data ): array {
    $data['restUrl'] = esc_url_raw( rest_url() );
    return $data;
} );
```

Client code can read that JSON by ID and parse it before initializing the module.

## Translations

Script Module translations are printed before modules execute. WordPress can auto-detect the default text domain and path from the module source URL. Use `wp_set_script_module_translations()` when the module uses a non-default text domain or a custom translations path.

```php
wp_set_script_module_translations(
    'my-plugin/view',
    'my-plugin',
    plugin_dir_path( __FILE__ ) . 'languages'
);
```
