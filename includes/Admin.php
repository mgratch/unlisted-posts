<?php
/**
 * Admin UI: block editor, classic editor, quick/bulk edit, list tables.
 *
 * @package MarcGratch\UnlistedPosts
 */

declare( strict_types=1 );

namespace MarcGratch\UnlistedPosts;

use WP_Post;

/**
 * Everything wp-admin.
 */
final class Admin {

	/**
	 * Hook everything up.
	 */
	public function register_hooks(): void {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'post_submitbox_misc_actions', [ $this, 'render_classic_link_row' ] );
		add_filter( 'post_row_actions', [ $this, 'add_row_action' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'add_row_action' ], 10, 2 );
	}

	/**
	 * Block editor sidebar panel.
	 */
	public function enqueue_block_editor_assets(): void {
		$screen = get_current_screen();

		if ( ! $screen || ! Plugin::supports( (string) $screen->post_type ) ) {
			return;
		}

		$this->enqueue_script( 'editor' );
	}

	/**
	 * Classic editor + quick/bulk edit assets.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$screen = get_current_screen();

		if ( ! $screen || ! Plugin::supports( (string) $screen->post_type ) ) {
			return;
		}

		$label = _x( 'Unlisted', 'post status', 'unlisted-posts' );

		if ( 'edit.php' === $hook_suffix ) {
			$this->enqueue_script(
				'quick-edit',
				'unlistedPostsQuickEdit',
				[
					'status' => Plugin::STATUS,
					'label'  => $label,
				]
			);

			return;
		}

		if ( in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) && ! $screen->is_block_editor() ) {
			$post = get_post();

			$this->enqueue_script(
				'classic-editor',
				'unlistedPostsClassic',
				[
					'status'     => Plugin::STATUS,
					'label'      => $label,
					'isUnlisted' => $post && Plugin::STATUS === $post->post_status,
				]
			);
		}
	}

	/**
	 * Show the hash URL in the classic editor publish box.
	 *
	 * @param WP_Post $post The post being edited.
	 */
	public function render_classic_link_row( WP_Post $post ): void {
		if ( Plugin::STATUS !== $post->post_status || ! Plugin::supports( $post->post_type ) ) {
			return;
		}

		$url = Permalinks::get_url( $post );

		if ( '' === $url ) {
			return;
		}

		printf(
			'<div class="misc-pub-section misc-pub-unlisted-url"><strong>%s</strong><br /><a href="%s" target="_blank" rel="noopener noreferrer"><code style="font-size:11px;word-break:break-all;">%s</code></a></div>',
			esc_html__( 'Unlisted link:', 'unlisted-posts' ),
			esc_url( $url ),
			esc_html( $url )
		);
	}

	/**
	 * "View unlisted" row action in list tables.
	 *
	 * @param string[] $actions Row actions.
	 * @param WP_Post  $post    Post object.
	 * @return string[]
	 */
	public function add_row_action( array $actions, WP_Post $post ): array {
		if ( Plugin::STATUS !== $post->post_status || ! Plugin::supports( $post->post_type ) ) {
			return $actions;
		}

		$url = Permalinks::get_url( $post );

		if ( '' === $url || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$actions['view_unlisted'] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
			esc_url( $url ),
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'View unlisted link for &#8220;%s&#8221;', 'unlisted-posts' ), get_the_title( $post ) ) ),
			esc_html__( 'View unlisted link', 'unlisted-posts' )
		);

		return $actions;
	}

	/**
	 * Enqueue a wp-scripts build with its generated asset metadata.
	 *
	 * @param string               $entry       Entry name (matches src/{entry}.js).
	 * @param string               $object_name Optional JS global for inline data.
	 * @param array<string, mixed> $data        Optional inline data.
	 */
	private function enqueue_script( string $entry, string $object_name = '', array $data = [] ): void {
		$asset_file = DIR . 'build/' . $entry . '.asset.php';

		if ( ! is_readable( $asset_file ) ) {
			return; // Build has not been run.
		}

		$asset  = require $asset_file;
		$handle = 'unlisted-posts-' . $entry;

		wp_enqueue_script(
			$handle,
			URL . 'build/' . $entry . '.js',
			$asset['dependencies'] ?? [],
			$asset['version'] ?? VERSION,
			[ 'in_footer' => true ]
		);

		wp_set_script_translations( $handle, 'unlisted-posts' );

		if ( '' !== $object_name ) {
			wp_add_inline_script(
				$handle,
				sprintf( 'window.%s = %s;', $object_name, wp_json_encode( $data ) ),
				'before'
			);
		}
	}
}
