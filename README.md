# Unlisted Posts

Adds an **Unlisted** post status to WordPress — content is reachable only via a private, non-crawlable random hash URL, e.g.

```
https://example.com/unlisted/3f9a1c8e2b7d4a6f9c0e1b2a3d4f5e6c/
```

Like YouTube's "Unlisted" visibility, for WordPress posts, pages, and public custom post types.

## Features

- **New post status** integrated everywhere statuses live: the block editor sidebar (toggle + "Copy unlisted link" button), Quick Edit, Bulk Edit, the classic editor Publish box, list-table status filters, post state labels, and a "View unlisted link" row action.
- **Non-crawlable by design**: `noindex, nofollow, noarchive` via both `X-Robots-Tag` header and robots meta; excluded from sitemaps, feeds, archives, search, and REST listings; shortlink/oEmbed/REST discovery links stripped so the post ID doesn't leak; canonical redirects to the real slug are suppressed.
- **404 everywhere else**: the post's normal permalink is not viewable by visitors; only the hash URL renders it.
- **Stable, secure hashes**: 128 bits from `random_bytes()` by default; generated once and kept so shared links survive status toggles.
- **Modern stack**: PHP 8.1+, `declare(strict_types=1)`, PSR-4 namespaced classes, WordPress 6.6+ APIs, block editor UI built with `@wordpress/scripts`.

## Requirements

- WordPress 6.6+
- PHP 8.1+

## Installation (from source)

Build artifacts are **not** committed to this repo — the plugin is built on release/deploy.

```bash
git clone https://github.com/mgratch/unlisted-posts.git
cd unlisted-posts
npm install
npm run build
```

Then copy the folder into `wp-content/plugins/` (or symlink it) and activate. No runtime Composer dependencies are required — `composer install` is only needed for dev tooling (PHPCS/WPCS):

```bash
composer install
composer lint
```

## Releases

Pushing a tag like `v1.2.3` triggers the GitHub Actions release workflow, which builds the JS with `@wordpress/scripts` and packages the plugin with [`wp dist-archive`](https://developer.wordpress.org/cli/commands/dist-archive/). Exclusions are controlled by [.distignore](.distignore) — only runtime files ship (`build/` included; `src/`, configs, and dev tooling excluded). The resulting `unlisted-posts.zip` is attached to a GitHub Release; install it via **Plugins → Add New → Upload Plugin**.

There are currently no production Composer dependencies (`composer.json` requires PHP only), so no `vendor/` directory ships; the plugin autoloads its own classes. If runtime dependencies are ever added, run `composer install --no-dev` during the release build and remove `vendor` from `.distignore`.

To build a zip locally:

```bash
npm run build
wp dist-archive . ./unlisted-posts.zip --plugin-dirname=unlisted-posts
```

## Development

```bash
npm install
npm start          # wp-scripts watch mode
npm run lint:js
composer install
composer lint      # PHPCS with WordPress Coding Standards
```

### Project layout

```
unlisted-posts.php     Bootstrap: constants, autoloader, activation hooks
includes/              PSR-4 PHP classes (MarcGratch\UnlistedPosts\)
  Plugin.php           Service wiring, supported post types
  PostStatus.php       register_post_status(), hash generation/lookup
  Permalinks.php       Hash URL building, permalink filters
  Frontend.php         Rewrite rule, request resolution, robots hardening
  RestApi.php          unlisted_url REST field for the editor
  Admin.php            Editor/Quick Edit/Classic assets & UI
src/                   JS sources (built with @wordpress/scripts)
  editor.js            Block editor status panel
  quick-edit.js        Quick/Bulk Edit status option
  classic-editor.js    Classic editor status option
build/                 Generated output (gitignored)
```

### How the hash URL works

1. When a post first transitions to `unlisted`, a hash is stored in `_unlisted_posts_hash` post meta.
2. A rewrite rule maps `/unlisted/{hash}/` to the `unlisted_hash` query var (`?unlisted_hash={hash}` works without pretty permalinks).
3. On the front end, the hash is resolved to the post and the main query is pointed at it; the post's status is swapped to `publish` **in memory only** for that request (the same technique as the Public Post Preview plugin), so themes render it normally.
4. All permalink functions (`get_permalink()` etc.) return the hash URL for unlisted posts, so "View" links in the admin always point to the working link.

### Filters

| Filter | Purpose | Default |
| --- | --- | --- |
| `unlisted_posts_post_types` | Post types supporting the status | all public types except attachments |
| `unlisted_posts_hash_bytes` | Random bytes per hash (hex length = 2×) | `16` |
| `unlisted_posts_url` | The generated hash URL | `home_url( "unlisted/{hash}/" )` |

## Security model

The link is unguessable (128 bits of entropy) and hidden from compliant crawlers, but **anyone with the link can view the content** — the same model as unlisted YouTube videos. For confidential content use private posts or password protection instead.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
