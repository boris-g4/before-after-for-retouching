<?php
/**
 * Local smoke tests for release preparation. This file is not shipped.
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['bafrt_actions'] = array();
$GLOBALS['bafrt_filters'] = array();
$GLOBALS['bafrt_shortcodes'] = array();
$GLOBALS['bafrt_image_calls'] = array();
$GLOBALS['bafrt_registered_block'] = '';
$GLOBALS['bafrt_enqueued_styles'] = array();
$GLOBALS['bafrt_enqueued_scripts'] = array();
$GLOBALS['bafrt_block_align'] = '';
$GLOBALS['bafrt_user_locale'] = 'en_US';
$GLOBALS['bafrt_current_user_can'] = true;

class WP_Post {
	public $post_content = '';
}

function plugin_dir_path( $file ) { return dirname( $file ) . DIRECTORY_SEPARATOR; }
function plugin_dir_url() { return 'https://example.test/plugins/before-after-for-retouching/'; }
function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function is_admin() { return false; }
function is_singular() { return false; }
function add_action( $hook, $callback ) { $GLOBALS['bafrt_actions'][ $hook ][] = $callback; }
function add_filter( $hook, $callback ) { $GLOBALS['bafrt_filters'][ $hook ][] = $callback; }
function add_shortcode( $tag, $callback ) { $GLOBALS['bafrt_shortcodes'][ $tag ] = $callback; }
function load_plugin_textdomain() { return true; }
function wp_register_style() { return true; }
function wp_register_script() { return true; }
function wp_enqueue_style( $handle ) { $GLOBALS['bafrt_enqueued_styles'][ $handle ] = true; }
function wp_enqueue_script( $handle ) { $GLOBALS['bafrt_enqueued_scripts'][ $handle ] = true; }
function wp_set_script_translations() { return true; }
function wp_localize_script() { return true; }
function register_block_type( $path ) {
	$metadata = json_decode( file_get_contents( $path . '/block.json' ), true );
	$GLOBALS['bafrt_registered_block'] = $metadata['name'];
	return true;
}
function get_block_wrapper_attributes( $extra_attributes = array() ) {
	$classes = 'wp-block-before-after-for-retouching-compare';
	if ( $GLOBALS['bafrt_block_align'] ) {
		$classes .= ' align' . $GLOBALS['bafrt_block_align'];
	}
	$extra_attributes['class'] = trim( $classes . ' ' . ( isset( $extra_attributes['class'] ) ? $extra_attributes['class'] : '' ) );
	$html = array();
	foreach ( $extra_attributes as $name => $value ) {
		$html[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}
	return implode( ' ', $html );
}
function has_shortcode() { return false; }
function has_block() { return false; }
function current_user_can() { return $GLOBALS['bafrt_current_user_can']; }
function get_user_locale() { return $GLOBALS['bafrt_user_locale']; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function __( $text ) { return $text; }
function esc_html__( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr__( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_hex_color( $value ) { return preg_match( '/^#[0-9a-f]{6}$/i', $value ) ? strtolower( $value ) : null; }
function wp_generate_uuid4() { static $i = 0; return '00000000-0000-4000-8000-' . str_pad( (string) ++$i, 12, '0', STR_PAD_LEFT ); }
function wp_attachment_is_image( $id ) { return in_array( (int) $id, array( 123, 124 ), true ); }
function wp_get_attachment_metadata( $id ) { return 123 === (int) $id ? array( 'width' => 1200, 'height' => 800 ) : array( 'width' => 1000, 'height' => 800 ); }
function wp_get_registered_image_subsizes() {
	return array(
		'thumbnail' => array( 'width' => 150, 'height' => 150 ),
		'medium' => array( 'width' => 300, 'height' => 300 ),
		'large' => array( 'width' => 1024, 'height' => 1024 ),
		'custom-size' => array( 'width' => 400, 'height' => 300 ),
		'unavailable-size' => array( 'width' => 500, 'height' => 500 ),
	);
}
function wp_get_attachment_image_src( $id, $size ) {
	$variants = array(
		123 => array(
			'full' => array( 'image-123.jpg', 965, 578, false ),
			'medium' => array( 'image-123-medium.jpg', 300, 200, true ),
			'large' => array( 'image-123-large.jpg', 1024, 683, true ),
			'custom-size' => array( 'image-123-custom.jpg', 400, 300, true ),
		),
		124 => array(
			'full' => array( 'image-124.jpg', 1000, 800, false ),
			'medium' => array( 'image-124-medium.jpg', 300, 240, true ),
			'large' => array( 'image-124-large.jpg', 1000, 800, false ),
			'custom-size' => array( 'image-124-custom.jpg', 400, 300, true ),
		),
	);
	return isset( $variants[ $id ][ $size ] ) ? $variants[ $id ][ $size ] : false;
}
function shortcode_atts( $pairs, $atts ) { return array_merge( $pairs, array_intersect_key( (array) $atts, $pairs ) ); }
function wp_get_attachment_image( $id, $size, $icon, $attrs ) {
	$GLOBALS['bafrt_image_calls'][] = compact( 'id', 'size', 'attrs' );
	$attrs = array_merge(
		array(
			'alt' => 'Attachment ' . $id,
			'loading' => 'lazy',
			'width' => '1200',
			'height' => '800',
			'srcset' => 'image-600.jpg 600w, image-1200.jpg 1200w',
			'sizes' => '(max-width: 1200px) 100vw, 1200px',
		),
		$attrs
	);
	$html = '<img src="image-' . (int) $id . '.jpg"';
	foreach ( $attrs as $name => $value ) {
		$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
	return $html . '>';
}

function bafrt_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$json_files = array_merge(
	array( dirname( __DIR__ ) . '/block/block.json' ),
	glob( dirname( __DIR__ ) . '/languages/*.json' )
);
foreach ( $json_files as $json_file ) {
	json_decode( file_get_contents( $json_file ), true, 512, JSON_THROW_ON_ERROR );
}
$block_metadata = json_decode( file_get_contents( dirname( __DIR__ ) . '/block/block.json' ), true, 512, JSON_THROW_ON_ERROR );
bafrt_assert( true === $block_metadata['attributes']['showSlider']['default'], 'block showSlider defaults to true' );
bafrt_assert( array( 'wide', 'full' ) === $block_metadata['supports']['align'], 'block supports wide and full alignment' );
$translations = json_decode( file_get_contents( dirname( __DIR__ ) . '/languages/translations.json' ), true, 512, JSON_THROW_ON_ERROR );
bafrt_assert( 'Блог для фотографа — статьи, уроки, курсы' === $translations['ru_RU']['Photography Blog — Articles, Tutorials, and Courses'], 'Russian blog translation' );
bafrt_assert( 'Блог для фотографа — статті, уроки, курси' === $translations['uk']['Photography Blog — Articles, Tutorials, and Courses'], 'Ukrainian blog translation' );
bafrt_assert( 'Настроить сравнение' === $translations['ru_RU']['Set up comparison'], 'Russian action-link translation' );
bafrt_assert( 'Налаштувати порівняння' === $translations['uk']['Set up comparison'], 'Ukrainian action-link translation' );
bafrt_assert( 'Вставьте этот шорткод в запись, страницу или другой блок, где должно отображаться сравнение изображений.' === $translations['ru_RU']['Paste this shortcode into the post, page, or shortcode-compatible block where you want the image comparison to appear.'], 'Russian shortcode-help translation' );
bafrt_assert( 'Вставте цей шорткод у запис, сторінку або інший блок, де має відображатися порівняння зображень.' === $translations['uk']['Paste this shortcode into the post, page, or shortcode-compatible block where you want the image comparison to appear.'], 'Ukrainian shortcode-help translation' );
$pot = file_get_contents( dirname( __DIR__ ) . '/languages/before-after-for-retouching.pot' );
foreach ( array( 'ru_RU', 'uk' ) as $locale ) {
	$po = file_get_contents( dirname( __DIR__ ) . '/languages/before-after-for-retouching-' . $locale . '.po' );
	preg_match_all( '/^msgid "(.*)"\r?$/m', $po, $po_matches );
	$po_counts = array_count_values( array_filter( $po_matches[1], 'strlen' ) );
	bafrt_assert( ! array_filter( $po_counts, function ( $count ) { return $count > 1; } ), "{$locale} PO has no duplicate msgids" );
	foreach ( $translations[ $locale ] as $source => $translated ) {
		$escaped_source = addcslashes( $source, "\\\"" );
		bafrt_assert( false !== strpos( $po, 'msgid "' . $escaped_source . '"' ), "{$locale} PO contains source string" );
		bafrt_assert( false !== strpos( $pot, 'msgid "' . $escaped_source . '"' ), 'POT contains source string' );
	}
	$mo = file_get_contents( dirname( __DIR__ ) . '/languages/before-after-for-retouching-' . $locale . '.mo' );
	bafrt_assert( 0x950412de === unpack( 'Vmagic', substr( $mo, 0, 4 ) )['magic'], "{$locale} MO magic" );
	bafrt_assert( false !== strpos( $mo, 'Photography Blog — Articles, Tutorials, and Courses' ), "{$locale} MO contains blog source" );
}

require dirname( __DIR__ ) . '/before-after-for-retouching.php';
require dirname( __DIR__ ) . '/includes/class-bafrt-admin.php';
BAFRT_Admin::init();

bafrt_assert( isset( $GLOBALS['bafrt_shortcodes']['before_after_retouching'] ), 'new shortcode is registered' );
bafrt_assert( ! isset( $GLOBALS['bafrt_shortcodes']['g4_before_after'] ), 'old shortcode is not registered' );
bafrt_assert( isset( $GLOBALS['bafrt_filters']['plugin_action_links_before-after-for-retouching/before-after-for-retouching.php'] ), 'plugin-specific action-link filter is registered' );

$plugin_actions = array( 'deactivate' => '<a href="#">Deactivate</a>' );
$plugin_actions_with_setup = BAFRT_Admin::add_plugin_action_link( $plugin_actions );
$plugin_action_keys = array_keys( $plugin_actions_with_setup );
bafrt_assert( 'bafrt_setup_comparison' === $plugin_action_keys[0] && 'deactivate' === $plugin_action_keys[1], 'setup action link appears before Deactivate' );
bafrt_assert( false !== strpos( $plugin_actions_with_setup['bafrt_setup_comparison'], 'href="https://example.test/wp-admin/upload.php?page=before-after-for-retouching"' ), 'setup action link targets the existing Media page' );
bafrt_assert( false === strpos( $plugin_actions_with_setup['bafrt_setup_comparison'], 'target=' ), 'setup action link opens in the same tab' );
$GLOBALS['bafrt_current_user_can'] = false;
bafrt_assert( $plugin_actions === BAFRT_Admin::add_plugin_action_link( $plugin_actions ), 'setup action link is hidden without upload_files capability' );
$GLOBALS['bafrt_current_user_can'] = true;

BAFRT_Block::register();
bafrt_assert( 'before-after-for-retouching/compare' === $GLOBALS['bafrt_registered_block'], 'new block name is registered' );
bafrt_assert( isset( $GLOBALS['bafrt_actions']['wp_enqueue_scripts'] ), 'frontend asset hook is registered' );
BAFRT_Plugin::enqueue_frontend_assets();
bafrt_assert( isset( $GLOBALS['bafrt_enqueued_styles']['before-after-for-retouching'] ), 'frontend style is enqueued on public requests' );
bafrt_assert( isset( $GLOBALS['bafrt_enqueued_scripts']['before-after-for-retouching'] ), 'frontend script is enqueued on public requests' );

$admin_js = file_get_contents( dirname( __DIR__ ) . '/assets/js/before-after-for-retouching-admin.js' );
$block_js = file_get_contents( dirname( __DIR__ ) . '/assets/js/before-after-for-retouching-block.js' );
$public_js = file_get_contents( dirname( __DIR__ ) . '/assets/js/before-after-for-retouching.js' );
$public_css = file_get_contents( dirname( __DIR__ ) . '/assets/css/before-after-for-retouching.css' );
$admin_css = file_get_contents( dirname( __DIR__ ) . '/assets/css/before-after-for-retouching-admin.css' );
$admin_php = file_get_contents( dirname( __DIR__ ) . '/includes/class-bafrt-admin.php' );
bafrt_assert( false !== strpos( $admin_js, "var parts = ['before_after_retouching'];" ), 'admin generator uses the new shortcode' );
bafrt_assert( false !== strpos( $block_js, "var parts = ['before_after_retouching'];" ), 'Gutenberg copy uses the new shortcode' );
bafrt_assert( false !== strpos( $block_js, "block: 'before-after-for-retouching/compare'" ), 'ServerSideRender uses the new block name' );
bafrt_assert( false === strpos( file_get_contents( dirname( __DIR__ ) . '/includes/class-bafrt-block.php' ), 'beforeUrl' ), 'preview URLs are not frontend image sources' );
bafrt_assert( false !== strpos( $admin_js, "addAttribute(parts, 'show_slider', 'off')" ), 'admin generator copies show_slider=off' );
bafrt_assert( false !== strpos( $block_js, "attributes.showSlider === false" ), 'Gutenberg copies show_slider=off' );
bafrt_assert( false !== strpos( $public_css, '.bafrt-compare--slider-hidden .bafrt-compare__range' ), 'hidden slider uses a scoped modifier' );
bafrt_assert( false !== strpos( $public_css, '.bafrt-compare--slider-hidden:focus-within' ), 'hidden slider has a visible focus indicator' );
bafrt_assert( false !== strpos( $public_css, 'pointer-events: none' ), 'hidden control does not intercept pointer input' );
bafrt_assert( false !== strpos( $public_css, 'clip-path: inset(0 calc(100% - var(--bafrt-position)) 0 0)' ), 'After layer clips image and nested label in the existing direction' );
bafrt_assert( false !== strpos( $public_css, '.bafrt-compare__divider' ) && false !== strpos( $public_css, 'z-index: 4' ), 'divider and handle remain above image layers' );
bafrt_assert( false !== strpos( $public_js, "stage.addEventListener('pointerdown'" ) && false !== strpos( $public_js, "stage.addEventListener('pointermove'" ), 'mouse and touch pointer logic remains present' );
bafrt_assert( false !== strpos( $public_js, "range.addEventListener('input'" ), 'native keyboard range changes remain synchronized' );
bafrt_assert( false !== strpos( file_get_contents( dirname( __DIR__ ) . '/includes/class-bafrt-renderer.php' ), 'get_block_wrapper_attributes( $root_attributes )' ), 'renderer uses WordPress block wrapper attributes' );
bafrt_assert( false !== strpos( $admin_css, '.bafrt-admin [hidden]' ), 'admin hidden controls cannot be overridden by field layout' );
bafrt_assert( false !== strpos( $admin_js, 'updateRatioControls();' ) && false !== strpos( $admin_js, 'selectedVariant(state.before)' ), 'admin Auto ratio follows selected Before variant' );
bafrt_assert( false !== strpos( $admin_php, 'value="3/2" data-bafrt-field="customRatio"' ) && false === strpos( $admin_php, 'data-bafrt-field="customRatio" inputmode="numeric" placeholder=' ), 'Custom ratio starts with a real 3/2 value' );
bafrt_assert( false !== strpos( $admin_php, "get_asset_version( 'assets/css/before-after-for-retouching-admin.css' )" ) && false !== strpos( $admin_php, "get_asset_version( 'assets/js/before-after-for-retouching-admin.js' )" ), 'admin ratio assets use cache-busting file versions' );
bafrt_assert( false !== strpos( $admin_css, 'justify-items: end' ) && false !== strpos( $admin_css, 'text-align: right' ), 'admin footer is compact and right-aligned' );
bafrt_assert( false !== strpos( $admin_css, '.bafrt-admin__footer a:hover' ) && false !== strpos( $admin_css, '.bafrt-admin__footer a:focus' ) && false !== strpos( $admin_css, '.bafrt-admin__footer a:focus-visible' ), 'admin footer links restore underlines for pointer and keyboard focus' );
bafrt_assert( false !== strpos( $admin_css, 'text-decoration: none' ) && false !== strpos( $admin_css, 'text-decoration: underline' ), 'admin footer link decoration states are present' );
$green_blog = 'green4' . '.photo';
$english_blog = 'yellowphotoschool' . '.com';
bafrt_assert( false === strpos( $block_js, $green_blog ) && false === strpos( $block_js, $english_blog ), 'blog domains are absent from Gutenberg JavaScript' );
bafrt_assert( false === strpos( $public_js, $green_blog ) && false === strpos( $public_js, $english_blog ), 'blog domains are absent from frontend JavaScript' );
bafrt_assert( false === strpos( file_get_contents( dirname( __DIR__ ) . '/includes/class-bafrt-renderer.php' ), $green_blog ), 'blog domain is absent from renderer' );

foreach (
	array(
		'ru_RU' => 'https://' . $green_blog . '/',
		'ru'    => 'https://' . $green_blog . '/',
		'uk_UA' => 'https://' . $green_blog . '/uk/',
		'uk'    => 'https://' . $green_blog . '/uk/',
		'en_GB' => 'https://' . $english_blog . '/',
	) as $locale => $expected_blog_url
) {
	$GLOBALS['bafrt_user_locale'] = $locale;
	ob_start();
	BAFRT_Admin::render_page();
	$admin_html = ob_get_clean();
	bafrt_assert( false !== strpos( $admin_html, 'href="' . $expected_blog_url . '" target="_blank" rel="noopener noreferrer"' ), "admin blog link for {$locale}" );
	bafrt_assert( false !== strpos( $admin_html, 'href="https://profiles.wordpress.org/borisph/" target="_blank" rel="noopener noreferrer"' ), 'author link opens safely in a new tab' );
}
$GLOBALS['bafrt_user_locale'] = 'en_US';
$textarea_position = strpos( $admin_html, 'id="bafrt-generated-shortcode"' );
$help_position = strpos( $admin_html, 'id="bafrt-shortcode-help" class="description"' );
$copy_position = strpos( $admin_html, 'data-bafrt-copy disabled' );
bafrt_assert( false !== $textarea_position && false !== $help_position && false !== $copy_position && $textarea_position < $help_position && $help_position < $copy_position, 'shortcode help is between textarea and Copy button' );
bafrt_assert( false !== strpos( $admin_html, 'aria-describedby="bafrt-shortcode-help"' ), 'shortcode textarea references its help text' );
$shortcode_help = 'Paste this shortcode into the post, page, or shortcode-compatible block where you want the image comparison to appear.';
bafrt_assert( false === strpos( $block_js, $shortcode_help ) && false === strpos( $public_js, $shortcode_help ), 'shortcode help is absent from Gutenberg and frontend JavaScript' );
bafrt_assert( false === strpos( BAFRT_Renderer::render( array( 'before' => 123, 'after' => 124 ) ), $shortcode_help ), 'shortcode help is absent from renderer output' );

$block_base = array( 'beforeId' => 123, 'afterId' => 124, 'ratio' => 'auto', 'imageSize' => 'full' );
foreach ( array( '' => '', 'wide' => 'alignwide', 'full' => 'alignfull' ) as $align => $expected_class ) {
	$GLOBALS['bafrt_block_align'] = $align;
	$block_html = BAFRT_Block::render( $block_base + ( $align ? array( 'align' => $align ) : array() ) );
	bafrt_assert( false !== strpos( $block_html, 'wp-block-before-after-for-retouching-compare' ), 'standard block wrapper class' );
	if ( $expected_class ) {
		bafrt_assert( false !== strpos( $block_html, $expected_class ), "block wrapper {$expected_class}" );
	}
}
$GLOBALS['bafrt_block_align'] = '';

$base = array( 'before' => '123', 'after' => '124' );
$html = BAFRT_Renderer::render( $base );
bafrt_assert( 3 === substr_count( $html, '<img ' ), 'renderer outputs both attachments and the noscript fallback' );
bafrt_assert( false !== strpos( $html, 'srcset=' ) && false !== strpos( $html, 'sizes=' ), 'responsive attributes are preserved' );
bafrt_assert( false !== strpos( $html, 'width=' ) && false !== strpos( $html, 'height=' ), 'intrinsic dimensions are preserved' );
bafrt_assert( false !== strpos( $html, '--bafrt-ratio:965/578' ), 'shortcode defaults to automatic Before image ratio' );
bafrt_assert( false !== strpos( $html, 'aria-valuetext="50% of the After image is visible"' ), 'server aria-valuetext contains one percent sign' );
bafrt_assert( false === strpos( $html, 'aria-valuetext="50%%' ), 'server aria-valuetext contains no double percent sign' );
bafrt_assert( false === strpos( $html, 'bafrt-compare--divider' ) && false === strpos( $html, 'bafrt-compare--handle' ), 'divider and handle default off' );
bafrt_assert( false === strpos( $html, 'wp-block-before-after-for-retouching-compare' ), 'shortcode renderer has no block classes' );
bafrt_assert( false === strpos( $html, 'bafrt-compare--slider-hidden' ), 'bottom slider is visible by default' );
bafrt_assert( 1 === substr_count( $html, 'class="bafrt-compare__label bafrt-compare__label--before"' ), 'renderer has one Before label element' );
bafrt_assert( 1 === substr_count( $html, 'class="bafrt-compare__label bafrt-compare__label--after"' ), 'renderer has one After label element' );
bafrt_assert( 1 === preg_match( '/bafrt-compare__layer--before[^>]*>\s*<img[^>]*bafrt-compare__image--before[^>]*>\s*<div[^>]*bafrt-compare__label--before[^>]*>.*?<\/div>\s*<\/div>\s*<div[^>]*bafrt-compare__layer--after/s', $html ), 'Before label is inside Before layer' );
bafrt_assert( 1 === preg_match( '/bafrt-compare__layer--after[^>]*>\s*<img[^>]*bafrt-compare__image--after[^>]*>\s*<div[^>]*bafrt-compare__label--after[^>]*>.*?<\/div>\s*<\/div>\s*<div[^>]*bafrt-compare__divider/s', $html ), 'After label is inside clipped After layer before divider' );
$start_zero = BAFRT_Renderer::render( $base + array( 'start' => 0 ) );
$start_full = BAFRT_Renderer::render( $base + array( 'start' => 100 ) );
bafrt_assert( false !== strpos( $start_zero, '--bafrt-position:0%' ), 'start=0 keeps the fully Before position' );
bafrt_assert( false !== strpos( $start_full, '--bafrt-position:100%' ), 'start=100 keeps the fully After position' );

$valid_ids = array( 123, '123', ' 123 ', '000123' );
foreach ( $valid_ids as $valid_id ) {
	bafrt_assert( 123 === BAFRT_Renderer::validate_attachment_id( $valid_id ), 'valid attachment ID: ' . var_export( $valid_id, true ) );
}
$invalid_ids = array( '', '0', 0, '-123', '+123', '123abc', '123.0', '1e3', '12 3', 'https://example.com/image.jpg', 123.0, true, array( 123 ), (object) array( 'id' => 123 ), 999, 125 );
foreach ( $invalid_ids as $invalid_id ) {
	bafrt_assert( 0 === BAFRT_Renderer::validate_attachment_id( $invalid_id ), 'invalid attachment ID: ' . gettype( $invalid_id ) );
}

$calls = $GLOBALS['bafrt_image_calls'];
bafrt_assert( ! isset( $calls[0]['attrs']['loading'] ), 'auto leaves loading to WordPress Core' );

foreach ( array( 'lazy', 'eager' ) as $loading ) {
	$GLOBALS['bafrt_image_calls'] = array();
	BAFRT_Renderer::render( $base + array( 'loading' => $loading ) );
	bafrt_assert( $loading === $GLOBALS['bafrt_image_calls'][0]['attrs']['loading'], "loading={$loading}" );
}
$GLOBALS['bafrt_image_calls'] = array();
BAFRT_Renderer::render( $base + array( 'loading' => 'invalid' ) );
bafrt_assert( ! isset( $GLOBALS['bafrt_image_calls'][0]['attrs']['loading'] ), 'invalid loading falls back to auto' );

$url_error = BAFRT_Renderer::render( array( 'before' => 'https://example.com/before.jpg', 'after' => 124 ) );
bafrt_assert( false !== strpos( $url_error, 'Both Before and After images are required.' ), 'direct URL is rejected' );
$id_error = BAFRT_Renderer::render( array( 'before' => 999, 'after' => 124 ) );
bafrt_assert( false !== strpos( $id_error, 'Both Before and After images are required.' ), 'nonexistent attachment is rejected' );
$format_error = BAFRT_Renderer::render( array( 'before' => '123abc', 'after' => 124 ) );
bafrt_assert( false !== strpos( $format_error, 'Both Before and After images are required.' ), 'malformed attachment ID is rejected with localized error' );

$label_cases = array(
	array( 'off', 'off', false, false ),
	array( 'on', 'off', true, false ),
	array( 'off', 'on', false, true ),
	array( 'on', 'on', true, true ),
);
foreach ( $label_cases as $case ) {
	$html = BAFRT_Renderer::render( $base + array( 'show_before_label' => $case[0], 'show_after_label' => $case[1] ) );
	bafrt_assert( $case[2] === ( false !== strpos( $html, 'bafrt-compare--label-before' ) ), 'Before label independence' );
	bafrt_assert( $case[3] === ( false !== strpos( $html, 'bafrt-compare--label-after' ) ), 'After label independence' );
}

$html = BAFRT_Renderer::render( $base + array( 'divider' => 'on', 'handle' => 'on' ) );
bafrt_assert( false !== strpos( $html, 'bafrt-compare--divider' ) && false !== strpos( $html, 'bafrt-compare--handle' ), 'divider and handle can be enabled' );
$html = BAFRT_Renderer::render( $base + array( 'ratio' => '16:9' ) );
bafrt_assert( false !== strpos( $html, '--bafrt-ratio:16/9' ), 'valid ratio is normalized' );
$html = BAFRT_Renderer::render( $base + array( 'ratio' => 'invalid' ) );
bafrt_assert( false !== strpos( $html, '--bafrt-ratio:3/2' ), 'invalid ratio falls back' );
$html = BAFRT_Renderer::render( $base + array( 'ratio' => 'auto', 'size' => 'full' ) );
bafrt_assert( false !== strpos( $html, '--bafrt-ratio:965/578' ), 'automatic ratio uses full dimensions' );
$html = BAFRT_Renderer::render( $base + array( 'ratio' => 'auto', 'size' => 'medium' ) );
bafrt_assert( false !== strpos( $html, '--bafrt-ratio:300/200' ), 'automatic ratio uses resized dimensions' );
$html = BAFRT_Renderer::render( $base + array( 'size' => 'custom-size' ) );
bafrt_assert( false !== strpos( $html, '--bafrt-ratio:400/300' ), 'default automatic ratio uses custom hard crop dimensions' );
$html = BAFRT_Renderer::render( $base + array( 'ratio' => 'auto', 'size' => 'unavailable-size' ) );
bafrt_assert( false !== strpos( $html, '--bafrt-ratio:965/578' ), 'unavailable attachment variant falls back to full dimensions' );

$sizes = BAFRT_Renderer::get_image_size_options();
bafrt_assert( isset( $sizes['custom-size'] ), 'dynamic image size is available' );
bafrt_assert( 'custom-size' === BAFRT_Renderer::sanitize_size( 'custom-size' ), 'registered size is accepted' );
bafrt_assert( 'full' === BAFRT_Renderer::sanitize_size( 'missing-size' ), 'missing size falls back to full' );

$slider_cases = array(
	array( 'on', 'off', 'off', false ),
	array( 'on', 'on', 'off', false ),
	array( 'off', 'on', 'off', true ),
	array( 'off', 'off', 'off', true ),
	array( 'off', 'off', 'on', true ),
);
foreach ( $slider_cases as $case ) {
	$html = BAFRT_Renderer::render( $base + array( 'show_slider' => $case[0], 'handle' => $case[1], 'divider' => $case[2] ) );
	bafrt_assert( $case[3] === ( false !== strpos( $html, 'bafrt-compare--slider-hidden' ) ), 'slider modifier combination' );
	bafrt_assert( false !== strpos( $html, 'type="range"' ), 'range remains in DOM' );
	bafrt_assert( false === strpos( $html, 'aria-hidden="true" type="range"' ), 'range remains available to assistive technology' );
}

BAFRT_Plugin::render_shortcode( $base );
bafrt_assert( isset( $GLOBALS['bafrt_enqueued_styles']['before-after-for-retouching'] ) && isset( $GLOBALS['bafrt_enqueued_scripts']['before-after-for-retouching'] ), 'late shortcode render keeps shared assets enqueued' );

$GLOBALS['bafrt_image_calls'] = array();
BAFRT_Renderer::render( $base + array( 'before_alt' => 'Before override', 'after_alt' => 'After override' ) );
bafrt_assert( 'Before override' === $GLOBALS['bafrt_image_calls'][0]['attrs']['alt'], 'Before alt override' );
bafrt_assert( 'After override' === $GLOBALS['bafrt_image_calls'][1]['attrs']['alt'], 'After alt override' );

echo "Smoke tests passed.\n";
