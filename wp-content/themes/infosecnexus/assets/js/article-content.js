/* Progressive reading tools for ordinary WordPress article content. */
(function () {
  'use strict';

  const article = document.querySelector('.single-entry');
  if (!article) {
    return;
  }

  const labels = Object.assign({
    copy: 'Copy code',
    copied: 'Copied',
    copyFailed: 'Select the code and copy it manually.',
    table: 'Article table: scroll horizontally to see all columns',
    expandGuide: 'Expand article guide',
    collapseGuide: 'Collapse article guide'
  }, window.infosecnexusArticle || {});

  article.querySelectorAll('.entry-content pre').forEach((pre) => {
    if (pre.closest('.article-code')) {
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'article-code';
    const toolbar = document.createElement('div');
    toolbar.className = 'article-code__toolbar';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'article-code__copy';
    button.textContent = labels.copy;
    const status = document.createElement('span');
    status.className = 'article-code__status';
    status.setAttribute('role', 'status');
    toolbar.append(status, button);
    pre.before(wrapper);
    wrapper.append(toolbar, pre);
    pre.tabIndex = 0;

    button.addEventListener('click', async () => {
      const code = pre.querySelector('code') || pre;
      try {
        await window.navigator.clipboard.writeText(code.textContent);
        status.textContent = labels.copied;
      } catch {
        const selection = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(code);
        if (selection) {
          selection.removeAllRanges();
          selection.addRange(range);
        }
        status.textContent = labels.copyFailed;
      }
    });
  });

  article.querySelectorAll('.entry-content table').forEach((table) => {
    let wrapper = table.closest('.wp-block-table, .article-table');
    if (!wrapper) {
      wrapper = document.createElement('div');
      table.before(wrapper);
      wrapper.append(table);
    }
    wrapper.classList.add('article-table');
    wrapper.setAttribute('role', 'region');
    wrapper.setAttribute('aria-label', labels.table);
    wrapper.tabIndex = 0;
  });

  const guide = article.querySelector('[data-article-guide]');
  if (!guide) {
    return;
  }

  const guideLinks = Array.from(guide.querySelectorAll('a[href^="#"]'));
  const guideToggle = guide.querySelector('[data-guide-toggle]');
  const guideList = guide.querySelector('[data-guide-list]');
  const compactGuide = window.matchMedia('(max-width: 1100px)');
  const header = document.querySelector('[data-site-header]');
  let expandedOnSmallScreens = false;
  let observer = null;
  const headerOffset = () => Math.ceil(Math.max(0, header ? header.getBoundingClientRect().bottom : 0) + 18);
  const syncReaderOffset = () => document.documentElement.style.setProperty('--iv-reader-offset', `${headerOffset()}px`);
  const renderGuide = () => {
    if (!guideToggle || !guideList) {
      return;
    }
    const expanded = !compactGuide.matches || expandedOnSmallScreens;
    const listHadFocus = guideList.contains(document.activeElement);
    const toggleHadFocus = document.activeElement === guideToggle;
    guideToggle.hidden = !compactGuide.matches;
    guideList.hidden = !expanded;
    guideToggle.setAttribute('aria-expanded', String(expanded));
    guideToggle.setAttribute('aria-label', expanded ? labels.collapseGuide : labels.expandGuide);
    if (!expanded && listHadFocus) {
      guideToggle.focus({ preventScroll: true });
    } else if (guideToggle.hidden && toggleHadFocus) {
      const link = guideLinks.find((item) => item.hasAttribute('aria-current')) || guideLinks[0];
      if (link) {
        link.focus({ preventScroll: true });
        const linkRect = link.getBoundingClientRect();
        const listRect = guideList.getBoundingClientRect();
        if (linkRect.top < listRect.top) {
          guideList.scrollTop += linkRect.top - listRect.top - 4;
        } else if (linkRect.bottom > listRect.bottom) {
          guideList.scrollTop += linkRect.bottom - listRect.bottom + 4;
        }
      }
    }
  };

  if (guideToggle && guideList) {
    guideToggle.addEventListener('click', () => {
      expandedOnSmallScreens = !expandedOnSmallScreens;
      renderGuide();
    });
    compactGuide.addEventListener('change', () => {
      expandedOnSmallScreens = false;
      renderGuide();
    });
    renderGuide();
    document.documentElement.classList.remove('isn-guide-pending');
  }
  syncReaderOffset();
  const targetFor = (link) => {
    try {
      return document.getElementById(decodeURIComponent(link.hash.slice(1)));
    } catch {
      return null;
    }
  };
  const markCurrent = (link) => {
    guideLinks.forEach((item) => {
      if (item === link) {
        item.setAttribute('aria-current', 'location');
      } else {
        item.removeAttribute('aria-current');
      }
    });
  };

  const revealTarget = (target) => {
    const hidden = target.closest('[data-post-unlock-content][hidden]');
    if (!hidden) {
      return;
    }
    const gate = hidden.closest('[data-post-unlock]');
    const button = gate && gate.querySelector('[data-post-unlock-button]');
    if (button) {
      button.click();
    }
    hidden.hidden = false;
    if (gate) {
      gate.classList.add('is-unlocked');
    }
  };

  const scrollToTarget = (target, behavior) => {
    if (compactGuide.matches) {
      expandedOnSmallScreens = false;
      renderGuide();
    }
    syncReaderOffset();
    const top = target.getBoundingClientRect().top + window.scrollY - headerOffset();
    window.scrollTo({ top: Math.max(0, top), behavior });
  };

  guideLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      const target = targetFor(link);
      if (!target) {
        return;
      }
      event.preventDefault();
      revealTarget(target);
      markCurrent(link);
      if (window.location.hash !== link.hash) {
        window.history.pushState(null, '', link.hash);
      }
      target.tabIndex = -1;
      target.focus({ preventScroll: true });
      scrollToTarget(target, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth');
    });
  });

  const navigateToHash = () => {
    const link = guideLinks.find((item) => item.hash === window.location.hash);
    const target = link && targetFor(link);
    if (target) {
      revealTarget(target);
      markCurrent(link);
      scrollToTarget(target, 'auto');
    }
  };
  if (window.location.hash) {
    Promise.resolve(document.fonts && document.fonts.ready).then(() => {
      window.requestAnimationFrame(navigateToHash);
    });
  }
  window.addEventListener('hashchange', navigateToHash);

  const observeTargets = () => {
    if (!('IntersectionObserver' in window)) {
      return;
    }
    if (observer) {
      observer.disconnect();
    }
    const visible = new Set();
    observer = new window.IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          visible.add(entry.target);
        } else {
          visible.delete(entry.target);
        }
      });
      const focused = guideLinks.find((link) => targetFor(link) === document.activeElement && visible.has(targetFor(link)));
      const current = focused || guideLinks.find((link) => visible.has(targetFor(link)));
      if (current) {
        markCurrent(current);
      }
    }, { rootMargin: `-${headerOffset()}px 0px -55% 0px`, threshold: 0 });
    guideLinks.forEach((link) => {
      const target = targetFor(link);
      if (target) {
        observer.observe(target);
      }
    });
  };
  observeTargets();
  window.addEventListener('resize', () => {
    syncReaderOffset();
    observeTargets();
  });
  if (header && 'ResizeObserver' in window) {
    new window.ResizeObserver(() => {
      syncReaderOffset();
      observeTargets();
    }).observe(header);
  }
}());
