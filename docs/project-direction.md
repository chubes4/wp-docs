# Project Direction

## Purpose

`wordpress-core-docs` uses `docs-agent` to generate WordPress Core documentation from public source material.

The project should become both the generation workspace and the future publishing foundation for a WordPress.org-compatible docs experience.

## Product Model

```text
wordpress-core-docs
  = docs-agent generation workflow
  + generated WordPress Core documentation corpus
  + provenance and review metadata
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

Raw generated output is allowed to be replaced. Reviewed and accepted output should keep provenance, version, and review metadata.

## WordPress.org Compatibility

The project should assume that successful output eventually needs to fit WordPress.org patterns and expectations:

- Public source and public review by default.
- WordPress-native publishing path.
- Clear content provenance and review state.
- Separation between user documentation and developer documentation.
- Original implementation inspired by strong docs sites, not copied from source-available codebases.

## Previous Corpus

The first generated corpus is preserved on `archive/sarai-v1-generated-corpus` and tag `sarai-v1-generated-corpus`.

Use it as a baseline and seed, not as the canonical structure.
