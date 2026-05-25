# WordPress Core Docs

This repository is an active experiment for generating WordPress Core documentation with `docs-agent`.

The goal is to produce accurate, source-verified documentation from WordPress Core and related public source material, then shape it into documentation that can eventually fit the WordPress.org documentation ecosystem.

## Current Direction

This repo is being reset around the generation workflow, content policy, provenance model, and future publishing surface. Earlier generated material has been preserved for reference, but `main` is now the working surface for the next iteration.

## Repository Layout

- `agent/` — docs-agent configuration, prompts, policies, and generation notes.
- `sources/` — public source manifests and inventories used by generation.
- `content/` — generated and reviewed documentation outputs.
- `provenance/` — source maps, generation metadata, coverage reports, and review state.
- `site/` — WordPress site, import, and theme work when the publishing surface is ready.
- `tools/` — scripts and utilities that support generation, validation, and import.
- `docs/` — project architecture, decisions, and operating notes.

## Archived Corpus

The previous generated corpus is preserved at:

- Branch: `archive/sarai-v1-generated-corpus`
- Tag: `sarai-v1-generated-corpus`

That archive is useful as seed material and historical context, but it is not the target information architecture for this project.

## Principles

- Generate from public WordPress source and public documentation sources.
- Keep generated drafts reproducible and disposable.
- Preserve provenance for every reviewed or published page.
- Separate user-facing docs, developer docs, reference material, and internals.
- Optimize for eventual WordPress.org compatibility, not a standalone side project.
