---
schema_version: 1
id: developer-script-modules-dependencies-and-imports
slug: /developer/script-modules/dependencies-and-imports/
title: Dependencies And Imports
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
generation_note: Prepared from source-backed page specs; not a full WP Codebox runtime corpus run.
generated_at: 2026-06-16T16:38:25Z
nav:
  label: Dependencies And Imports
  parent: /developer/script-modules/
  order: 30
search:
  weight: 0.85
  keywords:
    - static import
    - dynamic import
    - import map
    - modulepreload
---

# Dependencies And Imports

Script Module dependencies are declared by module identifier. A dependency can be a string or an array with an `id` key and an optional `import` key.

```php
wp_register_script_module(
    'my-plugin/search',
    plugins_url( 'assets/search.js', __FILE__ ),
    array(
        '@wordpress/interactivity',
        array( 'id' => 'my-plugin/search-overlay', 'import' => 'dynamic' ),
    ),
    '1.0.0'
);
```

String dependencies are treated as static imports. Array dependencies default to static imports unless `import` is set to `dynamic`.

## Static Dependencies

Static dependencies are part of the initial module graph. WordPress uses them when it sorts dependencies, builds import-map entries, and prints module preload links.

Use static dependencies for modules imported at the top of your JavaScript module.

```js
import { store } from '@wordpress/interactivity';
```

## Dynamic Dependencies

Dynamic dependencies are still known to WordPress, but they represent modules loaded later with dynamic import.

Use dynamic dependencies for code paths that load on demand.

```js
const overlay = await import( 'my-plugin/search-overlay' );
```

## Missing Dependencies

WordPress validates dependency IDs while sorting the module graph. If a dependency has not been registered, WordPress triggers a developer warning and the affected branch is not printed from that dependency path.

That behavior is useful during development because it catches typos and registration-order mistakes before they become silent frontend failures.

## Core Default Modules

WordPress registers default modules from `src/wp-includes/assets/script-modules-packages.php`. File names are converted into identifiers by prefixing `@wordpress/` and removing `.js`, `.min.js`, and a trailing `/index` when present.

Examples from the source inventory include:

- `interactivity/index.js` becomes `@wordpress/interactivity`.
- `interactivity-router/index.js` becomes `@wordpress/interactivity-router`.
- `block-library/navigation/view.js` becomes `@wordpress/block-library/navigation/view`.

Several Core block-library modules depend statically on `@wordpress/interactivity`. The query block view module also records a dynamic dependency on `@wordpress/interactivity-router`.
