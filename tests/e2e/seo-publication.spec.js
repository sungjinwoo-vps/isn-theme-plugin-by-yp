const { test, expect } = require('@playwright/test');
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');
const articles = [
  { path: '/cve-2025-62593-cve-2025-62593-ray-project-ray-code-injection-vulnerability/', title: 'Ray CVE-2025-62593: DNS Rebinding Risk and the 2.52.0 Fix', published: '2026-08-17T14:15:23Z', source: 'https://github.com/ray-project/ray/security/advisories/GHSA-q279-jhrf-cc6v' },
  { path: '/cve-2026-123158-cve-2026-123158-openbsd-openssh-unexpected-status-code-or/', title: 'OpenSSH CVE-2026-123158: Alpine Fixes and SSH Checks', published: '2026-11-09T16:45:24Z', source: 'https://alpinelinux.org/posts/2026-11-05-openssh-password-authentication-bypass/' }
];
test('reviewed article search metadata and schema preserve original publication identity', async ({ page }) => {
  for (const article of articles) {
    const response = await page.goto(baseURL + article.path);
    expect(response.status()).toBe(200);
    await expect(page).toHaveTitle(article.title);
    await expect(page.locator('meta[property="og:title"]')).toHaveAttribute('content', article.title);
    await expect(page.locator('meta[name="twitter:title"]')).toHaveAttribute('content', article.title);
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', baseURL + article.path);
    const h1 = page.locator('h1');
    await expect(h1).toHaveCount(1);
    expect(await h1.innerText()).not.toBe(article.title);
    const schema = await page.locator('script[type="application/ld+json"]').evaluateAll(scripts => scripts.map(s => JSON.parse(s.textContent)).find(s => s['@type'] === 'NewsArticle'));
    expect(Date.parse(schema.datePublished)).toBe(Date.parse(article.published));
    expect(schema.headline).toBe(await h1.innerText());
    expect(schema.author.name).toBe('Yash Patel');
    expect(schema.citation.some(citation => citation.url === article.source)).toBeTruthy();
    const description = await page.locator('meta[name="description"]').getAttribute('content');
    expect(description.length).toBeGreaterThan(70);
    await expect(page.locator('meta[property="og:description"]')).toHaveAttribute('content', description);
    expect(schema.description).toBe(description);
  }
});
test('reviewed exact package tokens, inventory code and article navigation remain readable', async ({ page }) => {
  await page.goto(baseURL + articles[1].path);
  const unlock = page.locator('[data-post-unlock-button]');
  if (await unlock.count()) await unlock.click();
  const body = page.locator('.single-entry .entry-content');
  for (const version of ['10.3_p1-r2', '10.2_p1-r1', '10.0_p1-r11', '9.9_p2-r1', '10.6_p2-r0']) await expect(body).toContainText(version);
  await expect(body).toContainText('PasswordAuthentication no');
  await expect(body).toContainText('rc-service sshd restart');
  expect(await body.innerText()).not.toMatch(/10\.3_p\s+1|10\.6\s+p\s+2/);
  await expect(page.locator('#article-updates')).toHaveCount(1);
  const ids = await page.locator('.single-entry .entry-content h2[id], .single-entry .entry-content h3[id]').evaluateAll(headings => headings.map(h => h.id));
  expect(new Set(ids).size).toBe(ids.length);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
  await page.goto(baseURL + articles[0].path);
  if (await page.locator('[data-post-unlock-button]').count()) await page.locator('[data-post-unlock-button]').click();
  await expect(page.locator('.single-entry .entry-content pre')).toContainText('from importlib.metadata import version; print(version("ray"))');
  await expect(page.locator('.single-entry .entry-content')).toContainText('Firefox or Safari');
});
test('rolling brief keeps a stable search title and resolves current remediation guide links', async ({ page }) => {
  await page.goto(baseURL + '/live-cybersecurity-brief/');
  await expect(page).toHaveTitle('Live Cybersecurity Brief: Exploited CVEs, Fixes and Advisories');
  if (await page.locator('[data-post-unlock-button]').count()) await page.locator('[data-post-unlock-button]').click();
  await expect(page.getByRole('heading', { name: 'Priority scan', exact: true })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Source availability', exact: true })).toBeVisible();
  const body = page.locator('.single-entry .entry-content');
  await expect(body.getByRole('heading', { name: 'Executive security snapshot', exact: true })).toHaveCount(0);
  await expect(body).not.toContainText('Start with relevant known-exploited products.');
  await expect(body.locator('h2').first()).toHaveText('Priority scan');
  await expect(page.getByRole('link', { name: 'Executive security snapshot', exact: true })).toHaveCount(0);
  expect(await body.locator('table tbody tr').count()).toBeGreaterThan(0);
  for (const article of articles) await expect(body.locator(`a[href="${baseURL}${article.path}"]`).last()).toBeVisible();
  const schema = await page.locator('script[type="application/ld+json"]').evaluateAll(scripts => scripts.map(s => JSON.parse(s.textContent)).find(s => s['@type'] === 'NewsArticle'));
  expect(Date.parse(schema.datePublished)).toBe(Date.parse('2026-08-10T12:00:02Z'));
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});
