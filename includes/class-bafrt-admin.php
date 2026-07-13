<?php
/**
 * Admin shortcode generator.
 *
 * @package BeforeAfterForRetouching
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the Media submenu generator and its scoped assets.
 */
final class BAFRT_Admin {

	/**
	 * Generator page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'before-after-for-retouching';

	/**
	 * Capability required to use the generator.
	 *
	 * @var string
	 */
	const CAPABILITY = 'upload_files';

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private static $page_hook = '';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( BAFRT_FILE ), array( __CLASS__, 'add_plugin_action_link' ) );
	}

	/**
	 * Add the generator below Media.
	 *
	 * @return void
	 */
	public static function add_menu_page() {
		self::$page_hook = add_media_page(
			__( 'Before & After Generator', 'before-after-for-retouching' ),
			__( 'Before & After', 'before-after-for-retouching' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Add a shortcut to the comparison generator on the Plugins screen.
	 *
	 * @param array $actions Existing plugin action links.
	 * @return array
	 */
	public static function add_plugin_action_link( $actions ) {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return $actions;
		}

		$link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( self::get_generator_url() ),
			esc_html__( 'Set up comparison', 'before-after-for-retouching' )
		);

		return array_merge( array( 'bafrt_setup_comparison' => $link ), $actions );
	}

	/**
	 * Return the existing generator page URL.
	 *
	 * @return string
	 */
	public static function get_generator_url() {
		return admin_url( 'upload.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Load scripts only on this plugin page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( self::$page_hook !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();
		BAFRT_Plugin::enqueue_public_assets();

		wp_enqueue_style(
			'before-after-for-retouching-admin',
			BAFRT_URL . 'assets/css/before-after-for-retouching-admin.css',
			array( 'before-after-for-retouching' ),
			self::get_asset_version( 'assets/css/before-after-for-retouching-admin.css' )
		);

		wp_enqueue_script(
			'before-after-for-retouching-admin',
			BAFRT_URL . 'assets/js/before-after-for-retouching-admin.js',
			array( 'before-after-for-retouching', 'media-editor' ),
			self::get_asset_version( 'assets/js/before-after-for-retouching-admin.js' ),
			true
		);

		wp_localize_script(
			'before-after-for-retouching-admin',
			'bafrtAdmin',
			array(
				'beforeTitle'       => __( 'Select the Before image', 'before-after-for-retouching' ),
				'afterTitle'        => __( 'Select the After image', 'before-after-for-retouching' ),
				'useImage'          => __( 'Use this image', 'before-after-for-retouching' ),
				'ratioWarning'      => __( 'The selected images have noticeably different aspect ratios. Alignment may be inaccurate.', 'before-after-for-retouching' ),
				'sizeWarning'       => __( 'The selected images have noticeably different pixel dimensions. For the best comparison, use matching exports.', 'before-after-for-retouching' ),
				'copySuccess'       => __( 'Shortcode copied.', 'before-after-for-retouching' ),
				'copyError'         => __( 'Copy failed. Select the shortcode and copy it manually.', 'before-after-for-retouching' ),
				'afterVisible'      => __( 'of the After image is visible', 'before-after-for-retouching' ),
				/* translators: 1: Percentage, 2: After label. */
				'valueTemplate'      => __( '%1$s%% of the %2$s image is visible', 'before-after-for-retouching' ),
				'defaultBefore'     => __( 'Before', 'before-after-for-retouching' ),
				'defaultAfter'      => __( 'After', 'before-after-for-retouching' ),
				'imagesRequired'    => __( 'Select both images to generate a shortcode.', 'before-after-for-retouching' ),
				'invalidCustomRatio' => __( 'Enter a custom ratio in width/height format, for example 3/2.', 'before-after-for-retouching' ),
				/* translators: 1: Image width, 2: Image height. */
				'currentRatio'      => __( 'Current ratio: %1$s / %2$s', 'before-after-for-retouching' ),
				'selectBefore'      => __( 'Select a Before image', 'before-after-for-retouching' ),
			)
		);
	}

	/**
	 * Return a cache-busting version for an administrative asset.
	 *
	 * @param string $relative_path Path relative to the plugin directory.
	 * @return string
	 */
	private static function get_asset_version( $relative_path ) {
		$modified = filemtime( BAFRT_PATH . $relative_path );

		return false !== $modified ? (string) $modified : BAFRT_VERSION;
	}

	/**
	 * Render the generator screen.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'before-after-for-retouching' ) );
		}

		$image_sizes = BAFRT_Renderer::get_image_size_options();
		$user_locale = strtolower( str_replace( '-', '_', get_user_locale() ) );

		if ( 0 === strpos( $user_locale, 'ru' ) ) {
			$blog_url = 'https://green4.photo/';
		} elseif ( 0 === strpos( $user_locale, 'uk' ) ) {
			$blog_url = 'https://green4.photo/uk/';
		} else {
			$blog_url = 'https://yellowphotoschool.com/';
		}

		$blog_text = __( 'Photography Blog — Articles, Tutorials, and Courses', 'before-after-for-retouching' );
		?>
		<div class="wrap bafrt-admin" data-bafrt-generator>
			<h1><?php echo esc_html__( 'Before & After Generator', 'before-after-for-retouching' ); ?></h1>
			<p class="description"><?php echo esc_html__( 'Choose two images, adjust the comparison, and copy the generated shortcode.', 'before-after-for-retouching' ); ?></p>

			<div class="bafrt-admin__layout">
				<main class="bafrt-admin__main">
					<section class="bafrt-panel" aria-labelledby="bafrt-images-title">
						<h2 id="bafrt-images-title"><?php echo esc_html__( 'Images', 'before-after-for-retouching' ); ?></h2>
						<div class="bafrt-image-grid">
							<?php self::render_image_picker( 'before', __( 'Before image', 'before-after-for-retouching' ) ); ?>
							<?php self::render_image_picker( 'after', __( 'After image', 'before-after-for-retouching' ) ); ?>
						</div>
						<p><button type="button" class="button" data-bafrt-swap disabled><?php echo esc_html__( 'Swap images', 'before-after-for-retouching' ); ?></button></p>
						<div class="bafrt-warnings" aria-live="polite">
							<p class="notice notice-warning inline" data-bafrt-ratio-warning hidden></p>
							<p class="notice notice-warning inline" data-bafrt-size-warning hidden></p>
						</div>
					</section>

					<section class="bafrt-panel" aria-labelledby="bafrt-settings-title">
						<h2 id="bafrt-settings-title"><?php echo esc_html__( 'Comparison settings', 'before-after-for-retouching' ); ?></h2>
						<div class="bafrt-fields">
							<label class="bafrt-field">
								<span><?php echo esc_html__( 'Aspect ratio', 'before-after-for-retouching' ); ?></span>
								<select data-bafrt-field="ratio">
									<option value="auto"><?php echo esc_html__( 'Auto (Before image)', 'before-after-for-retouching' ); ?></option>
									<option value="1/1">1:1</option>
									<option value="4/3">4:3</option>
									<option value="3/2">3:2</option>
									<option value="16/9">16:9</option>
									<option value="custom"><?php echo esc_html__( 'Custom', 'before-after-for-retouching' ); ?></option>
								</select>
							</label>
							<p class="description bafrt-current-ratio" data-bafrt-current-ratio><?php echo esc_html__( 'Select a Before image', 'before-after-for-retouching' ); ?></p>
							<label class="bafrt-field" data-bafrt-custom-ratio-wrap hidden>
								<span><?php echo esc_html__( 'Custom ratio', 'before-after-for-retouching' ); ?></span>
								<input type="text" value="3/2" data-bafrt-field="customRatio" inputmode="numeric">
							</label>
							<label class="bafrt-field">
								<span><?php echo esc_html__( 'Initial position', 'before-after-for-retouching' ); ?>: <output data-bafrt-start-output>50%</output></span>
								<input type="range" min="0" max="100" step="1" value="50" data-bafrt-field="start">
							</label>
							<label class="bafrt-field">
								<span><?php echo esc_html__( 'Image fit', 'before-after-for-retouching' ); ?></span>
								<select data-bafrt-field="fit">
									<option value="contain"><?php echo esc_html__( 'Contain (no cropping)', 'before-after-for-retouching' ); ?></option>
									<option value="cover"><?php echo esc_html__( 'Cover (may crop)', 'before-after-for-retouching' ); ?></option>
								</select>
							</label>
							<label class="bafrt-field">
								<span><?php echo esc_html__( 'Pointer behavior', 'before-after-for-retouching' ); ?></span>
								<select data-bafrt-field="interaction">
									<option value="hover"><?php echo esc_html__( 'Follow pointer', 'before-after-for-retouching' ); ?></option>
									<option value="drag"><?php echo esc_html__( 'Click and drag', 'before-after-for-retouching' ); ?></option>
								</select>
							</label>
							<label class="bafrt-field">
								<span><?php echo esc_html__( 'Loading', 'before-after-for-retouching' ); ?></span>
								<select data-bafrt-field="loading">
									<option value="auto"><?php echo esc_html__( 'Auto (recommended)', 'before-after-for-retouching' ); ?></option>
									<option value="lazy"><?php echo esc_html__( 'Lazy', 'before-after-for-retouching' ); ?></option>
									<option value="eager"><?php echo esc_html__( 'Eager', 'before-after-for-retouching' ); ?></option>
								</select>
							</label>
							<label class="bafrt-field">
								<span><?php echo esc_html__( 'WordPress image size', 'before-after-for-retouching' ); ?></span>
								<select data-bafrt-field="size">
									<?php foreach ( $image_sizes as $image_size ) : ?>
										<option value="<?php echo esc_attr( $image_size['value'] ); ?>"><?php echo esc_html( $image_size['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>
					</section>

					<section class="bafrt-panel" aria-labelledby="bafrt-appearance-title">
						<h2 id="bafrt-appearance-title"><?php echo esc_html__( 'Appearance', 'before-after-for-retouching' ); ?></h2>
						<div class="bafrt-toggles">
							<label><input type="checkbox" data-bafrt-field="showSlider" checked> <?php echo esc_html__( 'Show bottom slider', 'before-after-for-retouching' ); ?></label>
							<label><input type="checkbox" data-bafrt-field="divider"> <?php echo esc_html__( 'Show divider', 'before-after-for-retouching' ); ?></label>
							<label><input type="checkbox" data-bafrt-field="handle"> <?php echo esc_html__( 'Show handle', 'before-after-for-retouching' ); ?></label>
							<label><input type="checkbox" data-bafrt-field="showBeforeLabel"> <?php echo esc_html__( 'Show Before label', 'before-after-for-retouching' ); ?></label>
							<label><input type="checkbox" data-bafrt-field="showAfterLabel"> <?php echo esc_html__( 'Show After label', 'before-after-for-retouching' ); ?></label>
						</div>
						<div class="bafrt-fields" data-bafrt-label-fields>
							<label class="bafrt-field" data-bafrt-before-label-field hidden><span><?php echo esc_html__( 'Before label', 'before-after-for-retouching' ); ?></span><input type="text" value="<?php echo esc_attr__( 'Before', 'before-after-for-retouching' ); ?>" data-bafrt-field="beforeLabel"></label>
							<label class="bafrt-field" data-bafrt-after-label-field hidden><span><?php echo esc_html__( 'After label', 'before-after-for-retouching' ); ?></span><input type="text" value="<?php echo esc_attr__( 'After', 'before-after-for-retouching' ); ?>" data-bafrt-field="afterLabel"></label>
						</div>
						<details class="bafrt-advanced">
							<summary><?php echo esc_html__( 'Colors and advanced settings', 'before-after-for-retouching' ); ?></summary>
							<div class="bafrt-fields">
								<label class="bafrt-field"><span><?php echo esc_html__( 'Divider color', 'before-after-for-retouching' ); ?></span><input type="color" value="#ffffff" data-bafrt-field="lineColor"></label>
								<label class="bafrt-field"><span><?php echo esc_html__( 'Divider width', 'before-after-for-retouching' ); ?></span><input type="number" min="1" max="20" value="2" data-bafrt-field="lineWidth"></label>
								<label class="bafrt-field"><span><?php echo esc_html__( 'Handle color', 'before-after-for-retouching' ); ?></span><input type="color" value="#ffffff" data-bafrt-field="handleColor"></label>
								<label class="bafrt-field"><span><?php echo esc_html__( 'Label text color', 'before-after-for-retouching' ); ?></span><input type="color" value="#ffffff" data-bafrt-field="labelColor"></label>
								<label class="bafrt-field"><span><?php echo esc_html__( 'Label background', 'before-after-for-retouching' ); ?></span><input type="color" value="#000000" data-bafrt-field="labelBackground"></label>
								<label class="bafrt-field"><span><?php echo esc_html__( 'Additional CSS class', 'before-after-for-retouching' ); ?></span><input type="text" data-bafrt-field="customClass"></label>
							</div>
						</details>
					</section>

					<footer class="bafrt-admin__footer">
						<p class="bafrt-blog-link">
							<a href="<?php echo esc_url( $blog_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $blog_text ); ?></a>
						</p>
						<p class="bafrt-author">
							<?php echo esc_html__( 'Plugin by', 'before-after-for-retouching' ); ?>
							<a href="https://profiles.wordpress.org/borisph/" target="_blank" rel="noopener noreferrer">Boris PH</a>
						</p>
					</footer>
				</main>

				<aside class="bafrt-admin__sidebar">
					<section class="bafrt-panel bafrt-panel--sticky" aria-labelledby="bafrt-preview-title">
						<h2 id="bafrt-preview-title"><?php echo esc_html__( 'Live preview', 'before-after-for-retouching' ); ?></h2>
						<div class="bafrt-preview-empty" data-bafrt-preview-empty><?php echo esc_html__( 'Select both images to see the preview.', 'before-after-for-retouching' ); ?></div>
						<div class="bafrt-compare bafrt-preview" data-bafrt-compare data-bafrt-preview data-start="50" data-interaction="hover" role="group" aria-label="<?php echo esc_attr__( 'Before and after image comparison', 'before-after-for-retouching' ); ?>" hidden>
							<div class="bafrt-compare__stage" data-bafrt-stage>
								<div class="bafrt-compare__layer bafrt-compare__layer--before">
									<img class="bafrt-compare__image bafrt-compare__image--before" alt="">
									<div class="bafrt-compare__label bafrt-compare__label--before" aria-hidden="true"><?php echo esc_html__( 'Before', 'before-after-for-retouching' ); ?></div>
								</div>
								<div class="bafrt-compare__layer bafrt-compare__layer--after">
									<img class="bafrt-compare__image bafrt-compare__image--after" alt="">
									<div class="bafrt-compare__label bafrt-compare__label--after" aria-hidden="true"><?php echo esc_html__( 'After', 'before-after-for-retouching' ); ?></div>
								</div>
								<div class="bafrt-compare__divider" aria-hidden="true"><span class="bafrt-compare__handle"></span></div>
							</div>
							<input class="bafrt-compare__range" data-bafrt-range type="range" min="0" max="100" step="0.1" value="50" aria-label="<?php echo esc_attr__( 'Before and after comparison position', 'before-after-for-retouching' ); ?>" aria-valuetext="<?php echo esc_attr__( '50% of the After image is visible', 'before-after-for-retouching' ); ?>" data-value-label="<?php echo esc_attr__( 'After', 'before-after-for-retouching' ); ?>" data-value-template="<?php /* translators: 1: Percentage, 2: After label. */ echo esc_attr__( '%1$s%% of the %2$s image is visible', 'before-after-for-retouching' ); ?>">
						</div>

						<hr>
						<h2><?php echo esc_html__( 'Shortcode', 'before-after-for-retouching' ); ?></h2>
						<textarea id="bafrt-generated-shortcode" class="large-text code" rows="5" readonly data-bafrt-shortcode aria-label="<?php echo esc_attr__( 'Generated shortcode', 'before-after-for-retouching' ); ?>" aria-describedby="bafrt-shortcode-help"></textarea>
						<p id="bafrt-shortcode-help" class="description"><?php echo esc_html__( 'Paste this shortcode into the post, page, or shortcode-compatible block where you want the image comparison to appear.', 'before-after-for-retouching' ); ?></p>
						<p><button type="button" class="button button-primary" data-bafrt-copy disabled><?php echo esc_html__( 'Copy shortcode', 'before-after-for-retouching' ); ?></button></p>
						<p class="description" data-bafrt-copy-status aria-live="polite"></p>
					</section>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one media-library selector.
	 *
	 * @param string $type  Before or after.
	 * @param string $label Visible label.
	 * @return void
	 */
	private static function render_image_picker( $type, $label ) {
		?>
		<div class="bafrt-image-picker" data-bafrt-picker="<?php echo esc_attr( $type ); ?>">
			<h3><?php echo esc_html( $label ); ?></h3>
			<div class="bafrt-image-picker__preview"><span><?php echo esc_html__( 'No image selected', 'before-after-for-retouching' ); ?></span><img src="" alt="" hidden></div>
			<input type="hidden" value="" data-bafrt-image-id>
			<p>
				<button type="button" class="button button-secondary" data-bafrt-select><?php echo esc_html__( 'Select image', 'before-after-for-retouching' ); ?></button>
				<button type="button" class="button" data-bafrt-remove hidden><?php echo esc_html__( 'Remove', 'before-after-for-retouching' ); ?></button>
			</p>
			<p class="description" data-bafrt-image-meta></p>
		</div>
		<?php
	}

}
