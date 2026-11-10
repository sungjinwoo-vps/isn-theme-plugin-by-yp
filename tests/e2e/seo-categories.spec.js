const { test, expect } = require('@playwright/test');
const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');

test('category indexing and sitemap entries reflect useful public archive listings', async ({ page, request }) => {
  const response = await request.get(`${baseURL}/wp-json/wp/v2/categories?per_page=100&_fields=id,link`);
  expect(response.ok()).toBeTruthy();
  const categories = await response.json();
  const sitemap = await request.get(`${baseURL}/wp-sitemap-taxonomies-category-1.xml`);
  expect(sitemap.ok()).toBeTruthy();
  const xml = await sitemap.text();
  const paths = [...xml.matchAll(/<loc>(.*?)<\/loc>/g)].map((match) => new URL(match[1].replaceAll('&amp;', '&')).pathname);
  let empty = 0;
  for (const category of categories) {
    const url = new URL(category.link);
    const archive = await page.goto(`${baseURL}${url.pathname}`);
    expect(archive.status()).toBe(200);
    const isolated = archive.headers()['x-infosecnexus-preview'] === 'isolated-sqlite';
    const articlePaths = await page.locator('main .post-grid--archive > .post-card .post-card__link').evaluateAll((links) => links.map((link) => new URL(link.href).pathname));
    const useful = articlePaths.some((path) => path !== '/live-cybersecurity-brief/');
    const robots = await page.locator('meta[name="robots"]').getAttribute('content');
    if (useful) {
      if (!isolated) expect(robots).not.toMatch(/\bnoindex\b/);
      expect(paths).toContain(url.pathname);
    } else {
      empty += 1;
      expect(robots).toMatch(/\bnoindex\b/);
      if (!isolated) expect(robots).toMatch(/\bfollow\b/);
      expect(paths).not.toContain(url.pathname);
    }
  }
  expect(categories.length).toBeGreaterThan(0);
  expect(empty).toBeLessThan(categories.length);
});
