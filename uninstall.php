<?php
/**
 * Uninstall routine: remove all plugin data.
 *
 * Posts left in the "unlisted" status are intentionally untouched;
 * WordPress will treat the unknown status as non-public, and editors can
 * change the status from the posts list.
 *
 * @package MarcGratch\UnlistedPosts
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_post_meta_by_key( '_unlisted_posts_hash' );
