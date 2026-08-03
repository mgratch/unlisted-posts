<?php
/**
 * REST API integration.
 *
 * @package MarcGratch\UnlistedPosts
 */

declare( strict_types=1 );

namespace MarcGratch\UnlistedPosts;

/**
 * Exposes the hash URL to the block editor.
 */
final class RestApi {

	/**
	 * Hook everything up.
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_fields' ] );
	}

	/**
	 * Register the read-only unlisted_url field on supported types.
	 */
	public function register_fields(): void {
		register_rest_field(
			Plugin::post_types(),
			'unlisted_url',
			[
				'get_callback' => static function ( array $post ): string {
					if ( ! current_user_can( 'edit_post', (int) $post['id'] ) ) {
						return '';
					}

					return Permalinks::get_url( (int) $post['id'] );
				},
				'schema'       => [
					'description' => __( 'The private hash URL for an unlisted post.', 'unlisted-posts' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
			]
		);
	}
}
