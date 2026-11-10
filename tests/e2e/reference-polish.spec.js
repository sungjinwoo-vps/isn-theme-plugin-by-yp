const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');

test('header mode stays reachable and footer navigation has no overlapping links', async ({ page }, testInfo) => {
  const width = testInfo.project.name === 'mobile' ? 320 : 1024;
  await page.setViewportSize({ width, height: 900 });
  await page.goto(baseURL);
  const toggle = page.locator('.header-color-mode-toggle');
  await expect(toggle).toBeVisible();
  const bounds = await toggle.boundingBox();
  expect(bounds.y + bounds.height).toBeLessThan(100);
  expect(bounds.width).toBeGreaterThanOrEqual(44);
  const footer = page.locator('.site-footer');
  await expect(footer.locator('[data-color-mode-toggle]')).toHaveCount(0);
  await expect(footer.getByRole('link', { name: 'Get the Daily Brief' })).toHaveCount(0);
  const geometry = await footer.locator('a:visible').evaluateAll((links) => {
    const rects = links.map((link) => link.getBoundingClientRect());
    let collisions = 0;
    rects.forEach((rect, index) => rects.slice(index + 1).forEach((other) => {
      if (Math.min(rect.right, other.right) - Math.max(rect.left, other.left) > 1 && Math.min(rect.bottom, other.bottom) - Math.max(rect.top, other.top) > 1) collisions += 1;
    }));
    return { collisions, left: Math.min(...rects.map((rect) => rect.left)), right: Math.max(...rects.map((rect) => rect.right)) };
  });
  expect(geometry.collisions).toBe(0);
  expect(geometry.left).toBeGreaterThanOrEqual(0);
  expect(geometry.right).toBeLessThanOrEqual(width);
  const alignment = await footer.evaluate((element) => {
    const bounds = element.getBoundingClientRect();
    const legal = [...element.querySelectorAll('.ivory-footer__legal li')].map((item) => item.getBoundingClientRect());
    const copyright = document.createRange();
    copyright.selectNodeContents(element.querySelector('.ivory-footer__copyright p'));
    const copyBounds = copyright.getBoundingClientRect();
    return {
      center: bounds.x + bounds.width / 2,
      copyrightCenter: copyBounds.x + copyBounds.width / 2,
      legalCenter: (Math.min(...legal.map((rect) => rect.left)) + Math.max(...legal.map((rect) => rect.right))) / 2,
      categoryEdges: [...element.querySelectorAll('.ivory-footer__topics a')].map((link) => link.getBoundingClientRect().left - bounds.left),
      pageEdges: [...element.querySelectorAll('.ivory-footer__site a')].map((link) => bounds.right - link.getBoundingClientRect().right)
    };
  });
  expect(Math.abs(alignment.copyrightCenter - alignment.center)).toBeLessThan(2);
  expect(Math.abs(alignment.legalCenter - alignment.center)).toBeLessThan(2);
  expect(alignment.categoryEdges.every((edge) => Math.abs(edge) < 2)).toBeTruthy();
  expect(alignment.pageEdges.every((edge) => Math.abs(edge) < 2)).toBeTruthy();
});

test('saved dark mode keeps homepage and open search text readable', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: testInfo.project.name === 'mobile' ? 390 : 1366, height: 900 });
  await page.addInitScript(() => localStorage.setItem('infosecnexus-color-mode', 'dark'));
  await page.goto(baseURL, { waitUntil: 'networkidle' });
  await expect(page.locator('html')).toHaveAttribute('data-color-mode', 'dark');
  await expect.poll(() => page.locator('.cg-hero h1').evaluate((heading) => getComputedStyle(heading).opacity)).toBe('1');
  const home = await new AxeBuilder({ page }).withRules(['color-contrast']).analyze();
  expect(home.violations).toEqual([]);
  await page.locator('[data-search-toggle]').click();
  await expect(page.locator('[data-search-modal] input[name=s]')).toBeFocused();
  const modal = await new AxeBuilder({ page }).include('[data-search-modal]').withRules(['color-contrast']).analyze();
  expect(modal.violations).toEqual([]);
});
