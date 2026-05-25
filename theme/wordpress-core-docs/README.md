# WordPress Core Docs Theme

Prototype WordPress block theme for the generated WordPress Core docs experience.

This theme lives in the monorepo so content generation, content structure, and presentation can evolve together. It is intentionally WordPress-native. Tailwind, shadcn, and other modern docs sites are structural references for navigation, information architecture, page density, examples, and polish; they are not dependency requirements.

The theme is an extraction surface. Build the experience here, then later cherry-pick or port the useful parts into the final WordPress.org implementation path.

## Initial Goals

- Establish a WordPress-native home for the docs UI.
- Support generated docs, reference pages, and guide-style pages.
- Make navigation and search first-class design concerns.
- Keep the implementation easy to port into WordPress.org theme infrastructure later.
