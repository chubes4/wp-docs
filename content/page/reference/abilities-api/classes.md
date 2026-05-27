---
title: Abilities API Class Reference
slug: abilities-api-classes
status: publish
type: page
author: 1
---

# Abilities API Class Reference

This page summarizes the Core classes that back the Abilities API.

## `WP_Ability`

`WP_Ability` encapsulates a registered ability. It stores the ability name, label, description, category, schemas, callbacks, and metadata.

Important properties and concepts:

- `name`: namespaced ability identifier
- `label`: human-readable label
- `description`: detailed description
- `category`: registered category slug
- `input_schema`: optional JSON Schema input contract
- `output_schema`: optional JSON Schema output contract
- `execute_callback`: callback used to run the ability
- `permission_callback`: callback used to authorize execution
- `meta`: metadata such as `show_in_rest` and `annotations`

Default annotations include `readonly`, `destructive`, and `idempotent`. They are hints for tooling and REST method selection, not a replacement for permissions or careful implementation.

## `WP_Abilities_Registry`

`WP_Abilities_Registry` stores registered abilities and enforces registration rules. It is a singleton created after `init`. Use the public procedural functions instead of calling the registry directly.

The registry validates ability names, duplicate registration, category existence, and custom ability classes.

## `WP_Ability_Category`

`WP_Ability_Category` stores a category slug, label, description, and metadata. It should be created through `wp_register_ability_category()`.

## `WP_Ability_Categories_Registry`

`WP_Ability_Categories_Registry` stores registered categories. It validates category slugs, prevents duplicates, and fires `wp_abilities_api_categories_init` when the registry is prepared.

## REST Controller Classes

- `WP_REST_Abilities_V1_List_Controller` lists REST-exposed abilities and returns single ability records.
- `WP_REST_Abilities_V1_Run_Controller` validates requests and executes REST-exposed abilities.
- `WP_REST_Abilities_V1_Categories_Controller` lists and returns ability categories.

## Source Provenance

Generated from the Abilities API classes and REST controllers at WordPress Core revision 01debdf37c3ef3690dbe06988c71e63408de7c29.