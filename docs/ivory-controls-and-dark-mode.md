# Reference controls and dark appearance

Version `0.3.4` is deployed at https://infosecnexus.com/ on November 9, 2026.

Appearance is now directly available in the header on desktop, tablet and
mobile. Small mobile headers show three comfortable mode/search/menu controls;
Daily Brief remains in the hero/mobile menu and in larger-screen headers.
Footer navigation is grouped into semantic topic, site and legal lists, without
duplicate appearance or Daily Brief controls. Existing custom menus are retained.

Buttons share a warm orange gradient, rounded edge, raised shadow and consistent
hover, focus and pressed feedback. Cards have softer depth, rounded overlapping
captions and restrained image movement. Below 360px, category/topic cards become
horizontal rows, and the final CTA uses a short label that stays on one line.

Optional dark mode uses charcoal surfaces throughout the hero, cards, ticket,
newsletter, forms and dialogs. Borders and highlights are restrained, image
blending is quieter, and saved preference behavior is retained. Reduced motion
disables movement even during hover and mouse-down states. Small cookie actions
have readable dark text on soft orange; Not now still never grants consent.

Changed implementation files:

- `assets/css/ivory-pages.css`: shared controls, header/footer, archive cards.
- `assets/css/ivory-home.css`: homepage cards/buttons and narrow mobile layout.
- `assets/css/ivory-preferences.css`: dark surfaces, physical states, cookie UI.
- `inc/header-builder.php`, `inc/footer-builder.php`: accessible controls,
  semantic lists and preservation of configured navigation/social profiles.
- `template-parts/home/ivory.php`: accessible compact final CTA label.
- `style.css`, `functions.php`, `readme.txt`: release metadata/changelog.
- `tests/e2e/infosecnexus.spec.js`, `tests/e2e/reference-polish.spec.js`:
  visible header mode, synchronization/persistence, footer bounds and dark contrast.

Preview passed 80 browser checks; production passed 78 with two preview-only
mail tests skipped. JS/CSS lint, focused PHPStan and all 82 deployed PHP syntax
checks passed. Production Chromium/WebKit passed 48 control contexts, while
independent review passed ten responsive visual cases and ten reduced-motion
cases. An additional full page matrix passed all 22 cases across 320-2560px,
light/dark and shared page templates. All 132 header icon measurements are centered and all 11 fetched
CSS/JS files match source hashes. Actual enabled contact-button states were
verified without submission; no production POST or consent action was sent.

All thirteen preserved production fields match before and after installation,
including complete publication identity/body and private Toolkit-settings hashes.
No new configuration is required. Branding, existing forms, default light mode
and visitors' saved preferences remain available.

Release `auto-v0.3.4`, source `914dc55`, Actions run `42567669298`. The official
GitHub package was obtained through its public asset API after temporary ZIP
link failures, checksum/source-verified and installed through WordPress' native
installer. Rollback checkpoint and ignored evidence are in PROJECT-MEMORY.md.
