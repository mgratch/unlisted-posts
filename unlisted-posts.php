<?php
/**
 * Plugin Name:       Unlisted Posts
 * Plugin URI:        https://github.com/mgratch/unlisted-posts
 * Description:       Adds an "Unlisted" post status — content is reachable only via a private, non-crawlable random hash URL.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Marc Gratch
 * Author URI:        https://marcgratch.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       unlisted-posts
 *
 * @package MarcGratch\UnlistedPosts
 */

declare( strict_types=1 );

namespace MarcGratch\UnlistedPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '1.0.0';

define( __NAMESPACE__ . '\\FILE', __FILE__ );
define( __NAMESPACE__ . '\\DIR', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\\URL', plugin_dir_url( __FILE__ ) );

/*
 * Autoload. Prefer the Composer autoloader when present (development),
 * fall back to a tiny PSR-4 loader so the plugin has no runtime
 * dependency on Composer.
 */
if ( is_readable( DIR . 'vendor/autoload.php' ) ) {
	require DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = __NAMESPACE__ . '\\';

			if ( ! str_starts_with( $class, $prefix ) ) {
				return;
			}

			$relative = substr( $class, strlen( $prefix ) );
			$path     = DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

			if ( is_readable( $path ) ) {
				require $path;
			}
		}
	);
}

register_activation_hook(
	__FILE__,
	static function (): void {
		PostStatus::register_status();
		Frontend::add_rewrite_rules();
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'plugins_loaded', [ Plugin::class, 'instance' ] );
