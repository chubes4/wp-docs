# Recipes

This directory holds project-specific WP Codebox recipes for reproducible generation and review runs.

WP Codebox owns the recipe schema, sandbox runtime, previews, and artifact bundle shape. This repo only stores recipes that describe how WordPress docs work should run for this project.

Expected contents:

- Recipes that mount WordPress core, canonical plugins, or related public source checkouts.
- Recipes that run docs-agent generation against a bounded source inventory.
- Recipes that import generated docs into a WordPress preview site when that path is ready.
- Notes that connect generated artifacts back to content review decisions.

Current recipes:

- `script-modules-pilot.md` — first bounded developer-docs generation pass for the WordPress Core Script Modules API.
