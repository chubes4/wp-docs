# Navigation and Search Metadata

The theme can render docs navigation from published WordPress page hierarchy. When that content is not available yet, it falls back to the committed pilot fixture at `theme/wp-docs/assets/docs-index.json`.

Search/navigation entries use this shape:

```json
{
  "id": "stable page id or slug",
  "title": "display title",
  "url": "runtime URL or committed content path",
  "section": "top-level docs section label",
  "sectionOrder": 10,
  "order": 10,
  "parent": "optional parent id for nested IA",
  "excerpt": "short result summary",
  "headings": ["visible heading"],
  "body": "plain-text searchable content"
}
```

Ordering is predictable: `sectionOrder`, then `order`, then `title`. The shell integration points are `[data-wp-docs-shell]`, `[data-wp-docs-sidebar]`, `[data-wp-docs-content]`, `[data-wp-docs-toc]`, and `[data-wp-docs-search]`.
