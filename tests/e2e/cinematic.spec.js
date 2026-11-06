const { test, expect } = require('@playwright/test');

const baseURL = (process.env.WP_BASE_URL || 'http://localhost:8890').replace(/\/$/, '');
const topicSlugs = ['critical-cves', 'cloud-security', 'linux-administration', 'artificial-intelligence', 'network-security'];
let published;
let categories;

async function publicPosts(request) {
  const first = await request.get(`${baseURL}/wp-json/wp/v2/posts?per_page=100&_fields=id,link,slug,title,categories`);
  expect(first.ok()).toBeTruthy();
  const posts = await first.json();
  const pages = Number(first.headers()['x-wp-totalpages'] || 1);
  for (let current = 2; current <= pages; current += 1) {
    const response = await request.get(`${baseURL}/wp-json/wp/v2/posts?per_page=100&page=${current}&_fields=id,link,slug,title,categories`);
    expect(response.ok()).toBeTruthy();
    posts.push(...await response.json());
  }
  return posts;
}

function pathOf(url) {
  return new URL(url, baseURL).pathname;
}

async function mainCardIds(page) {
  return page.locator('main .content-area .post-grid > .post-card').evaluateAll((cards) => cards.map((card) => Number(card.id.replace('post-', ''))));
}

test.beforeAll(async ({ request }) => {
  published = await publicPosts(request);
  const response = await request.get(`${baseURL}/wp-json/wp/v2/categories?per_page=100&_fields=id,slug,link`);
  expect(response.ok()).toBeTruthy();
  categories = await response.json();
});

test('cinematic homepage uses live stories, unique article links and correct topic archives', async ({ page, request }) => {
  const response = await page.goto(baseURL, { waitUntil: 'networkidle' });
  expect(response.status()).toBe(200);
  await expect(page.locator('body')).toHaveClass(/cinematic-editorial/);
  await expect(page.locator('h1')).toHaveCount(1);
  await expect(page.locator('.cg-hero h1')).toContainText('Intelligence');
  await expect(page.locator('.cg-hero h1')).toContainText('Beyond the Noise.');
  const byPath = new Map(published.map((post) => [pathOf(post.link), post]));
  const articleLinks = await page.locator('.cg-highlights .cg-highlight__link, .cg-latest__list .cg-latest__item > a').evaluateAll((links) => links.map((link) => link.href));
  expect(articleLinks.length).toBeGreaterThan(0);
  const stories = [];
  for (const href of articleLinks) {
    const path = pathOf(href);
    if (path.startsWith('/category/')) {
      expect(categories.some((term) => pathOf(term.link) === path)).toBeTruthy();
    } else {
      const post = byPath.get(path);
      expect(post, `Homepage article is publicly discoverable: ${path}`).toBeTruthy();
      stories.push(post);
    }
  }
  expect(new Set(stories.map((post) => post.id)).size).toBe(stories.length);
  const topicLinks = await page.locator('.cg-topics__grid .cg-topic').evaluateAll((links) => links.map((link) => link.href));
  expect(topicLinks).toHaveLength(topicSlugs.length);
  for (const slug of topicSlugs) {
    const term = categories.find((item) => item.slug === slug);
    expect(term).toBeTruthy();
    expect(topicLinks.map(pathOf)).toContain(pathOf(term.link));
    const archive = await request.get(term.link);
    expect(archive.status()).toBe(200);
  }
  const rolling = published.find((post) => post.slug === 'live-cybersecurity-brief');
  if (rolling) {
    await expect(page.locator('.cg-featured a.button')).toHaveAttribute('href', rolling.link);
    expect(articleLinks.map(pathOf)).not.toContain(pathOf(rolling.link));
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBeTruthy();
});

test('hero keyword and topic controls submit a real intersection of published posts', async ({ page }) => {
  const term = categories.find((item) => item.slug === 'critical-cves');
  const candidate = published.find((post) => post.categories.includes(term.id) && /CVE-\d{4}-\d{4,}/i.test(post.title.rendered));
  expect(candidate).toBeTruthy();
  const keyword = candidate.title.rendered.match(/CVE-\d{4}-\d{4,}/i)[0];
  const expected = new Set(published.filter((post) => post.categories.includes(term.id) && post.title.rendered.includes(keyword)).map((post) => post.id));
  await page.goto(baseURL);
  const discovery = page.getByRole('search', { name: 'Filter security intelligence', exact: true });
  await discovery.locator('select[name=category_name]').selectOption(term.slug);
  await discovery.locator('[name=s]').fill(keyword);
  await discovery.getByRole('button', { name: 'Search', exact: true }).click();
  await expect(page).toHaveURL(new RegExp(`category_name=${term.slug}`));
  await expect(page.locator('body')).toHaveClass(/search/);
  const ids = await mainCardIds(page);
  expect(ids).toContain(candidate.id);
  ids.forEach((id) => expect(expected.has(id)).toBeTruthy());
  await expect(page.locator('main .post-grid .type-page')).toHaveCount(0);
});

test('hero priority controls distinguish recorded critical and high scores', async ({ page }) => {
  await page.goto(baseURL);
  const alert = page.locator('.cg-desk__alerts a').filter({ has: page.locator('.cg-priority--critical') }).first();
  expect(await alert.count()).toBeGreaterThan(0);
  const expectedPath = pathOf(await alert.getAttribute('href'));
  const keyword = (await alert.locator('span').first().innerText()).match(/CVE-\d{4}-\d{4,}/i)?.[0];
  expect(keyword).toBeTruthy();
  const form = page.locator('.cg-discovery');
  await form.locator('[name=category_name]').selectOption('critical-cves');
  await form.locator('[name=isn_severity]').selectOption('critical');
  await form.locator('[name=s]').fill(keyword);
  await form.getByRole('button', { name: 'Search', exact: true }).click();
  const links = await page.locator('main .content-area .post-card__link').evaluateAll((items) => items.map((item) => new URL(item.href).pathname));
  expect(links).toContain(expectedPath);
  const wrongPriority = new URL(page.url());
  wrongPriority.searchParams.set('isn_severity', 'high');
  await page.goto(wrongPriority.href);
  await expect(page.locator('main .content-area .post-card__link')).toHaveCount(0);
  wrongPriority.searchParams.set('isn_severity', 'critical');
  wrongPriority.searchParams.set('s', '');
  await page.goto(wrongPriority.href);
  const criticalCards = page.locator('main .content-area .post-card');
  expect(await criticalCards.count()).toBeGreaterThan(0);
  expect(await criticalCards.evaluateAll((cards) => cards.every((card) => card.classList.contains('post-card--critical')))).toBeTruthy();
});

test('modal search supports keyboard dismissal, focus restoration and actual post results', async ({ page }) => {
  await page.goto(baseURL);
  const toggle = page.locator('[data-search-toggle]').first();
  await toggle.click();
  const panel = page.locator('[data-search-modal]');
  await expect(panel).toBeVisible();
  await expect(panel.locator('input[name=s]')).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(panel).toBeHidden();
  await expect(toggle).toBeFocused();
  await toggle.click();
  const candidate = published.find((post) => /CVE-\d{4}-\d{4,}/i.test(post.title.rendered));
  const keyword = candidate.title.rendered.match(/CVE-\d{4}-\d{4,}/i)[0];
  await panel.locator('input[name=s]').fill(keyword);
  await panel.locator('button[type=submit]').click();
  await expect(page.locator('body')).toHaveClass(/search/);
  expect(await mainCardIds(page)).toContain(candidate.id);
});

test('blog pages are distinct, self-canonical and retain one permanent briefing', async ({ page }) => {
  await page.goto(`${baseURL}/blog/`);
  const first = await page.locator('.blog-hub__latest .post-card').evaluateAll((cards) => cards.map((card) => card.id));
  expect(first.length).toBeGreaterThan(0);
  const next = page.locator('.blog-hub__latest .pagination a.next');
  test.skip(await next.count() === 0, 'The public content set has no second blog page.');
  const pageTwoURL = await next.getAttribute('href');
  await next.click();
  await expect(page).toHaveURL(pageTwoURL);
  await expect(page.locator('link[rel=canonical]')).toHaveAttribute('href', pageTwoURL);
  const second = await page.locator('.blog-hub__latest .post-card').evaluateAll((cards) => cards.map((card) => card.id));
  expect(second.length).toBeGreaterThan(0);
  expect(second.some((id) => first.includes(id))).toBeFalsy();
  await expect(page.locator('.blog-hub__featured')).toHaveCount(0);
});

test('article guide, related stories, semantic dates and source citations remain functional', async ({ page }) => {
  const post = published.find((item) => item.slug !== 'live-cybersecurity-brief');
  await page.goto(post.link);
  await expect(page.locator('h1')).toHaveCount(1);
  await expect(page.locator('.single-hero .entry-meta time').first()).toHaveAttribute('datetime', /^\d{4}-\d{2}-\d{2}T/);
  const targets = await page.locator('.toc a[href^="#"]').evaluateAll((links) => links.map((link) => ({ label: link.textContent, count: document.querySelectorAll(`[id="${CSS.escape(decodeURIComponent(link.hash.slice(1)))}"]`).length })));
  expect(targets.length).toBeGreaterThan(0);
  targets.forEach((target) => expect(target.count, target.label).toBe(1));
  const related = await page.locator('.related-posts .post-card__link').evaluateAll((links) => links.map((link) => link.href));
  const publishedPaths = new Set(published.map((item) => pathOf(item.link)));
  related.forEach((href) => {
    expect(publishedPaths.has(pathOf(href))).toBeTruthy();
    expect(pathOf(href)).not.toBe(pathOf(post.link));
  });
  const articleSchema = await page.locator('script[type="application/ld+json"]').evaluateAll((scripts) => scripts.map((script) => JSON.parse(script.textContent)).find((item) => ['Article', 'NewsArticle', 'BlogPosting'].includes(item['@type'])));
  expect(articleSchema.mainEntityOfPage['@id']).toBe(post.link);
  expect(articleSchema.citation.length).toBeGreaterThan(0);
});

test('contact, legal pages and the 404 use the complete shared shell without overflow', async ({ page }) => {
  for (const path of ['/about/', '/contact/', '/privacy-policy/', '/terms-and-conditions/', '/disclaimer/', '/cinematic-preview-no-such-page/']) {
    const response = await page.goto(`${baseURL}${path}`);
    expect(response.status()).toBe(path.includes('no-such-page') ? 404 : 200);
    await expect(page.locator('.site-header')).toBeVisible();
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('.site-footer')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), path).toBeTruthy();
  }
});

test('sitemaps preserve active article routes and exclude tag and retired archives', async ({ request }) => {
  const index = await request.get(`${baseURL}/wp-sitemap.xml`);
  expect(index.ok()).toBeTruthy();
  const indexXML = await index.text();
  expect(indexXML).toContain('<sitemapindex');
  expect(indexXML).not.toContain('wp-sitemap-taxonomies-post_tag');
  const postMap = await request.get(`${baseURL}/wp-sitemap-posts-post-1.xml`);
  expect(postMap.ok()).toBeTruthy();
  const mapXML = await postMap.text();
  const paths = [...mapXML.matchAll(/<loc>(.*?)<\/loc>/g)].map((match) => pathOf(match[1].replaceAll('&amp;', '&')));
  expect(paths.length).toBeGreaterThan(0);
  const publishedPaths = new Set(published.map((post) => pathOf(post.link)));
  paths.forEach((path) => expect(publishedPaths.has(path)).toBeTruthy());
  const rolling = published.find((post) => post.slug === 'live-cybersecurity-brief');
  if (rolling) {
    expect(paths.filter((path) => path === pathOf(rolling.link))).toHaveLength(1);
  }
  const news = await request.get(`${baseURL}/news-sitemap.xml`);
  expect(news.ok()).toBeTruthy();
  expect(await news.text()).not.toContain('/live-cybersecurity-brief/');
});

test('newsletter reports missing preview delivery and contact validates without sending mail', async ({ page, request }) => {
  const health = await request.get(baseURL);
  test.skip(health.headers()['x-infosecnexus-preview'] !== 'isolated-sqlite', 'Form postbacks run only inside the mail-blocked design preview.');
  await page.goto(baseURL);
  const newsletter = page.locator('[data-isnx-newsletter]').first();
  await newsletter.locator('[name=email]').fill(`cinematic-${Date.now()}@example.invalid`);
  const responsePromise = page.waitForResponse((response) => response.url().endsWith('/infosecnexus/v1/newsletter') && response.request().method() === 'POST');
  await newsletter.locator('button[type=submit]').click();
  const response = await responsePromise;
  expect(response.status()).toBe(503);
  expect(await response.json()).toMatchObject({ status: 'mail-delayed' });
  await expect(newsletter.locator('[data-isnx-newsletter-status]')).toContainText('after mail delivery is configured');
  await expect(newsletter.locator('[data-isnx-newsletter-status]')).not.toContainText('Check your inbox');
  await page.goto(`${baseURL}/contact/`);
  const form = page.locator('.isnx-contact-form').first();
  await expect(form).toHaveAttribute('method', 'post');
  expect(new URL(await form.getAttribute('action')).origin).toBe(new URL(baseURL).origin);
  await form.locator('[name=name]').fill('Preview validation');
  await form.locator('[name=email]').fill('invalid-email');
  expect(await form.locator('[name=email]').evaluate((input) => input.checkValidity())).toBeFalsy();
  const challenge = await request.get(await form.getAttribute('data-isnx-contact-guard-url'));
  expect(challenge.ok()).toBeTruthy();
  expect(await challenge.json()).toMatchObject({ issued: expect.any(Number), nonce: expect.stringMatching(/^[a-f0-9]{32}$/), token: expect.stringMatching(/^[a-f0-9]{64}$/) });
});

test('motion reduction and disabled JavaScript retain all homepage content', async ({ browser, page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto(baseURL);
  await expect(page.locator('.cg-reveal-pending')).toHaveCount(0);
  await page.locator('.cg-final-cta').scrollIntoViewIfNeeded();
  await expect(page.locator('.cg-final-cta h2')).toBeVisible();
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: page.viewportSize() });
  const plain = await context.newPage();
  await plain.goto(baseURL);
  await expect(plain.locator('.cg-hero h1')).toBeVisible();
  await plain.locator('.cg-final-cta').scrollIntoViewIfNeeded();
  await expect(plain.locator('.cg-final-cta h2')).toBeVisible();
  await expect(plain.locator('.cg-topics__grid .cg-topic')).toHaveCount(topicSlugs.length);
  await context.close();
});

test('hero movement stays restrained and the sticky header follows scroll', async ({ page }, testInfo) => {
  await page.goto(baseURL);
  const hero = page.locator('[data-cg-hero]');
  const layer = hero.locator('[data-cg-parallax]').first();
  const box = await hero.boundingBox();
  await page.mouse.move(box.x + box.width * 0.9, box.y + Math.min(box.height * 0.4, 350));
  if (testInfo.project.name === 'desktop') {
    await expect.poll(() => layer.evaluate((element) => element.style.getPropertyValue('--cg-parallax-x'))).not.toBe('');
    const movement = await layer.evaluate((element) => parseFloat(element.style.getPropertyValue('--cg-parallax-x')));
    expect(Math.abs(movement)).toBeLessThanOrEqual(7);
  } else {
    expect(await layer.evaluate((element) => element.style.getPropertyValue('--cg-parallax-x'))).toBe('');
  }
  await page.evaluate(() => scrollTo(0, 200));
  await expect(page.locator('.site-header')).toHaveClass(/is-scrolled/);
  expect(await page.locator('.site-header').evaluate((element) => getComputedStyle(element).position)).toBe('sticky');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect.poll(() => layer.evaluate((element) => element.style.getPropertyValue('--cg-parallax-x'))).toBe('');
});
