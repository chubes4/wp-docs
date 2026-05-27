---
title: Abilities API
slug: abilities-api
status: publish
type: page
author: 1
---

# Abilities API

The Abilities API is a WordPress Core API for registering discrete, executable capabilities with a consistent contract. An ability describes what it does, what input it accepts, what output it returns, who may run it, and whether it can be exposed through the REST API.

Use an ability when another system needs to discover and run a bounded WordPress capability without coupling to a private function, admin screen, or one-off REST route. Abilities are especially useful for agent workflows, integrations, site automation, and tooling that needs machine-readable contracts.

## What an Ability Contains

An ability is registered with `wp_register_ability()` and includes:

- a namespaced name such as `my-plugin/export-users`
- a human-readable `label`
- a `description`
- a registered `category`
- an `execute_callback`
- a `permission_callback`
- optional `input_schema` and `output_schema` values
- optional `meta`, including REST exposure and behavioral annotations

The source-backed registration contract lives in `src/wp-includes/abilities-api.php` and `src/wp-includes/abilities-api/class-wp-ability.php`.

## Lifecycle

Register ability categories on `wp_abilities_api_categories_init`. Register abilities on `wp_abilities_api_init`. WordPress validates these hooks and emits `_doing_it_wrong()` notices when registration happens at the wrong time.

## REST Exposure

Abilities are private to PHP unless their metadata sets `show_in_rest` to `true`. REST-exposed abilities are listed under `wp-abilities/v1/abilities`, grouped by `wp-abilities/v1/categories`, and executed through `wp-abilities/v1/abilities/{name}/run`.

## Start Here

- Register your first ability: [Registering Abilities](./registering-abilities/)
- Organize abilities: [Ability Categories](./ability-categories/)
- Execute abilities over REST: [Running Abilities](./running-abilities/)
- Review the public API: [Abilities API Function Reference](../../reference/abilities-api/functions/)

## Source Provenance

Generated from WordPress Core revision 01debdf37c3ef3690dbe06988c71e63408de7c29.

Source files:

- `src/wp-includes/abilities-api.php`
- `src/wp-includes/abilities.php`
- `src/wp-includes/abilities-api/class-wp-ability.php`
- `src/wp-includes/abilities-api/class-wp-abilities-registry.php`
- `src/wp-includes/abilities-api/class-wp-ability-category.php`
- `src/wp-includes/abilities-api/class-wp-ability-categories-registry.php`
- `src/wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-list-controller.php`
- `src/wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-run-controller.php`
- `src/wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-categories-controller.php`