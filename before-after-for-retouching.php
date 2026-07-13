<?php
/**
 * Plugin Name:       Before & After for Retouching
 * Description:       An accessible before-and-after image comparison block and shortcode with a visual generator.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Boris PH
 * Author URI:        https://profiles.wordpress.org/borisph/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       before-after-for-retouching
 * Domain Path:       /languages
 *
 * @package BeforeAfterForRetouching
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BAFRT_VERSION', '1.0.0' );
define( 'BAFRT_FILE', __FILE__ );
define( 'BAFRT_PATH', plugin_dir_path( __FILE__ ) );
define( 'BAFRT_URL', plugin_dir_url( __FILE__ ) );

require_once BAFRT_PATH . 'includes/class-bafrt-renderer.php';
require_once BAFRT_PATH . 'includes/class-bafrt-block.php';

if ( is_admin() ) {
	require_once BAFRT_PATH . 'includes/class-bafrt-admin.php';
}

/**
 * Coordinates the public assets, shortcode, block, and admin generator.
 */
final class BAFRT_Plugin {

	const SHORTCODE = 'before_after_retouching';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );

		BAFRT_Block::init();

		if ( is_admin() ) {
			BAFRT_Admin::init();
		}
	}

	/**
	 * Load bundled translations while allowing WordPress.org language packs to win.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'before-after-for-retouching', false, dirname( plugin_basename( BAFRT_FILE ) ) . '/languages' );
	}

	/**
	 * Register the shared frontend assets.
	 *
	 * @return void
	 */
	public static function register_public_assets() {
		wp_register_style(
			'before-after-for-retouching',
			BAFRT_URL . 'assets/css/before-after-for-retouching.css',
			array(),
			BAFRT_VERSION
		);

		wp_register_script(
			'before-after-for-retouching',
			BAFRT_URL . 'assets/js/before-after-for-retouching.js',
			array(),
			BAFRT_VERSION,
			true
		);
	}

	/**
	 * Load the small shared assets early on every public frontend request.
	 *
	 * @return void
	 */
	public static function enqueue_frontend_assets() {
		if ( is_admin() ) {
			return;
		}

		self::enqueue_public_assets();
	}

	/**
	 * Load frontend assets.
	 *
	 * @return void
	 */
	public static function enqueue_public_assets() {
		self::register_public_assets();
		wp_enqueue_style( 'before-after-for-retouching' );
		wp_enqueue_script( 'before-after-for-retouching' );
	}

	/**
	 * Render the public shortcode.
	 *
	 * @param array|string $attributes Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $attributes ) {
		self::enqueue_public_assets();

		return BAFRT_Renderer::render( is_array( $attributes ) ? $attributes : array() );
	}
}

BAFRT_Plugin::init();
