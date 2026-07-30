(function () {
  'use strict';

  const config = window.infosecnexusToolkit || {};

  function debounce(callback, delay) {
    let timeout = 0;
    return function (...args) {
      window.clearTimeout(timeout);
      timeout = window.setTimeout(() => callback.apply(this, args), delay);
    };
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, (character) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    })[character]);
  }

  const liveSearch = document.querySelector('[data-isnx-live-search]');
  const liveInput = document.querySelector('[data-isnx-live-search-input]');
  const liveResults = document.querySelector('[data-isnx-live-search-results]');
  let searchController = null;

  function closeSearch() {
    if (liveSearch) {
      liveSearch.hidden = true;
    }
  }

  function openSearch() {
    if (!liveSearch || !liveInput) {
      return;
    }
    liveSearch.hidden = false;
    liveInput.focus();
  }

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-live-search-open]')) {
      openSearch();
    }
    if (event.target.closest('[data-isnx-live-search-close]')) {
      closeSearch();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeSearch();
    }
  });

  if (liveInput && liveResults) {
    liveInput.addEventListener('input', debounce(async () => {
      const query = liveInput.value.trim();
      if (query.length < 2) {
        liveResults.innerHTML = '';
        return;
      }
      if (searchController) {
        searchController.abort();
      }
      searchController = new AbortController();
      const params = new URLSearchParams({ q: query });
      const response = await fetch(`${config.restUrl}search?${params.toString()}`, {
        signal: searchController.signal,
        headers: { 'X-WP-Nonce': config.restNonce || '' }
      }).catch(() => null);
      if (!response || !response.ok) {
        return;
      }
      const payload = await response.json();
      liveResults.innerHTML = (payload.results || []).map((item) => (
        `<a class="isnx-live-search__result" role="option" href="${escapeHtml(item.url)}"><strong>${escapeHtml(item.title)}</strong><span>${escapeHtml(item.type)}</span><small>${escapeHtml(item.excerpt)}</small></a>`
      )).join('') || '<p>No results found.</p>';
    }, 240));
  }

  document.querySelectorAll('[data-isnx-newsletter]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const status = form.querySelector('[data-isnx-newsletter-status]');
      const button = form.querySelector('button[type="submit"]');
      const formData = new FormData(form);
      form.setAttribute('aria-busy', 'true');
      if (button) {
        button.disabled = true;
      }
      if (status) {
        status.textContent = 'Submitting securely...';
        status.classList.remove('is-error');
      }
      const response = await fetch(`${config.restUrl}newsletter`, {
        method: 'POST',
        headers: { 'X-WP-Nonce': config.restNonce || '' },
        body: formData
      }).catch(() => null);
      const payload = response ? await response.json().catch(() => ({})) : {};
      if (status) {
        status.textContent = payload.message || 'Unable to subscribe right now.';
        status.classList.toggle('is-error', !response || !response.ok);
      }
      if (response && response.ok) {
        form.reset();
      }
      form.removeAttribute('aria-busy');
      if (button) {
        button.disabled = false;
      }
    });
  });

  document.querySelectorAll('.isnx-contact-form').forEach((form) => {
    form.addEventListener('submit', () => {
      if (!form.checkValidity()) {
        return;
      }
      const button = form.querySelector('button[type="submit"]');
      form.setAttribute('aria-busy', 'true');
      if (button) {
        button.disabled = true;
        button.textContent = 'Sending...';
      }
    });
  });

  const cookie = document.querySelector('[data-isnx-cookie]');
  const cookieAccepted = () => {
    try {
      return localStorage.getItem('infosecnexus-cookie-ok') === '1';
    } catch {
      return false;
    }
  };
  if (cookie && !cookieAccepted()) {
    cookie.hidden = false;
  }
  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-isnx-cookie-accept]')) {
      try {
        localStorage.setItem('infosecnexus-cookie-ok', '1');
      } catch {
        document.documentElement.dataset.cookiePreference = 'accepted';
      }
      if (cookie) {
        cookie.hidden = true;
      }
      document.dispatchEvent(new CustomEvent('infosecnexus:ads-consent'));
    }
  });

  document.querySelectorAll('[data-isnx-popup]').forEach((popup) => {
    window.setTimeout(() => {
      if (!sessionStorage.getItem('infosecnexus-popup-shown')) {
        popup.hidden = false;
        sessionStorage.setItem('infosecnexus-popup-shown', '1');
      }
    }, 1200);
  });

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-isnx-popup-close]')) {
      const popup = event.target.closest('[data-isnx-popup]');
      if (popup) {
        popup.hidden = true;
      }
    }
  });
}());
