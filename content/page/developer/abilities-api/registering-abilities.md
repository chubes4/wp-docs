---
title: Registering Abilities
slug: registering-abilities
status: publish
type: page
author: 1
---

# Registering Abilities

Register an ability with `wp_register_ability()` during the `wp_abilities_api_init` action. The ability name must include a namespace and an ability slug separated by one slash, for example `my-plugin/analyze-text`.

```php
add_action( 'wp_abilities_api_init', 'my_plugin_register_abilities' );

function my_plugin_register_abilities(): void {
    wp_register_ability(
        'my-plugin/analyze-text',
        array(
            'label'               => __( 'Analyze Text', 'my-plugin' ),
            'description'         => __( 'Analyze text and return a sentiment label.', 'my-plugin' ),
            'category'            => 'text-processing',
            'input_schema'        => array(
                'type'        => 'string',
                'description' => __( 'The text to analyze.', 'my-plugin' ),
                'minLength'   => 10,
                'required'    => true,
            ),
            'output_schema'       => array(
                'type'        => 'string',
                'enum'        => array( 'positive', 'negative', 'neutral' ),
                'description' => __( 'The sentiment result.', 'my-plugin' ),
                'required'    => true,
            ),
            'execute_callback'    => 'my_plugin_analyze_text',
            'permission_callback' => 'my_plugin_can_analyze_text',
            'meta'                => array(
                'annotations'  => array(
                    'readonly' => true,
                ),
                'show_in_rest' => true,
            ),
        )
    );
}
```

## Required Arguments

`wp_register_ability()` requires a valid ability name and an argument array with a label, description, category, execute callback, and permission callback. The registry validates the category before creating the ability, so register categories first.

Ability names are validated by `WP_Abilities_Registry::register()`. They must match a lowercase namespace and slug shape such as `my-plugin/my-ability`.

## Schemas

Use `input_schema` when the ability accepts input. Use `output_schema` when the ability returns a structured value. WordPress uses JSON Schema-style arrays compatible with the REST API schema model.

Schemas serve two jobs:

- validate input before execution
- describe the contract for REST clients, documentation, and tools

## Callbacks

The `execute_callback` receives the normalized input and returns a result or `WP_Error`. The `permission_callback` receives the same input and returns `true`, `false`, or `WP_Error`.

Return `WP_Error` for expected failures so REST and tool callers receive structured error responses.

## Source Provenance

Generated from `src/wp-includes/abilities-api.php`, `src/wp-includes/abilities-api/class-wp-ability.php`, and `src/wp-includes/abilities-api/class-wp-abilities-registry.php` at WordPress Core revision 01debdf37c3ef3690dbe06988c71e63408de7c29.