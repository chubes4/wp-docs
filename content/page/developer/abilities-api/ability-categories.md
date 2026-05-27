---
title: Ability Categories
slug: ability-categories
status: publish
type: page
author: 1
---

# Ability Categories

Ability categories group related abilities for discovery. Register categories before registering abilities that reference them.

Use `wp_register_ability_category()` during the `wp_abilities_api_categories_init` action.

```php
add_action( 'wp_abilities_api_categories_init', 'my_plugin_register_ability_categories' );

function my_plugin_register_ability_categories(): void {
    wp_register_ability_category(
        'text-processing',
        array(
            'label'       => __( 'Text Processing', 'my-plugin' ),
            'description' => __( 'Abilities for analyzing and transforming text.', 'my-plugin' ),
        )
    );
}
```

## Category Shape

A category has:

- a slug such as `text-processing`
- a label
- a description
- optional metadata

Category slugs may contain lowercase alphanumeric characters and dashes. The category registry rejects duplicate slugs and invalid slug shapes.

## Core Categories

WordPress Core registers the `site` and `user` categories in `wp_register_core_ability_categories()`.

The `site` category is used for site and environment information abilities. The `user` category is used for current-user profile information.

## Discovery

PHP callers can use:

- `wp_get_ability_categories()` to list all categories
- `wp_get_ability_category(  )` to retrieve one category
- `wp_has_ability_category(  )` to check whether a category is registered

REST clients can list categories through `GET /wp-json/wp-abilities/v1/categories` and retrieve one category through `GET /wp-json/wp-abilities/v1/categories/{slug}`.

## Source Provenance

Generated from `src/wp-includes/abilities-api.php`, `src/wp-includes/abilities.php`, `src/wp-includes/abilities-api/class-wp-ability-category.php`, `src/wp-includes/abilities-api/class-wp-ability-categories-registry.php`, and `src/wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-categories-controller.php` at WordPress Core revision 01debdf37c3ef3690dbe06988c71e63408de7c29.