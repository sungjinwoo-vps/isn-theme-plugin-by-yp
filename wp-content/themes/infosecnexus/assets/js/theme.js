(function () {
  'use strict';

  const root = document.documentElement;
  const siteHeader = document.querySelector('[data-site-header]');
  const colorModeToggles = document.querySelectorAll('[data-color-mode-toggle]');
  const colorModeStorageKey = 'infosecnexus-color-mode';
  const defaultColorMode = root.dataset.defaultColorMode || 'light';
  const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');
  const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  const adsenseConfig = window.infosecnexusAdSense || null;
  let adsensePromise = null;

  function loadAdSense() {
    if (!adsenseConfig || !adsenseConfig.src) {
      return Promise.resolve(false);
    }

    if (adsensePromise) {
      return adsensePromise;
    }

    const existing = document.querySelector('script[data-infosecnexus-adsense]');
    if (existing) {
      adsensePromise = Promise.resolve(true);
      return adsensePromise;
    }

    adsensePromise = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.async = true;
      script.src = adsenseConfig.src;
      script.crossOrigin = 'anonymous';
      script.dataset.infosecnexusAdsense = 'true';
      script.addEventListener('load', () => resolve(true), { once: true });
      script.addEventListener('error', () => reject(new Error('AdSense failed to load')), { once: true });
      document.head.appendChild(script);
    });

    return adsensePromise;
  }

  window.infosecnexusLoadAdSense = loadAdSense;
  document.addEventListener('infosecnexus:ads-consent', () => {
    loadAdSense().catch(() => {});
  });

  const scheduleAdSense = () => {
    const schedule = window.requestIdleCallback || ((callback) => window.setTimeout(callback, 500));
    schedule(() => loadAdSense().catch(() => {}), { timeout: 2500 });
  };

  try {
    if (localStorage.getItem('infosecnexus-cookie-ok') === '1') {
      window.addEventListener('load', scheduleAdSense, { once: true });
    } else if (!adsenseConfig?.consentRequired) {
      const interactionEvents = ['pointerdown', 'keydown', 'touchstart'];
      const loadAfterInteraction = () => {
        interactionEvents.forEach((eventName) => {
          document.removeEventListener(eventName, loadAfterInteraction);
        });
        scheduleAdSense();
      };
      interactionEvents.forEach((eventName) => {
        document.addEventListener(eventName, loadAfterInteraction, { passive: true });
      });
    }
  } catch {
    root.dataset.adStorage = 'unavailable';
  }

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
    root.setAttribute('data-theme', resolvedMode);
    root.style.colorScheme = resolvedMode;
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
  const searchToggle = document.querySelector('[data-search-toggle]');
  const searchPanel = document.querySelector('[data-search-modal]');
  let menuPreviousFocus = null;
  let searchPreviousFocus = null;

  function focusableElements(container) {
    if (!container) {
      return [];
    }

    return Array.from(container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
      .filter((element) => !element.hidden && element.getAttribute('aria-hidden') !== 'true');
  }

  function trapFocus(event, container) {
    if (event.key !== 'Tab' || !container || container.hidden) {
      return;
    }

    const focusable = focusableElements(container);
    if (!focusable.length) {
      event.preventDefault();
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function updateBodyModalState() {
    const menuOpen = panel && !panel.hidden;
    const searchOpen = searchPanel && !searchPanel.hidden;
    document.body.classList.toggle('has-modal-open', Boolean(menuOpen || searchOpen));
  }

  function openPanel() {
    if (!panel || !menuToggle) {
      return;
    }

    closeSearch(false);
    menuPreviousFocus = document.activeElement;
    panel.hidden = false;
    menuToggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('mobile-panel-open');
    updateBodyModalState();

    const focusable = panel.querySelector('button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusable) {
      focusable.focus();
    }
  }

  function closePanel(restoreFocus = true) {
    if (!panel || !menuToggle || panel.hidden) {
      return;
    }

    panel.hidden = true;
    menuToggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('mobile-panel-open');

    updateBodyModalState();

    if (restoreFocus && menuPreviousFocus) {
      menuPreviousFocus.focus();
    }

    menuPreviousFocus = null;
  }

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-mobile-menu-toggle]')) {
      openPanel();
    }

    if (event.target.closest('[data-mobile-menu-close]')) {
      closePanel();
    }
  });

  function closeSearch(restoreFocus = true) {
    if (!searchToggle || !searchPanel || searchPanel.hidden) {
      return;
    }

    searchPanel.hidden = true;
    searchToggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('search-modal-open');
    updateBodyModalState();

    if (restoreFocus && searchPreviousFocus) {
      searchPreviousFocus.focus();
    }

    searchPreviousFocus = null;
  }

  if (searchToggle && searchPanel) {
    searchToggle.addEventListener('click', (event) => {
      event.preventDefault();
      const nextOpen = searchPanel.hidden;
      if (nextOpen) {
        searchPreviousFocus = document.activeElement;
        closePanel(false);
      }
      searchPanel.hidden = !nextOpen;
      searchToggle.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
      document.body.classList.toggle('search-modal-open', nextOpen);
      updateBodyModalState();

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
      if (panel && !panel.hidden) {
        closePanel();
      } else if (searchPanel && !searchPanel.hidden) {
        closeSearch();
      }
      return;
    }

    if (panel && !panel.hidden) {
      trapFocus(event, panel.querySelector('[role="dialog"]'));
    } else if (searchPanel && !searchPanel.hidden) {
      trapFocus(event, searchPanel.querySelector('[role="dialog"]'));
    }
  });

  const recentSearches = document.querySelector('[data-recent-searches]');
  const searchForms = document.querySelectorAll('.search-modal form[role="search"], .search-modal .search-form');
  const searchStorageKey = 'infosecnexus-recent-searches';

  function readRecentSearches() {
    try {
      const value = JSON.parse(window.localStorage.getItem(searchStorageKey) || '[]');
      return Array.isArray(value) ? value.filter((item) => typeof item === 'string').slice(0, 4) : [];
    } catch {
      return [];
    }
  }

  function renderRecentSearches() {
    if (!recentSearches) {
      return;
    }

    const searches = readRecentSearches();
    recentSearches.replaceChildren();
    searches.forEach((term) => {
      const link = document.createElement('a');
      link.href = `${window.infosecnexusTheme?.homeUrl || '/'}?s=${encodeURIComponent(term)}`;
      link.textContent = term;
      recentSearches.appendChild(link);
    });
    recentSearches.hidden = searches.length === 0;
  }

  searchForms.forEach((form) => {
    form.addEventListener('submit', () => {
      const input = form.querySelector('input[type="search"]');
      const value = input ? input.value.trim() : '';
      if (!value) {
        return;
      }
      try {
        const searches = [value, ...readRecentSearches().filter((item) => item.toLowerCase() !== value.toLowerCase())].slice(0, 4);
        window.localStorage.setItem(searchStorageKey, JSON.stringify(searches));
      } catch {
        root.dataset.searchStorage = 'unavailable';
      }
    });
  });
  renderRecentSearches();

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
    window.scrollTo({ top: Math.max(targetTop, 0), behavior: reducedMotionQuery.matches ? 'auto' : 'smooth' });

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

    const loadGateAd = () => {
      const ad = gate.querySelector('[data-post-gate-ad]');
      if (!ad || ad.dataset.loaded === 'true') {
        return ad;
      }

      const client = ad.dataset.adClient || '';
      const slot = ad.dataset.adSlot || '';
      const adInner = ad.querySelector('[data-post-gate-ad-inner]');
      if (!client || !slot || !adInner) {
        return null;
      }

      const unit = document.createElement('ins');
      unit.className = 'adsbygoogle';
      unit.style.display = 'block';
      unit.dataset.adClient = client;
      unit.dataset.adSlot = slot;
      unit.dataset.adFormat = 'fluid';
      unit.dataset.fullWidthResponsive = 'true';
      adInner.appendChild(unit);
      ad.hidden = false;
      ad.dataset.loaded = 'true';

      loadAdSense()
        .then(() => {
          (window.adsbygoogle = window.adsbygoogle || []).push({});
        })
        .catch(() => {
          ad.dataset.loadError = 'true';
        });

      return ad;
    };

    button.addEventListener('click', () => {
      const ad = loadGateAd();
      content.hidden = false;
      gate.classList.add('is-unlocked');
      const scrollTarget = ad || content.querySelector('h2, h3, p');
      if (scrollTarget) {
        scrollTarget.scrollIntoView({ behavior: reducedMotionQuery.matches ? 'auto' : 'smooth', block: 'start' });
      }
    });
  });

  const scrollTop = document.querySelector('[data-scroll-top]');
  if (scrollTop) {
    scrollTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reducedMotionQuery.matches ? 'auto' : 'smooth' }));

    const siteFooter = document.querySelector('.site-footer');
    let updateQueued = false;

    const update = () => {
      updateQueued = false;
      const total = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
      const ratio = total > 0 ? Math.min(Math.max(window.scrollY / total, 0), 1) : 0;
      const threshold = Math.max(900, window.innerHeight * 1.25);
      let footerOffset = 0;

      if (siteFooter) {
        const footerOverlap = Math.max(0, window.innerHeight - siteFooter.getBoundingClientRect().top);
        const maximumOffset = Math.max(0, window.innerHeight - scrollTop.offsetHeight - 24);
        footerOffset = Math.min(footerOverlap, maximumOffset);
      }

      scrollTop.style.setProperty('--isn-scroll-progress', `${Math.round(ratio * 1000) / 10}%`);
      scrollTop.style.setProperty('--isn-scroll-footer-offset', `${Math.ceil(footerOffset)}px`);
      scrollTop.hidden = total <= 0 || window.scrollY < threshold;
    };

    const requestUpdate = () => {
      if (updateQueued) {
        return;
      }

      updateQueued = true;
      window.requestAnimationFrame(update);
    };

    update();
    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);
    window.addEventListener('load', requestUpdate, { once: true });
  }

  if (siteHeader) {
    window.addEventListener('scroll', () => {
      siteHeader.classList.toggle('is-compact', window.scrollY > 80);
    }, { passive: true });
  }

  document.querySelectorAll('[data-animated-hero]').forEach((hero) => {
    const video = hero.querySelector('video[data-src]');
    const toggle = hero.querySelector('.anime-hero-media__toggle');
    if (!video || !toggle || reducedMotionQuery.matches) {
      if (toggle) {
        toggle.hidden = true;
      }
      return;
    }

    let sourceReady = false;
    const prepareVideo = () => {
      if (sourceReady || !video.dataset.src) {
        return;
      }
      sourceReady = true;
      video.src = video.dataset.src;
      video.load();
    };
    const setPlaying = (playing) => {
      hero.classList.toggle('is-playing', playing);
      toggle.setAttribute('aria-pressed', playing ? 'true' : 'false');
      toggle.setAttribute('aria-label', playing ? 'Pause hero animation' : 'Play hero animation');
    };
    const playVideo = () => {
      prepareVideo();
      video.play().then(() => setPlaying(true)).catch(() => setPlaying(false));
    };

    const initializeVideo = () => {
      const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              playVideo();
            } else {
              video.pause();
              setPlaying(false);
            }
          });
        }, { rootMargin: '160px 0px', threshold: 0.2 })
        : null;

      if (observer) {
        observer.observe(hero);
      } else {
        playVideo();
      }
    };

    if (document.readyState === 'complete') {
      initializeVideo();
    } else {
      window.addEventListener('load', initializeVideo, { once: true });
    }

    toggle.addEventListener('click', () => {
      if (video.paused) {
        playVideo();
      } else {
        video.pause();
        setPlaying(false);
      }
    });
  });
}());
