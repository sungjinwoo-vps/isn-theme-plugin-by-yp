const { test, expect } = require('@playwright/test');
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');

async function expectCentered(control) {
  const offset = await control.evaluate((element) => {
    const parent = element.getBoundingClientRect();
    const icon = element.querySelector('svg').getBoundingClientRect();
    return { x: Math.abs(icon.x + icon.width / 2 - parent.x - parent.width / 2), y: Math.abs(icon.y + icon.height / 2 - parent.y - parent.height / 2) };
  });
  expect(offset.x).toBeLessThanOrEqual(0.75);
  expect(offset.y).toBeLessThanOrEqual(0.75);
}

test('wide desktop hero retains its reading width and aligns with the header', async ({ browser }, testInfo) => {
  const context = await browser.newContext({ viewport: { width: testInfo.project.name === 'desktop' ? 2124 : 2560, height: 1000 }, isMobile: false });
  try {
    const page = await context.newPage();
    await page.goto(baseURL);
    await page.evaluate(() => document.fonts.ready);
    const geometry = await page.evaluate(() => {
      const hero = document.querySelector('.cg-hero').getBoundingClientRect();
      const copy = document.querySelector('.cg-hero__copy').getBoundingClientRect();
      const header = document.querySelector('.site-header__main').getBoundingClientRect();
      const heading = document.querySelector('.cg-hero h1');
      return { heroHeight: hero.height, copyWidth: copy.width, leftOffset: Math.abs(copy.left - header.left), lines: heading.getBoundingClientRect().height / parseFloat(getComputedStyle(heading).lineHeight) };
    });
    expect(geometry.copyWidth).toBeGreaterThanOrEqual(650);
    expect(geometry.leftOffset).toBeLessThanOrEqual(12);
    expect(geometry.heroHeight).toBeLessThanOrEqual(560);
    expect(geometry.lines).toBeLessThanOrEqual(2.1);
  } finally {
    await context.close();
  }
});

test('search and menu glyphs stay centered before and after scrolling', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: testInfo.project.name === 'mobile' ? 320 : 768, height: 900 });
  await page.goto(baseURL);
  await page.evaluate(() => document.fonts.ready);
  for (const y of [0, 700]) {
    await page.evaluate((position) => scrollTo(0, position), y);
    if (y) await expect(page.locator('.site-header')).toHaveClass(/is-scrolled/);
    const controls = page.locator('.site-header__actions .icon-button:visible');
    for (let index = 0; index < await controls.count(); index += 1) await expectCentered(controls.nth(index));
  }
  await page.locator('[data-search-toggle]').first().click();
  await expect(page.locator('[data-search-modal]')).toBeVisible();
  await expectCentered(page.locator('.search-modal__close'));
});

test('responsive search dialog gives its copy and field a full usable column', async ({ page }, testInfo) => {
  const width = testInfo.project.name === 'mobile' ? 390 : 768;
  await page.setViewportSize({ width, height: 900 });
  await page.goto(baseURL);
  await page.locator('[data-search-toggle]').first().click();
  const panel = page.locator('[data-search-modal]');
  await expect(panel).toBeVisible();
  const copy = await panel.locator('.search-modal__copy').boundingBox();
  const input = await panel.locator('input[name=s]').boundingBox();
  expect(copy.width).toBeGreaterThan(width * 0.65);
  expect(copy.x).toBeGreaterThanOrEqual(0);
  expect(copy.x + copy.width).toBeLessThanOrEqual(width);
  expect(input.width).toBeGreaterThanOrEqual(200);
  expect(input.x + input.width).toBeLessThanOrEqual(copy.x + copy.width + 1);
  await expect(panel.locator('input[name=s]')).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(panel).toBeHidden();
});
