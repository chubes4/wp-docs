# Agent Configuration

This directory holds WP Docs project-specific agent inputs.

Reusable documentation agent behavior belongs in `Automattic/docs-agent`. This repo should only define WordPress documentation scope, source inventories, content lanes, review policy, and generation targets.

## Bundle Usage

- Technical/developer docs use the `technical-docs-agent` bundle from `Automattic/docs-agent`.
- Product/user docs use the `user-docs-agent` bundle from `Automattic/docs-agent`.
- Separate audiences should run as separate generation passes and produce separate reviewable changes.

## WP Docs Responsibilities

- Define which WordPress sources are in scope.
- Define which content paths each run may write.
- Keep provenance and review status with the generated corpus.
- Preserve a project-specific record of accepted generation targets and rejected drafts.

## Current Pilot

The first narrow pilot target is the WordPress Core Script Modules API. The source inventory is `sources/wordpress-core.json`, and the pilot runbook is `recipes/script-modules-pilot.md`.
