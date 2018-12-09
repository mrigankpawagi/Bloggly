((domready, debounce, drupalSettings, RuntimeCss, $) => {
  if (!drupalSettings.backgroundImage) {
    return;
  }

  const settings = drupalSettings.backgroundImage || {};
  if (!settings.baseClass) {
    settings.baseClass = 'background-image';
  }

  const trigger = (element, name, data = null) => {
    let event;
    data = data || {};
    if (window.CustomEvent) {
      event = new CustomEvent(name, {
        bubbles: true,
        cancelable: true,
        detail: data,
      });
    }
    else {
      event = document.createEvent('CustomEvent');
      event.initCustomEvent(name, true, true, data);
    }

    element.dispatchEvent(event);
  };

  let scrollTop = RuntimeCss.getScrollTop();
  domready(() => {
    const wrapper = document.querySelector(`.${settings.baseClass}-wrapper`);
    const image = wrapper && wrapper.querySelector(`.${settings.baseClass}`);

    // Immediately return if no image can be found.
    if (!image) {
      return;
    }

    // Create new runtime CSS.
    const css = new RuntimeCss(settings.baseClass);

    const updateOffset = () => {
      if (settings.fullViewport) {
        css.add(wrapper, { marginTop: `-${RuntimeCss.getOffset().top}px` });
      }
    };
    updateOffset();

    // Handle responsive blurred hero images.
    const doBlur = () => {
      updateOffset();

      // Immediately return if the background image should only blur on
      // scroll or when it's also full viewport.
      if (!(parseInt(settings.blur.type, 10) === 1 || (parseInt(settings.blur.type, 10) === 2 && settings.fullViewport))) {
        css.add(image, { prefixFilter: '' });
        return;
      }

      const max = parseInt(settings.blur.radius, 10) || 50;
      const speed = (parseInt(settings.blur.speed, 10) || 1) / 10;

      let blur = scrollTop * speed;
      if (blur > max) {
        blur = max;
      }
      css.add(image, { prefixFilter: `blur(${blur}px)` });

      const overlay = image.querySelector(`.${settings.baseClass}-overlay`);
      if (overlay) {
        let opacity = 0.25 + (blur / 100);
        if (opacity > 1) {
          opacity = 1;
        }
        css.add(overlay, { prefixOpacity: opacity });
      }

      trigger(image, 'blur.background_image', settings);
    };

    const draw = debounce(() => {
      scrollTop = RuntimeCss.getScrollTop();
      RuntimeCss.raf(doBlur);
    }, 10);

    window.addEventListener('scroll', draw);
    window.addEventListener('resize', draw);
    window.addEventListener('touchmove', draw);

    // If jQuery is loaded, listen to special Drupal events (like the Toolbar).
    if ($) {
      $(document)
        .on('drupalViewportOffsetChange', draw)
        .on('drupalToolbarOrientationChange', draw)
        .on('drupalToolbarTabChange', draw)
        .on('drupalToolbarTrayChange', draw);
    }

    // Initialize once.
    draw();
  });
})(domready, Drupal.debounce, drupalSettings, window.RuntimeCss, jQuery);
