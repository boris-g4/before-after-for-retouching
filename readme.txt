=== Before & After for Retouching ===
Contributors: borisph
Tags: before after, image comparison, photo retouching, slider, gutenberg
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An accessible before-and-after image comparison block and shortcode with a visual generator.

== Description ==

Before & After for Retouching presents image edits with an accessible interactive comparison.

Choose two images from the WordPress Media Library, preview the comparison, and copy a ready-to-use shortcode. You can also add the dynamic Before & After Comparison block directly in the block editor.

The dynamic block name is `before-after-for-retouching/compare`.

Features:

* Pointer-follow comparison designed for retouching work.
* Optional click-and-drag interaction.
* Dynamic Gutenberg block.
* Visual shortcode generator under Media > Before & After.
* One-click shortcode copy.
* Automatic aspect ratio based on the Before image.
* Contain and Cover image fitting.
* Optional divider, handle, and independently controlled Before/After labels. Labels are disabled by default.
* A bottom range slider that is visible by default and can be visually hidden without disabling keyboard access.
* Configurable colors and divider width.
* Image dimension and aspect-ratio warnings.
* Responsive WordPress image sizes.
* Automatic, lazy, and eager loading modes.
* Keyboard and screen-reader support.
* English, Ukrainian, and Russian translations.
* No additional jQuery dependencies, tracking, remote scripts, or dependencies on external services.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the ZIP through Plugins > Add New.
2. Activate Before & After for Retouching.
3. Open Media > Before & After to generate a shortcode, or add the Before & After Comparison block in the editor.
4. Select the Before and After images and adjust the settings.
5. Insert the block or copy the generated shortcode into any shortcode-compatible location.

== Shortcode ==

Basic shortcode:

`[before_after_retouching before="123" after="124"]`

Recommended generator output:

`[before_after_retouching before="123" after="124" ratio="auto" loading="auto"]`

Hide the visible bottom slider while keeping keyboard control:

`[before_after_retouching before="123" after="124" show_slider="off" handle="on"]`

Available attributes:

* `before`: Required valid positive WordPress image attachment ID from the Media Library.
* `after`: Required valid positive WordPress image attachment ID from the Media Library.
* `ratio`: `auto` or a width/height ratio such as `3/2`, `4/3`, or `16/9`. Defaults to `auto`.
* `start`: Initial comparison position from `0` to `100`.
* `fit`: `contain` or `cover`.
* `interaction`: `hover` to follow the pointer or `drag` for click-and-drag behavior.
* `loading`: `auto`, `lazy`, or `eager`.
* `size`: A registered WordPress image size. Defaults to `full`.
* `divider`: `on` or `off`. Defaults to `off`.
* `handle`: `on` or `off`. Defaults to `off`.
* `show_slider`: `on` or `off`. The bottom range control is visible by default. When set to `off`, it is visually hidden but remains available to keyboard and screen-reader users.
* `show_before_label`: `on` or `off`. Enables the Before label independently and defaults to `off`.
* `show_after_label`: `on` or `off`. Enables the After label independently and defaults to `off`.
* `label_before`: Custom Before label.
* `label_after`: Custom After label.
* `line_color`: Divider hex color.
* `line_width`: Divider width from `1` to `20` pixels.
* `handle_color`: Handle hex color.
* `label_color`: Label text hex color.
* `label_bg`: Label background hex color.
* `before_alt`: Optional alternative text override for the Before image.
* `after_alt`: Optional alternative text override for the After image.
* `class`: Optional additional CSS class.

The `before` and `after` values accept attachment IDs only. Select images through the WordPress Media Library; direct image URLs are rejected.

== Frequently Asked Questions ==

= Does the plugin modify my images? =

No. It displays existing Media Library images and does not create or alter image files.

= Should the two images have the same dimensions? =

Matching dimensions and composition provide the most accurate comparison. The generator and block editor warn when selected images differ noticeably.

= Why does the comparison follow the mouse pointer? =

Pointer-follow mode is the default because it provides fast visual inspection for photographers and retouchers. Select Click and drag if you prefer conventional slider behavior.

= Can I hide the slider, divider, handle, and labels? =

Yes. The bottom slider is visible by default and can be visually hidden without disabling keyboard accessibility. The divider, handle, Before label, and After label remain independent; divider, handle, and labels are disabled by default.

= Does it work with page builders? =

The shortcode works anywhere that processes standard WordPress shortcodes. Compatibility depends on the content element used by the page builder.

= Is JavaScript required? =

JavaScript provides interaction. When JavaScript is unavailable, the After image is displayed as a fallback.

== Changelog ==

= 1.0.0 =

* First public release.
* Added a visual shortcode generator with Media Library selection and live preview.
* Added a dynamic Gutenberg block.
* Added automatic aspect ratio, labels, divider, handle, loading, and appearance controls.
* Added image dimension and aspect-ratio warnings.
* Added one-click shortcode copying.
* Added Ukrainian and Russian translations.
* Improved keyboard and screen-reader information.
* Added server-side attachment validation and public-plugin metadata.
