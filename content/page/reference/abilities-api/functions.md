---
title: Abilities API Function Reference
slug: abilities-api-functions
status: publish
type: page
author: 1
---

# Abilities API Function Reference

This page summarizes the public procedural API for registering, discovering, and unregistering abilities and categories.

## Ability Functions

### `wp_register_ability( string , array  ): ?WP_Ability`

Registers an ability during `wp_abilities_api_init`. Returns the registered `WP_Ability` instance or `null` on failure.

### `wp_unregister_ability( string  ): ?WP_Ability`

Unregisters a previously registered ability. Returns the removed ability instance or `null` when the ability is not registered.

### `wp_has_ability( string  ): bool`

Checks whether an ability is registered.

### `wp_get_ability( string  ): ?WP_Ability`

Retrieves one registered ability instance.

### `wp_get_abilities(): array`

Retrieves all registered ability instances.

## Category Functions

### `wp_register_ability_category( string , array  ): ?WP_Ability_Category`

Registers an ability category during `wp_abilities_api_categories_init`. Returns the registered category instance or `null` on failure.

### `wp_unregister_ability_category( string  ): ?WP_Ability_Category`

Unregisters a category and returns the removed category instance or `null` when the category is not registered.

### `wp_has_ability_category( string  ): bool`

Checks whether a category is registered.

### `wp_get_ability_category( string  ): ?WP_Ability_Category`

Retrieves one registered category instance.

### `wp_get_ability_categories(): array`

Retrieves all registered category instances.

## Core Registration Helpers

### `wp_register_core_ability_categories(): void`

Registers the default Core categories, including `site` and `user`.

### `wp_register_core_abilities(): void`

Registers Core abilities such as site, user, and environment information abilities.

## Source Provenance

Generated from `src/wp-includes/abilities-api.php` and `src/wp-includes/abilities.php` at WordPress Core revision 01debdf37c3ef3690dbe06988c71e63408de7c29.