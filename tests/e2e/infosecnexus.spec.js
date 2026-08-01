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

test('homepage post cards use distinct generated artwork', async ({ page, request }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });

  const artwork = page.locator('img[src*="/infosecnexus-artwork/"]');
  const count = await artwork.count();
  expect(count).toBeGreaterThanOrEqual(3);

  const sources = await artwork.evaluateAll((images) => images.map((image) => image.currentSrc || image.src));
  expect(new Set(sources).size).toBe(count);

  const pixelHashes = await Promise.all(
    sources.map(async (source) => {
      const response = await request.get(source);
      expect(response.ok()).toBeTruthy();
      return createHash('sha256').update(await response.body()).digest('hex');
    })
  );
  expect(new Set(pixelHashes).size).toBe(count);
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
  await expect(contact.locator('input[name="name"]')).toHaveAttribute('autocomplete', 'name');
  await expect(contact.locator('input[name="email"]')).toHaveAttribute('autocomplete', 'email');
  await expect(contact.locator('textarea[name="message"]')).toHaveAttribute('required', '');
  expect(await contact.evaluate((form) => form.previousElementSibling?.tagName)).not.toBe('P');
  await expect(page.locator('a[href="mailto:yashpatel@infosecnexus.com"]')).toHaveCount(3);
  await expect(page.locator('body')).not.toContainText('contact@infosecnexus.com');

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
