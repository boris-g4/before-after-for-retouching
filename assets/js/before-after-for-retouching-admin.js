(function () {
  'use strict';

  var root = document.querySelector('[data-bafrt-generator]');

  if (!root || typeof window.wp === 'undefined' || !wp.media) {
    return;
  }

  var state = {
    before: null,
    after: null
  };
  var fields = {};
  var shortcode = root.querySelector('[data-bafrt-shortcode]');
  var copyButton = root.querySelector('[data-bafrt-copy]');
  var copyStatus = root.querySelector('[data-bafrt-copy-status]');
  var swapButton = root.querySelector('[data-bafrt-swap]');
  var preview = root.querySelector('[data-bafrt-preview]');
  var previewEmpty = root.querySelector('[data-bafrt-preview-empty]');
  var ratioWarning = root.querySelector('[data-bafrt-ratio-warning]');
  var sizeWarning = root.querySelector('[data-bafrt-size-warning]');
  var startOutput = root.querySelector('[data-bafrt-start-output]');
  var customRatioWrap = root.querySelector('[data-bafrt-custom-ratio-wrap]');
  var currentRatio = root.querySelector('[data-bafrt-current-ratio]');

  root.querySelectorAll('[data-bafrt-field]').forEach(function (field) {
    fields[field.getAttribute('data-bafrt-field')] = field;
  });

  function normalizeVariant(variant) {
    if (!variant || !variant.url) {
      return null;
    }

    return {
      url: variant.url,
      width: Number(variant.width) || 0,
      height: Number(variant.height) || 0
    };
  }

  function normalizeSizes(data) {
    var sizes = {};

    Object.keys(data.sizes || {}).forEach(function (name) {
      var variant = normalizeVariant(data.sizes[name]);
      if (variant) {
        sizes[name] = variant;
      }
    });

    if (data.url) {
      sizes.full = {
        url: data.url,
        width: Number(data.width) || 0,
        height: Number(data.height) || 0
      };
    }

    return sizes;
  }

  function normalizeAttachment(data) {
    return {
      id: Number(data.id) || 0,
      alt: data.alt || '',
      filename: data.filename || '',
      sizes: normalizeSizes(data)
    };
  }

  function selectedVariant(attachment) {
    if (!attachment) {
      return null;
    }

    var selectedSize = fields.size ? fields.size.value : 'full';
    return attachment.sizes[selectedSize] || attachment.sizes.full || null;
  }

  function openMedia(type) {
    var frame = wp.media({
      title: type === 'before' ? bafrtAdmin.beforeTitle : bafrtAdmin.afterTitle,
      button: { text: bafrtAdmin.useImage },
      library: { type: 'image' },
      multiple: false
    });

    frame.on('select', function () {
      state[type] = normalizeAttachment(frame.state().get('selection').first().toJSON());
      updatePicker(type);
      updateAll();
    });

    frame.open();
  }

  function updatePicker(type) {
    var picker = root.querySelector('[data-bafrt-picker="' + type + '"]');
    var image = picker.querySelector('img');
    var empty = picker.querySelector('.bafrt-image-picker__preview span');
    var remove = picker.querySelector('[data-bafrt-remove]');
    var meta = picker.querySelector('[data-bafrt-image-meta]');
    var id = picker.querySelector('[data-bafrt-image-id]');
    var item = state[type];
    var variant = selectedVariant(item);

    if (!item || !variant) {
      image.hidden = true;
      image.removeAttribute('src');
      empty.hidden = false;
      remove.hidden = true;
      meta.textContent = '';
      id.value = '';
      return;
    }

    image.src = variant.url;
    image.alt = item.alt;
    image.hidden = false;
    empty.hidden = true;
    remove.hidden = false;
    id.value = item.id;
    meta.textContent = item.filename + (variant.width && variant.height ? ' — ' + variant.width + ' × ' + variant.height + ' px' : '');
  }

  function getRatioValue() {
    if (fields.ratio.value === 'custom') {
      var custom = fields.customRatio.value.replace(':', '/').replace(/\s/g, '');
      return /^[1-9]\d{0,4}\/[1-9]\d{0,4}$/.test(custom) ? custom : '';
    }

    return fields.ratio.value;
  }

  function ratioForPreview() {
    var ratio = getRatioValue();

    if (ratio === 'auto') {
      var beforeVariant = selectedVariant(state.before);
      return beforeVariant && beforeVariant.width && beforeVariant.height
        ? beforeVariant.width + ' / ' + beforeVariant.height
        : '3 / 2';
    }

    return ratio ? ratio.replace('/', ' / ') : '3 / 2';
  }

  function toggleClass(element, className, enabled) {
    if (enabled) {
      element.classList.add(className);
    } else {
      element.classList.remove(className);
    }
  }

  function formatValueText(template, value, label) {
    return String(template || '%1$s%%').replace(/%1\$s|%2\$s|%%/g, function (token) {
      if (token === '%1$s') return value;
      if (token === '%2$s') return label;
      return '%';
    });
  }

  function updateRatioControls() {
    var mode = fields.ratio.value;
    var beforeVariant;

    customRatioWrap.hidden = mode !== 'custom';
    currentRatio.hidden = mode !== 'auto';

    if (mode !== 'auto') {
      return;
    }

    beforeVariant = selectedVariant(state.before);
    currentRatio.textContent = beforeVariant && beforeVariant.width && beforeVariant.height
      ? formatValueText(bafrtAdmin.currentRatio, beforeVariant.width, beforeVariant.height)
      : bafrtAdmin.selectBefore;
  }

  function updatePreview() {
    var ready = Boolean(state.before && state.after);
    var beforeImage = preview.querySelector('.bafrt-compare__image--before');
    var afterImage = preview.querySelector('.bafrt-compare__image--after');
    var range = preview.querySelector('[data-bafrt-range]');
    var beforeLabel = fields.beforeLabel.value.trim() || bafrtAdmin.defaultBefore;
    var afterLabel = fields.afterLabel.value.trim() || bafrtAdmin.defaultAfter;
    var beforeVariant = selectedVariant(state.before);
    var afterVariant = selectedVariant(state.after);

    ready = Boolean(ready && beforeVariant && afterVariant);
    preview.hidden = !ready;
    previewEmpty.hidden = ready;

    if (!ready) {
      return;
    }

    beforeImage.src = beforeVariant.url;
    beforeImage.alt = state.before.alt;
    afterImage.src = afterVariant.url;
    afterImage.alt = state.after.alt;
    preview.querySelector('.bafrt-compare__label--before').textContent = beforeLabel;
    preview.querySelector('.bafrt-compare__label--after').textContent = afterLabel;
    preview.dataset.start = fields.start.value;
    preview.dataset.interaction = fields.interaction.value;
    range.value = fields.start.value;
    range.dataset.valueLabel = afterLabel;
    range.dataset.valueTemplate = bafrtAdmin.valueTemplate;
    range.setAttribute('aria-valuetext', formatValueText(bafrtAdmin.valueTemplate, fields.start.value, afterLabel));

    preview.style.setProperty('--bafrt-position', fields.start.value + '%');
    preview.style.setProperty('--bafrt-ratio', ratioForPreview());
    preview.style.setProperty('--bafrt-fit', fields.fit.value);
    preview.style.setProperty('--bafrt-line-color', fields.lineColor.value);
    preview.style.setProperty('--bafrt-line-width', fields.lineWidth.value + 'px');
    preview.style.setProperty('--bafrt-handle-color', fields.handleColor.value);
    preview.style.setProperty('--bafrt-label-color', fields.labelColor.value);
    preview.style.setProperty('--bafrt-label-bg', fields.labelBackground.value);

    toggleClass(preview, 'bafrt-compare--divider', fields.divider.checked);
    toggleClass(preview, 'bafrt-compare--handle', fields.handle.checked);
    toggleClass(preview, 'bafrt-compare--slider-hidden', !fields.showSlider.checked);
    toggleClass(preview, 'bafrt-compare--label-before', fields.showBeforeLabel.checked);
    toggleClass(preview, 'bafrt-compare--label-after', fields.showAfterLabel.checked);
  }

  function updateWarnings() {
    var beforeVariant = selectedVariant(state.before);
    var afterVariant = selectedVariant(state.after);

    if (!beforeVariant || !afterVariant || !beforeVariant.width || !beforeVariant.height || !afterVariant.width || !afterVariant.height) {
      ratioWarning.hidden = true;
      sizeWarning.hidden = true;
      return;
    }

    var beforeRatio = beforeVariant.width / beforeVariant.height;
    var afterRatio = afterVariant.width / afterVariant.height;
    var ratioDifference = Math.abs(beforeRatio - afterRatio) / beforeRatio;
    var widthDifference = Math.abs(beforeVariant.width - afterVariant.width) / beforeVariant.width;
    var heightDifference = Math.abs(beforeVariant.height - afterVariant.height) / beforeVariant.height;

    ratioWarning.textContent = bafrtAdmin.ratioWarning;
    ratioWarning.hidden = ratioDifference <= 0.02;
    sizeWarning.textContent = bafrtAdmin.sizeWarning;
    sizeWarning.hidden = Math.max(widthDifference, heightDifference) <= 0.20;
  }

  function encodeAttribute(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\[/g, '&#91;')
      .replace(/\]/g, '&#93;');
  }

  function addAttribute(parts, name, value) {
    parts.push(name + '="' + encodeAttribute(value) + '"');
  }

  function generateShortcode() {
    if (!state.before || !state.after) {
      shortcode.value = '';
      shortcode.placeholder = bafrtAdmin.imagesRequired;
      copyButton.disabled = true;
      return;
    }

    var ratio = getRatioValue();
    if (!ratio) {
      shortcode.value = '';
      shortcode.placeholder = bafrtAdmin.invalidCustomRatio;
      copyButton.disabled = true;
      return;
    }

    var parts = ['before_after_retouching'];
    addAttribute(parts, 'before', state.before.id);
    addAttribute(parts, 'after', state.after.id);
    addAttribute(parts, 'ratio', ratio);
    addAttribute(parts, 'loading', fields.loading.value);

    if (fields.start.value !== '50') addAttribute(parts, 'start', fields.start.value);
    if (fields.fit.value !== 'contain') addAttribute(parts, 'fit', fields.fit.value);
    if (fields.interaction.value !== 'hover') addAttribute(parts, 'interaction', fields.interaction.value);
    if (fields.size.value !== 'full') addAttribute(parts, 'size', fields.size.value);
    if (fields.divider.checked) addAttribute(parts, 'divider', 'on');
    if (fields.handle.checked) addAttribute(parts, 'handle', 'on');
    if (!fields.showSlider.checked) addAttribute(parts, 'show_slider', 'off');

    if (fields.showBeforeLabel.checked) {
      addAttribute(parts, 'show_before_label', 'on');
      if (fields.beforeLabel.value.trim() !== bafrtAdmin.defaultBefore) addAttribute(parts, 'label_before', fields.beforeLabel.value.trim());
    }
    if (fields.showAfterLabel.checked) {
      addAttribute(parts, 'show_after_label', 'on');
      if (fields.afterLabel.value.trim() !== bafrtAdmin.defaultAfter) addAttribute(parts, 'label_after', fields.afterLabel.value.trim());
    }

    if (fields.lineColor.value.toLowerCase() !== '#ffffff') addAttribute(parts, 'line_color', fields.lineColor.value);
    if (fields.lineWidth.value !== '2') addAttribute(parts, 'line_width', fields.lineWidth.value);
    if (fields.handleColor.value.toLowerCase() !== '#ffffff') addAttribute(parts, 'handle_color', fields.handleColor.value);
    if (fields.labelColor.value.toLowerCase() !== '#ffffff') addAttribute(parts, 'label_color', fields.labelColor.value);
    if (fields.labelBackground.value.toLowerCase() !== '#000000') addAttribute(parts, 'label_bg', fields.labelBackground.value);
    if (fields.customClass.value.trim()) addAttribute(parts, 'class', fields.customClass.value.trim());

    shortcode.value = '[' + parts.join(' ') + ']';
    shortcode.placeholder = '';
    copyButton.disabled = false;
  }

  function updateAll() {
    updateRatioControls();
    startOutput.value = fields.start.value + '%';
    startOutput.textContent = fields.start.value + '%';
    root.querySelector('[data-bafrt-before-label-field]').hidden = !fields.showBeforeLabel.checked;
    root.querySelector('[data-bafrt-after-label-field]').hidden = !fields.showAfterLabel.checked;
    swapButton.disabled = !(state.before && state.after);
    copyStatus.textContent = '';
    updatePicker('before');
    updatePicker('after');
    updateWarnings();
    updatePreview();
    generateShortcode();
  }

  root.querySelectorAll('[data-bafrt-picker]').forEach(function (picker) {
    var type = picker.getAttribute('data-bafrt-picker');

    picker.querySelector('[data-bafrt-select]').addEventListener('click', function () {
      openMedia(type);
    });

    picker.querySelector('[data-bafrt-remove]').addEventListener('click', function () {
      state[type] = null;
      updatePicker(type);
      updateAll();
    });
  });

  root.querySelectorAll('[data-bafrt-field]').forEach(function (field) {
    field.addEventListener('input', updateAll);
    field.addEventListener('change', updateAll);
  });

  swapButton.addEventListener('click', function () {
    var temporary = state.before;
    state.before = state.after;
    state.after = temporary;
    updatePicker('before');
    updatePicker('after');
    updateAll();
  });

  copyButton.addEventListener('click', function () {
    var copyPromise;

    if (navigator.clipboard && window.isSecureContext) {
      copyPromise = navigator.clipboard.writeText(shortcode.value);
    } else {
      shortcode.focus();
      shortcode.select();
      copyPromise = new Promise(function (resolve, reject) {
        document.execCommand('copy') ? resolve() : reject();
      });
    }

    copyPromise.then(
      function () {
        copyStatus.textContent = bafrtAdmin.copySuccess;
      },
      function () {
        copyStatus.textContent = bafrtAdmin.copyError;
      }
    );
  });

  preview.querySelector('[data-bafrt-range]').addEventListener('input', function (event) {
    fields.start.value = String(Math.round(Number(event.target.value) || 0));
    updateAll();
  });

  if (typeof window.bafrtInit === 'function') {
    window.bafrtInit(preview);
  }

  updateAll();
})();
