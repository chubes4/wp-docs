# WP Docs

This repository is an active experiment for generating WordPress core and canonical plugin documentation with `docs-agent`.

The goal is to produce comprehensive, massively useful WordPress documentation that is accurate, source-verified, and pleasant to navigate. The output should meet the quality bar set by the best modern documentation sites while fitting the WordPress.org documentation ecosystem.

## Current Direction

This repo is being reset around the generation workflow, content policy, provenance model, and future publishing surface. Earlier generated material has been preserved for reference, but `main` is now the working surface for the next iteration.

## Repository Layout

- `sources/` — project-specific source inventories and generation targets.
- `agent/` — WP Docs-specific agent scope and bundle selection notes.
- `content/` — generated and reviewed documentation outputs plus page-level metadata.
- `recipes/` — project-specific WP Codebox recipes for reproducible generation and review runs.
- `provenance/` — lightweight provenance conventions that belong with this corpus.
- `theme/wp-docs/` — prototype WordPress block theme for the docs experience.
- `docs/` — project architecture, decisions, and operating notes.

## Archived Corpus

The previous generated corpus is preserved at:

- Branch: `archive/sarai-v1-generated-corpus`
- Tag: `sarai-v1-generated-corpus`

That archive is useful as seed material and historical context, but it is not the target information architecture for this project.

## Principles

- Generate from public WordPress source and public documentation sources.
- Keep generated drafts reproducible and disposable.
- Preserve provenance in generated page metadata and WP Codebox artifacts rather than inventing another artifact format here.
- Separate user-facing docs, developer docs, reference material, and internals.
- Treat navigation, search, examples, and information architecture as first-class product work.
- Compare the result against leading documentation sites, not against the minimum viable generated output.
- Optimize for eventual WordPress.org compatibility, not a standalone side project.
- Use strong documentation sites as structural references for information architecture and navigation, not as code or dependency templates.

## Boundaries

- `docs-agent` owns reusable agent behavior, prompts, and bundle mechanics.
- `wp-codebox` owns isolated WordPress execution, recipes, previews, and artifact bundles.
- This repo owns WordPress core and canonical plugin docs inputs, generated content, project-specific recipes, and publishing direction.
