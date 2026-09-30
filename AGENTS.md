# AGENTS.md

Public personal site: Nextra 4 (`nextra-theme-docs`) on Next.js App Router, statically exported to GitHub Pages. Plain JSX, no TypeScript. All content is Vietnamese.

## Commands

- `npm run dev` — local dev server
- `npm run build` — static export to `out/`; this is the **only** automated check. There are no lint, test, or typecheck scripts.

## Source of truth

- This repo only holds what is **safe to publish**. The canonical notes live in a separate private repo, registered here as the `minhxuvi-source` read-only reference in `opencode.jsonc` (see that file for the path on your machine).
- Never copy a private note over verbatim. Reads are fine; edits to the private repo are not.
- Before adding anything, ask whether it could identify a child, a family member, a colleague or client, or reveal health, finances, private journaling, internal work, local filesystem paths, or credentials. When unsure, leave it out and say so.

## Content & routing

- All pages are `.md`/`.mdx` files under `content/`. Routing is a single catch-all: `app/[[...mdxPath]]/page.jsx`. Don't add route files under `app/`; add content files under `content/`.
- Prefer `.md` over `.mdx` for prose. Nextra parses `.mdx` as MDX, where `{` and `<` are expressions and will break the build; `.md` treats them as text.
- Front matter carries `title` and `description`. Use Vietnamese titles.
- Internal links use site-absolute paths, e.g. `[Giáo dục](/giao-duc)`. A folder's `index.md` is served at the folder path.
- Sidebar labels and order come from per-directory **`_meta.js`** (Nextra 4 reads `_meta.{js,jsx,ts,tsx}`; **`_meta.json` is silently ignored** and folders fall back to their slugified name). Every top-level folder that needs a Vietnamese label must have one.
- A single-file section is just `content/<name>.md`; only use `<name>/index.md` when the section has siblings.

## Dependencies

- `nextra` and `nextra-theme-docs` are on **4.6.1** (latest at time of writing). `zod` is pinned to **4.3.6** through `overrides` **on purpose** — do not "fix" it.
  - Nextra 4.6.1's `<Layout>` destructures `children` out of props *before* validating the rest against `LayoutPropsSchema`, where `children` is required. Zod ≥ 4.4 rejects the absent key, so **every page** throws `Invalid input: expected nonoptional, received undefined → at children`. Upstream: nextra#5036, nextra#5034, zod#5917.
  - 4.3.6 is inside Nextra's declared range (`^4.1.12`), so this is a legitimate pin, not a range violation. Remove it once Nextra publishes the fix (it is merged on `main`, not yet on npm).
- `sharp` is pinned to `^0.35.5` via the same `overrides` block; `<0.35.4` carries a libheif advisory.
- `next` is on `^16.3.7`. Versions `>=16.0.0 <16.3.3` carry two critical advisories (unauthenticated RCE), fixed in 16.3.3 — do not downgrade below 16.3.3.
- `react` is left at its installed 19.x; it already satisfies Nextra's peer range (`react >=18`).

## Gotchas

- `next.config.mjs` sets `output: 'export'`: no server features or API routes; images are unoptimized. Content references remote image URLs — there is no local image pipeline.
- Filenames under `content/` contain Vietnamese diacritics — quote them in shell commands.
- Don't reintroduce Obsidian vault copies, `.obsidian/` config, or `[[wikilinks]]`; Nextra does not resolve wikilinks and they render as literal text.

## Deploy

Pushing to `main` triggers `.github/workflows/deploy.yml` (Node 20 → `npm ci` → `npm run build` → uploads `./out` to GitHub Pages). No manual deploy step.

<!-- BEGIN:nextjs-agent-rules -->

# This is NOT the Next.js you know

This version has breaking changes — APIs, conventions, and file structure may all differ from your training data. Read the relevant guide in `node_modules/next/dist/docs/` (resolved from this file's directory; in monorepos the `next` package may not be visible from the repo root) before writing any code. Heed deprecation notices.

This block is written and re-added by `next dev` — verify at `node_modules/next/dist/server/lib/generate-agent-files.js`. Removing it from a diff only re-creates the uncommitted change; committing it with your work keeps the tree clean.

<!-- END:nextjs-agent-rules -->
