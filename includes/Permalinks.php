<?php
/**
 * Hash URL generation and permalink filtering.
 *
 * @package MarcGratch\UnlistedPosts
 */

declare( strict_types=1 );

namespace MarcGratch\UnlistedPosts;

use WP_Post;

/**
 * Makes every permalink for an unlisted post point at its hash URL.
 */
final class Permalinks {

	/**
	 * Hook everything up.
	 */
	public function register_hooks(): void {
		add_filter( 'post_link', [ $this, 'filter_post_link' ], 10, 2 );
		add_filter( 'post_type_link', [ $this, 'filter_post_link' ], 10, 2 );
		add_filter( 'page_link', [ $this, 'filter_page_link' ], 10, 2 );
	}

	/**
	 * Build the public hash URL for a post.
	 *
	 * @param int|WP_Post $post Post ID or object.
	 * @return string Empty string when the post has no hash.
	 */
	public static function get_url( int|WP_Post $post ): string {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$hash = PostStatus::get_hash( $post->ID );

		if ( '' === $hash ) {
			return '';
		}

		$url = get_option( 'permalink_structure' )
			? home_url( user_trailingslashit( 'unlisted/' . $hash ) )
			: add_query_arg( Plugin::QUERY_VAR, $hash, home_url( '/' ) );

		/**
		 * Filters the unlisted hash URL for a post.
		 *
		 * @param string  $url  The hash URL.
		 * @param WP_Post $post The post.
		 * @param string  $hash The hash.
		 */
		return (string) apply_filters( 'unlisted_posts_url', $url, $post, $hash );
	}

	/**
	 * Replace permalinks of unlisted posts with the hash URL.
	 *
	 * @param string  $permalink Original permalink.
	 * @param WP_Post $post      Post object.
	 */
	public function filter_post_link( string $permalink, WP_Post $post ): string {
		return $this->maybe_replace( $permalink, $post );
	}

	/**
	 * Same as filter_post_link() but page_link passes an ID.
	 *
	 * @param string $permalink Original permalink.
	 * @param int    $post_id   Post ID.
	 */
	public function filter_page_link( string $permalink, int $post_id ): string {
		$post = get_post( $post_id );

		return $post ? $this->maybe_replace( $permalink, $post ) : $permalink;
	}

	/**
	 * Swap in the hash URL when appropriate.
	 *
	 * @param string  $permalink Original permalink.
	 * @param WP_Post $post      Post object.
	 */
	private function maybe_replace( string $permalink, WP_Post $post ): string {
		if ( Plugin::STATUS !== $post->post_status || ! Plugin::supports( $post->post_type ) ) {
			return $permalink;
		}

		$url = self::get_url( $post );

		return '' !== $url ? $url : $permalink;
	}
}
