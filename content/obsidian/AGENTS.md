# AGENTS.md

Hand-synced copy of an Obsidian vault: personal/family/school notes, mostly Vietnamese, published by the parent Nextra site.

## Conventions

- Notes use Obsidian `[[wikilinks]]`; dangling links to not-yet-created notes are intentional. Don't convert them to markdown links or delete them. When creating a note to satisfy a link, use the exact link text as the filename.
- No YAML front matter — notes open directly with prose/bullets. Empty files are valid placeholder stubs.
- Preserve existing style: Vietnamese prose, `- [ ]` task lists, tables, and ~~strikethrough~~ for cancelled items.
- Filenames contain spaces and Vietnamese diacritics — always quote them in shell commands.

## Sync & git

- This folder mirrors an external vault; changes can arrive from either side. Dirty or untracked files here are normal — never clean, revert, or commit them unprompted.
- Never edit anything under `.obsidian/`. Expect `workspace.json` (UI state) to churn in every diff; exclude it unless asked.
