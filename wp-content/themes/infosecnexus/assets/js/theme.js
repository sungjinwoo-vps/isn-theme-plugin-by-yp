(function () {
  'use strict';

  const root = document.documentElement;
  const config = window.infosecnexusTheme || {};
  const storageKey = 'infosecnexus-color-mode';

  function preferredMode() {
    const saved = localStorage.getItem(storageKey);
    if (saved === 'dark' || saved === 'light') {
      return saved;
    }
    if (config.defaultColorMode === 'dark' || config.defaultColorMode === 'light') {
      return config.defaultColorMode;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function setMode(mode) {
    root.setAttribute('data-color-mode', mode);
    document.querySelectorAll('[data-color-mode-toggle]').forEach((button) => {
      button.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
    });
  }

  setMode(preferredMode());

  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-color-mode-toggle]');
    if (!toggle) {
      return;
    }
    const next = root.getAttribute('data-color-mode') === 'dark' ? 'light' : 'dark';
    localStorage.setItem(storageKey, next);
    setMode(next);
  });

  const panel = document.querySelector('[data-mobile-panel]');
  const toggle = document.querySelector('[data-mobile-menu-toggle]');
  let previousFocus = null;

  function openPanel() {
    if (!panel || !toggle) {
      return;
    }
    previousFocus = document.activeElement;
    panel.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    const focusable = panel.querySelector('button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusable) {
      focusable.focus();
    }
    document.body.classList.add('mobile-panel-open');
  }

  function closePanel() {
    if (!panel || !toggle) {
      return;
    }
    panel.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('mobile-panel-open');
    if (previousFocus) {
      previousFocus.focus();
    }
  }

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-mobile-menu-toggle]')) {
      openPanel();
    }
    if (event.target.closest('[data-mobile-menu-close]')) {
      closePanel();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closePanel();
    }
  });

  const progress = document.querySelector('[data-reading-progress]');
  if (progress) {
    const updateProgress = () => {
      const article = document.querySelector('.single-entry');
      if (!article) {
        return;
      }
      const rect = article.getBoundingClientRect();
      const total = rect.height - window.innerHeight;
      const read = Math.min(Math.max(-rect.top, 0), total);
      progress.style.transform = `scaleX(${total > 0 ? read / total : 0})`;
    };
    updateProgress();
    window.addEventListener('scroll', updateProgress, { passive: true });
    window.addEventListener('resize', updateProgress);
  }

  document.querySelectorAll('[data-enhance-headings]').forEach((content) => {
    content.querySelectorAll('h2, h3').forEach((heading, index) => {
      if (!heading.id) {
        heading.id = `section-${index + 1}`;
      }
    });
  });

  const scrollTop = document.querySelector('[data-scroll-top]');
  if (scrollTop) {
    scrollTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    const update = () => {
      scrollTop.hidden = window.scrollY < 600;
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
  }

  let lastScroll = window.scrollY;
  const siteHeader = document.querySelector('[data-site-header]');
  if (siteHeader) {
    window.addEventListener('scroll', () => {
      const current = window.scrollY;
      siteHeader.classList.toggle('is-compact', current > 80);
      siteHeader.classList.toggle('is-revealed', current < lastScroll || current < 120);
      lastScroll = current;
    }, { passive: true });
  }
}());

