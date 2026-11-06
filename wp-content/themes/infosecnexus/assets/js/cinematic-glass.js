/* Small, progressive enhancements for the cinematic editorial theme. */
(function () {
  'use strict';
  if (!document.body.classList.contains('cinematic-editorial')) {
    return;
  }
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine) and (min-width: 1025px)');
  const header = document.querySelector('.site-header');
  let scrollFrame = false;
  const updateHeader = () => {
    if (scrollFrame) {
      return;
    }
    scrollFrame = true;
    window.requestAnimationFrame(() => {
      if (header) {
        header.classList.toggle('is-scrolled', window.scrollY > 18);
      }
      scrollFrame = false;
    });
  };
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  // Everything starts visible. Only offscreen content opts into the reveal.
  let observer;
  const revealAll = () => {
    document.querySelectorAll('.cg-reveal-pending').forEach((element) => {
      element.classList.remove('cg-reveal-pending');
      element.classList.add('cg-reveal-visible');
    });
    if (observer) {
      observer.disconnect();
    }
  };
  if (!motion.matches && 'IntersectionObserver' in window) {
    try {
      observer = new window.IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.remove('cg-reveal-pending');
            entry.target.classList.add('cg-reveal-visible');
            window.setTimeout(() => entry.target.classList.remove('cg-reveal-visible'), 650);
            observer.unobserve(entry.target);
          }
        });
      }, { rootMargin: '0px 0px -35px 0px', threshold: 0.06 });
      document.querySelectorAll('[data-reveal]').forEach((element) => {
        if (element.getBoundingClientRect().top > window.innerHeight - 35) {
          element.classList.add('cg-reveal-pending');
          observer.observe(element);
        }
      });
    } catch {
      revealAll();
    }
  }

  const hero = document.querySelector('[data-cg-hero]');
  const layers = hero ? Array.from(hero.querySelectorAll('[data-cg-parallax]')) : [];
  let pointerFrame = false;
  let position = { x: 0, y: 0 };
  const resetLayers = () => layers.forEach((layer) => {
    layer.style.removeProperty('--cg-parallax-x');
    layer.style.removeProperty('--cg-parallax-y');
  });
  if (hero) {
    hero.addEventListener('pointermove', (event) => {
      if (motion.matches || !finePointer.matches) {
        return;
      }
      const rect = hero.getBoundingClientRect();
      position = { x: (event.clientX - rect.left) / rect.width - 0.5, y: (event.clientY - rect.top) / rect.height - 0.5 };
      if (pointerFrame) {
        return;
      }
      pointerFrame = true;
      window.requestAnimationFrame(() => {
        layers.forEach((layer) => {
          const strength = Math.min(Number(layer.dataset.cgParallax) || 0, 1);
          layer.style.setProperty('--cg-parallax-x', `${position.x * 14 * strength}px`);
          layer.style.setProperty('--cg-parallax-y', `${position.y * 12 * strength}px`);
        });
        pointerFrame = false;
      });
    }, { passive: true });
    hero.addEventListener('pointerleave', resetLayers, { passive: true });
  }
  motion.addEventListener('change', () => {
    if (motion.matches) {
      revealAll();
      resetLayers();
    }
  });
  finePointer.addEventListener('change', resetLayers);

  // Existing search/menu handlers retain focus, escape, and submission behavior.
  document.querySelectorAll('[data-search-modal], [data-mobile-panel]').forEach((panel) => {
    new window.MutationObserver(() => {
      if (panel.hidden || motion.matches) {
        return;
      }
      const dialog = panel.querySelector('[role="dialog"]') || panel;
      if (dialog.animate) {
        dialog.animate([{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 240, easing: 'cubic-bezier(.2,.75,.25,1)' });
      }
    }).observe(panel, { attributes: true, attributeFilter: ['hidden'] });
  });
  document.querySelectorAll('.cg-discovery select').forEach((select) => {
    select.addEventListener('change', () => {
      select.closest('label').classList.toggle('has-selection', select.value !== '');
      if (!motion.matches && select.animate) {
        select.animate([{ opacity: 0.65 }, { opacity: 1 }], { duration: 180 });
      }
    });
  });
}());
