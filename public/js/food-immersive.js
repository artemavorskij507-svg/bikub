(function () {
  'use strict';

  var ACTIVE_INSTANCE = null;

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function rafThrottle(fn) {
    var ticking = false;
    var lastArgs = null;

    return function throttled() {
      lastArgs = arguments;
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        ticking = false;
        fn.apply(null, lastArgs);
      });
    };
  }

  function readDepth(el, fallback) {
    var raw = el.getAttribute('data-parallax');
    var depth = parseFloat(raw);
    if (isNaN(depth)) return fallback;
    return depth;
  }

  function setupHeroParallax(root, reducedMotion) {
    if (!root) return function noop() {};

    var layers = Array.prototype.slice.call(root.querySelectorAll('[data-parallax]'));
    if (!layers.length) return function noop() {};

    layers.forEach(function (layer) {
      layer.style.willChange = 'transform';
      layer.style.transformStyle = 'preserve-3d';
    });

    if (reducedMotion) {
      layers.forEach(function (layer) {
        layer.style.transform = 'translate3d(0,0,0)';
      });
      return function noop() {};
    }

    var rect = null;

    function updateFromPoint(clientX, clientY) {
      if (!rect) rect = root.getBoundingClientRect();
      if (!rect.width || !rect.height) return;

      var rx = ((clientX - rect.left) / rect.width) * 2 - 1;
      var ry = ((clientY - rect.top) / rect.height) * 2 - 1;

      var cx = clamp(rx, -1, 1);
      var cy = clamp(ry, -1, 1);

      layers.forEach(function (layer) {
        var depth = readDepth(layer, 10);
        var tx = cx * depth;
        var ty = cy * depth;
        layer.style.transform = 'translate3d(' + tx.toFixed(2) + 'px,' + ty.toFixed(2) + 'px,0)';
      });
    }

    var onMove = rafThrottle(function (event) {
      updateFromPoint(event.clientX, event.clientY);
    });

    var onTouchMove = rafThrottle(function (event) {
      if (!event.touches || !event.touches.length) return;
      var t = event.touches[0];
      updateFromPoint(t.clientX, t.clientY);
    });

    function reset() {
      layers.forEach(function (layer) {
        layer.style.transform = 'translate3d(0,0,0)';
      });
    }

    function onResizeOrScroll() {
      rect = null;
    }

    var usePointer = 'PointerEvent' in window;
    if (usePointer) {
      root.addEventListener('pointermove', onMove, { passive: true });
    } else {
      root.addEventListener('mousemove', onMove, { passive: true });
    }
    root.addEventListener('touchmove', onTouchMove, { passive: true });
    root.addEventListener('pointerleave', reset, { passive: true });
    root.addEventListener('mouseleave', reset, { passive: true });
    root.addEventListener('touchend', reset, { passive: true });
    root.addEventListener('touchcancel', reset, { passive: true });
    window.addEventListener('resize', onResizeOrScroll, { passive: true });
    window.addEventListener('scroll', onResizeOrScroll, { passive: true });

    return function cleanup() {
      root.removeEventListener('pointermove', onMove);
      root.removeEventListener('mousemove', onMove);
      root.removeEventListener('touchmove', onTouchMove);
      root.removeEventListener('pointerleave', reset);
      root.removeEventListener('mouseleave', reset);
      root.removeEventListener('touchend', reset);
      root.removeEventListener('touchcancel', reset);
      window.removeEventListener('resize', onResizeOrScroll);
      window.removeEventListener('scroll', onResizeOrScroll);
    };
  }

  function setupTiltCards(selector, reducedMotion) {
    var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (!finePointer) return function noop() {};

    var cards = Array.prototype.slice.call(document.querySelectorAll(selector || '.tilt-card'));
    if (!cards.length) return function noop() {};

    cards.forEach(function (card) {
      card.style.transformStyle = 'preserve-3d';
      card.style.willChange = 'transform';
      var inner = card.querySelector('.tilt-inner') || card;
      var gloss = card.querySelector('.tilt-gloss');

      if (reducedMotion) {
        card.style.transform = 'translate3d(0,0,0)';
        if (gloss) gloss.style.opacity = '0';
        return;
      }

      var rect = null;

      function setTransform(clientX, clientY) {
        if (!rect) rect = card.getBoundingClientRect();
        var px = (clientX - rect.left) / rect.width;
        var py = (clientY - rect.top) / rect.height;

        var x = clamp((px - 0.5) * 2, -1, 1);
        var y = clamp((py - 0.5) * 2, -1, 1);

        var rotateY = x * 8;
        var rotateX = -y * 8;

        card.classList.add('is-tilting');
        card.style.transform =
          'perspective(900px) rotateX(' + rotateX.toFixed(2) + 'deg) rotateY(' + rotateY.toFixed(2) + 'deg) translate3d(0,-2px,0)';

        if (inner && inner !== card) {
          inner.style.transform = 'translate3d(' + (x * 6).toFixed(2) + 'px,' + (y * 6).toFixed(2) + 'px,18px)';
        }

        if (gloss) {
          gloss.style.opacity = '1';
          gloss.style.transform =
            'translate3d(' + (x * 12).toFixed(2) + 'px,' + (y * 12).toFixed(2) + 'px,0)';
        }
      }

      var onMove = rafThrottle(function (event) {
        setTransform(event.clientX, event.clientY);
      });

      var onTouchMove = rafThrottle(function (event) {
        if (!event.touches || !event.touches.length) return;
        var t = event.touches[0];
        setTransform(t.clientX, t.clientY);
      });

      function reset() {
        rect = null;
        card.classList.remove('is-tilting');
        card.style.transform = 'perspective(900px) rotateX(0deg) rotateY(0deg) translate3d(0,0,0)';
        if (inner && inner !== card) inner.style.transform = 'translate3d(0,0,0)';
        if (gloss) {
          gloss.style.opacity = '0';
          gloss.style.transform = 'translate3d(0,0,0)';
        }
      }

      card.addEventListener('pointermove', onMove, { passive: true });
      card.addEventListener('mousemove', onMove, { passive: true });
      card.addEventListener('touchmove', onTouchMove, { passive: true });
      card.addEventListener('pointerleave', reset, { passive: true });
      card.addEventListener('mouseleave', reset, { passive: true });
      card.addEventListener('touchend', reset, { passive: true });
      card.addEventListener('touchcancel', reset, { passive: true });
      window.addEventListener('resize', reset, { passive: true });

      card.__foodCleanup = function () {
        card.removeEventListener('pointermove', onMove);
        card.removeEventListener('mousemove', onMove);
        card.removeEventListener('touchmove', onTouchMove);
        card.removeEventListener('pointerleave', reset);
        card.removeEventListener('mouseleave', reset);
        card.removeEventListener('touchend', reset);
        card.removeEventListener('touchcancel', reset);
        window.removeEventListener('resize', reset);
      };
    });

    return function cleanupAll() {
      cards.forEach(function (card) {
        if (typeof card.__foodCleanup === 'function') {
          card.__foodCleanup();
          delete card.__foodCleanup;
        }
      });
    };
  }

  function setupReveal(reducedMotion) {
    var items = Array.prototype.slice.call(document.querySelectorAll('.reveal-item'));
    if (!items.length) return function noop() {};

    if (reducedMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (item) {
        item.classList.add('is-visible');
      });
      return function noop() {};
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        });
      },
      {
        root: null,
        rootMargin: '0px 0px 20% 0px',
        threshold: 0.01,
      }
    );

    // Ensure first-screen content is visible immediately on load.
    var firstScreenLimit = window.innerHeight * 1.2;
    items.forEach(function (item) {
      var rect = item.getBoundingClientRect();
      if (rect.top < firstScreenLimit) {
        item.classList.add('is-visible');
      }
      observer.observe(item);
    });

    return function cleanup() {
      observer.disconnect();
    };
  }

  function setupSlideAutoplay(options, reducedMotion) {
    var onNext = typeof options.onNextSlide === 'function' ? options.onNextSlide : null;
    if (!onNext || reducedMotion) return function noop() {};

    var interval = typeof options.slideInterval === 'number' ? options.slideInterval : 6800;
    var target = document.querySelector(options.heroSelector || '.immersive-hero');
    var timer = null;
    var paused = false;

    function safeNext() {
      if (paused || document.hidden) return;
      try {
        onNext();
      } catch (_) {
        // no-op
      }
    }

    function schedule() {
      clearTimeout(timer);
      timer = window.setTimeout(function tick() {
        safeNext();
        schedule();
      }, Math.max(2500, interval));
    }

    function onVisibility() {
      paused = !!document.hidden;
    }

    function onEnter() {
      paused = true;
    }

    function onLeave() {
      paused = !!document.hidden;
    }

    document.addEventListener('visibilitychange', onVisibility, { passive: true });
    if (target) {
      target.addEventListener('mouseenter', onEnter, { passive: true });
      target.addEventListener('mouseleave', onLeave, { passive: true });
      target.addEventListener('touchstart', onEnter, { passive: true });
      target.addEventListener('touchend', onLeave, { passive: true });
    }
    schedule();

    return function cleanup() {
      clearTimeout(timer);
      document.removeEventListener('visibilitychange', onVisibility);
      if (target) {
        target.removeEventListener('mouseenter', onEnter);
        target.removeEventListener('mouseleave', onLeave);
        target.removeEventListener('touchstart', onEnter);
        target.removeEventListener('touchend', onLeave);
      }
    };
  }

  window.initFoodImmersiveEffects = function initFoodImmersiveEffects(options) {
    if (ACTIVE_INSTANCE && typeof ACTIVE_INSTANCE.destroy === 'function') {
      ACTIVE_INSTANCE.destroy();
    }

    var opts = options || {};
    var media = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var reducedMotion = !!(media && media.matches);

    var cleanups = [];
    document.documentElement.classList.add('immersive-motion');

    try {
      cleanups.push(setupHeroParallax(document.querySelector(opts.heroSelector || '.immersive-hero'), reducedMotion));
      cleanups.push(setupTiltCards(opts.cardSelector || '.tilt-card', reducedMotion));
      cleanups.push(setupReveal(reducedMotion));
      cleanups.push(setupSlideAutoplay(opts, reducedMotion));
    } catch (e) {
      document.documentElement.classList.remove('immersive-motion');
      cleanups = [];
    }

    ACTIVE_INSTANCE = {
      destroy: function destroy() {
        cleanups.forEach(function (fn) {
          if (typeof fn === 'function') fn();
        });
        document.documentElement.classList.remove('immersive-motion');
        if (ACTIVE_INSTANCE === this) {
          ACTIVE_INSTANCE = null;
        }
      },
      reducedMotion: reducedMotion,
    };

    return ACTIVE_INSTANCE;
  };
})();
