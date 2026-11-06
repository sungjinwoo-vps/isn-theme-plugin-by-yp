# Runtime and visual repairs after the ivory rollout

The owner reported that the live site still looked wrong. Fresh browser audits
used normal motion, default preferences, saved dark/system preferences and
Chromium plus WebKit; they reproduced issues missed by earlier static/Axe
screenshots.

- A more-specific sticky-header rule overrode mobile widths. Controls moved
  sideways after scrolling and the 320px menu button clipped outside the
  viewport. A shared width variable now keeps default/sticky/compact geometry
  identical. Regression tests check actual control rectangles, not only page
  scrollWidth.
- The mobile hero stretched a wide image across a tall area, while its sizes
  hint requested too few pixels. It now uses a separate 220px image area,
  matching responsive preload hints and compact action labels on one row.
- Saved dark appearance mixed a cream text rectangle with pale text. The hero
  now uses a coherent dark overlay and readable text. Existing preferences and
  the optional appearance control remain functional.
- Story excerpts, topic descriptions and dates were too small. Text sizes are
  now readable instead of shrinking the page to a poster's proportions.
- Mobile navigation expanded all ten topic links immediately. A separate 44px
  disclosure now keeps primary navigation in view; the real Blog parent link
  stays available, hidden links leave the tab sequence, and Escape restores
  focus. Without JavaScript all topic links remain available.
- Cookie preferences covered stories and article facts. They now sit after the
  footer in the document flow. "Not now" dismisses them for the current browser
  session without setting consent or sending the advertising-consent event.
  The existing explicit acceptance workflow is retained. Preview now includes
  this public UI so subsequent QA cannot silently omit it.
- Generic CVE classification repeated the same lock image on 11 of 12 stories.
  Actual technology/product titles and topic categories now select relevant
  existing software, network or protection artwork before generic CVE fallback.
  Manually selected editorial images, their alt text/dimensions and srcsets are
  preserved. No article body, identity, category or private setting is changed.

Deployed version: `0.3.2` at **https://infosecnexus.com/**. Local validation passed 70 browser tests, all 82
theme PHP syntax checks, focused PHPStan and JS/CSS lint. Fifty standalone media
fixtures cover classification and preservation of manual editorial images.
Normal/reduced mobile-menu checks and fifty shared-page header/appearance cases
also passed. Production passed 68 applicable browser checks (two preview-only
mail tests skipped), all 11 normal-motion visual/Axe cases, 45 links and three
icons. Published content bodies, identities, retirement state, appearance and
private Toolkit settings have matching before/after hashes. The production
state and verified rollback checkpoint are recorded in PROJECT-MEMORY.md.

Evidence remains ignored: `.tmp/current-visual-audit/`,
`.tmp/current-runtime-audit/`, `.tmp/repaired-visual-audit/`,
`.tmp/repaired-webkit-audit/`, `.tmp/runtime-repair-home/` and
`.tmp/runtime-repair-e2e.txt`.
Final live screenshots and reports: `.tmp/runtime-repair-production/`.
