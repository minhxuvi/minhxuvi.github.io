# AGENTS.md

Personal digital garden / docs site: Nextra 4 (`nextra-theme-docs`) on Next.js App Router, statically exported to GitHub Pages. Plain JSX, no TypeScript.

## Commands

- `npm run dev` — local dev server
- `npm run build` — static export to `out/`; this is the **only** automated check. There are no lint, test, or typecheck scripts.

## Content & routing

- All pages are `.mdx`/`.md` files under `content/`. Routing is a single catch-all: `app/[[...mdxPath]]/page.jsx`. Don't add route files under `app/`; add content files under `content/`.
- Sidebar/nav order comes from per-directory `_meta.json`. Only `content/_meta.json` exists; other directories auto-sort alphabetically unless you add one.

## Gotchas

- `next.config.mjs` sets `output: 'export'`: no server features or API routes; images are unoptimized. Content references remote image URLs — there is no local image pipeline.
- `content/obsidian/` is a copy of an Obsidian vault (its `.obsidian/` config is committed). Treat it as externally synced notes; don't hand-edit `.obsidian/`.
- Filenames under `content/` contain spaces and Vietnamese diacritics — quote them in shell commands.
- Notes in `content/obsidian/` use Obsidian `[[wikilinks]]`; Nextra does not resolve them, so they render as literal text, not links.

## Deploy

Pushing to `main` triggers `.github/workflows/deploy.yml` (Node 20 → `npm ci` → `npm run build` → uploads `./out` to GitHub Pages). No manual deploy step.
