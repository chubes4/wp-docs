# Script Modules Pilot Recipe

This recipe defines the first bounded WP Docs generation pass. It is intentionally narrow so the output can be reviewed for quality, provenance, and information architecture before expanding the corpus.

## Source Target

- Source manifest: `sources/wordpress-core.json`
- Target set: `script-modules-pilot`
- Source checkout handle: `wordpress-develop`
- Source branch: `trunk`

## Inputs

- `src/wp-includes/script-modules.php`
- `src/wp-includes/class-wp-script-modules.php`
- `src/wp-includes/assets/script-modules-packages.php`

## Outputs

- `content/developer/script-modules/README.md`
- `content/developer/script-modules/api.md`
- `content/_meta/source-map.json`

## Agent Lane

- Bundle repo: `https://github.com/Automattic/docs-agent.git`
- Bundle: `bundles/technical-docs-agent`
- Agent slug: `technical-docs-agent`

## Generation Requirements

- Write for plugin, theme, and block developers.
- Explain the practical difference between Script Modules and classic WordPress scripts.
- Derive public API details from source, not from memory.
- Include concise examples that match current Core behavior.
- Record source commit, source files, generated timestamp, and review state in metadata.
- Keep generated drafts disposable until they pass source verification and review.

## Review Checklist

- Every public function or behavior claim maps to one of the source files in the manifest.
- Examples use current function names and argument shapes.
- The page helps a developer decide when Script Modules are the right tool.
- The page separates conceptual guidance from reference details.
- Metadata identifies the source revision and review status.
