<?php
/**
 * Registers the "Unlisted" post status and hash lifecycle.
 *
 * @package MarcGratch\UnlistedPosts
 */

declare( strict_types=1 );

namespace MarcGratch\UnlistedPosts;

use WP_Post;

/**
 * The custom post status.
 */
final class PostStatus {

	/**
	 * Hook everything up.
	 */
	public function register_hooks(): void {
		add_action( 'init', [ self::class, 'register_status' ] );
		add_action( 'transition_post_status', [ $this, 'maybe_generate_hash' ], 10, 3 );
		add_filter( 'display_post_states', [ $this, 'display_post_state' ], 10, 2 );
	}

	/**
	 * Register the status with WordPress.
	 */
	public static function register_status(): void {
		register_post_status(
			Plugin::STATUS,
			[
				'label'                     => _x( 'Unlisted', 'post status', 'unlisted-posts' ),
				'public'                    => false,
				'internal'                  => false,
				'protected'                 => true,
				'private'                   => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'date_floating'             => false,
				/* translators: %s: Number of unlisted posts. */
				'label_count'               => _n_noop(
					'Unlisted <span class="count">(%s)</span>',
					'Unlisted <span class="count">(%s)</span>',
					'unlisted-posts'
				),
			]
		);
	}

	/**
	 * Generate a hash the first time a post becomes unlisted.
	 *
	 * The hash is intentionally kept once generated so the link stays
	 * stable if the status is toggled. Use unlisted_posts_regenerate_hash()
	 * or delete the meta to rotate it.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function maybe_generate_hash( string $new_status, string $old_status, WP_Post $post ): void {
		if ( Plugin::STATUS !== $new_status || ! Plugin::supports( $post->post_type ) ) {
			return;
		}

		if ( '' !== self::get_hash( $post->ID ) ) {
			return;
		}

		add_post_meta( $post->ID, Plugin::META_KEY, self::generate_hash(), true );
	}

	/**
	 * Get the stored hash for a post.
	 */
	public static function get_hash( int $post_id ): string {
		return (string) get_post_meta( $post_id, Plugin::META_KEY, true );
	}

	/**
	 * Generate a new cryptographically random hash.
	 */
	public static function generate_hash(): string {
		/**
		 * Filters the number of random bytes used for the hash.
		 * The resulting hash is twice this length in hex characters.
		 *
		 * @param int $bytes Number of random bytes. Default 16 (32-char hash).
		 */
		$bytes = max( 8, (int) apply_filters( 'unlisted_posts_hash_bytes', 16 ) );

		return bin2hex( random_bytes( $bytes ) );
	}

	/**
	 * Find the post ID for a given hash.
	 */
	public static function find_post( string $hash ): int {
		if ( '' === $hash || ! ctype_xdigit( $hash ) ) {
			return 0;
		}

		$ids = get_posts(
			[
				'post_type'              => Plugin::post_types(),
				'post_status'            => Plugin::STATUS,
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_key'               => Plugin::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'             => $hash, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters'       => false,
			]
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Show "Unlisted" next to the title in list tables.
	 *
	 * @param string[] $states Post states.
	 * @param WP_Post  $post   Post object.
	 * @return string[]
	 */
	public function display_post_state( array $states, WP_Post $post ): array {
		if ( Plugin::STATUS === $post->post_status && Plugin::STATUS !== get_query_var( 'post_status' ) ) {
			$states[ Plugin::STATUS ] = _x( 'Unlisted', 'post status', 'unlisted-posts' );
		}

		return $states;
	}
}
