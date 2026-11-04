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
    table: 'Article table: scroll horizontally to see all columns'
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

  guideLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      const target = targetFor(link);
      if (!target) {
        return;
      }
      event.preventDefault();
      revealTarget(target);
      markCurrent(link);
      window.history.replaceState(null, '', link.hash);
      target.tabIndex = -1;
      target.focus({ preventScroll: true });
      target.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'start'
      });
    });
  });

  const initialLink = guideLinks.find((link) => link.hash === window.location.hash);
  if (initialLink) {
    const target = targetFor(initialLink);
    if (target) {
      revealTarget(target);
      markCurrent(initialLink);
      window.requestAnimationFrame(() => target.scrollIntoView({ block: 'start' }));
    }
  }

  if ('IntersectionObserver' in window) {
    const visible = new Set();
    const observer = new window.IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          visible.add(entry.target);
        } else {
          visible.delete(entry.target);
        }
      });
      const current = guideLinks.find((link) => visible.has(targetFor(link)));
      if (current) {
        markCurrent(current);
      }
    }, { rootMargin: '-100px 0px -55% 0px', threshold: 0 });
    guideLinks.forEach((link) => {
      const target = targetFor(link);
      if (target) {
        observer.observe(target);
      }
    });
  }
}());
