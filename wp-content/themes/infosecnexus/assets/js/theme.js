(function () {
  'use strict';

  const root = document.documentElement;
  const siteHeader = document.querySelector('[data-site-header]');
  const colorModeToggles = document.querySelectorAll('[data-color-mode-toggle]');
  const colorModeStorageKey = 'infosecnexus-color-mode';
  const defaultColorMode = root.dataset.defaultColorMode || 'light';
  const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

  function readStoredColorMode() {
    try {
      return window.localStorage.getItem(colorModeStorageKey) || defaultColorMode;
    } catch {
      return defaultColorMode;
    }
  }

  function storeColorMode(mode) {
    try {
      window.localStorage.setItem(colorModeStorageKey, mode);
    } catch {
      root.dataset.colorModePreference = mode;
    }
  }

  function resolveColorMode(mode) {
    if (mode === 'system') {
      return colorSchemeQuery.matches ? 'dark' : 'light';
    }

    return mode === 'dark' ? 'dark' : 'light';
  }

  function updateColorModeToggles(resolvedMode) {
    const nextMode = resolvedMode === 'dark' ? 'light' : 'dark';
    const actionLabel = nextMode === 'dark' ? 'Switch to dark mode' : 'Switch to light mode';
    const shortLabel = nextMode === 'dark' ? 'Dark mode' : 'Light mode';

    colorModeToggles.forEach((toggle) => {
      toggle.setAttribute('aria-label', actionLabel);
      toggle.setAttribute('title', actionLabel);

      const label = toggle.querySelector('[data-color-mode-label]');
      if (label) {
        label.textContent = shortLabel;
      }
    });
  }

  function applyColorMode(mode) {
    const resolvedMode = resolveColorMode(mode);
    root.setAttribute('data-color-mode', resolvedMode);
    root.dataset.colorModePreference = mode;
    updateColorModeToggles(resolvedMode);
  }

  applyColorMode(readStoredColorMode());

  colorModeToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const currentMode = root.getAttribute('data-color-mode') || resolveColorMode(readStoredColorMode());
      const nextMode = currentMode === 'dark' ? 'light' : 'dark';
      storeColorMode(nextMode);
      applyColorMode(nextMode);
    });
  });

  const handleSystemColorChange = () => {
    if (readStoredColorMode() === 'system') {
      applyColorMode('system');
    }
  };

  if (colorSchemeQuery.addEventListener) {
    colorSchemeQuery.addEventListener('change', handleSystemColorChange);
  } else if (colorSchemeQuery.addListener) {
    colorSchemeQuery.addListener(handleSystemColorChange);
  }

  const panel = document.querySelector('[data-mobile-panel]');
  const menuToggle = document.querySelector('[data-mobile-menu-toggle]');
  let previousFocus = null;

  function openPanel() {
    if (!panel || !menuToggle) {
      return;
    }

    previousFocus = document.activeElement;
    panel.hidden = false;
    menuToggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('mobile-panel-open');

    const focusable = panel.querySelector('button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusable) {
      focusable.focus();
    }
  }

  function closePanel() {
    if (!panel || !menuToggle) {
      return;
    }

    panel.hidden = true;
    menuToggle.setAttribute('aria-expanded', 'false');
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

  const searchToggle = document.querySelector('[data-search-toggle]');
  const searchPanel = document.querySelector('[data-search-modal]');

  function closeSearch() {
    if (!searchToggle || !searchPanel) {
      return;
    }

    searchPanel.hidden = true;
    searchToggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('search-modal-open');
  }

  if (searchToggle && searchPanel) {
    searchToggle.addEventListener('click', () => {
      const nextOpen = searchPanel.hidden;
      searchPanel.hidden = !nextOpen;
      searchToggle.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
      document.body.classList.toggle('search-modal-open', nextOpen);

      if (nextOpen) {
        const input = searchPanel.querySelector('input[type="search"]');
        if (input) {
          input.focus();
        }
      }
    });
  }

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-search-close]')) {
      closeSearch();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closePanel();
      closeSearch();
    }
  });

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href*="#"]');

    if (!link) {
      return;
    }

    let url = null;
    let target = null;
    try {
      url = new URL(link.href);
      if (url.origin !== window.location.origin || url.pathname !== window.location.pathname || !url.hash || url.hash === '#') {
        return;
      }
      target = document.querySelector(url.hash);
    } catch {
      return;
    }

    if (!target) {
      return;
    }

    event.preventDefault();
    closePanel();
    closeSearch();

    const headerOffset = (siteHeader ? siteHeader.getBoundingClientRect().height : 0) + 18;
    const targetTop = target.getBoundingClientRect().top + window.scrollY - headerOffset;
    window.scrollTo({ top: Math.max(targetTop, 0), behavior: 'smooth' });

    if (window.history.pushState) {
      window.history.pushState(null, '', url.hash);
    }
  });

  const progress = document.querySelector('[data-reading-progress]');
  if (progress) {
    const updateProgress = () => {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      const total = document.documentElement.scrollHeight - window.innerHeight;
      progress.style.transform = `scaleX(${total > 0 ? Math.min(scrollTop / total, 1) : 0})`;
    };

    updateProgress();
    window.addEventListener('scroll', updateProgress, { passive: true });
    window.addEventListener('resize', updateProgress);
  }

  document.querySelectorAll('[data-enhance-headings]').forEach((content) => {
    content.querySelectorAll('h2').forEach((heading, index) => {
      if (!heading.id) {
        heading.id = `section-${index + 1}`;
      }
    });
  });

  document.querySelectorAll('[data-post-unlock]').forEach((gate) => {
    const button = gate.querySelector('[data-post-unlock-button]');
    const content = gate.querySelector('[data-post-unlock-content]');

    if (!button || !content) {
      return;
    }

    button.addEventListener('click', () => {
      content.hidden = false;
      gate.classList.add('is-unlocked');
      content.querySelectorAll('h2').forEach((heading, index) => {
        if (!heading.id) {
          heading.id = `section-unlocked-${index + 1}`;
        }
      });
      const firstHeading = content.querySelector('h2, h3, p');
      if (firstHeading) {
        firstHeading.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  const scrollTop = document.querySelector('[data-scroll-top]');
  if (scrollTop) {
    scrollTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    const update = () => {
      const threshold = Math.max(900, window.innerHeight * 1.25);
      scrollTop.hidden = window.scrollY < threshold;
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
  }

  if (siteHeader) {
    window.addEventListener('scroll', () => {
      siteHeader.classList.toggle('is-compact', window.scrollY > 80);
    }, { passive: true });
  }
}());
