const { test, expect } = require('@playwright/test');
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');
const sonicPath = '/cve-2026-19868-cve-2026-19868-sonicwall-sonicos-sslvpn-use-of-single-factor/';

test('article byline opens the matching public author profile', async ({ page }) => {
  await page.goto(`${baseURL}${sonicPath}`);
  const author = page.locator('.single-hero a[rel=author]');
  const name = await author.innerText();
  const href = await author.getAttribute('href');
  await author.click();
  await expect(page).toHaveURL(href);
  await expect(page.locator('h1')).toHaveText(name);
  await expect(page.getByRole('heading', { name: 'Sources and context' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Send a correction' })).toHaveAttribute('href', /\/contact\//);
  const profile = await page.locator('script[type="application/ld+json"]').evaluateAll((scripts) => scripts.map((script) => JSON.parse(script.textContent)).find((row) => row['@type'] === 'ProfilePage'));
  expect(profile.mainEntity).toMatchObject({ '@type': 'Person', '@id': `${href}#person`, name, url: href });
  expect(await page.locator('.author-profile .post-card').count()).toBeGreaterThan(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
});

test('current article subscription uses the real protected newsletter form', async ({ page }) => {
  await page.goto(`${baseURL}${sonicPath}`);
  const section = page.locator('.reader-newsletter');
  await expect(section).toHaveCount(1);
  const form = section.locator('form[data-isnx-newsletter]');
  await expect(form).toHaveAttribute('method', 'post');
  expect(new URL(await form.getAttribute('action')).origin).toBe(new URL(baseURL).origin);
  await expect(form.locator('[name=isnx_token]')).not.toHaveValue('');
  await expect(form.locator('[name=isnx_newsletter_nonce]')).not.toHaveValue('');
  await expect(form.locator('input[type=email]')).toHaveAttribute('required', '');
  await section.scrollIntoViewIfNeeded();
  await expect(form.getByRole('button', { name: 'Subscribe' })).toBeInViewport();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
});

test('vendor decisions and distinct-product contextual links retain their evidence', async ({ page }) => {
  await page.goto(`${baseURL}${sonicPath}`);
  const unlock = page.locator('[data-post-unlock-button]');
  if (await unlock.count()) await unlock.click();
  const body = page.locator('.single-entry .entry-content');
  await expect(body).toContainText('7.3.4-7013');
  await expect(body).toContainText('8.2.3-8015');
  await expect(body).toContainText('LDAP, RADIUS and TACACS+');
  await expect(body.locator('.reader-context-link')).toContainText('distinct products');
  await expect(body.locator('.reader-context-link a')).toHaveAttribute('href', /cve-2026-83548/);
  await expect(body.locator('a[href="https://psirt.global.sonicwall.com/vuln-detail/SNWLID-2026-0014"]').first()).toBeVisible();
  await page.goto(`${baseURL}/cve-2026-86218-cve-2026-86218-n-able-n-central-static-code-injection-vulnerability/`);
  if (await unlock.count()) await unlock.click();
  await expect(body).toContainText('2026.3.1.14');
  await expect(body).toContainText('Agent upgrades are not required');
  await expect(body).toContainText('later KEV evidence');
});
