const { test, expect } = require('@playwright/test');
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');
const briefURL = `${baseURL}/live-cybersecurity-brief/`;

async function expectClearHeading(page, hash) {
  await expect.poll(() => page.evaluate((value) => {
    const heading = document.getElementById(decodeURIComponent(value.slice(1)));
    const header = document.querySelector('[data-site-header]');
    return Math.abs(heading.getBoundingClientRect().top - header.getBoundingClientRect().bottom - 18);
  }, hash)).toBeLessThanOrEqual(2);
}

test('tablet guide opens without overlapping share controls and uses one anchor navigation', async ({ page }) => {
  await page.setViewportSize({ width: 1024, height: 900 });
  await page.addInitScript(() => {
    window.articleLayoutShift = 0;
    new PerformanceObserver((list) => list.getEntries().forEach((entry) => {
      if (!entry.hadRecentInput) window.articleLayoutShift += entry.value;
    })).observe({ type: 'layout-shift', buffered: true });
  });
  await page.goto(briefURL, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  expect(await page.evaluate(() => window.articleLayoutShift)).toBeLessThan(0.05);
  const toggle = page.locator('[data-guide-toggle]');
  const list = page.locator('[data-guide-list]');
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  await expect(list).toBeHidden();
  await expect(page.locator('.single-rail .share-links')).toBeHidden();
  await toggle.click();
  await expect(list).toBeVisible();
  const targetLink = list.locator('ol a').first();
  const hash = await targetLink.getAttribute('href');
  await page.evaluate(() => {
    window.articleHistoryCalls = 0;
    const push = history.pushState.bind(history);
    const replace = history.replaceState.bind(history);
    history.pushState = (...args) => { window.articleHistoryCalls += 1; return push(...args); };
    history.replaceState = (...args) => { window.articleHistoryCalls += 1; return replace(...args); };
  });
  await targetLink.click();
  await expect(list).toBeHidden();
  expect(await page.evaluate(() => window.articleHistoryCalls)).toBe(1);
  await expectClearHeading(page, hash);
  await page.reload();
  await expectClearHeading(page, hash);
  await toggle.click();
  await list.evaluate((element) => { element.scrollTop = element.scrollHeight; });
  await toggle.focus();
  await page.setViewportSize({ width: 1366, height: 900 });
  await expect(toggle).toBeHidden();
  await expect(list).toBeVisible();
  await expect(page.locator('.toc a:focus')).toHaveCount(1);
  const focusedBounds = await page.locator('.toc a:focus').boundingBox();
  const listBounds = await list.boundingBox();
  expect(focusedBounds.y).toBeGreaterThanOrEqual(listBounds.y - 1);
  expect(focusedBounds.y + focusedBounds.height).toBeLessThanOrEqual(listBounds.y + listBounds.height + 1);
  await page.setViewportSize({ width: 390, height: 844 });
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  await expect(list).toBeHidden();
  await expect(toggle).toBeFocused();
});

test('desktop guide and sharing fit a short viewport and align with the article hero', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 1366, height: 600 });
  await page.addInitScript((mode) => localStorage.setItem('infosecnexus-color-mode', mode), testInfo.project.name === 'mobile' ? 'dark' : 'light');
  await page.goto(briefURL);
  await page.evaluate(() => document.fonts.ready);
  const guideLink = page.locator('.toc > ol > li > a').first();
  await guideLink.click();
  await expectClearHeading(page, await guideLink.getAttribute('href'));
  const geometry = await page.evaluate(() => {
    const rect = (selector) => document.querySelector(selector).getBoundingClientRect();
    const hero = rect('.single-hero');
    const reader = rect('.layout-shell--single');
    const rail = rect('.single-rail');
    const content = rect('.entry-content-wrap');
    return { aligned: Math.abs(hero.left - reader.left) < 1 && Math.abs(hero.width - reader.width) < 1, railWidth: rail.width, railBottom: rail.bottom, gap: content.left - rail.right };
  });
  expect(geometry.aligned).toBeTruthy();
  expect(geometry.railWidth).toBeGreaterThanOrEqual(275);
  expect(geometry.railBottom).toBeLessThanOrEqual(601);
  expect(geometry.gap).toBeGreaterThanOrEqual(23);
  const shares = page.locator('.share-links a');
  await expect(shares).toHaveCount(3);
  for (let index = 0; index < 3; index += 1) await expect(shares.nth(index)).toBeInViewport();
});

test('mobile article guide and full reading remain available without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
  try {
    const page = await context.newPage();
    await page.goto(briefURL);
    await expect(page.locator('[data-guide-list]')).toBeVisible();
    await expect(page.locator('[data-guide-toggle]')).toBeHidden();
    const rest = page.locator('[data-post-unlock-content]');
    if (await rest.count()) await expect(rest).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
  } finally {
    await context.close();
  }
});

test('article guide returns to visible native links if its enhancement script cannot load', async ({ page }) => {
  await page.setViewportSize({ width: 1024, height: 900 });
  await page.route('**/assets/js/article-content.js*', (route) => route.abort());
  await page.goto(briefURL);
  await expect(page.locator('[data-guide-list]')).toBeVisible({ timeout: 6000 });
  await expect(page.locator('[data-guide-toggle]')).toBeHidden();
  expect(await page.locator('.toc a').count()).toBeGreaterThan(0);
});
