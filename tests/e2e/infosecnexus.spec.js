const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const baseURL = process.env.WP_BASE_URL || 'http://127.0.0.1:8888';

test('home page renders header, content, and footer', async ({ page }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });
  await expect(page.locator('body')).toHaveClass(/infosecnexus/);
  await expect(page.locator('.site-header')).toBeVisible();
  await expect(page.locator('.site-footer')).toBeVisible();
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
