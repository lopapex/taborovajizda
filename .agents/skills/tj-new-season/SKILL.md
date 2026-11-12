---
name: tj-new-season
description: Prepare a new Táborová jízda camp season in Git by updating validated season data, checking yearly references, and coordinating the visual brief. Use when starting or revising the next annual camp theme.
---

# Prepare a new camp season

Read `AI-README.md` and treat Git as the source of truth for active season data and visuals.

1. Inspect `theme/config/season.json`, including `contactEmail`, `theme/theme.json`, current season assets, the branch, and Git status.
2. Ask only for missing facts that cannot be inferred: year, slug, public title, confirmed dates, price, registration state, optional manually maintained remaining-place count, external database season key, and visual brief. Never invent them. Use `null` when the remaining-place count should not be displayed.
3. Use `scripts/new-season.ps1` without `-Apply` to preview changes. Show the proposed branch, configuration, fixed `hero.jpg` path, and references to the previous year.
4. Before `-Apply`, require confirmation. The script refuses a dirty tree and creates `season/<year>-<slug>` when run from `main`.
5. Replace `theme/resources/assets/season/hero.jpg`, rename the `.season-<year>` accent class in `season.css`, update active design tokens in `theme/theme.json`, and keep only annual effects in `theme/resources/css/season.css`. Asset paths do not belong in `season.json`. Keep layout in prefixed Tailwind utilities; do not create another copied annual stylesheet or move component layout back into season CSS.
6. For visual design, read the repository skill `$design-taste-frontend`; preserve URLs, navigation, form fields, logo, and content voice unless explicitly authorized.
7. Run `npm run check` from `theme/`, PHP syntax checks, and the LocalWP checklist. Do not commit, push, release, or deploy without separate authorization.

During visual work keep `npm run dev` running so CSS, TypeScript, and season assets rebuild automatically. PHP and template changes need only a browser refresh.

Keep old seasons in Git history, not as active runtime variants. Do not delete old assets until their references have been audited and the user approves removal.

