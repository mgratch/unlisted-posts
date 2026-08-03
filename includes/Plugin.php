<?php
/**
 * Plugin bootstrap.
 *
 * @package MarcGratch\UnlistedPosts
 */

declare( strict_types=1 );

namespace MarcGratch\UnlistedPosts;

/**
 * Wires up all plugin services.
 */
final class Plugin {

	/**
	 * Post status slug.
	 */
	public const STATUS = 'unlisted';

	/**
	 * Post meta key holding the hash.
	 */
	public const META_KEY = '_unlisted_posts_hash';

	/**
	 * Rewrite/query var carrying the hash.
	 */
	public const QUERY_VAR = 'unlisted_hash';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get (and boot) the plugin instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}

		return self::$instance;
	}

	/**
	 * Register all hooks.
	 */
	private function boot(): void {
		( new PostStatus() )->register_hooks();
		( new Permalinks() )->register_hooks();
		( new Frontend() )->register_hooks();
		( new RestApi() )->register_hooks();

		if ( is_admin() ) {
			( new Admin() )->register_hooks();
		}
	}

	/**
	 * Post types that support the unlisted status.
	 *
	 * @return string[]
	 */
	public static function post_types(): array {
		$types = get_post_types( [ 'public' => true ], 'names' );
		unset( $types['attachment'] );

		/**
		 * Filters the post types that support the "Unlisted" status.
		 *
		 * @param string[] $types Post type names.
		 */
		return (array) apply_filters( 'unlisted_posts_post_types', array_values( $types ) );
	}

	/**
	 * Whether a post type supports the unlisted status.
	 *
	 * @param string $post_type Post type name.
	 */
	public static function supports( string $post_type ): bool {
		return in_array( $post_type, self::post_types(), true );
	}
}
