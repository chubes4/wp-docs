# User Docs Generation Plan

## Goal

Build a non-technical WordPress documentation lane for people using WordPress in the editor, admin, and site-building UI.

This lane should help site owners, admins, editors, publishers, and builders complete real tasks without needing to understand source code, APIs, CLI commands, package internals, or repository structure.

Source code, tests, existing docs, dev notes, and UI behavior are evidence for the generator. They are not the framing for the published user docs.

## Audience

Primary readers:

- Site owners who manage their own WordPress site.
- Editors and publishers creating pages and posts.
- Site builders using the Site Editor, patterns, templates, styles, navigation, and blocks.
- Admins configuring users, media, menus, comments, settings, plugins, and themes.

These readers should not need to know PHP, JavaScript, Gutenberg packages, REST APIs, hooks, WP-CLI, Trac, GitHub, or build tooling.

## Agent Bundle

- Bundle repo: `https://github.com/Automattic/docs-agent`
- Bundle: `bundles/user-docs-agent`
- Agent slug: `user-docs-agent`

The `user-docs-agent` owns reusable audience behavior for product-user documentation. `wp-docs` owns WordPress-specific source inventories, page specs, review policy, content paths, and publishing decisions.

## Content Lane

User docs should write to a separate namespace from technical docs, such as:

```text
content/user/
├── getting-started/
├── editor/
├── site-editor/
├── blocks/
├── media/
├── navigation/
├── patterns/
├── styles/
├── pages-and-posts/
├── users-and-permissions/
├── comments/
├── settings/
├── themes/
└── plugins/
```

Developer/reference/internals docs should stay in their own lanes, such as `content/developer/`, `content/reference/`, and `content/internals/`.

## Evidence Model

User docs are source-backed, but not source-framed.

Evidence inputs can include:

- WordPress Core and Gutenberg source files.
- Tests, fixtures, storybook examples, and editor behavior checks.
- Existing HelpHub/user-docs pages.
- Dev notes and release posts when they explain user-visible behavior.
- Screenshots, UI traces, and reproducible WordPress runtime observations.

The generated page should translate that evidence into user-facing guidance.

For example, source evidence may show how List View moves blocks. The published user doc should say how to open List View, select blocks, drag blocks, and recover from common mistakes. It should not mention internal block serialization, package names, selectors, stores, or source paths.

## Page Specs

Every user-doc page should start from a page spec, not a direct source-file prompt.

User page specs should define:

- Stable page ID.
- Output path under `content/user/`.
- Reader intent.
- UI task or concept being explained.
- Required source or behavior evidence.
- Required screenshots or UI states when relevant.
- Related user pages.
- Review state.
- Acceptance criteria.

Example shape:

```json
{
  "id": "user.editor.list-view",
  "output_path": "content/user/editor/list-view.md",
  "audience": "user",
  "lane": "user",
  "reader_intent": "Use List View to understand, select, reorder, and manage blocks on a page.",
  "evidence": [
    {
      "type": "source",
      "source_ref": "gutenberg",
      "source_path": "packages/edit-post/src/components/list-view-sidebar/index.js"
    },
    {
      "type": "behavior",
      "source_ref": "runtime-ui-trace",
      "scenario": "open-list-view-reorder-blocks"
    }
  ],
  "must_include": [
    "How to open List View",
    "How to select a block",
    "How to drag a block to reorder it",
    "How to rename or find nested blocks when applicable"
  ],
  "must_avoid": [
    "source file names in published prose",
    "developer API terminology",
    "CLI instructions"
  ],
  "review_state": "draft"
}
```

## Coverage Model

User-doc coverage should use Data Machine tracked items just like technical coverage.

Useful tracked item types for the user lane include:

- `user-task` for tasks such as creating a page, editing navigation, or adding an image.
- `ui-surface` for editor panels, admin screens, and Site Editor areas.
- `block-user-behavior` for user-visible block behavior.
- `setting-user-behavior` for settings and admin options.
- `workflow` for multi-step flows such as publishing a post or customizing a template.
- `known-confusion` for recurring user-facing pitfalls that need docs.

Each tracked item should record source or behavior evidence internally, then map to one or more user-doc page specs.

Coverage states should answer:

- Has this user-visible feature or task been discovered?
- Is it intentionally out of scope?
- Does it have a page spec?
- Has a draft been generated?
- Has the page been reviewed by someone who understands the product behavior?
- Is the page stale after a source or UI change?

## Block Editor And Site Editor Scope

The first major user-doc vertical should cover the Block Editor and Site Editor because they are high-value, high-confusion areas.

Initial areas:

- Editor basics: blocks, toolbar, sidebar, settings, List View, preview, publish.
- Pages and posts: create, edit, schedule, revise, organize.
- Blocks: add, move, transform, group, reusable/synced behavior, patterns.
- Media: images, galleries, featured images, captions, alt text.
- Site Editor: templates, template parts, styles, navigation, patterns.
- Troubleshooting: missing panels, unexpected layout changes, reusable content confusion, publish/update states.

The source-backed generation process should read implementation and behavior evidence, but the published pages should describe the UI in plain language.

## Output Requirements

User docs should:

- Use plain language and short task-oriented sections.
- Prefer UI labels, visible actions, and expected outcomes.
- Include screenshots or screenshot placeholders when the task depends on visual state.
- Link related user tasks together.
- Mention version/freshness only when helpful to users.
- Keep internal evidence in metadata or source maps, not in the main prose.

User docs should not:

- Tell readers to inspect source files.
- Mention implementation classes, packages, hooks, selectors, REST endpoints, or build tooling unless the page is intentionally technical.
- Use contributor/developer vocabulary for ordinary editor actions.
- Collapse product-user docs and developer docs into the same path.

## Review Bar

A user-doc page is reviewable when:

- It is accurate against current WordPress behavior.
- It explains a real user task or concept.
- It uses user-facing UI language.
- It does not leak internal implementation framing into published prose.
- It has provenance metadata linking to evidence.
- It has a clear owner state: draft, source-verified, reviewed, publish candidate, or accepted.

## Relationship To Technical Docs

The same source evidence can feed both lanes, but the output should diverge by audience.

Example:

- Technical docs explain the Script Modules API, function signatures, dependencies, import maps, and enqueue lifecycle.
- User docs explain what happens when a block or feature loads interactive behavior in the editor or front end only when that matters to a site builder.

The user lane should optimize for completing tasks in WordPress. The technical lane should optimize for understanding and extending WordPress.
