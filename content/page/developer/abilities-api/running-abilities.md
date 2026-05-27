---
title: Running Abilities
slug: running-abilities
status: publish
type: page
author: 1
---

# Running Abilities

Abilities can be executed in PHP through their registered `WP_Ability` object. REST execution is available only when the ability metadata includes `show_in_rest => true`.

## REST Routes

The REST controllers register these routes under the `wp-abilities/v1` namespace:

- `GET /wp-json/wp-abilities/v1/abilities`
- `GET /wp-json/wp-abilities/v1/abilities/{name}`
- `GET /wp-json/wp-abilities/v1/categories`
- `GET /wp-json/wp-abilities/v1/categories/{slug}`
- `/wp-json/wp-abilities/v1/abilities/{name}/run`

The run endpoint accepts all HTTP methods at route-registration time because abilities and their annotations are registered later in the WordPress load order. During request handling, `WP_REST_Abilities_V1_Run_Controller::validate_request_method()` chooses the expected method from the ability annotations.

## Method Selection

The run controller maps annotations to methods:

- `readonly => true` expects `GET`
- `destructive => true` and `idempotent => true` expects `DELETE`
- other abilities expect `POST`

If the request method does not match, the controller returns `rest_ability_invalid_method` with status `405`.

## Input Shape

For `GET` and `DELETE`, input is read from the `input` query parameter. For `POST`, input is read from the JSON body as `input`.

```json
{
  "input": {
    "fields": [ "name", "url" ]
  }
}
```

## Permissions and Errors

The run controller checks that the ability exists, is REST-exposed, uses the expected HTTP method, accepts the provided input, and passes the ability permission callback.

Common REST error codes include:

- `rest_ability_not_found`
- `rest_ability_invalid_method`
- `rest_ability_cannot_execute`

## Source Provenance

Generated from `src/wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-list-controller.php`, `src/wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-run-controller.php`, and `src/wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-categories-controller.php` at WordPress Core revision 01debdf37c3ef3690dbe06988c71e63408de7c29.