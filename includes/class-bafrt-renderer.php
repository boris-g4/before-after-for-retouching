<?php
/**
 * Shared frontend renderer.
 *
 * @package BeforeAfterForRetouching
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Produces the same accessible markup for shortcodes and blocks.
 */
final class BAFRT_Renderer {

	/**
	 * Render a comparison.
	 *
	 * @param array $attributes        Raw renderer attributes.
	 * @param bool  $use_block_wrapper Whether to merge WordPress block wrapper attributes.
	 * @return string
	 */
	public static function render( $attributes, $use_block_wrapper = false ) {
		$attributes = shortcode_atts(
			array(
				'before'       => '',
				'after'        => '',
				'ratio'        => 'auto',
				'start'        => '50',
				'fit'          => 'contain',
				'interaction'  => 'hover',
				'loading'      => 'auto',
				'before_alt'   => '',
				'after_alt'    => '',
				'size'         => 'full',
				'class'        => '',
				'divider'      => 'off',
				'handle'       => 'off',
				'show_slider'  => 'on',
				'show_before_label' => 'off',
				'show_after_label'  => 'off',
				'label_before' => __( 'Before', 'before-after-for-retouching' ),
				'label_after'  => __( 'After', 'before-after-for-retouching' ),
				'line_color'   => '#ffffff',
				'line_width'   => '2',
				'handle_color' => '#ffffff',
				'label_color'  => '#ffffff',
				'label_bg'     => '#000000',
			),
			$attributes,
			'before_after_retouching'
		);

		$before = self::validate_attachment_id( $attributes['before'] );
		$after  = self::validate_attachment_id( $attributes['after'] );

		if ( 0 === $before || 0 === $after ) {
			return self::editor_error( __( 'Both Before and After images are required.', 'before-after-for-retouching' ) );
		}

		$size         = self::sanitize_size( $attributes['size'] );
		$ratio        = self::sanitize_ratio( $attributes['ratio'], $before, $size );
		$start        = self::sanitize_number( $attributes['start'], 50, 0, 100, 1 );
		$fit          = self::allowed_value( $attributes['fit'], array( 'contain', 'cover' ), 'contain' );
		$interaction  = self::allowed_value( $attributes['interaction'], array( 'hover', 'drag' ), 'hover' );
		$loading      = self::sanitize_loading( $attributes );
		$show_divider = self::is_on( $attributes['divider'] );
		$show_handle  = self::is_on( $attributes['handle'] );
		$show_slider  = self::is_on( $attributes['show_slider'] );
		$show_before_label = self::is_on( $attributes['show_before_label'] );
		$show_after_label  = self::is_on( $attributes['show_after_label'] );

		$label_before = self::sanitize_label( $attributes['label_before'], __( 'Before', 'before-after-for-retouching' ) );
		$label_after  = self::sanitize_label( $attributes['label_after'], __( 'After', 'before-after-for-retouching' ) );
		$line_color   = self::sanitize_color( $attributes['line_color'], '#ffffff' );
		$line_width   = self::sanitize_number( $attributes['line_width'], 2, 1, 20, 0 );
		$handle_color = self::sanitize_color( $attributes['handle_color'], '#ffffff' );
		$label_color  = self::sanitize_color( $attributes['label_color'], '#ffffff' );
		$label_bg     = self::sanitize_color( $attributes['label_bg'], '#000000' );

		$before_html = self::get_image_html(
			$before,
			$size,
			'bafrt-compare__image bafrt-compare__image--before',
			self::sanitize_alt( $attributes['before_alt'] ),
			$loading
		);
		$after_html  = self::get_image_html(
			$after,
			$size,
			'bafrt-compare__image bafrt-compare__image--after',
			self::sanitize_alt( $attributes['after_alt'] ),
			$loading
		);

		if ( '' === $before_html || '' === $after_html ) {
			return self::editor_error( __( 'An image could not be found or is not a valid image attachment.', 'before-after-for-retouching' ) );
		}

		$uid     = 'bafrt-' . wp_generate_uuid4();
		$classes = array( 'bafrt-compare' );

		if ( $show_divider ) {
			$classes[] = 'bafrt-compare--divider';
		}
		if ( $show_handle ) {
			$classes[] = 'bafrt-compare--handle';
		}
		if ( ! $show_slider ) {
			$classes[] = 'bafrt-compare--slider-hidden';
		}
		if ( $show_before_label ) {
			$classes[] = 'bafrt-compare--label-before';
		}
		if ( $show_after_label ) {
			$classes[] = 'bafrt-compare--label-after';
		}

		$custom_class = trim( preg_replace( '/[^A-Za-z0-9_\-\s]/', '', (string) $attributes['class'] ) );
		if ( '' !== $custom_class ) {
			$classes[] = $custom_class;
		}

		$style = sprintf(
			'--bafrt-position:%1$s%%;--bafrt-ratio:%2$s;--bafrt-fit:%3$s;--bafrt-line-color:%4$s;--bafrt-line-width:%5$spx;--bafrt-handle-color:%6$s;--bafrt-label-color:%7$s;--bafrt-label-bg:%8$s;',
			$start,
			$ratio,
			$fit,
			$line_color,
			$line_width,
			$handle_color,
			$label_color,
			$label_bg
		);

		/* translators: 1: Percentage, 2: After label. */
		$value_text = sprintf( __( '%1$s%% of the %2$s image is visible', 'before-after-for-retouching' ), $start, $label_after );
		$root_attributes = array(
			'id'                     => $uid,
			'class'                  => implode( ' ', $classes ),
			'style'                  => $style,
			'data-bafrt-compare'     => '',
			'data-start'             => $start,
			'data-interaction'       => $interaction,
			'role'                   => 'group',
			'aria-label'             => __( 'Before and after image comparison', 'before-after-for-retouching' ),
		);
		$root_attribute_html = $use_block_wrapper
			? get_block_wrapper_attributes( $root_attributes )
			: self::format_html_attributes( $root_attributes );

		ob_start();
		?>
		<div <?php echo $root_attribute_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="bafrt-compare__stage" data-bafrt-stage>
				<div class="bafrt-compare__layer bafrt-compare__layer--before">
					<?php echo $before_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bafrt-compare__layer bafrt-compare__layer--after">
					<?php echo $after_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="bafrt-compare__label bafrt-compare__label--after" aria-hidden="true"><?php echo esc_html( $label_after ); ?></div>
				<div class="bafrt-compare__label bafrt-compare__label--before" aria-hidden="true"><?php echo esc_html( $label_before ); ?></div>
				<div class="bafrt-compare__divider" aria-hidden="true">
					<span class="bafrt-compare__handle"></span>
				</div>
			</div>
			<p id="<?php echo esc_attr( $uid . '-instructions' ); ?>" class="bafrt-compare__screen-reader-text"><?php echo esc_html__( 'Use the Left and Right arrow keys to adjust the comparison.', 'before-after-for-retouching' ); ?></p>
			<input class="bafrt-compare__range" data-bafrt-range type="range" min="0" max="100" step="0.1" value="<?php echo esc_attr( $start ); ?>" aria-label="<?php echo esc_attr__( 'Before and after comparison position', 'before-after-for-retouching' ); ?>" aria-valuetext="<?php echo esc_attr( $value_text ); ?>" aria-describedby="<?php echo esc_attr( $uid . '-instructions' ); ?>" data-value-label="<?php echo esc_attr( $label_after ); ?>" data-value-template="<?php echo esc_attr__( '%1$s%% of the %2$s image is visible', 'before-after-for-retouching' ); ?>">
		</div>
		<noscript>
			<style>#<?php echo esc_html( $uid ); ?>{display:none!important;}</style>
			<div class="bafrt-compare__noscript">
				<?php echo $after_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</noscript>
		<?php
		return trim( ob_get_clean() );
	}

	/**
	 * Validate an image attachment ID.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function validate_attachment_id( $value ) {
		if ( is_int( $value ) ) {
			$raw_value = (string) $value;
		} elseif ( is_string( $value ) ) {
			$raw_value = trim( $value );
		} else {
			return 0;
		}

		if ( ! preg_match( '/^[0-9]+$/D', $raw_value ) ) {
			return 0;
		}

		$attachment_id = absint( $raw_value );

		return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
	}

	/**
	 * Produce an image element.
	 *
	 * @param int    $value        Attachment ID.
	 * @param string $size         Registered image size.
	 * @param string $class        CSS classes.
	 * @param string $alt_override Optional alternative text.
	 * @param string $loading      Loading mode.
	 * @return string
	 */
	private static function get_image_html( $value, $size, $class, $alt_override, $loading ) {
		$attrs = array(
			'class'    => $class,
			'decoding' => 'async',
		);

		if ( '' !== $alt_override ) {
			$attrs['alt'] = $alt_override;
		}
		if ( in_array( $loading, array( 'lazy', 'eager' ), true ) ) {
			$attrs['loading'] = $loading;
		}
		$html = wp_get_attachment_image( absint( $value ), $size, false, $attrs );

		return $html ? $html : '';
	}

	/**
	 * Resolve an explicit or attachment-derived aspect ratio.
	 *
	 * @param mixed  $ratio  Raw ratio.
	 * @param int    $before Before image attachment ID.
	 * @param string $size   Validated image size.
	 * @return string
	 */
	private static function sanitize_ratio( $ratio, $before, $size ) {
		$ratio = strtolower( trim( (string) $ratio ) );

		if ( 'auto' === $ratio && $before > 0 ) {
			$image = wp_get_attachment_image_src( $before, $size );
			if ( ( ! is_array( $image ) || empty( $image[1] ) || empty( $image[2] ) ) && 'full' !== $size ) {
				$image = wp_get_attachment_image_src( $before, 'full' );
			}

			if ( is_array( $image ) ) {
				$width  = isset( $image[1] ) ? absint( $image[1] ) : 0;
				$height = isset( $image[2] ) ? absint( $image[2] ) : 0;
				if ( $width > 0 && $height > 0 ) {
					return $width . '/' . $height;
				}
			}
		}

		$ratio = str_replace( ':', '/', $ratio );
		$ratio = preg_replace( '/\s+/', '', $ratio );

		if ( preg_match( '/^([1-9][0-9]{0,4})\/([1-9][0-9]{0,4})$/', $ratio, $matches ) ) {
			return absint( $matches[1] ) . '/' . absint( $matches[2] );
		}

		return '3/2';
	}

	/**
	 * Validate the loading mode.
	 *
	 * @param array $attributes Renderer attributes.
	 * @return string
	 */
	private static function sanitize_loading( $attributes ) {
		$loading = strtolower( trim( (string) $attributes['loading'] ) );
		if ( in_array( $loading, array( 'auto', 'lazy', 'eager' ), true ) ) {
			return $loading;
		}

		return 'auto';
	}

	/**
	 * Validate a registered image size.
	 *
	 * @param mixed $size Raw size.
	 * @return string
	 */
	public static function sanitize_size( $size ) {
		$size    = sanitize_key( $size );
		$allowed = array_keys( self::get_image_size_options() );

		return in_array( $size, $allowed, true ) ? $size : 'full';
	}

	/**
	 * Return all image sizes registered in the current WordPress installation.
	 *
	 * The same data powers the admin generator and Gutenberg controls.
	 *
	 * @return array<string,array{value:string,label:string,width:int,height:int}>
	 */
	public static function get_image_size_options() {
		$options = array(
			'full' => array(
				'value'  => 'full',
				'label'  => __( 'Full', 'before-after-for-retouching' ) . ' [full]',
				'width'  => 0,
				'height' => 0,
			),
		);

		foreach ( wp_get_registered_image_subsizes() as $slug => $details ) {
			$slug   = sanitize_key( $slug );
			if ( '' === $slug || 'full' === $slug ) {
				continue;
			}

			$width  = isset( $details['width'] ) ? absint( $details['width'] ) : 0;
			$height = isset( $details['height'] ) ? absint( $details['height'] ) : 0;
			$label  = ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ) . ' [' . $slug . ']';

			if ( $width > 0 && $height > 0 ) {
				$label .= sprintf( ' — %1$d × %2$d', $width, $height );
			}

			$options[ $slug ] = array(
				'value'  => $slug,
				'label'  => $label,
				'width'  => $width,
				'height' => $height,
			);
		}

		return $options;
	}

	/**
	 * Sanitize a translated or custom label.
	 *
	 * @param mixed  $value    Raw label.
	 * @param string $fallback Fallback label.
	 * @return string
	 */
	private static function sanitize_label( $value, $fallback ) {
		$value = sanitize_text_field( html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) );

		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Sanitize alternative text.
	 *
	 * @param mixed $value Raw text.
	 * @return string
	 */
	private static function sanitize_alt( $value ) {
		return sanitize_text_field( html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) );
	}

	/**
	 * Validate a hex color.
	 *
	 * @param mixed  $value    Raw color.
	 * @param string $fallback Default color.
	 * @return string
	 */
	private static function sanitize_color( $value, $fallback ) {
		$color = sanitize_hex_color( (string) $value );

		return $color ? $color : $fallback;
	}

	/**
	 * Clamp and format a number.
	 *
	 * @param mixed $value    Raw value.
	 * @param float $fallback Default value.
	 * @param float $minimum  Minimum value.
	 * @param float $maximum  Maximum value.
	 * @param int   $decimals Decimal places.
	 * @return string
	 */
	private static function sanitize_number( $value, $fallback, $minimum, $maximum, $decimals ) {
		$value = is_numeric( $value ) ? (float) $value : (float) $fallback;
		$value = max( $minimum, min( $maximum, $value ) );
		$value = number_format( $value, $decimals, '.', '' );

		return $decimals > 0 ? rtrim( rtrim( $value, '0' ), '.' ) : $value;
	}

	/**
	 * Select a value from an allowlist.
	 *
	 * @param mixed  $value    Raw value.
	 * @param array  $allowed  Allowed values.
	 * @param string $fallback Default value.
	 * @return string
	 */
	private static function allowed_value( $value, $allowed, $fallback ) {
		$value = strtolower( trim( (string) $value ) );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Interpret common on values.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function is_on( $value ) {
		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'on', 'true', 'yes' ), true );
	}

	/**
	 * Format already-sanitized root attributes for shortcode output.
	 *
	 * @param array $attributes Attribute map.
	 * @return string
	 */
	private static function format_html_attributes( $attributes ) {
		$html = array();
		foreach ( $attributes as $name => $value ) {
			$html[] = sprintf( '%1$s="%2$s"', esc_attr( $name ), esc_attr( $value ) );
		}

		return implode( ' ', $html );
	}

	/**
	 * Return a non-public diagnostic for editors.
	 *
	 * @param string $message Error message.
	 * @return string
	 */
	private static function editor_error( $message ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return sprintf( '<!-- Before & After for Retouching: %s -->', esc_html( $message ) );
		}

		return '';
	}
}
