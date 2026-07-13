(function () {
  'use strict';

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function formatValue(value) {
    var rounded = Math.round(value * 10) / 10;
    return String(rounded).replace(/\.0$/, '');
  }

  function formatValueText(template, value, label) {
    return String(template || '%1$s%%').replace(/%1\$s|%2\$s|%%/g, function (token) {
      if (token === '%1$s') return value;
      if (token === '%2$s') return label;
      return '%';
    });
  }

  function setPosition(component, range, value) {
    var nextValue = clamp(Number(value) || 0, 0, 100);
    var formatted = formatValue(nextValue);
    var label = range ? range.getAttribute('data-value-label') : '';
    var template = range ? range.getAttribute('data-value-template') : '';

    component.style.setProperty('--bafrt-position', formatted + '%');

    if (range) {
      if (String(range.value) !== formatted) {
        range.value = formatted;
      }

      range.setAttribute('aria-valuetext', formatValueText(template, formatted, label || 'After'));
    }
  }

  function getPointerPosition(event, stage) {
    var rect = stage.getBoundingClientRect();

    if (!rect.width) {
      return 50;
    }

    return ((event.clientX - rect.left) / rect.width) * 100;
  }

  function initComponent(component) {
    if (!component || component.dataset.bafrtReady === '1') {
      return;
    }

    var stage = component.querySelector('[data-bafrt-stage]');
    var range = component.querySelector('[data-bafrt-range]');

    if (!stage || !range) {
      return;
    }

    component.dataset.bafrtReady = '1';

    var initialValue = component.dataset.start !== undefined && component.dataset.start !== ''
      ? component.dataset.start
      : (range.value !== undefined && range.value !== '' ? range.value : 50);
    var isDragging = false;
    var activePointerId = null;

    setPosition(component, range, initialValue);

    function updateFromPointer(event) {
      setPosition(component, range, getPointerPosition(event, stage));
    }

    stage.addEventListener('pointerdown', function (event) {
      if (event.pointerType === 'mouse' && event.button !== 0) {
        return;
      }

      isDragging = true;
      activePointerId = event.pointerId;

      if (stage.setPointerCapture) {
        stage.setPointerCapture(event.pointerId);
      }

      updateFromPointer(event);
    });

    stage.addEventListener('pointermove', function (event) {
      var interaction = component.dataset.interaction || 'hover';

      if (event.pointerType === 'mouse' && interaction === 'hover') {
        updateFromPointer(event);
        return;
      }

      if (isDragging && (activePointerId === null || activePointerId === event.pointerId)) {
        updateFromPointer(event);
      }
    });

    function stopDragging(event) {
      if (activePointerId !== null && event.pointerId !== activePointerId) {
        return;
      }

      isDragging = false;

      if (stage.releasePointerCapture && activePointerId !== null) {
        try {
          stage.releasePointerCapture(activePointerId);
        } catch (error) {
          // Pointer capture may already be released by the browser.
        }
      }

      activePointerId = null;
    }

    stage.addEventListener('pointerup', stopDragging);
    stage.addEventListener('pointercancel', stopDragging);
    stage.addEventListener('lostpointercapture', function () {
      isDragging = false;
      activePointerId = null;
    });

    range.addEventListener('input', function () {
      setPosition(component, range, range.value);
    });
  }

  function initAll(root) {
    var context = root && root.querySelectorAll ? root : document;

    if (context.matches && context.matches('[data-bafrt-compare]')) {
      initComponent(context);
    }

    context.querySelectorAll('[data-bafrt-compare]').forEach(initComponent);
  }

  window.bafrtInit = initAll;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initAll(document);
    });
  } else {
    initAll(document);
  }
})();
