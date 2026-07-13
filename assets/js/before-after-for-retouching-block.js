(function (blocks, blockEditor, components, element, i18n, ServerSideRender) {
  'use strict';

  var el = element.createElement;
  var Fragment = element.Fragment;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var MediaUpload = blockEditor.MediaUpload;
  var MediaUploadCheck = blockEditor.MediaUploadCheck;
  var useBlockProps = blockEditor.useBlockProps;
  var PanelBody = components.PanelBody;
  var Button = components.Button;
  var SelectControl = components.SelectControl;
  var RangeControl = components.RangeControl;
  var ToggleControl = components.ToggleControl;
  var TextControl = components.TextControl;
  var Notice = components.Notice;
  var imageSizeOptions = window.bafrtBlockData && Array.isArray(window.bafrtBlockData.imageSizes)
    ? window.bafrtBlockData.imageSizes.map(function (size) {
      return { label: size.label, value: size.value };
    })
    : [{ label: 'Full [full]', value: 'full' }];

  function normalizeCustomRatio(value) {
    var normalized = String(value || '').trim().replace(/\s+/g, '');
    return /^[1-9]\d{0,4}\/[1-9]\d{0,4}$/.test(normalized) ? normalized : '';
  }

  function normalizeBlockRatio(attributes) {
    if (attributes.ratio === 'auto') {
      return 'auto';
    }

    if (attributes.ratio === 'custom') {
      return normalizeCustomRatio(attributes.customRatio) || '3/2';
    }

    return normalizeCustomRatio(attributes.ratio) || 'auto';
  }

  function normalizeImageSize(value) {
    var exists = imageSizeOptions.some(function (size) {
      return size.value === value;
    });

    return exists ? value : 'full';
  }

  function normalizeMediaSizes(media) {
    var sizes = {};

    Object.keys(media.sizes || {}).forEach(function (name) {
      var variant = media.sizes[name];
      if (variant && variant.url) {
        sizes[name] = {
          url: variant.url,
          width: Number(variant.width) || 0,
          height: Number(variant.height) || 0
        };
      }
    });

    if (media.url) {
      sizes.full = {
        url: media.url,
        width: Number(media.width) || 0,
        height: Number(media.height) || 0
      };
    }

    return sizes;
  }

  function previewVariant(sizes, selectedSize, fallback) {
    var variants = sizes || {};
    var order = [selectedSize, 'medium_large', 'large', 'medium', 'thumbnail'];
    var seen = {};

    for (var index = 0; index < order.length; index += 1) {
      var name = order[index];
      if (!name || seen[name]) continue;
      seen[name] = true;
      if (variants[name] && variants[name].url) {
        return variants[name];
      }
    }

    if (variants.full && variants.full.url) {
      return variants.full;
    }

    return fallback && fallback.url ? fallback : null;
  }

  function getRenderableAttributes(attributes) {
    var renderAttributes = Object.assign({}, attributes);
    var normalizedRatio = normalizeBlockRatio(attributes);

    renderAttributes.imageSize = normalizeImageSize(attributes.imageSize);
    if (attributes.ratio === 'custom') {
      renderAttributes.customRatio = normalizedRatio;
    } else {
      renderAttributes.ratio = normalizedRatio;
    }

    return renderAttributes;
  }

  function imageButton(label, id, url, onSelect, onRemove) {
    return el('div', { className: 'bafrt-block-image' },
      el('h3', {}, label),
      url
        ? el('img', { src: url, alt: '', className: 'bafrt-block-image__preview' })
        : el('div', { className: 'bafrt-block-image__empty' }, __('No image selected', 'before-after-for-retouching')),
      el(MediaUploadCheck, {},
        el(MediaUpload, {
          onSelect: onSelect,
          allowedTypes: ['image'],
          value: id,
          render: function (media) {
            return el(Button, { variant: 'secondary', onClick: media.open },
              url ? __('Replace image', 'before-after-for-retouching') : __('Select image', 'before-after-for-retouching')
            );
          }
        })
      ),
      url && el(Button, { variant: 'tertiary', isDestructive: true, onClick: onRemove }, __('Remove', 'before-after-for-retouching'))
    );
  }

  function hasRatioWarning(attributes) {
    if (!attributes.beforeWidth || !attributes.beforeHeight || !attributes.afterWidth || !attributes.afterHeight) {
      return false;
    }

    var beforeRatio = attributes.beforeWidth / attributes.beforeHeight;
    var afterRatio = attributes.afterWidth / attributes.afterHeight;
    return Math.abs(beforeRatio - afterRatio) / beforeRatio > 0.02;
  }

  function hasSizeWarning(attributes) {
    if (!attributes.beforeWidth || !attributes.beforeHeight || !attributes.afterWidth || !attributes.afterHeight) {
      return false;
    }

    var widthDifference = Math.abs(attributes.beforeWidth - attributes.afterWidth) / attributes.beforeWidth;
    var heightDifference = Math.abs(attributes.beforeHeight - attributes.afterHeight) / attributes.beforeHeight;
    return Math.max(widthDifference, heightDifference) > 0.20;
  }

  function shortcodeFromAttributes(attributes) {
    if (!attributes.beforeId || !attributes.afterId) {
      return '';
    }

    function clean(value) {
      return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/\[/g, '&#91;').replace(/\]/g, '&#93;');
    }

    function add(parts, name, value) {
      parts.push(name + '="' + clean(value) + '"');
    }

    var parts = ['before_after_retouching'];
    add(parts, 'before', attributes.beforeId);
    add(parts, 'after', attributes.afterId);
    add(parts, 'ratio', normalizeBlockRatio(attributes));
    add(parts, 'loading', attributes.loading);
    if (attributes.start !== 50) add(parts, 'start', attributes.start);
    if (attributes.fit !== 'contain') add(parts, 'fit', attributes.fit);
    if (attributes.interaction !== 'hover') add(parts, 'interaction', attributes.interaction);
    var imageSize = normalizeImageSize(attributes.imageSize);
    if (imageSize !== 'full') add(parts, 'size', imageSize);
    if (attributes.showDivider) add(parts, 'divider', 'on');
    if (attributes.showHandle) add(parts, 'handle', 'on');
    if (attributes.showSlider === false) add(parts, 'show_slider', 'off');
    if (attributes.showBeforeLabel) {
      add(parts, 'show_before_label', 'on');
      if (attributes.beforeLabel) add(parts, 'label_before', attributes.beforeLabel);
    }
    if (attributes.showAfterLabel) {
      add(parts, 'show_after_label', 'on');
      if (attributes.afterLabel) add(parts, 'label_after', attributes.afterLabel);
    }
    if (attributes.lineColor !== '#ffffff') add(parts, 'line_color', attributes.lineColor);
    if (attributes.lineWidth !== 2) add(parts, 'line_width', attributes.lineWidth);
    if (attributes.handleColor !== '#ffffff') add(parts, 'handle_color', attributes.handleColor);
    if (attributes.labelColor !== '#ffffff') add(parts, 'label_color', attributes.labelColor);
    if (attributes.labelBackground !== '#000000') add(parts, 'label_bg', attributes.labelBackground);
    return '[' + parts.join(' ') + ']';
  }

  blocks.registerBlockType('before-after-for-retouching/compare', {
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var bothSelected = attributes.beforeId && attributes.afterId;
      var copyStatus = element.useState('');
      var ratioDraftState = element.useState(attributes.customRatio || '3/2');
      var ratioDraft = ratioDraftState[0];
      var normalizedRatioDraft = normalizeCustomRatio(ratioDraft);
      var customRatioInvalid = attributes.ratio === 'custom' && !normalizedRatioDraft;
      var renderAttributes = getRenderableAttributes(attributes);
      var blockProps = useBlockProps({ className: 'bafrt-block' });

      element.useEffect(function () {
        var normalizedSize = normalizeImageSize(attributes.imageSize);
        if (normalizedSize !== attributes.imageSize) {
          setAttributes({ imageSize: normalizedSize });
        }
      }, [attributes.imageSize]);

      element.useEffect(function () {
        var selectedSize = normalizeImageSize(attributes.imageSize);
        var update = {};

        ['before', 'after'].forEach(function (prefix) {
          var variant = previewVariant(
            attributes[prefix + 'Sizes'],
            selectedSize,
            {
              url: attributes[prefix + 'Url'],
              width: attributes[prefix + 'Width'],
              height: attributes[prefix + 'Height']
            }
          );

          if (!variant) return;
          if (attributes[prefix + 'Url'] !== variant.url) update[prefix + 'Url'] = variant.url;
          if (attributes[prefix + 'Width'] !== variant.width) update[prefix + 'Width'] = variant.width;
          if (attributes[prefix + 'Height'] !== variant.height) update[prefix + 'Height'] = variant.height;
        });

        if (Object.keys(update).length) {
          setAttributes(update);
        }
      }, [attributes.imageSize, attributes.beforeSizes, attributes.afterSizes]);

      element.useEffect(function () {
        var normalizedStoredRatio = normalizeCustomRatio(attributes.customRatio) || '3/2';
        if (normalizedStoredRatio !== attributes.customRatio) {
          setAttributes({ customRatio: normalizedStoredRatio });
        }

        if (attributes.ratio !== 'auto' && attributes.ratio !== 'custom' && !normalizeCustomRatio(attributes.ratio)) {
          setAttributes({ ratio: 'auto' });
        }
      }, [attributes.customRatio, attributes.ratio]);

      function selectImage(prefix, media) {
        var update = {};
        var sizes = normalizeMediaSizes(media);
        var variant = previewVariant(sizes, normalizeImageSize(attributes.imageSize), {
          url: media.url || '',
          width: Number(media.width) || 0,
          height: Number(media.height) || 0
        });
        update[prefix + 'Id'] = Number(media.id) || 0;
        update[prefix + 'Sizes'] = sizes;
        update[prefix + 'Url'] = variant ? variant.url : '';
        update[prefix + 'Width'] = variant ? variant.width : 0;
        update[prefix + 'Height'] = variant ? variant.height : 0;
        setAttributes(update);
      }

      function removeImage(prefix) {
        var update = {};
        update[prefix + 'Id'] = 0;
        update[prefix + 'Url'] = '';
        update[prefix + 'Sizes'] = {};
        update[prefix + 'Width'] = 0;
        update[prefix + 'Height'] = 0;
        setAttributes(update);
      }

      function copyShortcode() {
        var value = shortcodeFromAttributes(attributes);
        if (!value) return;

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(value).then(function () {
            copyStatus[1](__('Shortcode copied.', 'before-after-for-retouching'));
          });
        } else {
          copyStatus[1](__('Copy the shortcode from the Media → Before & After generator.', 'before-after-for-retouching'));
        }
      }

      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: __('Comparison settings', 'before-after-for-retouching'), initialOpen: true },
            el(SelectControl, {
              label: __('Aspect ratio', 'before-after-for-retouching'),
              value: attributes.ratio,
              options: [
                { label: __('Auto (Before image)', 'before-after-for-retouching'), value: 'auto' },
                { label: '1:1', value: '1/1' },
                { label: '4:3', value: '4/3' },
                { label: '3:2', value: '3/2' },
                { label: '16:9', value: '16/9' },
                { label: __('Custom', 'before-after-for-retouching'), value: 'custom' }
              ],
              onChange: function (value) { setAttributes({ ratio: value }); }
            }),
            attributes.ratio === 'custom' && el(TextControl, {
              label: __('Custom ratio', 'before-after-for-retouching'),
              value: ratioDraft,
              onChange: function (value) {
                var normalized = normalizeCustomRatio(value);
                ratioDraftState[1](value);
                if (normalized) {
                  setAttributes({ customRatio: normalized });
                }
              }
            }),
            customRatioInvalid && el(Notice, { status: 'warning', isDismissible: false }, __('Enter a custom ratio in width/height format, for example 3/2.', 'before-after-for-retouching')),
            el(RangeControl, {
              label: __('Initial position', 'before-after-for-retouching'),
              value: attributes.start,
              min: 0,
              max: 100,
              onChange: function (value) { setAttributes({ start: value }); }
            }),
            el(SelectControl, {
              label: __('Image fit', 'before-after-for-retouching'),
              value: attributes.fit,
              options: [
                { label: __('Contain (no cropping)', 'before-after-for-retouching'), value: 'contain' },
                { label: __('Cover (may crop)', 'before-after-for-retouching'), value: 'cover' }
              ],
              onChange: function (value) { setAttributes({ fit: value }); }
            }),
            el(SelectControl, {
              label: __('Pointer behavior', 'before-after-for-retouching'),
              value: attributes.interaction,
              options: [
                { label: __('Follow pointer', 'before-after-for-retouching'), value: 'hover' },
                { label: __('Click and drag', 'before-after-for-retouching'), value: 'drag' }
              ],
              onChange: function (value) { setAttributes({ interaction: value }); }
            }),
            el(SelectControl, {
              label: __('Loading', 'before-after-for-retouching'),
              value: attributes.loading,
              options: [
                { label: __('Auto (recommended)', 'before-after-for-retouching'), value: 'auto' },
                { label: __('Lazy', 'before-after-for-retouching'), value: 'lazy' },
                { label: __('Eager', 'before-after-for-retouching'), value: 'eager' }
              ],
              onChange: function (value) { setAttributes({ loading: value }); }
            }),
            el(SelectControl, {
              label: __('WordPress image size', 'before-after-for-retouching'),
              value: normalizeImageSize(attributes.imageSize),
              options: imageSizeOptions,
              onChange: function (value) { setAttributes({ imageSize: value }); }
            })
          ),
          el(PanelBody, { title: __('Appearance', 'before-after-for-retouching'), initialOpen: false },
            el(ToggleControl, { label: __('Show bottom slider', 'before-after-for-retouching'), checked: attributes.showSlider !== false, onChange: function (value) { setAttributes({ showSlider: value }); } }),
            el(ToggleControl, { label: __('Show divider', 'before-after-for-retouching'), checked: attributes.showDivider, onChange: function (value) { setAttributes({ showDivider: value }); } }),
            el(ToggleControl, { label: __('Show handle', 'before-after-for-retouching'), checked: attributes.showHandle, onChange: function (value) { setAttributes({ showHandle: value }); } }),
            el(ToggleControl, { label: __('Show Before label', 'before-after-for-retouching'), checked: attributes.showBeforeLabel, onChange: function (value) { setAttributes({ showBeforeLabel: value }); } }),
            attributes.showBeforeLabel && el(TextControl, { label: __('Before label', 'before-after-for-retouching'), value: attributes.beforeLabel || __('Before', 'before-after-for-retouching'), onChange: function (value) { setAttributes({ beforeLabel: value }); } }),
            el(ToggleControl, { label: __('Show After label', 'before-after-for-retouching'), checked: attributes.showAfterLabel, onChange: function (value) { setAttributes({ showAfterLabel: value }); } }),
            attributes.showAfterLabel && el(TextControl, { label: __('After label', 'before-after-for-retouching'), value: attributes.afterLabel || __('After', 'before-after-for-retouching'), onChange: function (value) { setAttributes({ afterLabel: value }); } }),
            el(TextControl, { label: __('Divider color', 'before-after-for-retouching'), type: 'color', value: attributes.lineColor, onChange: function (value) { setAttributes({ lineColor: value }); } }),
            el(RangeControl, { label: __('Divider width', 'before-after-for-retouching'), value: attributes.lineWidth, min: 1, max: 20, onChange: function (value) { setAttributes({ lineWidth: value }); } }),
            el(TextControl, { label: __('Handle color', 'before-after-for-retouching'), type: 'color', value: attributes.handleColor, onChange: function (value) { setAttributes({ handleColor: value }); } }),
            el(TextControl, { label: __('Label text color', 'before-after-for-retouching'), type: 'color', value: attributes.labelColor, onChange: function (value) { setAttributes({ labelColor: value }); } }),
            el(TextControl, { label: __('Label background', 'before-after-for-retouching'), type: 'color', value: attributes.labelBackground, onChange: function (value) { setAttributes({ labelBackground: value }); } })
          ),
          el(PanelBody, { title: __('Shortcode', 'before-after-for-retouching'), initialOpen: false },
            el('textarea', { className: 'components-textarea-control__input', readOnly: true, rows: 5, value: shortcodeFromAttributes(attributes) }),
            el(Button, { variant: 'secondary', disabled: !bothSelected, onClick: copyShortcode }, __('Copy shortcode', 'before-after-for-retouching')),
            copyStatus[0] && el('p', {}, copyStatus[0])
          )
        ),
        el('div', blockProps,
          el('div', { className: 'bafrt-block-images' },
            imageButton(__('Before image', 'before-after-for-retouching'), attributes.beforeId, attributes.beforeUrl, function (media) { selectImage('before', media); }, function () { removeImage('before'); }),
            imageButton(__('After image', 'before-after-for-retouching'), attributes.afterId, attributes.afterUrl, function (media) { selectImage('after', media); }, function () { removeImage('after'); })
          ),
          hasRatioWarning(attributes) && el(Notice, { status: 'warning', isDismissible: false }, __('The selected images have noticeably different aspect ratios. Alignment may be inaccurate.', 'before-after-for-retouching')),
          hasSizeWarning(attributes) && el(Notice, { status: 'warning', isDismissible: false }, __('The selected images have noticeably different pixel dimensions. For the best comparison, use matching exports.', 'before-after-for-retouching')),
          bothSelected
            ? el(ServerSideRender, { block: 'before-after-for-retouching/compare', attributes: renderAttributes })
            : el('p', { className: 'bafrt-block-help' }, __('Select both images to preview the comparison.', 'before-after-for-retouching'))
        )
      );
    },
    save: function () {
      return null;
    }
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender);
