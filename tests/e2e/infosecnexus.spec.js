const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const baseURL = process.env.WP_BASE_URL || 'http://127.0.0.1:8888';

test('home page renders header, content, and footer', async ({ page }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });
  await expect(page.locator('body')).toHaveClass(/infosecnexus/);
  await expect(page.locator('.site-header')).toBeVisible();
  await expect(page.locator('.site-footer')).toBeVisible();
});

test('homepage post cards use distinct generated artwork', async ({ page }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });

  const artwork = page.locator('img[src*="/infosecnexus-artwork/"]');
  const count = await artwork.count();
  expect(count).toBeGreaterThanOrEqual(3);

  const sources = await artwork.evaluateAll((images) => images.map((image) => image.currentSrc || image.src));
  expect(new Set(sources).size).toBe(count);
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
