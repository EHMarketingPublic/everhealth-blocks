=== EverHealth Blocks ===
Contributors: everhealth
Tags: acf, blocks, gutenberg
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Shared ACF Blocks plugin for use across the EverHealth family of
WordPress sites (DrChrono, CollaborateMD, Updox, iSalus).

== Description ==

This plugin is meant to be installed identically on every EverHealth
brand site. It:

* Registers ACF Blocks using the block.json convention (ACF 6.1+
  "v3" style registration), auto-discovered from the /blocks folder.
* Requires ACF or ACF PRO to be active. Shows an admin notice (not a
  fatal error) if neither is active.
* Detects the ACF FontAwesome add-on and only exposes font_awesome
  fields on blocks when it's actually installed, so blocks degrade
  gracefully on sites without it.
* Checks for updates via GitHub Releases instead of WordPress.org,
  so a single tagged release can be rolled out to every site.

== Setup ==

1. Set `EB_GITHUB_REPO` in everhealth-blocks.php to your GitHub
   "owner/repo" (e.g. `everhealth/everhealth-blocks`).
2. If the repo is private, set `EB_GITHUB_ACCESS_TOKEN` to a
   fine-grained personal access token with read-only "Contents"
   permission on that repo (store it via an environment-based
   constant in wp-config.php in production, not committed to the
   repo).
3. Tag GitHub releases with a version number (`1.1.0` or `v1.1.0`)
   and either attach a built `.zip` as a release asset, or rely on
   GitHub's auto-generated source zip.
4. Bump `EB_VERSION` in everhealth-blocks.php and the plugin header's
   `Version:` line to match each release tag before tagging.

== Adding a new block ==

1. Copy `blocks/example-block` to `blocks/your-block-name`.
2. Edit `block.json` — set `name`, `title`, `description`, and point
   `style`/`editorStyle` at `file:../../dist/blocks/your-block-name/style.css`
   and `.../editor.css` respectively.
3. Edit `fields.php` — set the field group's `location` block value
   to match your new block's name (`acf/your-block-name`), and add
   your fields.
4. Edit `render.php` to output your markup.
5. Add `style.scss` / `editor.scss` for your block's styles (shared
   variables live in `assets/scss/_variables.scss`, available to any
   block via `@use "variables" as v;`).
6. Run `npm run build`.
7. No further registration step needed — the loader picks up any
   folder under /blocks automatically on the next page load.

== SCSS build ==

Styles are authored as SCSS per-block (`blocks/<name>/style.scss` and
`blocks/<name>/editor.scss`) and compiled to compressed CSS in `dist/`,
mirroring the block folder structure. `block.json` references the
compiled `dist/` files, not the SCSS sources.

    npm install         # first time only
    npm run build        # one-off compile, minified, to /dist
    npm run watch         # recompile on change while developing

`dist/` is committed to the repo (not gitignored) since it needs to be
present in the shipped plugin zip — run `npm run build` and commit the
result before tagging a GitHub release. `node_modules/` is gitignored.
