# First SEO implementation batch

This implements the highest-priority actionable findings from the November 9
traffic audit. It does not assert a ranking or traffic increase. The owner
confirmed Search Console is configured and GA4 is not configured; authenticated
query/click reports are not connected to this session. No analytics ID or traffic
baseline was invented, and no unsolicited external posts or emails were sent.

## Discovery and indexing

Active category membership uses current standalone public posts, excluding
retired, superseded and password-protected content. Parent archives include
their descendants. A separately pinned rolling brief does not make an empty
standalone listing useful. Empty category URLs still return 200 and remain
crawlable, with noindex/follow and no category-sitemap entry. They recover
automatically when populated. Dedicated SEO plugins retain indexing ownership.

Homepage highlights, topic cards, navigation and search shortcuts now promote
categories with actual articles. Categories and assignments were not deleted.
The blog's topic selector collapses on mobile/tablet with a native no-JavaScript
fallback. Latest standalone stories precede the one permanent rolling block.
In the 390px comparison, the first latest card moved from 1845px to 605px; at 768px,
from 1254px to 526px. These are layout measurements, not traffic results.

## Author and subscriber paths

Bylines link to the real WordPress author archive. The profile uses existing
public profile text when supplied, otherwise identifies the byline without
inventing a biography, qualifications, job title or test results. Source context
and correction paths are visible. ProfilePage and article Person identifiers
match. Current articles reuse the existing protected double-opt-in newsletter
form; retired and password-protected posts do not gain the prompt.

## Reviewed article improvements

Only existing posts 3699 and 1810 are rebuilt from dated vendor evidence. The
existing CNA records, scoring authority distinctions, exploitation evidence,
publication dates and URLs remain intact. Reviewed vendor fields participate in
the fingerprint and editorial history, so a later normal refresh retains the
improvement without accumulating duplicate rows or revisions. The global content
schema remains 12 to avoid unrelated editorial migrations.

- SonicWall CVE-2026-19868: generation-specific fixed builds, SAML applicability,
  the vendor's November 9 workaround and recommended validation. The individual
  CVE's vendor score is distinguished from the grouped advisory headline.
- N-able CVE-2026-86218: fixed server build, hosted/self-hosted distinction,
  supported upgrade paths, agent-update scope and separation of the older vendor
  statement from later CISA exploitation evidence.
- Contextual links distinguish SonicOS SSLVPN from the separate SMA1000 product
  family. They do not transplant remediation between products, and appear only
  when the matching active destination exists.

Primary evidence reviewed November 10:

- [SonicWall SNWLID-2026-0014](https://psirt.global.sonicwall.com/vuln-detail/SNWLID-2026-0014)
- [N-central hotfix notice](https://status.n-able.com/2026/09/06/n-central-2026-3-hotfix-4-cve-2026-86218/)
- [Separate SMA1000 advisory](https://psirt.global.sonicwall.com/vuln-detail/SNWLID-2026-0016)
- [Google noindex guidance](https://developers.google.com/search/docs/crawling-indexing/block-indexing)
- [Google sitemap guidance](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap)
- [Google ProfilePage guidance](https://developers.google.com/search/docs/appearance/structured-data/profile-page)

## Validation and remaining measurement work

Use dry-run/apply/rerun for each selected post and compare per-post hashes,
modified dates and history counts. Verify unrelated bodies, identities,
taxonomies, media and private settings remain unchanged. Permanent fixtures
cover reviewed evidence identity guards/idempotence and category lifecycle;
browser checks cover discovery, author navigation, subscriptions and article
evidence. The preview refresh now flushes its own rewrite rules so a refreshed
public-content snapshot retains its custom news sitemap route.

Next measurement step: connect the existing Search Console through the previously
suggested Windsor.ai integration or provide its Queries/Pages reports. Use actual
28/90-day query/page data to choose the next article/title improvements. GA4
requires a real property/measurement ID before tracking can be configured.
New guides should be independently tested and useful; category counts alone do
not justify publishing generic filler. Temporary diagnostics are removed after
verification under the standing project cleanup policy.

## Verified rollout

The substantive batch shipped in 0.3.8 and the final exact-product matching
guard in 0.3.9. Preview passed 102 suite checks plus four custom-sitemap checks
after its isolated rewrite refresh. Production passed 102 checks with four
preview-only checks skipped; six final focused checks passed after 0.3.9.
Category fixtures passed 21 checks and final source-evidence fixtures passed 93.
Author/newsletter visual and contrast checks passed in 12 Chromium/WebKit cases
each in preview and production. PHPStan, JS/CSS lint, 85 PHP syntax checks,
239-file release verification and all 15 fetched asset comparisons passed.

The final sitemap crawl passed all 102 URLs: 87 posts, seven page URLs, seven
categories and one author. URLs, private settings, taxonomy/media assignments
and standalone publication dates were preserved. Only the two intended article
bodies changed once during the substantive batch, with stable repeat applies.
The normal scheduled refresh of rolling hub 1169 during final guard verification
was preserved; it was not an unintended standalone-content migration.

Temporary diagnostics, captures, extracted releases and test reports were
removed after verification. Secure connections, actual backups/records and prior
owner deliverables remain private. Post-cleanup live blog/author checks passed.
