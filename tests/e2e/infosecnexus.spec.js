const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const baseURL = process.env.WP_BASE_URL || 'http://127.0.0.1:8888';

test('home page renders header, content, and footer', async ({ page }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });
  await expect(page.locator('body')).toHaveClass(/infosecnexus/);
  await expect(page.locator('.site-header')).toBeVisible();
  await expect(page.locator('.site-footer')).toBeVisible();
});

test('color mode toggle is keyboard reachable', async ({ page }) => {
  await page.goto(baseURL);
  const toggle = page.locator('[data-color-mode-toggle]').first();
  await expect(toggle).toBeVisible();
  await toggle.focus();
  await page.keyboard.press('Enter');
  await expect(page.locator('html')).toHaveAttribute('data-color-mode', /dark|light/);
});

test('axe smoke check has no serious violations', async ({ page }) => {
  await page.goto(baseURL, { waitUntil: 'networkidle' });
  const results = await new AxeBuilder({ page })
    .disableRules(['color-contrast'])
    .analyze();
  const serious = results.violations.filter((violation) => ['serious', 'critical'].includes(violation.impact));
  expect(serious).toEqual([]);
});

