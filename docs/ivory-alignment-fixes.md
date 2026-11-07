# Responsive alignment repairs

Version `0.3.3` is deployed at https://infosecnexus.com/ on November 7, 2026.
The existing ivory/orange design, branding and editorial content are preserved.

The supplied wide desktop screenshot exposed viewport-based padding inside a
capped hero. At 2124px its copy narrowed to 316px and five heading lines; at
2560px it narrowed to 54px. Bounded padding now keeps the copy at 726px, the
heading at two lines and the hero at 480px across the checked wide widths.

Shared icon buttons now explicitly center their SVGs and remove native button
padding. This repairs the search and hamburger icons in default and sticky
headers, plus dialog close controls. The responsive search rule now targets
the actual layout class; mobile/tablet no longer retain an empty image column.
Footer fallback links now share the configured menu's spacing and wrapping.

Changed implementation files:

- `assets/css/ivory-home.css`: bounded hero padding.
- `assets/css/ivory-pages.css`: icon centering, modal bounds and footer spacing.
- `assets/css/cinematic-pages.css`: correct responsive search layout selector.
- Theme version metadata and changelog: `style.css`, `functions.php`, `readme.txt`.
- `tests/e2e/alignment.spec.js`: wide viewport and actual control/dialog geometry.
- `tests/e2e/cinematic.spec.js`: selects a genuine critical search result instead
  of assuming the changing three-story daily list always includes one.

Validation includes 76 preview browser checks, JS/CSS lint, all 82 deployed PHP
syntax checks and 234 release files compared with source plus established CI
stamps. The final full production suite passed 74 checks with two preview-only
mail tests skipped. Production visual review passed 19 responsive page cases and four
footer widths. Chromium/WebKit passed 20 geometry and 20 keyboard contexts
across five control widths, light/dark and scrolled states. All 764 measured
control centers have zero offset; modal bounds, focus trapping, Escape and
focus restoration passed. Eleven frontend CSS/JS assets match source hashes.

All thirteen preserved production state fields match before and after the
native update, including complete publication identity/body and private
Toolkit-settings hashes. No production form was submitted or advertising
consent accepted. No new configuration is required.

Release: `auto-v0.3.3`, code `f8b0f75`, successful Actions run `42346798306`.
Rollback checkpoint and ignored screenshot/test evidence are recorded in
`PROJECT-MEMORY.md`; credentials and private paths are excluded from Git.
