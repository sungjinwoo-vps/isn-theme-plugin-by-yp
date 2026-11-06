const { test, expect } = require('@playwright/test');
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');

test('small-screen sticky header keeps every control visible and topics collapse accessibly', async ({ page }, testInfo) => {
  const width = testInfo.project.name === 'mobile' ? 320 : 390;
  await page.setViewportSize({ width, height: 844 });
  await page.goto(baseURL);
  await page.evaluate(() => document.fonts.ready);
  const main = page.locator('.site-header__main');
  const before = await main.boundingBox();
  await page.evaluate(() => scrollTo(0, 700));
  await expect(page.locator('.site-header')).toHaveClass(/is-scrolled/);
  const after = await main.boundingBox();
  expect(Math.abs(before.x - after.x)).toBeLessThan(1);
  expect(Math.abs(before.width - after.width)).toBeLessThan(1);
  const controls = await page.locator('.site-header__actions > :visible').evaluateAll((items) => items.map((item) => {
    const rect = item.getBoundingClientRect();
    return { left: rect.left, right: rect.right, width: rect.width, height: rect.height };
  }));
  controls.forEach((rect) => {
    expect(rect.left).toBeGreaterThanOrEqual(0);
    expect(rect.right).toBeLessThanOrEqual(width);
    expect(rect.width).toBeGreaterThanOrEqual(44);
    expect(rect.height).toBeGreaterThanOrEqual(44);
  });
  const menu = page.locator('[data-mobile-menu-toggle]');
  await menu.click();
  const disclosure = page.locator('[data-mobile-submenu-toggle]');
  await expect(disclosure).toHaveAttribute('aria-expanded', 'false');
  const topics = page.locator(`#${await disclosure.getAttribute('aria-controls')}`);
  await expect(topics).toBeHidden();
  await disclosure.click();
  await expect(disclosure).toHaveAttribute('aria-expanded', 'true');
  await expect(topics).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(page.locator('[data-mobile-panel]')).toBeHidden();
  await expect(menu).toBeFocused();
});

test('mobile hero uses a compact clear image and keeps both actions on one row', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: testInfo.project.name === 'mobile' ? 320 : 390, height: 844 });
  await page.goto(baseURL);
  await page.evaluate(() => document.fonts.ready);
  const art = await page.locator('.cg-hero__art').boundingBox();
  expect(art.height).toBeLessThanOrEqual(240);
  expect(art.width).toBeLessThanOrEqual(page.viewportSize().width + 1);
  const image = page.locator('.cg-hero__image');
  await expect.poll(() => image.evaluate((element) => element.complete && element.naturalHeight > 0)).toBeTruthy();
  expect(await image.evaluate((element) => element.naturalHeight / element.getBoundingClientRect().height)).toBeGreaterThanOrEqual(1);
  const actions = await page.locator('.cg-actions > a').evaluateAll((items) => items.map((item) => item.getBoundingClientRect().top));
  expect(Math.abs(actions[0] - actions[1])).toBeLessThan(1);
  expect((await page.locator('.cg-hero').boundingBox()).height).toBeLessThan(650);
});

test('cookie preferences do not cover content and dismissal never accepts consent', async ({ page }) => {
  await page.addInitScript(() => {
    localStorage.removeItem('infosecnexus-cookie-ok');
    sessionStorage.removeItem('infosecnexus-cookie-dismissed');
  });
  await page.goto(baseURL);
  const notice = page.locator('[data-isnx-cookie]');
  await expect(notice).toBeVisible();
  expect(await notice.evaluate((element) => getComputedStyle(element).position)).not.toBe('fixed');
  const rects = await page.evaluate(() => ({ notice: document.querySelector('[data-isnx-cookie]').getBoundingClientRect().top, footer: document.querySelector('.site-footer').getBoundingClientRect().bottom }));
  expect(rects.notice).toBeGreaterThanOrEqual(rects.footer);
  await page.evaluate(() => {
    window.testConsentEvents = 0;
    document.addEventListener('infosecnexus:ads-consent', () => { window.testConsentEvents += 1; });
  });
  await page.locator('[data-isnx-cookie-dismiss]').click();
  await expect(notice).toBeHidden();
  expect(await page.evaluate(() => localStorage.getItem('infosecnexus-cookie-ok'))).toBeNull();
  expect(await page.evaluate(() => window.testConsentEvents)).toBe(0);
  expect(await page.evaluate(() => sessionStorage.getItem('infosecnexus-cookie-dismissed'))).toBe('1');
});
