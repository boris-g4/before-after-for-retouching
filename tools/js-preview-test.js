'use strict';

var fs = require('fs');
var path = require('path');
var root = path.dirname(__dirname);

function assert(condition, message) {
  if (!condition) {
    throw new Error('FAIL: ' + message);
  }
}

function extractFunction(source, name) {
  var start = source.indexOf('function ' + name + '(');
  assert(start >= 0, 'function found: ' + name);
  var brace = source.indexOf('{', start);
  var depth = 0;

  for (var index = brace; index < source.length; index += 1) {
    if (source[index] === '{') depth += 1;
    if (source[index] === '}') {
      depth -= 1;
      if (depth === 0) {
        return source.slice(start, index + 1);
      }
    }
  }

  throw new Error('FAIL: unterminated function: ' + name);
}

function loadFunctions(source, names, context) {
  var keys = Object.keys(context || {});
  var values = keys.map(function (key) { return context[key]; });
  var declarations = names.map(function (name) { return extractFunction(source, name); }).join('\n');
  var exports = names.map(function (name) { return name + ': ' + name; }).join(',');
  return Function.apply(null, keys.concat(declarations + '\nreturn {' + exports + '};')).apply(null, values);
}

var adminSource = fs.readFileSync(path.join(root, 'assets/js/before-after-for-retouching-admin.js'), 'utf8');
var adminFields = {
  size: { value: 'full' },
  ratio: { value: 'auto' },
  customRatio: { value: '3/2' }
};
var adminState = { before: null, after: null };
var customRatioWrap = {};
var currentRatio = {};
var admin = loadFunctions(
  adminSource,
  ['normalizeVariant', 'normalizeSizes', 'normalizeAttachment', 'selectedVariant', 'getRatioValue', 'ratioForPreview', 'formatValueText', 'updateRatioControls'],
  {
    fields: adminFields,
    state: adminState,
    customRatioWrap: customRatioWrap,
    currentRatio: currentRatio,
    bafrtAdmin: {
      currentRatio: 'Current ratio: %1$s / %2$s',
      selectBefore: 'Select a Before image'
    }
  }
);
var media = {
  id: 123,
  url: 'full.jpg',
  width: 965,
  height: 578,
  sizes: {
    medium: { url: 'medium.jpg', width: 300, height: 180 },
    large: { url: 'large.jpg', width: 800, height: 479 },
    custom_crop: { url: 'crop.jpg', width: 400, height: 300 }
  }
};
adminState.before = admin.normalizeAttachment(media);

admin.updateRatioControls();
assert(customRatioWrap.hidden === true, 'admin Auto hides Custom ratio');
assert(currentRatio.hidden === false, 'admin Auto shows Current ratio');
assert(currentRatio.textContent === 'Current ratio: 6000 / 4000', 'admin Auto ratio uses full Before dimensions');

adminFields.size.value = 'full';
assert(admin.selectedVariant(adminState.before).url === 'full.jpg', 'admin full preview');
adminFields.size.value = 'medium';
assert(admin.selectedVariant(adminState.before).url === 'medium.jpg', 'admin medium preview');
adminFields.size.value = 'large';
assert(admin.selectedVariant(adminState.before).url === 'large.jpg', 'admin large preview');
adminFields.size.value = 'custom_crop';
assert(admin.selectedVariant(adminState.before).url === 'crop.jpg', 'admin custom crop preview');
assert(admin.ratioForPreview() === '400 / 300', 'admin auto ratio uses selected crop');
admin.updateRatioControls();
assert(currentRatio.textContent === 'Current ratio: 400 / 300', 'admin Current ratio uses selected crop dimensions');
adminFields.customRatio.value = '923/1000';
adminFields.ratio.value = 'custom';
admin.updateRatioControls();
assert(customRatioWrap.hidden === false, 'admin Custom shows Custom ratio');
assert(currentRatio.hidden === true, 'admin Custom hides Current ratio');
adminFields.ratio.value = 'auto';
admin.updateRatioControls();
assert(adminFields.customRatio.value === '923/1000', 'admin preserves Custom ratio while switching modes');
adminState.after = admin.normalizeAttachment({
  id: 124,
  url: 'swapped-full.jpg',
  width: 1200,
  height: 800,
  sizes: {
    custom_crop: { url: 'swapped-crop.jpg', width: 600, height: 600 }
  }
});
var swappedBefore = adminState.before;
adminState.before = adminState.after;
adminState.after = swappedBefore;
adminFields.size.value = 'full';
admin.updateRatioControls();
assert(currentRatio.textContent === 'Current ratio: 1200 / 800', 'admin Current ratio updates after Swap images');
adminFields.size.value = 'custom_crop';
admin.updateRatioControls();
assert(currentRatio.textContent === 'Current ratio: 600 / 600', 'admin Current ratio updates after Swap and image-size change');
adminState.before = null;
admin.updateRatioControls();
assert(currentRatio.textContent === 'Select a Before image', 'admin Auto prompts when Before image is missing');
adminState.before = admin.normalizeAttachment(media);
adminFields.size.value = 'missing_size';
assert(admin.selectedVariant(adminState.before).url === 'full.jpg', 'admin missing size falls back to full');
assert(admin.formatValueText('%1$s%% of the %2$s image is visible', '50', 'Retouched') === '50% of the Retouched image is visible', 'admin English aria value');
assert(admin.formatValueText('Видно %1$s%% изображения «%2$s»', '50', 'После') === 'Видно 50% изображения «После»', 'admin Russian aria value');
assert(admin.formatValueText('Видно %1$s%% зображення «%2$s»', '50', 'Після') === 'Видно 50% зображення «Після»', 'admin Ukrainian aria value');

var blockSource = fs.readFileSync(path.join(root, 'assets/js/before-after-for-retouching-block.js'), 'utf8');
var block = loadFunctions(
  blockSource,
  ['normalizeImageSize', 'normalizeMediaSizes', 'previewVariant'],
  {
    imageSizeOptions: [
      { value: 'full' },
      { value: 'medium' },
      { value: 'custom_crop' },
      { value: 'missing_size' }
    ]
  }
);
var editorMedia = {
  url: 'original.jpg',
  width: 6000,
  height: 4000,
  sizes: {
    thumbnail: { url: 'thumb.jpg', width: 150, height: 150 },
    medium: { url: 'medium.jpg', width: 300, height: 200 },
    large: { url: 'large.jpg', width: 1024, height: 683 },
    medium_large: { url: 'medium-large.jpg', width: 768, height: 512 },
    custom_crop: { url: 'crop.jpg', width: 400, height: 300 }
  }
};
var editorSizes = block.normalizeMediaSizes(editorMedia);
assert(block.previewVariant(editorSizes, 'custom_crop').url === 'crop.jpg', 'Gutenberg selected custom size');
assert(block.previewVariant(editorSizes, 'medium').url === 'medium.jpg', 'Gutenberg selected standard size');
assert(block.previewVariant(editorSizes, 'missing_size').url === 'medium-large.jpg', 'Gutenberg missing selected size uses reduced fallback');
assert(block.previewVariant({ large: editorSizes.large, full: editorSizes.full }, 'missing_size').url === 'large.jpg', 'Gutenberg fallback order reaches large');
assert(block.previewVariant({ thumbnail: editorSizes.thumbnail, full: editorSizes.full }, 'missing_size').url === 'thumb.jpg', 'Gutenberg fallback order reaches thumbnail');
assert(block.previewVariant({ full: editorSizes.full }, 'missing_size').url === 'original.jpg', 'Gutenberg original is last fallback');
assert(block.normalizeImageSize('unknown') === 'full', 'Gutenberg removed registered size falls back to full');

var publicSource = fs.readFileSync(path.join(root, 'assets/js/before-after-for-retouching.js'), 'utf8');
var publicFunctions = loadFunctions(publicSource, ['formatValueText']);
assert(publicFunctions.formatValueText('%1$s%% of the %2$s image is visible', '50', 'Retouched') === '50% of the Retouched image is visible', 'frontend English aria value');
assert(publicFunctions.formatValueText('Видно %1$s%% изображения «%2$s»', '50', 'После') === 'Видно 50% изображения «После»', 'frontend Russian aria value');
assert(publicFunctions.formatValueText('Видно %1$s%% зображення «%2$s»', '50', 'Після') === 'Видно 50% зображення «Після»', 'frontend Ukrainian aria value');
assert(publicFunctions.formatValueText('%2$s: %1$s%%', '73.5', 'Custom After') === 'Custom After: 73.5%', 'frontend supports translated placeholder order and custom label');

console.log('JavaScript preview logic tests passed.');
