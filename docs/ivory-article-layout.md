# Shared article reader alignment

Release `0.3.5` repairs the article guide and reading layout across the rolling
brief and ordinary posts. Article content, sources, heading IDs, URLs and stored
editorial metadata are preserved.

The supplied screenshot exposed several independent issues:

- Conflicting legacy breakpoints left a two-column sidebar inside a 210px rail
  at 1024px. A required 240px share track collapsed the guide track to zero,
  covered its links and intercepted clicks.
- Desktop's 235px rail left roughly 128px for nested guide text. Long CVE labels
  wrapped to as many as eight lines, and the guide/share rail exceeded the
  available viewport height.
- Mobile displayed a tall hero followed by a fully expanded guide, placing the
  first article text roughly 1285-1404px below the page top.
- Article and generic theme handlers both processed guide links. Clicks used
  different offsets/history behavior from fresh hash navigation; root scroll
  padding and heading margins also stacked their clearances.

The new post-only `assets/css/ivory-article.css` loads after shared appearance
styles and owns the reader's grid and breakpoints. Desktop uses a 280px rail,
24px gap and flexible content column inside the same 1200px shell as the hero.
Sharing is a compact row, and the guide uses the remaining viewport height.
Below 1100px, the reader has one column and an accessible expandable guide.

Labels remain complete DOM text with full titles; visual wrapping is limited
to two lines and keyboard focus exposes the complete label. No-JavaScript
reading retains the native guide links and full article content. A small
post-only pre-paint marker reserves the compact guide before enhancement;
it clears after setup, or falls back to visible links at DOM readiness if the
enhancement script fails. Waiting for deferred scripts avoids a slow-network
timeout race. The guide header reserves its control height.

`article-content.js` owns guide clicks and hash navigation using a measured
sticky-header bottom plus 18px. The generic hash handler respects already
handled events. Gated sections still reveal before offset measurement, code
copy/table tools work, and responsive disclosure changes preserve keyboard
focus. Natural scrolling of an H1 behind a sticky header is not an initial
page-load overlap: none was reproduced during the baseline audit.

The post-only stylesheet selects `font-display: optional`: visits where the
face is ready promptly, including cached navigation, retain Nunito Sans;
otherwise the reader keeps a stable system fallback for that page instead of
shifting content when the font arrives late. Other pages retain their existing
font policy. Throttled
browser tests reproduced a late-swap shift and verified this fallback behavior;
these are lab observations rather than field Core Web Vitals guarantees.

Changed implementation files are `inc/assets.php`, `inc/template-tags.php`,
`assets/js/article-content.js`, `assets/js/theme.js`, the new article stylesheet,
and release metadata. `tests/e2e/article-layout.spec.js` covers the tablet grid,
single navigation ownership, click/reload offsets, short desktop rail,
responsive focus, initial layout stability, no-JS access and failed-script
fallback.

Preview measurements show matched hero/reader edges, approximately 18px anchor
clearance, desktop rail fitting both 1000px and 600px viewports, and mobile
content starting around 755-774px. Captures and raw measurements remain ignored
under `.tmp/article-layout-before/`, `.tmp/article-layout-preview/` and
`.tmp/article-layout-preview-controls/`.
