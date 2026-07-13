<?php
/**
 * Dynamic block integration.
 *
 * @package BeforeAfterForRetouching
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the editor script and server-rendered block.
 */
final class BAFRT_Block {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
	}

	/**
	 * Register block assets and metadata.
	 *
	 * @return void
	 */
	public static function register() {
		BAFRT_Plugin::register_public_assets();

		wp_register_script(
			'before-after-for-retouching-block-editor',
			BAFRT_URL . 'assets/js/before-after-for-retouching-block.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
			BAFRT_VERSION,
			true
		);

		wp_register_style(
			'before-after-for-retouching-block-editor',
			BAFRT_URL . 'assets/css/before-after-for-retouching-block.css',
			array(),
			BAFRT_VERSION
		);

		wp_set_script_translations( 'before-after-for-retouching-block-editor', 'before-after-for-retouching', BAFRT_PATH . 'languages' );
		wp_localize_script(
			'before-after-for-retouching-block-editor',
			'bafrtBlockData',
			array(
				'imageSizes' => array_values( BAFRT_Renderer::get_image_size_options() ),
			)
		);

		register_block_type(
			BAFRT_PATH . 'block',
			array(
				'render_callback' => array( __CLASS__, 'render' ),
			)
		);
	}

	/**
	 * Render block attributes through the shared renderer.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		BAFRT_Plugin::enqueue_public_assets();

		$map = array(
			'before'       => isset( $attributes['beforeId'] ) ? $attributes['beforeId'] : '',
			'after'        => isset( $attributes['afterId'] ) ? $attributes['afterId'] : '',
			'ratio'        => isset( $attributes['ratio'] ) && 'custom' === $attributes['ratio'] && ! empty( $attributes['customRatio'] ) ? $attributes['customRatio'] : ( isset( $attributes['ratio'] ) ? $attributes['ratio'] : 'auto' ),
			'start'        => isset( $attributes['start'] ) ? $attributes['start'] : 50,
			'fit'          => isset( $attributes['fit'] ) ? $attributes['fit'] : 'contain',
			'interaction'  => isset( $attributes['interaction'] ) ? $attributes['interaction'] : 'hover',
			'loading'      => isset( $attributes['loading'] ) ? $attributes['loading'] : 'auto',
			'size'         => BAFRT_Renderer::sanitize_size( isset( $attributes['imageSize'] ) ? $attributes['imageSize'] : 'full' ),
			'divider'      => ! empty( $attributes['showDivider'] ) ? 'on' : 'off',
			'handle'       => ! empty( $attributes['showHandle'] ) ? 'on' : 'off',
			'show_slider'  => ! isset( $attributes['showSlider'] ) || $attributes['showSlider'] ? 'on' : 'off',
			'show_before_label' => ! empty( $attributes['showBeforeLabel'] ) ? 'on' : 'off',
			'show_after_label'  => ! empty( $attributes['showAfterLabel'] ) ? 'on' : 'off',
			'label_before' => ! empty( $attributes['beforeLabel'] ) ? $attributes['beforeLabel'] : __( 'Before', 'before-after-for-retouching' ),
			'label_after'  => ! empty( $attributes['afterLabel'] ) ? $attributes['afterLabel'] : __( 'After', 'before-after-for-retouching' ),
			'line_color'   => isset( $attributes['lineColor'] ) ? $attributes['lineColor'] : '#ffffff',
			'line_width'   => isset( $attributes['lineWidth'] ) ? $attributes['lineWidth'] : 2,
			'handle_color' => isset( $attributes['handleColor'] ) ? $attributes['handleColor'] : '#ffffff',
			'label_color'  => isset( $attributes['labelColor'] ) ? $attributes['labelColor'] : '#ffffff',
			'label_bg'     => isset( $attributes['labelBackground'] ) ? $attributes['labelBackground'] : '#000000',
		);

		return BAFRT_Renderer::render( $map, true );
	}
}
