const { test, expect } = require('@playwright/test');
const { createHash } = require('node:crypto');
const AxeBuilder = require('@axe-core/playwright').default;

const baseURL = process.env.WP_BASE_URL || 'http://127.0.0.1:8888';

test('home page renders header, content, and footer', async ({ page }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });
  await expect(page.locator('body')).toHaveClass(/infosecnexus/);
  await expect(page.locator('.site-header')).toBeVisible();
  await expect(page.locator('.site-footer')).toBeVisible();
});

test('homepage uses one permanent live brief without repeating it in the latest grid', async ({ page, request }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });

  const heroPath = new URL(await page.locator('.home-hero__lead').getAttribute('href')).pathname;
  expect(heroPath).toBe('/live-cybersecurity-brief/');

  const latestPaths = await page.locator('.latest-grid .intel-card').evaluateAll((cards) => cards.map((card) => new URL(card.href).pathname));
  expect(latestPaths).not.toContain('/live-cybersecurity-brief/');
  expect(latestPaths.some((path) => /\/\d{4}-\d{2}-\d{2}-live-cybersecurity-brief\/$/.test(path))).toBeFalsy();
  expect(latestPaths.some((path) => /\/\d{4}-\d{2}-\d{2}-(?:daily-cve-watch|cyber-security-brief|linux-security-brief|devops-security-brief|ai-security-brief|tutorial-run-daily-vulnerability-standup|cloud-security-brief|windows-security-brief|network-security-brief|web-security-brief)/.test(path))).toBeFalsy();

  const breakingPaths = await page.locator('.home-hero__side .side-story').evaluateAll((cards) => cards.map((card) => new URL(card.href).pathname));
  expect(breakingPaths.length).toBeGreaterThanOrEqual(2);
  expect(new Set(breakingPaths).size).toBe(breakingPaths.length);

  await page.goto(`${baseURL}/live-cybersecurity-brief/`, { waitUntil: 'networkidle' });
  await expect(page.locator('nav.post-navigation')).toHaveCount(0);
  await expect(page.locator('.single-hero .entry-meta time')).toContainText('Updated');
  await expect(page.locator('.single-hero .entry-meta time')).toHaveAttribute('datetime', /^\d{4}-\d{2}-\d{2}T/);

  const sitemap = await request.get(`${baseURL}/news-sitemap.xml`);
  expect(sitemap.ok()).toBeTruthy();
  expect(await sitemap.text()).not.toContain('/live-cybersecurity-brief/');
});

test('rolling brief uses its updated date throughout archive surfaces', async ({ page }) => {
  await page.goto(`${baseURL}/category/cybersecurity/`, { waitUntil: 'networkidle' });

  const rollingCard = page.locator('.post-card').filter({ has: page.locator('a[href$="/live-cybersecurity-brief/"]') }).first();
  await expect(rollingCard.locator('.entry-meta time')).toContainText('Updated');
  await expect(rollingCard.locator('.entry-meta time')).toHaveAttribute('datetime', /^\d{4}-\d{2}-\d{2}T/);

  const latestItem = page.locator('.sidebar-post-list li').filter({ has: page.locator('a[href$="/live-cybersecurity-brief/"]') });
  await expect(latestItem.locator('time')).toContainText('Updated');
  await expect(latestItem.locator('time')).toHaveAttribute('datetime', /^\d{4}-\d{2}-\d{2}T/);
});

test('different homepage posts use distinct generated artwork', async ({ page, request }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });

  const artwork = page.locator('img[src*="/infosecnexus-artwork/"]');
  const count = await artwork.count();
  expect(count).toBeGreaterThanOrEqual(2);

  const placements = await artwork.evaluateAll((images) => images.map((image) => ({
    article: image.closest('a')?.href || image.alt,
    source: image.currentSrc || image.src
  })));
  const sourcesByArticle = new Map();
  placements.forEach(({ article, source }) => {
    if (!sourcesByArticle.has(article)) {
      sourcesByArticle.set(article, source);
    }
  });
  const sources = [...sourcesByArticle.values()];
  expect(sources.length).toBeGreaterThanOrEqual(2);
  expect(new Set(sources).size).toBe(sources.length);

  const pixelHashes = await Promise.all(
    sources.map(async (source) => {
      const response = await request.get(source);
      expect(response.ok()).toBeTruthy();
      return createHash('sha256').update(await response.body()).digest('hex');
    })
  );
  expect(new Set(pixelHashes).size).toBe(sources.length);
});

test('header controls are keyboard reachable', async ({ page }, testInfo) => {
  await page.goto(baseURL);

  if (testInfo.project.name === 'mobile') {
    const toggle = page.locator('[data-mobile-menu-toggle]').first();
    await expect(toggle).toBeVisible();
    await toggle.focus();
    await page.keyboard.press('Enter');
    await expect(page.locator('[data-mobile-panel]')).toBeVisible();
    return;
  }

  const toggle = page.locator('[data-search-toggle]').first();
  await expect(toggle).toBeVisible();
  await toggle.focus();
  await page.keyboard.press('Enter');
  await expect(page.locator('[data-search-modal]')).toBeVisible();
});

test('axe smoke check has no serious violations', async ({ page }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });
  const results = await new AxeBuilder({ page }).analyze();
  const serious = results.violations.filter((violation) => ['serious', 'critical'].includes(violation.impact));
  expect(serious).toEqual([]);
});

test('public forms use secure same-site handlers and anti-spam fields', async ({ page }) => {
  await page.goto(`${baseURL}/contact/`, { waitUntil: 'networkidle' });

  const contact = page.locator('.isnx-contact-form').first();
  await expect(contact).toBeVisible();
  await expect(contact).toHaveAttribute('method', 'post');
  const contactAction = new URL(await contact.getAttribute('action'));
  expect(contactAction.origin).toBe(new URL(baseURL).origin);
  expect(contactAction.pathname).toMatch(/\/wp-admin\/admin-post\.php$/);
  await expect(contact.locator('input[name="isnx_token"]')).toHaveAttribute('value', /^[a-f0-9]{64}$/);
  await expect(contact).toHaveAttribute('data-isnx-contact-guard-url', /\/wp-json\/infosecnexus\/v1\/contact-challenge$/);
  await expect(contact.locator('input[name="isnx_guard_issued"]')).not.toHaveValue('');
  await expect(contact.locator('input[name="isnx_guard_nonce"]')).toHaveValue(/^[a-f0-9]{32}$/);
  await expect(contact.locator('input[name="isnx_guard_token"]')).toHaveValue(/^[a-f0-9]{64}$/);
  await expect(contact.locator('[data-isnx-contact-submit]')).toBeEnabled({ timeout: 5000 });
  await expect(contact.locator('input[name="name"]')).toHaveAttribute('autocomplete', 'name');
  await expect(contact.locator('input[name="email"]')).toHaveAttribute('autocomplete', 'email');
  await expect(contact.locator('textarea[name="message"]')).toHaveAttribute('required', '');
  expect(await contact.evaluate((form) => form.previousElementSibling?.tagName)).not.toBe('P');
  await expect(page.locator('a[href="mailto:yashpatel@infosecnexus.com"]')).toHaveCount(3);
  await expect(page.locator('body')).not.toContainText('contact@infosecnexus.com');

  const challenge = await page.request.get(await contact.getAttribute('data-isnx-contact-guard-url'));
  expect(challenge.ok()).toBeTruthy();
  expect(challenge.headers()['cache-control']).toContain('no-store');
  expect(await challenge.json()).toMatchObject({
    issued: expect.any(Number),
    nonce: expect.stringMatching(/^[a-f0-9]{32}$/),
    token: expect.stringMatching(/^[a-f0-9]{64}$/)
  });

  await page.goto(baseURL, { waitUntil: 'networkidle' });
  const newsletter = page.locator('[data-isnx-newsletter]').first();
  await expect(newsletter).toBeVisible();
  await expect(newsletter).toHaveAttribute('method', 'post');
  const newsletterAction = new URL(await newsletter.getAttribute('action'));
  expect(newsletterAction.origin).toBe(new URL(baseURL).origin);
  expect(newsletterAction.pathname).toMatch(/\/wp-admin\/admin-post\.php$/);
  await expect(newsletter.locator('input[name="isnx_token"]')).toHaveAttribute('value', /^[a-f0-9]{64}$/);
  await expect(newsletter.locator('input[name="email"]')).toHaveAttribute('autocomplete', 'email');
});

test('SEO, agent discovery, and deferred ads are present', async ({ page, request }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });

  await expect(page.locator('meta[name="description"]')).toHaveAttribute('content', /cybersecurity/i);
  await expect(page.locator('#infosecnexus-adsense-config')).toHaveCount(1);
  await expect(page.locator('script[data-infosecnexus-adsense]')).toHaveCount(0);

  const llms = await request.get(`${baseURL}/llms.txt`);
  expect(llms.ok()).toBeTruthy();
  expect(llms.headers()['content-type']).toContain('text/plain');
  expect(await llms.text()).toMatch(/^# InfoSecNexus/m);
});

test('published briefings hide internal notes and use topic-aware analysis', async ({ page, request }) => {
  const response = await request.get(`${baseURL}/wp-json/wp/v2/posts?per_page=1&orderby=date&order=desc`);
  expect(response.ok()).toBeTruthy();

  const [post] = await response.json();
  await page.goto(post.link, { waitUntil: 'networkidle' });

  const article = page.locator('.single-entry').first();
  await expect(article).toBeVisible();
  await expect(article).toContainText('Why it matters:');
  await expect(article).toContainText('What to verify:');
  await expect(article).not.toContainText(/Live verification|Validation checklist|Accuracy and source notes|Generator note/i);

  const featuredImage = article.locator('.single-hero__media img').first();
  await expect(featuredImage).toBeVisible();
  const renderedImage = await featuredImage.evaluate((image) => ({
    width: image.naturalWidth,
    height: image.naturalHeight
  }));
  expect(renderedImage.width).toBeGreaterThanOrEqual(300);
  expect(renderedImage.height).toBeGreaterThanOrEqual(160);

  const hasHorizontalOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1
  );
  expect(hasHorizontalOverflow).toBeFalsy();
});
