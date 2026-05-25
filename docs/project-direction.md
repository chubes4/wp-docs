# Project Direction

## Purpose

`wordpress-core-docs` uses `docs-agent` to generate WordPress Core documentation from public source material.

The project should become both the generation workspace and the future publishing foundation for a WordPress.org-compatible docs experience that is comprehensive, massively useful, and pleasant to navigate.

The quality target is not merely "generated documentation exists." The target is documentation on par with the best modern docs sites: clear information architecture, fast navigation, useful examples, trustworthy reference material, and paths that help different readers get from question to answer quickly.

## Product Model

```text
wordpress-core-docs
  = project-specific docs-agent inputs
  + generated WordPress Core documentation corpus
  + page-level provenance and review metadata
  + WP Codebox recipes for reproducible runs
  + eventual WordPress publishing surface
```

## Documentation Lanes

- `user` — task-oriented documentation for site owners, admins, publishers, and builders.
- `developer` — guides for plugin, theme, block, REST API, and integration developers.
- `reference` — source-derived API, symbol, endpoint, and package reference.
- `internals` — Core architecture notes for contributors and advanced maintainers.

## Status Model

Generated content should move through explicit states:

```text
raw generated -> source verified -> reviewed -> publish candidate -> accepted
```

Raw generated output is allowed to be replaced. Reviewed and accepted output should keep provenance, version, and review metadata in content metadata and generated run artifacts.

## Tool Boundaries

This repo should not reimplement the surrounding tools:

- `docs-agent` owns reusable agent bundles, prompt policy, and docs maintenance behavior.
- `wp-codebox` owns sandbox recipes, isolated WordPress execution, previews, and artifact bundle shape.
- `wordpress-core-docs` owns the WordPress Core docs project: source inventories, content lanes, project-specific recipes, review decisions, and publishing direction.

## WordPress.org Compatibility

The project should assume that successful output eventually needs to fit WordPress.org patterns and expectations:

- Public source and public review by default.
- WordPress-native publishing path.
- Clear content provenance and review state.
- Separation between user documentation and developer documentation.
- Original implementation inspired by strong docs sites, not copied from source-available codebases.

## Product Quality Bar

Successful output should be competitive with excellent documentation products, not just acceptable as generated text.

Key expectations:

- Comprehensive coverage of WordPress Core concepts, APIs, behavior, and workflows.
- Navigation that makes the corpus feel intentionally designed rather than mechanically generated.
- Strong examples that show real usage, edge cases, and common mistakes.
- Search and indexes that help readers recover from not knowing the exact term.
- Clear audience paths for site owners, builders, developers, and Core contributors.
- Source-backed reference details with freshness and review status visible enough to build trust.
- A reading experience that feels modern, fast, and worth using daily.

## Previous Corpus

The first generated corpus is preserved on `archive/sarai-v1-generated-corpus` and tag `sarai-v1-generated-corpus`.

Use it as a baseline and seed, not as the canonical structure.
