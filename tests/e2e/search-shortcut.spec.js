const { test, expect, webkit } = require('@playwright/test');
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');
const platforms = [
  { name: 'Windows', platform: 'Win32', modern: 'Windows', shortcut: 'Control+k', aria: 'Control+K' },
  { name: 'Linux', platform: 'Linux x86_64', modern: 'Linux', shortcut: 'Control+k', aria: 'Control+K' },
  { name: 'macOS', platform: 'MacIntel', modern: 'macOS', shortcut: 'Meta+k', aria: 'Meta+K' },
  { name: 'iPad platform fallback', platform: 'MacIntel', shortcut: 'Meta+k', aria: 'Meta+K' }
];

async function usePlatform(page, platform) {
  await page.addInitScript(({ platform, modern }) => {
    Object.defineProperty(navigator, 'platform', { configurable: true, value: platform });
    Object.defineProperty(navigator, 'userAgentData', { configurable: true, value: modern ? { platform: modern } : undefined });
  }, platform);
}

for (const platform of platforms) {
  test(`${platform.name} search shortcut opens, refocuses and restores its origin`, async ({ page }) => {
    await usePlatform(page, platform);
    await page.goto(baseURL);
    const origin = page.locator('.header-color-mode-toggle');
    await origin.focus();
    await page.keyboard.press(platform.shortcut);
    const panel = page.locator('[data-search-modal]');
    const input = panel.locator('input[name=s]');
    await expect(input).toBeFocused();
    await expect(page.locator('[data-search-toggle]')).toHaveAttribute('aria-keyshortcuts', platform.aria);
    await input.fill('OpenSSH');
    await panel.locator('.search-modal__close').focus();
    await page.keyboard.press(platform.shortcut);
    await expect(input).toBeFocused();
    await expect(input).toHaveValue('OpenSSH');
    await page.keyboard.press('Escape');
    await expect(panel).toBeHidden();
    await expect(origin).toBeFocused();
  });
}

test('shortcut respects editing, IME, extra modifiers and previously handled events', async ({ page }) => {
  await usePlatform(page, platforms[0]);
  await page.goto(`${baseURL}/contact/`);
  const message = page.locator('#isnx-contact-message');
  await message.fill('Preserve this message without submitting it.');
  const ignoredInEditor = await message.evaluate(element => {
    const event = new KeyboardEvent('keydown', { key: 'k', code: 'KeyK', ctrlKey: true, bubbles: true, cancelable: true });
    element.dispatchEvent(event);
    return !event.defaultPrevented;
  });
  expect(ignoredInEditor).toBeTruthy();
  const ignored = await page.evaluate(() => [
    { key: 'k' }, { key: 'k', metaKey: true }, { key: 'k', ctrlKey: true, altKey: true },
    { key: 'K', ctrlKey: true, shiftKey: true }, { key: 'k', ctrlKey: true, isComposing: true }
  ].map(options => {
    const event = new KeyboardEvent('keydown', { ...options, bubbles: true, cancelable: true });
    document.dispatchEvent(event);
    return !event.defaultPrevented;
  }));
  expect(ignored.every(Boolean)).toBeTruthy();
  await page.evaluate(() => {
    const event = new KeyboardEvent('keydown', { key: 'k', ctrlKey: true, bubbles: true, cancelable: true });
    event.preventDefault(); document.dispatchEvent(event);
  });
  await expect(page.locator('[data-search-modal]')).toBeHidden();
  await page.locator('[data-search-toggle]').focus();
  await page.keyboard.press('Control+k');
  await expect(page.locator('[data-search-modal] input[name=s]')).toBeFocused();
  await expect(message).toHaveValue('Preserve this message without submitting it.');
});

test('shortcut replaces mobile navigation and survives an animated close/reopen', async ({ page }, testInfo) => {
  await usePlatform(page, platforms[0]);
  await page.setViewportSize({ width: testInfo.project.name === 'mobile' ? 390 : 768, height: 900 });
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.addInitScript(() => localStorage.setItem('infosecnexus-color-mode', 'dark'));
  await page.goto(`${baseURL}/live-cybersecurity-brief/`);
  await page.locator('[data-mobile-menu-toggle]').click();
  const menu = page.locator('[data-mobile-panel]');
  await expect(menu).toBeVisible();
  await menu.locator('a[href]').first().focus();
  await page.keyboard.press('Control+k');
  const panel = page.locator('[data-search-modal]');
  await expect(menu).toBeHidden();
  await expect(panel.locator('input[name=s]')).toBeFocused();
  const queuedWhileClosing = await panel.evaluate(element => {
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    const closing = element.dataset.cgClosing === 'true';
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', code: 'KeyK', ctrlKey: true, repeat: true, bubbles: true, cancelable: true }));
    return closing;
  });
  expect(queuedWhileClosing).toBeTruthy();
  await expect(panel).not.toHaveAttribute('data-cg-closing', 'true');
  await expect(panel.locator('input[name=s]')).toBeFocused();
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
  await page.keyboard.press('Escape');
  await expect(panel).toBeHidden();
  await expect(page.locator('[data-search-toggle]')).toBeFocused();
  await expect(page.locator('body')).not.toHaveClass(/has-modal-open/);
  await page.keyboard.press('Control+k');
  await page.evaluate(() => {
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    document.querySelector('[data-mobile-menu-toggle]').click();
  });
  await expect(menu).toBeVisible();
  await expect(panel).toBeHidden();
  await expect(panel).not.toHaveAttribute('data-cg-closing', 'true');
  expect(await menu.evaluate(element => element.contains(document.activeElement))).toBeTruthy();
});

test('search opened by shortcut submits an actual published-post query', async ({ page }) => {
  await usePlatform(page, platforms[0]);
  await page.goto(baseURL);
  await page.keyboard.press('Control+k');
  const input = page.locator('[data-search-modal] input[name=s]');
  await input.fill('OpenSSH');
  await input.press('Enter');
  await expect(page).toHaveURL(url => url.searchParams.get('s') === 'OpenSSH');
  await expect(page.locator('main .post-card').filter({ hasText: /OpenSSH/i }).first()).toBeVisible();
});

test('Safari engine Apple fallback opens search with Command and preserves Control', async ({}, testInfo) => {
  const browser = await webkit.launch();
  try {
    const page = await browser.newPage({ viewport: testInfo.project.use.viewport });
    await usePlatform(page, platforms[3]);
    await page.goto(baseURL);
    await page.keyboard.press('Control+k');
    await expect(page.locator('[data-search-modal]')).toBeHidden();
    await page.keyboard.press('Meta+k');
    await expect(page.locator('[data-search-modal] input[name=s]')).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(page.locator('[data-search-toggle]')).toBeFocused();
  } finally {
    await browser.close();
  }
});
