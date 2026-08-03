=== Unlisted Posts ===
Contributors: marcgratch
Tags: unlisted, private, secret link, post status, share
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds an "Unlisted" post status — content is reachable only via a private, non-crawlable random hash URL.

== Description ==

Unlisted Posts adds a new **Unlisted** post status alongside Publish, Private, and Draft. An unlisted post is not publicly listed anywhere — it can only be viewed by someone who has its secret link, a random 32-character hash URL such as:

`https://example.com/unlisted/3f9a1c8e2b7d4a6f9c0e1b2a3d4f5e6c/`

Think of it as YouTube's "Unlisted" visibility, for WordPress content.

= What "unlisted" means =

* The post's normal permalink returns a 404 for visitors.
* The post is excluded from archives, feeds, search results, and REST/WP_Query listings.
* The post never appears in XML sitemaps.
* The secret link is served with `noindex, nofollow, noarchive` — both as an `X-Robots-Tag` header and via the robots meta tag — so well-behaved crawlers stay away.
* Shortlink, oEmbed discovery, and REST discovery links are stripped from the unlisted view so the real post ID doesn't leak.
* The hash is generated with a cryptographically secure random source (`random_bytes()`), and stays stable once created so shared links don't break.

= Where you can set it =

* **Block editor** — an "Unlisted" toggle in the Summary/Status area of the post sidebar, with a one-click "Copy unlisted link" button.
* **Quick Edit and Bulk Edit** — "Unlisted" appears in the Status dropdown.
* **Classic editor** — "Unlisted" appears in the Publish box status dropdown, and the secret link is shown there.
* **Posts list** — an "Unlisted" filter link, an "Unlisted" post state label, and a "View unlisted link" row action.

= Supported post types =

All public post types (posts, pages, and public custom post types). Use the `unlisted_posts_post_types` filter to change this.

= Filters for developers =

* `unlisted_posts_post_types` — array of post type names that support the status.
* `unlisted_posts_hash_bytes` — number of random bytes for the hash (default 16 → 32 hex characters).
* `unlisted_posts_url` — the generated hash URL.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/unlisted-posts/`, or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Edit any post or page and switch its status to "Unlisted".
4. Copy the secret link and share it with whoever should see the content.

== Frequently Asked Questions ==

= Is this a substitute for password protection or real access control? =

No. Anyone who obtains the link can view the content. The link is unguessable (128 bits of randomness by default) and hidden from search engines, but it is security by obscurity by design — the same model as unlisted YouTube videos or secret Google Docs links. For confidential content, use private posts or password protection.

= Does the link change if I toggle the status back and forth? =

No. The hash is generated once and kept, so a shared link keeps working if you re-unlist the post. Delete the `_unlisted_posts_hash` post meta to rotate the link.

= What happens if I deactivate the plugin? =

Unlisted posts keep their status, which WordPress treats as non-public, so nothing leaks. The secret links stop resolving until the plugin is reactivated.

= Will search engines really not index it? =

The page is served with `noindex, nofollow, noarchive` headers and meta tags, is excluded from sitemaps, and is linked from nowhere. Compliant crawlers will not index it. Nothing can stop a human from sharing the link, though.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
