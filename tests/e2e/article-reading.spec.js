const { test, expect } = require('@playwright/test');
const path = require('node:path');

const theme = path.resolve(__dirname, '../../wp-content/themes/infosecnexus');

test('article guide reveals gated subsections and copies complete code without overflowing tables', async ({ page }) => {
  const source = 'uname -r\nss -Mlnp\n';
  await page.setContent(`<!doctype html><html><body>
    <article class="single-entry">
      <nav class="toc" data-article-guide aria-label="Table of contents"><ol>
        <li><a href="#overview">Overview</a><ol><li><a href="#checks">Checks</a></li></ol></li>
      </ol></nav>
      <div class="entry-content-wrap"><div class="entry-content" data-post-unlock>
        <h2 id="overview">Overview</h2><p>Verified source context.</p>
        <button type="button" data-post-unlock-button>Read More</button>
        <div data-post-unlock-content hidden><h3 id="checks">Checks</h3>
          <pre><code>${source}</code></pre>
          <table><caption>Supported versions</caption><thead><tr><th>Product</th><th>Affected</th><th>Fixed</th></tr></thead>
            <tbody><tr><th scope="row">Example distribution</th><td>1.0 through 1.2</td><td>1.3</td></tr></tbody></table>
        </div>
      </div></div>
    </article></body></html>`);
  await page.evaluate(() => {
    Object.defineProperty(window.navigator, 'clipboard', {
      value: { writeText: async (value) => { window.copiedArticleCode = value; } },
      configurable: true
    });
  });
  await page.addStyleTag({ content: ':root { --isn-text:#111;--isn-muted:#444;--isn-cyan:#005a75;--isn-surface-soft:#eee;--isn-surface-raised:#fff;--isn-border:#bbb; } body{margin:16px} .entry-content-wrap{width:100%}' });
  await page.addStyleTag({ path: path.join(theme, 'assets/css/article-content.css') });
  await page.addScriptTag({ path: path.join(theme, 'assets/js/article-content.js') });

  await page.getByRole('link', { name: 'Checks', exact: true }).click();
  await expect(page.locator('[data-post-unlock-content]')).toBeVisible();
  await expect(page.locator('#checks')).toBeFocused();
  await expect(page.getByRole('link', { name: 'Checks', exact: true })).toHaveAttribute('aria-current', 'location');
  await page.getByRole('button', { name: 'Copy code', exact: true }).click();
  await expect.poll(() => page.evaluate(() => window.copiedArticleCode)).toBe(source);
  await expect(page.getByRole('status')).toHaveText('Copied');
  await expect(page.getByRole('table')).toHaveCount(1);
  await expect(page.getByRole('region', { name: /Article table/ })).toHaveAttribute('tabindex', '0');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
});
