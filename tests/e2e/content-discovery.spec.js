const { test, expect } = require('@playwright/test');
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');

test('blog topic disclosure brings real briefings closer on mobile and tablet', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: testInfo.project.name === 'mobile' ? 390 : 1024, height: 900 });
  await page.goto(`${baseURL}/blog/`);
  const topics = page.locator('[data-blog-topics]');
  await expect(topics).not.toHaveAttribute('open', '');
  const firstStory = page.locator('.blog-hub__latest .post-card').first();
  await expect(firstStory).toBeVisible();
  expect((await firstStory.boundingBox()).y).toBeLessThan(900);
  const summary = topics.locator('summary');
  await summary.focus();
  await page.keyboard.press('Enter');
  await expect(topics).toHaveAttribute('open', '');
  await expect(topics.locator('a').first()).toBeVisible();
  await page.keyboard.press('Enter');
  await expect(topics).not.toHaveAttribute('open', '');
  await page.setViewportSize({ width: 1366, height: 900 });
  await expect(topics).toHaveAttribute('open', '');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
});

test('promoted categories lead to standalone published articles', async ({ page, request }) => {
  await page.goto(baseURL);
  const destinations = await page.locator('.site-nav .sub-menu a, .ivory-footer__topics a, .cg-topics__grid a, .search-modal__quick a').evaluateAll((links) => [...new Set(links.map((link) => link.href).filter((url) => new URL(url).pathname.startsWith('/category/')))]);
  expect(destinations.length).toBeGreaterThan(0);
  for (const destination of destinations) {
    const response = await request.get(destination);
    expect(response.status(), destination).toBe(200);
    const html = await response.text();
    const stories = [...html.matchAll(/class="post-card__link"\s+href="([^"]+)"/g)].map((match) => new URL(match[1], baseURL).pathname);
    expect(stories.some((route) => route !== '/live-cybersecurity-brief/'), destination).toBeTruthy();
  }
});

test('blog topic links remain available without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
  const plain = await context.newPage();
  await plain.goto(`${baseURL}/blog/`);
  const topics = plain.locator('[data-blog-topics]');
  await expect(topics).toHaveAttribute('open', '');
  await expect(topics.locator('a').first()).toBeVisible();
  await expect(plain.locator('.blog-hub__latest .post-card').first()).toBeVisible();
  await context.close();
});
