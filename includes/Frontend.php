<?php
/**
 * Front-end resolution of hash URLs.
 *
 * @package MarcGratch\UnlistedPosts
 */

declare( strict_types=1 );

namespace MarcGratch\UnlistedPosts;

use WP_Post;
use WP_Query;

/**
 * Resolves /unlisted/{hash}/ requests and keeps them out of crawlers.
 */
final class Frontend {

	/**
	 * Post ID currently being served via its hash URL, or 0.
	 *
	 * @var int
	 */
	private int $serving = 0;

	/**
	 * Hook everything up.
	 */
	public function register_hooks(): void {
		add_action( 'init', [ self::class, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'register_query_var' ] );
		add_filter( 'request', [ $this, 'resolve_request' ] );
		add_filter( 'posts_results', [ $this, 'allow_unlisted_post' ], 10, 2 );
		add_filter( 'redirect_canonical', [ $this, 'prevent_canonical_redirect' ] );
		add_action( 'template_redirect', [ $this, 'harden_unlisted_view' ], 0 );
		add_filter( 'wp_robots', [ $this, 'filter_robots' ] );
	}

	/**
	 * Register the pretty-permalink route.
	 */
	public static function add_rewrite_rules(): void {
		add_rewrite_rule(
			'^unlisted/([0-9a-f]+)/?$',
			'index.php?' . Plugin::QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Expose the hash query var.
	 *
	 * @param string[] $vars Public query vars.
	 * @return string[]
	 */
	public function register_query_var( array $vars ): array {
		$vars[] = Plugin::QUERY_VAR;

		return $vars;
	}

	/**
	 * Map a hash request onto the underlying post.
	 *
	 * @param array<string, mixed> $query_vars Main query vars.
	 * @return array<string, mixed>
	 */
	public function resolve_request( array $query_vars ): array {
		if ( empty( $query_vars[ Plugin::QUERY_VAR ] ) ) {
			return $query_vars;
		}

		$hash    = (string) $query_vars[ Plugin::QUERY_VAR ];
		$post_id = PostStatus::find_post( $hash );

		if ( ! $post_id ) {
			return [ 'error' => '404' ];
		}

		$this->serving = $post_id;

		return [
			'p'               => $post_id,
			'post_type'       => get_post_type( $post_id ),
			Plugin::QUERY_VAR => $hash,
		];
	}

	/**
	 * Let the main query render the unlisted post.
	 *
	 * The status is swapped to "publish" in memory only (never persisted),
	 * which is the same technique core-adjacent plugins such as Public Post
	 * Preview use to render non-public statuses.
	 *
	 * @param WP_Post[] $posts Query results.
	 * @param WP_Query  $query The query.
	 * @return WP_Post[]
	 */
	public function allow_unlisted_post( array $posts, WP_Query $query ): array {
		if (
			! $this->serving
			|| ! $query->is_main_query()
			|| ! $query->get( Plugin::QUERY_VAR )
			|| 1 !== count( $posts )
			|| $this->serving !== (int) $posts[0]->ID
			|| Plugin::STATUS !== $posts[0]->post_status
		) {
			return $posts;
		}

		$posts[0]->post_status = 'publish';

		return $posts;
	}

	/**
	 * Never canonical-redirect a hash URL to the real permalink.
	 *
	 * @param string|false $redirect_url Proposed redirect URL.
	 * @return string|false
	 */
	public function prevent_canonical_redirect( $redirect_url ) {
		return $this->is_unlisted_view() ? false : $redirect_url;
	}

	/**
	 * Send noindex headers and strip leaky discovery links.
	 */
	public function harden_unlisted_view(): void {
		if ( ! $this->is_unlisted_view() ) {
			return;
		}

		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		}

		// These would expose ?p= style links that 404, or invite embedding.
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	}

	/**
	 * Tell every well-behaved crawler to stay away.
	 *
	 * @param array<string, bool|string> $robots Robots directives.
	 * @return array<string, bool|string>
	 */
	public function filter_robots( array $robots ): array {
		if ( ! $this->is_unlisted_view() ) {
			return $robots;
		}

		$robots['noindex']   = true;
		$robots['nofollow']  = true;
		$robots['noarchive'] = true;
		unset( $robots['max-image-preview'] );

		return $robots;
	}

	/**
	 * Whether the current main query is an unlisted hash view.
	 */
	private function is_unlisted_view(): bool {
		return $this->serving > 0 && (bool) get_query_var( Plugin::QUERY_VAR );
	}
}
