# InfoSecNexus Project Memory

> Future Codex and project owner: read this file completely before changing the
> project. Then run the startup checklist in the next section. This is the
> authoritative continuity handoff for the current project state.

Last updated: 2026-11-07 (Asia/Kolkata)

## 1. Current Snapshot

- Project: InfoSecNexus WordPress cybersecurity newsroom.
- Live site: `https://infosecnexus.com`
- Repository: `https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp`
- Main release branch: `stable`
- Repository visibility: public, which permits unauthenticated WordPress update
  downloads. Moving it to private requires an authenticated update service.
- Current theme version: `0.3.3`
- Current known release: `auto-v0.3.3`
- Current known code commit: `f8b0f75` (`Fix wide hero and shared responsive control alignment`)
- WordPress was verified running version 7.1.4 at this checkpoint.
- The live site is theme-first. The InfoSecNexus Toolkit features are bundled
  into the theme and the separate toolkit plugin is not required on this site.
- Latest known verification on November 7: production is running `0.3.3` through
  the native updater. The wide hero, shared search/menu icons, responsive search
  dialog and fallback footer navigation are repaired. The final production
  suite passed 74 checks with two preview-only mail tests skipped. All 19 responsive page
  cases, four footer widths and 20 cross-browser geometry plus 20 keyboard
  contexts passed, including light/dark and normal motion. All 82 deployed PHP
  files passed syntax checks; all 234 release files match source plus expected
  workflow stamps. Complete body, identity and Toolkit-settings hashes match
  before/after this update. Counts are now 248 published posts, six pages, 159
  retired and five superseded posts (84 active stories); the additional post
  existed before this deployment. Branding, appearance, mail/ads and content
  were preserved. No production form was submitted or consent accepted.
- Previous verification on November 6: production was running `0.3.2`. Fresh normal-motion
  audits reproduced and repaired the mobile sticky-header clipping, oversized
  soft hero, broken saved-dark hero, tiny story copy and obstructive cookie
  banner. Mobile topics now collapse accessibly and editorial fallback art uses
  actual technology context. Production passed 68 applicable browser checks
  (two preview-only mail tests skipped), 11 normal-motion visual/Axe cases, 45
  internal links and three icon sizes. All 82 deployed PHP files passed syntax
  checks. Complete body/identity/Toolkit-settings hashes match before and after
  this update. Existing logo, Site Icon, saved appearance choices and mail/ads
  configuration remain unchanged. No test email/contact message was sent.
- Previous verification on November 6: production was running `0.3.1` with the owner's
  replacement ivory/orange reference design and original IN branding. Native
  logo attachment `3681` and Site Icon `3682` are installed, and light is the
  default; footer/mobile controls retain optional warm dark mode. All 82 deployed
  PHP files passed syntax checks. The full live functional suite passed 62
  applicable checks with two preview-only form tests skipped. Eleven production
  visual/Axe cases and 45 internal links were reviewed; an optional dark share
  badge contrast issue was repaired in `0.3.1` and retested in dark/light.
  Public post/page identities, stored publication dates, featured images,
  categories, retirement/supersession state and private Toolkit configuration
  match the pre-redesign audit. Counts remain 247 published posts, six pages,
  159 retired and five superseded posts. Zoho Mail and advertising stay active;
  preview blockers are absent. No test email/contact message was sent.
- Previous verification on November 6: production was running `0.2.0`, installed through
  WordPress' native updater after verified database/theme backups. The live
  desktop/mobile suite passed 62 checks with two preview-only form tests skipped.
  All 11 additional live desktop/tablet/mobile visual/Axe scenarios and all 45
  discovered internal links passed. All 79 deployed PHP files passed syntax
  checks. Published identities, article/page bodies, dates, featured images,
  categories, retirement state and Toolkit settings have matching before/after
  hashes. Production has 247 published posts, six pages, 159 retired and five
  superseded posts, leaving 83 active stories. Default navy/dark mode is set;
  visitors' saved light/dark preferences remain effective. Existing Zoho Mail
  stays active, advertising stays configured, and preview blockers are absent.
  Actual message delivery was not exercised by the production checks.
- Previous verification on November 4: production was running `0.1.64`. All 82 active
  articles use the source-backed editorial schema: 81 individual stories and
  the permanent rolling brief. The final desktop/mobile suite passed 40 of 42
  checks; the two existing homepage artwork-diversity failures also occurred
  before this task. All five additional actual-production desktop/tablet/mobile
  light/dark article scenarios passed without serious Axe violations,
  horizontal overflow, duplicate H1, page errors, or failed page responses.
  All 77 deployed PHP files passed syntax checks, focused PHPStan and JS/CSS
  linting passed, and 63 source-evidence regression checks plus editorial
  persistence fixtures passed. Lighthouse and Core Web Vitals remain targets,
  not permanent guarantees.

### Cinematic Redesign And Production Deployment - November 6, 2026

- The initial request required a local/staging implementation with no automatic
  deployment. After reviewing the completed preview, the owner explicitly asked
  to deploy production on November 6. Production was verified at `0.1.64` and a
  database/theme rollback backup was verified before preparing release `0.2.0`.
- Work began on local branch `design/cinematic-glass-preview`, preview version
  `0.2.0-preview.1`, then was promoted to `stable` as release `0.2.0`. The
  cinematic glass hero and raised 3D editorial layout are
  implemented across the homepage and every shared public page template, with
  real content, accessible controls, dark/light modes and reduced-motion/no-JS
  fallbacks. Existing branding, URLs, SEO, article content and forms are retained.
- Preview: `http://localhost:8890/`. Use `tools/preview-start.ps1` to resume and
  `tools/preview-sync.ps1` after theme edits. Its PHP server binds only loopback;
  an SSH tunnel exposes the preview locally. It uses an isolated temporary
  WordPress/SQLite instance with its database outside the document root, never
  the live database. Keep the ignored connection file and SSH key in secure
  records, never in Git or this memory.
- The public-only snapshot has 247 published posts, six public pages and 247
  attachment records; 83 stories are active, 159 remain retired and five remain
  superseded. No private mail settings, subscribers or contact messages were
  copied. Production cron/content changes after this snapshot do not appear
  until an explicit `preview-start.ps1 -RefreshContent`.
- Preview mail, server-side external requests, cron/generation, automatic
  updates and ads are blocked. Newsletter remains connected to the existing
  handler and honestly returns `503 mail-delayed` in preview. Actual staging
  mail delivery requires the existing SMTP integration and a deliberate removal
  of the preview mail blocker. Production mail settings were not changed.
- Repeat form tests can reach the normal spam limits. Run
  `tools/preview-start.ps1 -ResetChecks` before a fresh suite; its helper refuses
  to run outside the exact isolated preview SQLite database and clears only
  preview rate counters. It does not disable rate limiting.
- Original optimized WebP artwork, responsive helpers and full prompt provenance
  are documented in `docs/cinematic-assets.md`. The implementation, complete file
  map, preview operation and remaining configuration are in
  `docs/cinematic-preview.md`. Review artifacts stay ignored under
  `.tmp/cinematic-preview/`; `new_design/` remains untouched.
- Final local validation: all 64 desktop/mobile Playwright checks passed; 79
  theme PHP syntax checks, focused PHPStan, JS/CSS lint, 63 source-evidence
  checks, 28 actual SQLite priority-query checks and editorial persistence
  fixtures passed. The source checks retain the Atlasum-inspired evidence,
  affected/fixed versions, practical checks, patch/mitigation separation,
  exploitation status, source citations and history behavior from `0.1.64`.
  Final screenshot/Axe review passed all 21 desktop/tablet/mobile dark/light
  scenarios with no serious/critical violations, page errors or document
  overflow. The article screenshots show the complete unlocked content.
- Production release workflow run `42191345324` succeeded for source commit
  `30aad51`. Published theme ZIP SHA256:
  `716d3c85ab0b3e4d5d80c09f7e48097b5fd26dbb1dc904a8b03632d0b7c81e12`.
  All 177 packaged files match the committed source plus the existing workflow's
  update-URL stamp. Native WordPress updated `0.1.64` to `0.2.0`; caches were
  purged, the intended dark default was applied, and the live stylesheet and
  homepage components were verified anonymously.
- Rollback checkpoint: `cinematic-20261106T085325Z` in the site's existing private
  backups directory, with verified non-empty database export and valid compressed
  `0.1.64` theme archive. Exact location and checksums stay in the ignored local
  `.tmp/cinematic-production-backup-result.txt` and secure server records.
- Live review evidence is ignored under `.tmp/cinematic-production-review/`;
  live suite output is `.tmp/cinematic-production-e2e.txt`. All 11 visual cases
  passed without serious/critical Axe issues, JS page errors, document overflow
  or duplicate H1, and all 45 audited internal links returned successful pages.
  The production suite passed 62/62 applicable checks; its two preview-only form
  postbacks were skipped deliberately. The existing Zoho provider and all Toolkit
  settings remain unchanged. No test email or contact message was sent.
- The existing assigned footer menu was empty on production. It was populated
  with links to the existing published Blog, About and Contact pages; existing
  header/legal menus were preserved. Desktop/mobile footer links, layout and
  accessibility were checked again, and final home screenshots were refreshed.

### Ivory/Orange Reference Revision - November 6, 2026

- The owner rejected the navy `0.2.0` appearance and supplied the warm AHEAD
  reference, explicitly requesting the same cream/orange palette and layout,
  plus a replacement logo and Site Icon. This supersedes the earlier request
  to preserve the blue shield/navy branding. The project name, actual posts,
  URLs, SEO/article evidence and working integrations remain authoritative.
- Work began on `design/ivory-orange-reference` and was promoted to `stable`
  as `0.3.0`, followed by the targeted `0.3.1` contrast fix. The latest
  reference is implemented as responsive WordPress components: oval-window
  hero, five photographic category/story cards, four transparent 3D focus
  cards, compact briefing/desk and stories/newsletter rows, and a full-bleed
  banner. `ivory-home.css` and `ivory-pages.css` own the new appearance;
  `template-parts/home/ivory.php` owns the actual homepage markup. Default is
  light; optional warm dark mode is available in the footer/mobile menu.
- New original IN monogram/wordmark and favicon assets live under
  `assets/brand/`. `inc/brand.php` provides explicit idempotent native WordPress
  logo/icon installers. Existing editorial featured attachments are preserved.
  All new artwork/prompt provenance is in `docs/ivory-assets.md`; full handoff
  is `docs/ivory-redesign.md`. Nunito Sans is self-hosted with its OFL license.
- Local verification passed all 64 browser checks, 82 PHP syntax checks,
  focused PHPStan and JS/CSS lint. Six homepage and 22 shared-page visual/Axe
  scenarios passed without serious/critical issues, document overflow or page
  errors. The native logo/icon, responsive artwork, search disclosure, and
  four-card mobile grid were verified. The previous navy CSS's blue card
  overrides, mobile hero overflow and oversized banner were corrected before
  release preparation. Both releases are installed on production through the
  native WordPress updater and caches were purged after the native branding
  settings and light default were applied.
- `0.3.0` source commit: `d1483c3`, release workflow `42202155513`.
  `0.3.1` source commit: `8d58be3`, workflow `42215040772`.
  Published `0.3.1` theme SHA256:
  `770ccc3b3c8aedf6b0fa48e45ac8423c04a318fdecc53f3ac310a2aeca445054`.
  All 233 packaged files match source plus the existing workflow's update-URL
  stamp. Production syntax checks passed for all 82 PHP files.
- Rollback checkpoints `ivory-20261106T102004Z` (pre-`0.3.0`, theme `0.2.0`)
  and `ivory-20261106T120459Z` (pre-`0.3.1`, theme `0.3.0`) have verified database
  exports and compressed theme archives in the existing private backups folder.
  Exact paths/checksums remain in ignored `.tmp/ivory-production-backup-result.txt`
  and `.tmp/ivory-patch-backup-result.txt`, never in public configuration.
- Live evidence: `.tmp/ivory-production-e2e.txt` (62 passed, two skipped),
  `.tmp/ivory-production-review/` (11 visual cases, 45 links and three PNG icon
  sizes), plus targeted `.tmp/ivory-share-contrast-fix/` dark/light verification.
  Staging evidence remains `.tmp/ivory-e2e-final.txt`, `.tmp/ivory-shared-final/`
  and `.tmp/glass-home-review/`.
- The body aggregate changed across the longer design session; post `1169`, the
  permanent live brief, was the only published post/page with a newer modified
  timestamp in the deployment window. No editorial migration was run for this
  redesign. The subsequent `0.3.0` to `0.3.1` update has matching complete body,
  identity and Toolkit-settings hashes. The new branding imports are native
  attachments, not changes to editorial featured images.

### Wide Desktop And Shared Alignment Repair - November 7, 2026

- The owner's screenshot reproduced at 2124px: the hero capped at 1440px while
  percentage padding kept growing with its containing viewport, shrinking copy
  to 316px/five heading lines (54px/seven lines at 2560px). Bounded horizontal
  padding now preserves 726px copy, two heading lines and a 480px hero across
  all checked widths from 1366 to 2560. Copy aligns within 5px of the header.
- Legacy flex styling overrode grid centering on search/menu controls. Explicit
  flex alignment and zero native padding center search, hamburger and close
  SVGs in normal/sticky states across Chromium/WebKit and light/dark modes.
  All 764 visible control measurements had zero horizontal/vertical offset.
- The responsive search rule targeted nonexistent `.search-modal__grid` instead
  of actual `.search-modal__layout`, retaining an empty 260px image column on
  small screens. The real layout now collapses to one usable column below
  900px; copy cannot force overflow and the eyebrow reserves close-button room.
  Focus trapping, Escape and focus restoration pass in all 20 contexts.
- Fallback footer navigation lacked the configured menu's flex layout. Both
  now wrap with 16px horizontal and 4px vertical gaps, verified at 320, 390,
  768 and 2124px. The existing ivory/orange design and branding are unchanged.
- Release `0.3.3`, code `f8b0f75`, successful Actions run `42346798306`.
  Published theme SHA256:
  `1f08bb520e0846ff31dabfd08bf7fbd65f1e5041b4d232df2493b4a57b0d93f9`.
  All 234 packaged files match source and established CI stamps. A verified
  database/theme rollback checkpoint `alignment-20261107T062124Z` exists in
  the private backup directory; exact paths/checksums stay in ignored records.
  Native WordPress updated 0.3.2 to 0.3.3 and public caches were purged.
- Validation: preview 76/76 browser checks, JS/CSS lint, all 82 deployed PHP
  syntax checks, 19 responsive page cases and four footer widths passed.
  The final complete live suite passed 74 checks; two preview-only mail tests
  were skipped. No tests were disabled to obtain that result.
  Independent production review repeated all 19 page/four footer cases and
  20 geometry plus 20 keyboard contexts across Chromium/WebKit, five control
  widths, default light/saved dark and scrolled states. All 11 frontend CSS/JS
  assets independently matched local SHA256 bytes. No production form or
  advertising-consent submission occurred.
- The initial live priority check assumed that one of the three changing daily
  alerts was critical. Newly published content invalidated that fixture. It now
  selects a genuine critical result through the actual search form and retains
  assertions for category/severity, keyword match and exclusion by high priority.
  Result visibility is awaited before reading counts to avoid navigation races.
  The revised check passed on desktop/mobile in preview and production.
- Complete before/after publication identities, bodies and Toolkit-settings
  hashes match; thirteen preserved state fields match. Before and after counts
  are 248 posts, six pages, 159 retired and five superseded. Default light mode,
  original native logo/Site Icon, Zoho Mail and configured AdSense are preserved.
  Preview blockers remain absent from production. No editorial migration ran.
- Evidence is ignored under `.tmp/alignment-preview-wide/`,
  `.tmp/alignment-preview-controls/`, `.tmp/alignment-production-wide/`,
  `.tmp/alignment-production-controls/` and `.tmp/alignment-production-e2e-final.log`.
  Implementation/validation notes: `docs/ivory-alignment-fixes.md`.

### Live Runtime Repair - November 6, 2026

- The owner reported the site still looked wrong after the ivory rollout. Fresh
  contexts with normal animation and saved appearance reproduced real issues
  despite previous static/Axe checks passing. A clipped menu button could exist
  while document.scrollWidth still matched the viewport. Always test actual
  control rectangles at scroll 0 and 700, loaded fonts, normal motion, mobile
  navigation and existing dark/system preferences before declaring visual QA
  complete. Include the production consent UI in preview checks.
- Release `0.3.2`, commit `067ae15`, workflow `42224254408`, installed through the
  native updater. Published theme SHA256:
  `f691afbc2eefe0a23b3cfe748eaed92eee9020436175103138f2011af0db4442`.
  All 234 packaged files match source plus the established workflow stamp.
- The mobile sticky/compact header now uses consistent responsive widths and
  stays within 320px. Intelligence has a separate 44px disclosure while retaining
  its real Blog link; hidden topic links leave the tab sequence. Escape/focus
  restoration and no-JS topic access remain intact.
- The mobile hero uses a separate 220px image area with correct source/preload
  hints, compact action labels on one row, and readable coverage links. Tablet
  receives the sharp 1600px source. Dark preference uses a coherent overlay with
  readable text rather than a cream rectangle with pale text. Story text sizes
  increased and the page canvas matches the ivory/dark body below the footer.
- Cookie preferences are in document flow after the footer, never over stories.
  Not now dismisses the current session without setting consent or dispatching
  advertising consent. Explicit acceptance behavior is preserved. The isolated
  preview now enables this public module while continuing to block advertising,
  outbound mail and other side effects.
- Actual technology titles/topics now precede generic CVE artwork selection.
  The latest 12 stories use two protection, eight software-workspace and two
  network illustrations. No random AI/cloud claims were introduced. Fifty PHP
  fixtures preserve manual editor-selected image source, dimensions, alt and
  srcset; article bodies, IDs, URLs and categories are not rewritten.
- Validation: local 70/70 Playwright, six normal-motion light/dark home Axe cases,
  Chromium/WebKit rendering checks, 50 shared-page header cases, eight menu
  cases, 50 media fixtures, 82 PHP syntax checks, focused PHPStan and JS/CSS lint
  passed. Live 68 applicable browser checks plus all 11 normal-motion visual
  cases, 45 links and three icons passed. Evidence is ignored under
  `.tmp/runtime-repair-production/`, `.tmp/runtime-production-e2e.txt`,
  `.tmp/runtime-repair-home/`, `.tmp/repaired-webkit-audit/` and
  `.tmp/current-runtime-audit/`. Full implementation notes:
  `docs/ivory-runtime-fixes.md`.
- Rollback checkpoint `runtime-20261106T131430Z` contains verified database and
  compressed `0.3.1` theme backups in the private backup directory. Exact paths
  and checksums stay in ignored `.tmp/runtime-production-backup-result.txt` and
  secure records. This update's complete before/after body, identity, retirement,
  appearance and Toolkit-settings audits match.

### Startup Checklist For Every New Session

1. Read this whole file.
2. Run `git status --short --branch` and do not overwrite unrelated changes.
3. Run `git log -1 --oneline` and compare it with the current release.
4. Read the files that own the requested behavior before editing.
5. Check the live theme version and public anonymous behavior when live access
   is available. Logged-in and logged-out pages can have different caches.
6. Make a database and theme backup before any production deployment.
7. Implement, test desktop and mobile, package, release, deploy, purge caches,
   and verify as an anonymous visitor.
8. Update this memory file after any meaningful architecture, deployment,
   credential-location, feature, or workflow change.

Suggested first message on a new PC or in a new Codex chat:

```text
Read PROJECT-MEMORY.md completely first. Then check git status, the stable
branch, the latest GitHub release, and the live theme version before changing
anything. Continue using the project's existing release and verification flow.
```

## 2. Secret Handling

This repository is public. Never add any of the following to this file or Git:

- SSH private keys or their contents.
- WordPress, database, hosting, SMTP, Zoho, GitHub, or API passwords/tokens.
- `.env`, production `wp-config.php`, session cookies, or browser exports.
- Backup archives containing the live database or uploads.

The local convention for a temporarily staged live SSH key is
`.tmp/isn-live.pem`. The `.tmp` directory is not tracked and must be transferred
separately through secure media or a password manager. Live SSH host, username,
and hosting credentials must also remain in secure records, not this public
file. On a new computer, obtain them from the owner or hosting dashboard.

The public contact identity is not a secret:
`yashpatel@infosecnexus.com`.

## 3. Owner And Collaboration Preferences

- Communicate in friendly, direct Hinglish. Clear technical English is fine in
  code and documentation.
- Do the work end to end instead of stopping at a suggestion when implementation
  is possible.
- Verify the real site on desktop, tablet, and mobile. Functional buttons and
  responsive layout matter as much as visual polish.
- Preserve existing posts, settings, pages, and user changes unless deletion is
  explicitly requested.
- Never publish fake, guessed, duplicate, or repetitive cybersecurity news.
- New news posts should be useful long-form SEO content with primary sources,
  distinct topic-relevant featured images, and concrete defensive guidance.
- Never expose generator notes, debug details, or internal verification copy in
  public articles.
- The owner prefers theme-contained features and does not want a required
  companion plugin for this installation.
- Use the Git/WordPress update channel after changes instead of repeatedly
  asking the owner to upload ZIP files manually.
- Keep backups and make conservative production changes.

## 4. Repository Map

```text
.
|-- .github/workflows/release.yml       Automated stable-branch release
|-- docs/                               Detailed reports and operating guides
|-- elementor-kit/                      Optional Elementor kit package
|-- tests/                              Playwright desktop/mobile tests
|-- tools/package.ps1                   Release packaging and manifest builder
|-- wp-content/themes/infosecnexus/     Active product and primary codebase
|-- wp-content/plugins/
|   `-- infosecnexus-toolkit/           Legacy compatibility bridge only
|-- .env.example                        Local Docker example, no real secrets
|-- docker-compose.yml                  Optional local WordPress stack
|-- package.json                        JS/CSS/E2E tooling
|-- composer.json                       PHP quality tooling
`-- PROJECT-MEMORY.md                   This continuity handoff
```

Important theme areas:

- `functions.php`: version constant and theme bootstrap.
- `front-page.php`: newsroom homepage data and layout.
- `single.php`: article layout and content gate integration.
- `header.php`, `footer.php`, and `inc/header-builder.php`: navigation,
  search, theme controls, and footer.
- `inc/assets.php`: frontend asset loading and performance behavior.
- `inc/updater.php`: native WordPress GitHub release updates.
- `inc/security-headers.php`: browser security headers and CSP.
- `inc/adsense.php`: AdSense loader and ad placement behavior.
- `inc/agentic.php`: agent discovery and machine-readable endpoints.
- `inc/toolkit/`: theme-bundled content, forms, mail, newsletter, live news,
  artwork, related posts, settings, and optional Elementor features.
- `assets/css/theme.css`: primary responsive light/dark visual system.
- `assets/js/theme.js`: navigation, search modal, theme mode, smooth scroll,
  progress bar, back-to-top, and other interactions.

Some older documents still describe the separate toolkit plugin as required.
That is outdated for the current live install. Treat this file, current theme
code, current theme `readme.txt`, and the latest release as the current truth.

## 5. Product And Design Decisions

### Header And Navigation

- Header brand is compact and responsive.
- Desktop navigation is: Home, Blogs dropdown, About, Contact, Daily Cyber
  Brief, color-mode switch, and search.
- The Blogs dropdown is the scalable category menu. Do not put every category
  permanently across the desktop header.
- Mobile uses an off-canvas menu with the same information architecture.
- Dropdowns must remain open while moving the pointer from the trigger to the
  menu. Mobile submenus must not disappear on tap or hover.
- Search opens a polished modal and searches published blog posts only. Pages,
  legal pages, and internal custom post types must not appear in results.

### Homepage

- The first screen is the working newsroom, not a marketing landing page.
- It includes the security operations hero, two priority stories, Latest
  Intelligence, Critical CVEs, and the Daily Cyber Brief signup.
- The homepage should stay compact enough to scan and should not use giant text
  or oversized cards.
- Homepage data must be correct for logged-in and logged-out visitors. Anonymous
  cache purging was added specifically after stale old posts appeared only for
  logged-out users.

### Posts

- Articles use a readable main column with an article guide/table of contents
  and share actions.
- Related briefings include featured images and useful metadata.
- WordPress comments are closed and the former comment form was replaced with a
  clear contact-page call to action.
- The article Read More gate is placed from the article structure and word
  count, not after the first paragraph and not at a fixed pixel height.
- Unlocking content must never require clicking an advertisement. The button
  may request/lazy-load an ad and reveal the remaining article independently.
- Internal notes such as live-verification/debug copy are not public content.

### Pages And Archives

- About and Contact are first-class header pages, not footer-only links.
- Privacy Policy, Terms and Conditions, and Disclaimer are compact legal pages
  with rewritten site-specific copy.
- Category/archive sidebars are restrained. The obsolete Archives widget is not
  part of the intended layout.
- The 404 page uses the InfoSecNexus security visual language and offers useful
  routes back to current briefings.
- The footer has a centered brand row, a standard copyright line, and legal
  links aligned cleanly on larger screens and stacked on mobile.

### Global Interaction And Styling

- Light and dark modes must each be complete; do not mix white light-mode
  panels into dark mode or vice versa.
- Smooth scrolling is enabled.
- A thin but visible reading-progress line appears at the top.
- The back-to-top button remains hidden at the initial page position and only
  appears after meaningful scrolling.
- Avoid layout shifts, overlapping controls, clipped labels, and unstable card
  dimensions at all supported widths.

## 6. Canonical Pages And Categories

Core pages:

- Home: `/`
- About: `/about/`
- Contact: `/contact/`
- Privacy Policy: `/privacy-policy/`
- Terms and Conditions: `/terms-and-conditions/`
- Disclaimer: `/disclaimer/`

Current category slugs:

- `cybersecurity` - Cyber Security
- `critical-cves` - Critical CVEs
- `linux-administration` - Linux Administration / Linux and DevOps entry
- `devops` - DevOps
- `artificial-intelligence` - AI Security
- `tutorials` - Tutorials
- `cloud-security` - Cloud Security
- `web-security` - Web Security
- `windows-security` - Windows Security
- `network-security` - Network Security

The setup routine creates or repairs these pages, categories, and menu items.
Do not hard-delete user-created categories when adding new ones.

## 7. Theme-Bundled Toolkit

The former plugin functionality now loads from
`wp-content/themes/infosecnexus/inc/toolkit/`.

Current bundled capabilities include:

- Content Blocks custom post type and rendering.
- Conditional sidebars and query helpers.
- Optional Elementor widgets and locations.
- Live search behavior.
- Contact form, admin Contact Messages inbox, and mail delivery.
- Newsletter signup, double opt-in, subscriptions admin, unsubscribe handling,
  and scheduled Daily Cyber Brief delivery.
- Setup/demo content tools and explicit reset controls.
- Live cybersecurity source collection and daily article generation.
- Topic-aware featured image selection and WebP processing.
- Related posts and article utilities.
- AdSense settings and placements.
- Native theme update support.

The legacy plugin remains in the repository and automated release only for old
install compatibility. It should stay deactivated or removed on the current
live site unless a migration specifically requires it. Do not duplicate-load
the plugin and bundled toolkit.

### Settings Persistence And Reset

- Updating or replacing the theme must not reset WordPress options, forms,
  subscriptions, content blocks, menus, or posts.
- Settings live in the WordPress database, not in the theme ZIP.
- Reset/setup actions are separate explicit admin operations. Never run them
  automatically during an update.
- The primary admin areas are under Appearance > InfoSecNexus Features,
  Appearance > InfoSecNexus Setup, and related InfoSecNexus tools.

## 8. Live News And Daily Content

### Source Policy

The system collects current public information from primary or authoritative
sources such as:

- CISA Known Exploited Vulnerabilities and CISA advisories.
- NIST National Vulnerability Database.
- GitHub Advisory Database and GitHub Security Blog.
- Ubuntu Security Notices.
- Microsoft Security Blog and official Microsoft security sources.
- OpenAI security-relevant official news where applicable.

Rules:

- Never invent a headline, CVE, score, affected product, exploitation status,
  publication date, quotation, or source.
- Distinguish known exploitation from ordinary vulnerability disclosure.
- Preserve direct source links and source identifiers.
- Existing posts are preserved. New source IDs and content fingerprints are
  deduplicated before publishing.
- Content should explain operational context, affected scope, prioritization,
  implementation steps, validation, common mistakes, and next actions.
- Different categories and posts must not receive copy-pasted generic sections.
- Each post should use a distinct, topic-matched, credible WebP image whenever
  possible. Repeated image sources are tracked and avoided.

### Schedule

- Daily content setting is enabled on the current live site.
- Hook: `infosecnexus_publish_daily_content`.
- WordPress recurrence: `twicedaily`.
- Intended windows: near 6:30 AM and 6:30 PM in the WordPress site timezone.
- WordPress cron is traffic-driven, so execution can be later than the exact
  minute. Use a real server cron invoking `wp cron event run --due-now` if exact
  timing becomes a requirement.
- Appearance > InfoSecNexus Setup can force an immediate source refresh.
- The live source cache is intentionally short and public page caches are
  purged after successful publication.

## 9. Contact, Newsletter, And Zoho Mail

### Mail Identity

- Public and outbound identity: `yashpatel@infosecnexus.com`.
- The former `contact@infosecnexus.com` address was migrated out of public copy
  and mail routing.
- Zoho Mail is configured on the live WordPress site. Credentials and OAuth/API
  material are server-side only and must never enter Git.
- A provider accepting an API request does not guarantee inbox placement.
  Verify the WordPress/Zoho mail log and both recipient inbox and spam folder.

### Contact Form

- Contact submissions are stored as private `isnx_contact` posts.
- Admin location: Tools > Contact Messages.
- The form uses a same-origin POST, signed page token, one-time REST browser
  challenge, minimum human timing, replay prevention, honeypots, duplicate
  suppression, IP/email rate limits, and high-confidence campaign filtering.
- Contact endpoint: `/wp-json/infosecnexus/v1/contact-challenge` for the browser
  challenge; the actual form action remains protected by WordPress validation.
- Successful visitors receive clear on-page feedback and the site owner receives
  a Zoho-delivered notification.

On 2026-08-01 an automated multilingual price/reseller campaign repeatedly
submitted the form with rotating browser identities and disposable-looking mail
patterns. Version 0.1.36 added the current layered defenses. Existing campaign
messages were moved out of the active inbox, and malicious live simulations no
longer created a post or sent mail. Continue monitoring aggregate spam stats and
Contact Messages. If a distributed adaptive campaign returns, add an external
WAF or privacy-conscious challenge such as Cloudflare Turnstile without weakening
the current server-side controls.

### Newsletter

- Newsletter signups use confirmation/double opt-in.
- Admin location: Tools > Newsletter Subscriptions.
- Confirmation, digest, and unsubscribe flows are built into the theme.
- The visible signup must show success/error feedback and mail should use the
  configured Zoho sender.
- Never silently subscribe an address without confirmation.

## 10. AdSense

- Publisher client: `ca-pub-6550916382964760`.
- The Auto Ads loader is integrated permanently through `inc/adsense.php`.
- Loading is performance-aware and can be deferred until interaction where the
  implementation requires it.
- Desktop can use sticky side-rail ad areas; posts can use in-article slots.
- AdSense fill and display depend on Google approval, inventory, consent, and
  policy. Empty slots are not necessarily a theme bug.
- Never ask, force, or trick a visitor into clicking an ad.
- Never make article access conditional on an ad click.
- Read More may initialize an ad placement, but the article must unlock from the
  user's Read More action regardless of whether an ad loads or receives a click.

## 11. Security, SEO, Performance, And Agent Discovery

### Browser And HTTP Security

`inc/security-headers.php` implements the theme-level headers, including:

- Clickjacking protection (`X-Frame-Options` and CSP `frame-ancestors`).
- `X-Content-Type-Options: nosniff`.
- HSTS on HTTPS.
- Content Security Policy directives.
- Referrer Policy.
- Permissions Policy.

A WordPress theme cannot provide a network/application firewall. WAF and DDoS
protection belong at the CDN, reverse proxy, or hosting layer. Do not claim that
theme headers are a WAF.

`server/nginx-infosecnexus-production.conf` is the current deploy-ready hosting
template created on 2026-08-05. It fixes security-header inheritance for missing
static files such as `/404javascript.js`, adds a strict CSP to static/error
responses, and blocks sensitive files plus PHP execution in uploads. Its syntax
was validated with the production server's Nginx 1.30.4 binary, but it still
requires a manual provider paste, `sudo nginx -t`, reload, cache purge, and live
header verification. The compatible dynamic WordPress CSP intentionally retains
the inline allowances currently required by WordPress output and AdSense; do not
remove them without a coordinated nonce/hash and cache migration.

### SEO And Agentic Browsing

- Search and archives are scoped to useful public posts.
- Articles include SEO metadata and structured data where appropriate.
- `inc/agentic.php` exposes machine-readable discovery support including
  `llms.txt` and relevant agent-friendly site details.
- Public debug information, private CPTs, and administrative data must never be
  included in agent discovery output.

### Performance Practices

- Frontend JavaScript is deferred where possible.
- Images are resized, WebP-encoded, responsive, and preloaded only when useful.
- Avoid loading Elementor or post-only assets on the lean homepage unless
  needed.
- Purge public cache after theme updates, content publication, or image repair.
- Always test logged-out/incognito pages because admin sessions commonly bypass
  the cache that normal visitors receive.
- Do not chase a score by removing required accessibility or security behavior.

## 12. Release And Native WordPress Update Flow

The release workflow is `.github/workflows/release.yml` and runs on pushes to
`stable` or manual dispatch.

It:

1. Reads the release version from the theme `style.css` header.
2. Stamps theme/plugin metadata and the GitHub manifest URL.
3. Runs `tools/package.ps1`.
4. Creates theme, legacy plugin, and Elementor kit ZIPs.
5. Creates SHA256 sums, a release manifest, and an SBOM.
6. Publishes GitHub release `auto-v<version>`.

WordPress checks this manifest:

```text
https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp/releases/latest/download/infosecnexus-releases.json
```

### Required Version Bump

For every code release, update at least:

- `wp-content/themes/infosecnexus/style.css`
- `wp-content/themes/infosecnexus/functions.php`
- `wp-content/themes/infosecnexus/readme.txt`

Keep the legacy plugin version/readme aligned while it remains in the release
bundle, and update the changelog text in `tools/package.ps1`.

Never push code to `stable` with an already released version. The workflow will
attempt to create the same GitHub release tag and fail. For a documentation-only
commit that must not create a release, use a supported skip marker such as
`[skip ci]` in the commit message.

### Local Package And Push

```powershell
npm run lint:js
npm run lint:css
composer phpcs
composer phpstan
npx playwright test
./tools/package.ps1
git diff --check
git status --short
git add <intentional-files>
git commit -m "Describe the release"
git push origin stable
```

Do not commit `dist/`; it is generated and ignored.

### Refreshing The Live Update

After the GitHub release succeeds, use WP-CLI on the live server. Replace the
placeholders with credentials from secure storage:

```bash
cd <wordpress-root>
wp transient delete infosecnexus_theme_update_manifest --network
wp transient delete update_themes --network
wp theme update infosecnexus
wp cache flush
```

Then verify the version, homepage, one category, one post, Contact, newsletter,
search, light/dark mode, and anonymous/mobile behavior.

## 13. Development And Testing

### New Machine Dependencies

- Git.
- PowerShell 7 or a compatible PowerShell.
- Node.js LTS and npm.
- PHP 8.1+ and Composer for local PHP checks.
- OpenSSH client.
- Docker Desktop only if using the local Docker WordPress stack.
- A current Chromium browser for Playwright.

### Bootstrap

```powershell
git clone --branch stable https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp.git
Set-Location isn-theme-plugin-by-yp
npm ci
composer install
npx playwright install chromium
```

Restore `.env` and SSH material separately from secure storage. Do not expect
ignored directories such as `.tmp`, `node_modules`, `vendor`, `dist`, uploads,
test results, or local databases to appear after a clean clone.

### Optional Docker Local Site

```powershell
Copy-Item .env.example .env
docker compose up -d
```

The example binds WordPress to `127.0.0.1:8888`. Replace every example password
inside the untracked `.env` before using it beyond isolated local development.

### Existing iMac Tunnel Workflow

An older local WordPress environment was reached from Windows through the SSH
alias `yash-imac`:

```powershell
ssh -N -L 8888:127.0.0.1:8888 yash-imac
```

Open `http://localhost:8888` while that tunnel remains active. If the browser
says it cannot connect, the tunnel or the WordPress service on the iMac is off.
See `docs/WINDOWS-TO-IMAC-SSH.md`. This local environment is not the production
site and can contain older database content.

### Test Commands

```powershell
npm run lint:js
npm run lint:css
npm audit --omit=optional
composer phpcs
composer phpstan
composer audit
npx playwright test
```

Live E2E run:

```powershell
$env:WP_BASE_URL = 'https://infosecnexus.com'
npx playwright test
Remove-Item Env:WP_BASE_URL
```

At the latest checkpoint, 14 desktop/mobile live tests passed. Local PHP was not
always on the Windows PATH; when necessary, files were piped to a remote
`php -l` process without writing temporary production code.

## 14. Production Playbook

Before a live update:

1. Confirm Git is clean or understand every existing change.
2. Confirm the release version is new.
3. Run linting and tests.
4. Create a WordPress database backup.
5. Create a tar/ZIP backup of the active theme.
6. Confirm backups exist and have non-zero size.
7. Release through GitHub Actions.
8. Update with WP-CLI or native WordPress update.
9. Purge WordPress, page, object, CDN, and browser caches as applicable.
10. Test both logged-in and anonymous sessions.

Production WordPress commands should run as the site owner, not as root. Use the
live connection details and site user from secure records:

```bash
sudo -u <site-user> bash -lc 'cd <wordpress-root> && /usr/bin/wp <command> --skip-plugins=elementor'
```

The `--skip-plugins=elementor` fallback is useful for WP-CLI operations if the
Elementor runtime interferes. Do not deactivate Elementor for visitors unless a
real incident requires it.

Known backup checkpoints created before recent releases included database and
theme archives for versions 0.1.35 and 0.1.36 under the live user's backups
directory. Exact server paths and access details should be confirmed over SSH,
not assumed from this document. See `docs/BACKUP-RESTORE.md` and
`docs/ROLLBACK.md` before restoring.

## 15. New PC Transfer Checklist

1. Push all intended non-secret work to `stable`. Use `[skip ci]` for a
   documentation-only continuity update.
2. Copy SSH keys, `.env`, and any private operational notes separately through
   encrypted storage. Never place them inside the repository archive.
3. Install the dependencies listed above.
4. Clone `stable` and run `npm ci`, `composer install`, and Playwright browser
   installation.
5. Restore secure files to their new local locations and restrict their file
   permissions.
6. Confirm `git remote -v`, `git branch --show-current`, and `git status`.
7. Confirm access to GitHub Actions and the live WordPress admin.
8. Test SSH in non-interactive mode before any deployment.
9. Run the test suite once against local or live WordPress.
10. Start the new Codex session with the prompt from section 1.

If the entire old project folder is copied, remove or review bulky generated
directories such as `node_modules`, `vendor`, `dist`, `lighthouse-results`,
`test-results`, and `.tmp` before trusting them. Reinstall dependencies from lock
files. Treat any copied private key as sensitive and move it out of general
project backups.

## 16. Known Constraints And Future Options

- Production news scheduling now uses `/etc/cron.d/infosecnexus-wordpress`
  every five minutes to run due WordPress events under the site owner. The
  newsroom event recurs every 15 minutes; critical sources use a 15-minute
  cache and general sources use a 30-minute cache. `DISABLE_WP_CRON` is enabled
  so visitor traffic does not control publication timing.
- A cloud WAF/DDoS layer must be configured through Cloudflare, Sucuri, or an
  equivalent edge provider. Nginx hardening alone does not satisfy Sucuri's
  `Website Firewall Not Detected` check.
- The production CSP currently permits `unsafe-inline` for WordPress and Google
  AdSense compatibility. This is an accepted compatibility tradeoff, not a
  delayed cache result. Do not remove it without a coordinated nonce/hash
  migration and complete frontend, admin, form, search, and ad testing.
- AdSense ads can remain blank until Google approves and fills inventory.
- Zoho/API success does not prove inbox delivery; monitor logs and spam folders.
- The public GitHub update model exposes source code. A private repository needs
  an authenticated manifest/package proxy or signed licensed update service.
- Third-party official feeds and photo services can be temporarily unavailable.
  Fail safely, preserve existing posts, and retry rather than fabricating data.
- Anti-spam controls must evolve if attackers distribute traffic or solve the
  browser challenge. Add layers without relying only on a client-side check.
- Lighthouse varies with server load, cache warmth, AdSense, network, and test
  location. Diagnose individual metrics rather than assuming every lower score
  is caused by the theme.

## 17. Work History

This chronology records the major implementation path from the original theme
through the current checkpoint:

1. `38ed84b` - Built the initial InfoSecNexus WordPress theme ecosystem.
2. `35fbfb8` - Added the GitHub/native WordPress auto-update release flow.
3. `ffc27b8` - Corrected update metadata and replaced the old theme preview.
4. `7c93dd4` - Added toolkit update action links.
5. `73a2c19` - Polished article layout and sidebar behavior.
6. `db54c00` - Improved comment and related-briefing presentation.
7. `afaa102` - Bundled toolkit functionality into the theme.
8. `c537c96` - Refreshed site content and overall UX.
9. `55941af` - Expanded article content and replaced comments with contact CTA.
10. `bac118d` - Fixed the update channel and mobile navigation submenu.
11. `038971c` - Added AdSense placements and the article Read More gate.
12. `7267a8e` - Moved the gate deeper into article content.
13. `242e2d8` - Tuned the gate and lazy ad loading.
14. `2e3d1e7` - Improved frontend assets, performance, and security headers.
15. `debcfcb` - Added the permanent AdSense Auto Ads loader.
16. `574b2ff` - Added duplicate-safe daily category content.
17. `bda8cf1` - Added verified current security briefing sources.
18. `3249f3d` - Purged public caches after live briefing publication.
19. `9090dfe` - Purged stale public pages after theme updates.
20. `8ec2200` - Optimized Lighthouse behavior and agent discovery.
21. `b520026` - Added unique WebP artwork generation for posts.
22. `36c198b` - Removed public generator notes and switched to real topic photos.
23. `b1322ac` - Individualized older daily briefings.
24. `24a30fd` - Prevented reuse of the same featured-photo source.
25. `9306c5e` - Added secure contact and newsletter delivery workflows.
26. `24ccbb1` - Completed secure form mail delivery and visitor feedback.
27. `0b48af8` - Fixed the secure contact form layout.
28. `3909ef7` - Migrated public and outbound mail to the primary Zoho mailbox.
29. `0d2e790` - Blocked automated contact spam before storage or email delivery.
30. `2b5ee35` - Added the hardened production Nginx configuration for CloudPanel.
31. `1ff4e3b` - Shipped the rolling newsroom and reversible legacy-content retirement workflow.
32. `1189217` - Kept staged posts visible to the guarded retirement CLI.
33. `8bae5e5` - Matched breaking-news artwork to the affected product context.
34. `2019b21` - Prevented the rolling and featured breaking stories from repeating in the homepage grid.
35. `2d43f0d` - Consolidated all rolling coverage at one permanent live-brief URL.
36. `878c115` - Fixed the permanent brief's mobile article layout and retired-post navigation.
37. `fb64e63` - Kept staged daily briefs out of OR-based custom homepage queries.

The CloudPanel v2 deployment files are:

- `server/nginx-infosecnexus-production.conf` for the documented source.
- `server/cloudpanel-v2-infosecnexus-clean.conf` for comment-free Vhost Editor
  deployment.

The clean file preserves CloudPanel placeholders, the Varnish/port 8080 flow,
PHP-FPM routing, security headers, sensitive-file blocking, and strict headers
for missing static resources. On 2026-08-05, live checks confirmed the homepage
and `/404javascript.js` return CSP, HSTS, clickjacking, MIME-sniffing, referrer,
permissions, and cross-domain policy headers. A fresh Sucuri scan no longer
reported the missing CSP directive. Its remaining warnings were the absent
cloud WAF and the intentional `unsafe-inline` compatibility policy described
above.

Earlier visual and functional revisions also established the current responsive
newsroom homepage, compact logo, Blogs dropdown, About/Contact header placement,
centered footer brand, legal pages, search modal, dark mode, smooth scrolling,
reading progress, delayed back-to-top button, category templates, SEO content,
responsive post layout, and theme settings that survive updates.

## 18. Rolling Newsroom And Content Retirement (2026-08-06 To 2026-08-11)

The `stable` branch and production site are on InfoSecNexus `0.1.44`. GitHub
release `auto-v0.1.44` publishes the native WordPress update manifest and ZIP
assets. The live update manifest and WordPress updater were verified against
theme and optional legacy bridge version `0.1.44`.

The old category-per-day generator was replaced with a source-driven newsroom,
then consolidated into a permanent rolling hub:

- Exactly one rolling cybersecurity post uses the permanent path
  `/live-cybersecurity-brief/`. Its title, modified time, article body, source
  set, and featured image may refresh as verified intelligence changes, but its
  slug and canonical URL do not change at midnight.
- Same-day refreshes update post ID `1169` in place. A content fingerprint
  prevents needless database writes when the normalized source set is
  unchanged.
- The rolling article keeps its original publication date for stable
  `datePublished` history, while the public metadata explicitly renders its
  latest WordPress modified date as `Updated ...` with a semantic W3C
  `datetime`. The NewsArticle schema continues to expose both distinct dates.
- Rolling artwork refresh now ignores photographs attached only to staged or
  superseded posts. This lets the permanent reader-facing hub reclaim its
  preferred topic-matched source without allowing two active public posts to
  reserve the same photograph or triggering repeated regeneration.
- At most two separate breaking posts are allowed per day, and only for
  officially confirmed active exploitation.
- Items are merged by CVE/advisory identity and canonical URL before writing.
- Direct sources include CISA KEV and advisories, NIST NVD, SonicWall PSIRT,
  Palo Alto, Cisco, WordPress, GitHub Advisory Database, Ubuntu, Microsoft,
  GitHub Security, and OpenAI.
- Source failures retain a last-good copy, record health, and can alert after
  repeated failures. Cache purge runs after publication so anonymous visitors
  receive the same posts as administrators.
- Historical rolling IDs `1089`, `1094`, `1124`, `1154`, and `1165` retain
  their former dated paths only as permanent `301` redirects to post ID `1169`.
  They are excluded from archives, internal search, related navigation, and
  core/news sitemaps.
- The core post sitemap contains the permanent hub once and no dated rolling or
  staged daily URLs. The two-day News sitemap is reserved for qualifying
  `breaking` posts and intentionally excludes the continuously updated rolling
  hub.
- Production WordPress cron is traffic-independent. The `infosecnexus` user's
  crontab invokes `wp cron event run --due-now` every five minutes under
  `flock`; the live newsroom event itself recurs every 15 minutes. Verification
  confirmed that its next-run timestamp advanced without a browser request.
- NVIDIA, Intel, and AMD feeds/categories are a future vendor-coverage phase.
  They were deliberately not mixed into the URL-consolidation release.

Legacy-content retirement is intentionally reversible:

- Exactly 159 generated legacy post URLs were inventoried and staged. The
  durable inventory is `docs/retirement-inventory-2026-08-06.csv` with SHA-256
  `fcb10681886e54e1830531d7b27110b9d0f3c88a37f958cfdadff2ef432b76b7`.
- A staged URL remains an exact `200` response with both HTML robots metadata
  and `X-Robots-Tag: noindex, follow, noarchive`. It is hidden from homepage,
  archives, category pages, internal search, and XML sitemaps.
- Do not hard-delete these posts while Google still indexes their URLs. Search
  Console temporary Removals may be requested manually for speed; its public
  API does not expose that operation. Keep the crawlable `noindex` response
  until deindexing is confirmed.
- After confirmation, use the guarded content-retirement finalization command
  with its explicit `--confirm=DELETE` flag. Finalization deletes the staged
  posts and preserves their paths as `410 Gone`. Never run it speculatively.

Google Search Console's Pages report was still processing on 2026-08-07. A
public Google `site:infosecnexus.com` reconnaissance pass found 11 legacy URLs
still discoverable in search. Every URL matched the 159-entry retirement
inventory and was live-verified as `200` with
`X-Robots-Tag: noindex, follow, noarchive`. The preliminary list is stored at
`docs/google-site-discovered-old-urls-2026-08-07.csv`. Public `site:` results
are not exhaustive, so this list must be reconciled with the Search Console
export after processing completes; it is not authorization to hard-delete.
On 2026-08-07, the site owner submitted exact temporary-removal requests for
all 11 URLs in that preliminary list. Search Console showed all 11 requests as
`Processing request`. Do not cancel, duplicate, or broaden those requests to a
site-wide prefix; retain the crawlable `noindex` responses until permanent
deindexing is confirmed.

On 2026-08-11, Search Console showed all 11 exact requests as `Temporarily
removed`. This is successful short-term suppression, not permanent deindexing;
Google documents that temporary removals last about six months. The Page
Indexing report separately showed six examples excluded by `noindex`, five
examples reported as `404`, and six examples as crawled but currently not
indexed. Representative live checks confirmed that staged inventory URLs still
return exact `200` responses with `X-Robots-Tag: noindex, follow, noarchive`,
while a sampled pre-inventory July 24 URL returns `404`. The root feed remains
`200`, and the InfoSecNexus REST namespace returns `200` with `X-Robots-Tag:
noindex`. These report groups are expected or stale crawl snapshots and do not
require `Validate fix`. Do not finalize the 159-URL retirement batch until the
indexed-URL export has been reconciled against the inventory and no staged URL
is confirmed indexed.

The Indexed Pages export downloaded on 2026-08-11 contained 159 unique URLs
from Google's 2026-08-07 snapshot. Exact URL-set reconciliation found that 141
of those URLs belong to the 159-entry retirement inventory; 18 retirement URLs
were absent from the indexed export. A live audit of the full inventory found
all 159 URLs returning `200` with `X-Robots-Tag: noindex, follow, noarchive` and
no anomalies. Across the 159 exported Google URLs, the current live state was
141 retired `200 + noindex` URLs, 11 valid indexable `200` URLs, five intentional
`404` URLs, and two dated rolling URLs returning `301` to
`/live-cybersecurity-brief/`. This proves the implementation is correct but the
Google index snapshot has not caught up, so final deletion remains blocked on a
later export showing zero retirement-inventory overlap. The durable
reconciliation artifact is
`docs/search-console-indexed-reconciliation-2026-08-11.csv` with SHA-256
`47480e31b1431df7a4e0d201b1a5a48bc61ae578a40ffce6f97fd251a0e975f1`.

Production backups made before this migration are stored under
`/home/infosecnexus/backups/newsroom-20260806T170923Z`. A separate pre-`0.1.40`
theme archive is under
`/home/infosecnexus/backups/homepage-20260806T180434Zn`. Confirm archive and
database checksums before using either rollback point. The pre-`0.1.43` theme
rollback archive is
`/home/infosecnexus/backups/homequery-20260810T125732Z/infosecnexus-theme-0.1.42.tar.gz`
with SHA-256
`dc40610cb381004079997fb2bb049d221981ee02ea23eea443df4072bbfd2e2d`.

Release `0.1.43` fixed a query-composition defect where a custom top-level `OR`
metadata query could admit staged posts despite the public retirement filter.
The filter now wraps the existing metadata query and active-content clause in
an outer `AND`. This hides staged cards without changing any of the 159 staged
posts, their Search Console requests, or their exact crawlable noindex URLs.

Release `0.1.44` corrected two presentation defects on the permanent rolling
hub. Post ID `1169` now displays its modified date instead of looking one day
old, and its generated featured image moved from unrelated mail-app artwork to
the dedicated curated source `Bd7gNnWJBkU`. The production attachment changed
from ID `1170` to `1194`; the latter records the public source page
`https://unsplash.com/photos/Bd7gNnWJBkU`. A source audit on 2026-08-11 matched
the current NVD items and CISA KEV item `CVE-2026-8037` against their official
live records. The permanent URL remained self-canonical and returned `200`, a
sample historical rolling URL returned `301` to it, and a sample staged daily
URL remained exact `200` with `X-Robots-Tag: noindex, follow, noarchive`.

The pre-`0.1.44` database and theme backup is stored under
`/home/infosecnexus/backups/rolling-meta-20260811T122222Z`. The database export
SHA-256 is `a11ab023ebdc36aee63277f0ae091b5a3b0837c9efb90d822f27eb7854b098dd`;
the `0.1.43` theme archive SHA-256 is
`4996bae8855d38cf4ad91d64705dcead45494b00f319830fca8768ac96003953`.

Release `0.1.45` made the permanent rolling brief's modified date consistent
on every post-list surface. Archive cards, the Latest Briefings sidebar,
homepage data, related briefings, and Elementor query cards now share one date
helper and display `Updated <date>` for post ID `1169`; ordinary posts continue
to display their publication date. This fixed the category-page mismatch where
the rolling card showed August 11 while the sidebar still showed August 10.

The pre-`0.1.45` database and theme backup is stored under
`/home/infosecnexus/backups/rolling-date-20260811-194532`. The database export
SHA-256 is `a4d89992fb0596ec9591ba1f3d85788cbfba1a52bcc2414398773553c71d5a1e`;
the `0.1.44` theme archive SHA-256 is
`b2e28618ec7c4b7857fcdc4cebbdf44843a140f65e1e1e5705efa02e52022cb0`.

Live anonymous verification after the `0.1.45` deployment confirmed theme
assets versioned `0.1.45`, two matching `Updated 11/08/2026` labels on the
Cyber Security archive, no stale July 27 rolling title, and the same permanent
post identity (`1169`, `/live-cybersecurity-brief/`). A historical dated URL
returned `301` to the permanent hub. All 18 desktop/mobile Playwright checks
passed, including rolling-date consistency, duplicate hiding, distinct post
artwork, secure public forms, SEO/agent discovery, and accessibility smoke
coverage. The 159 staged legacy posts remain published only so their exact URLs
can serve crawlable `noindex`; this is why the WordPress admin post count is
still high. A sampled staged URL remained exact `200` with
`X-Robots-Tag: noindex, follow, noarchive` and staged posts stayed absent from
the public category archive.

Verification for the previous `0.1.44` checkpoint included JavaScript and CSS
linting, an npm dependency audit with zero reported vulnerabilities, production
PHP 8.4 syntax checks, focused PHPStan analysis, logged-out
redirect/header/sitemap checks, desktop and mobile visual inspection, and 16
of 16 live Playwright tests. The anonymous homepage contained theme assets
versioned `0.1.44`, the new rolling artwork, zero dated rolling links, and zero
legacy daily links; a historical rolling URL returned `301`, while a sampled
staged URL remained `200` with `X-Robots-Tag: noindex, follow, noarchive`.

Release `0.1.46` repaired the active newsroom's product artwork mappings. The
permanent rolling brief (post `1169`) uses cyber-operations photography from
curated source `commons:cyber-shield-9107702`; Cisco ASA post `1208` uses an
actual Cisco ASA 5510 image from `commons:cisco-asa-5510`; and Microsoft WinSock
post `1210` uses a Windows Update image from source `-jCY4oEMA3o`. The homepage
also deduplicates breaking cards by story identity. The release artifact SHA-256
was `105a3a56bd56fb1f21fefca83cb233716db6449b17f7340af1b1f3a1c6777aad`.

Release `0.1.47` fixed bundled multi-CVE source descriptions contaminating a
post's taxonomy and guidance. Classification now uses the advisory subject
fields rather than unrelated text elsewhere in a source bundle, focused source
records win description merges, and generic feed names are never presented as
affected vendors. After the forced production refresh, Cisco post `1208` has
only `critical-cves`, `cybersecurity`, and `network-security`, uses edge-appliance
guidance, and retains `commons:cisco-asa-5510`. Microsoft post `1210` has only
`critical-cves`, `cybersecurity`, and `windows-security`, uses Windows guidance,
and retains `-jCY4oEMA3o`. The anonymous homepage shows distinct artwork for
both posts and no MacBook image for the Microsoft advisory.

The pre-`0.1.47` database and theme backup is stored under
`/home/infosecnexus/backups/classification-release-20260812-201244`. The database
export SHA-256 is `e1297a26d17906e6b58e0674a35c390e7d3f7f76e440dcdcbcbf075c37e1d04e`;
the `0.1.46` theme archive SHA-256 is
`44942930f9b095862c74d96273744eb5614711d52e059364b8beda3a7367744e`.

Live verification for `0.1.47` included JavaScript and CSS linting, an npm audit
with zero vulnerabilities, production PHP 8.4 syntax checks, focused PHPStan,
a targeted bundled-advisory classifier regression, exact server-side post and
artwork audits, anonymous header and HTML checks, desktop/mobile visual review,
and all 18 Playwright tests. The release theme ZIP SHA-256 is
`1127ba49f54f310189c8b94cc97b7e71a72f51a71628951342c1af59179d794f`.
The public site returned `200`, served assets versioned `0.1.47`, and retained
its security headers after cache purge.

### Anime Cyber Editorial Redesign (2026-08-13)

Release `0.1.48` implemented the supplied anime cyber editorial design across
the existing WordPress theme without replacing its content model. The shared
header/footer, front page, archives/categories/search, single posts, About,
Contact, legal pages, search modal, 404 page, and post cards now use the same
responsive visual system. The implementation lives primarily in
`assets/css/anime-editorial.css`, `inc/anime-design.php`,
`template-parts/card/post-card.php`, and the existing WordPress templates. It
preserves manual featured images and attachment metadata as authoritative,
with category-aware artwork used only as a fallback.

Production artwork is bundled under `assets/anime/webp-1280/` and
`assets/anime/webp-640/`. The hero maps to `hero-cyber-sentinel.webp`; topic
fallbacks map to the matching `featured-ai-security`,
`featured-cloud-security`, `featured-critical-cve`,
`featured-linux-devops`, `featured-network-security`, and
`featured-windows-security` pairs; About and Contact use
`about-contact-team.webp`. The optional animation is
`assets/anime/animation/hero-cyber-sentinel-loop.mp4`, loaded only after the
static poster and never used as the LCP resource.

The redesign includes a pre-paint dark/light preference, system-color fallback,
reduced-motion handling, responsive images with intrinsic dimensions, an
accessible focus-trapped search modal, whole-card links with visible focus,
single-H1 page structure, canonical and social metadata, Article/BlogPosting
and BreadcrumbList JSON-LD, and no-JavaScript article readability. Release
`0.1.48` was source commit `d123079`; GitHub Actions run `31729418531` produced
release `auto-v0.1.48`. Its release ZIP SHA-256 is
`659bacd8f23859362f8b9f8bcdfabc51a209aae963bb8e6c8f71337295953c2c`.
The pre-release production backup is
`/home/infosecnexus/backups/anime-redesign-20260813T181928Z`; its database
SHA-256 is `cc717bb952e159c9ab6790d83ab00baca690cd14ec0bb070ba8c0d48a5973dc8`
and theme archive SHA-256 is
`28dc613ddc235beed9eae740ea314fcf22f54ec4bf1901dc41c8847e900faf61`.

Release `0.1.49` made single-post related briefings reuse the same compact,
equal-height, whole-link post-card partial instead of maintaining a second card
implementation. It was source commit `8322835`; GitHub Actions run
`31735564319` produced `auto-v0.1.49`. The release ZIP SHA-256 is
`531c44520a74dbafbf6a93c6b1f4bcece182d71eff6f29872311a2215dc50a66`.
The pre-release backup is
`/home/infosecnexus/backups/anime-related-20260813T192631Z`; its database
SHA-256 is `aa4d41aede4b660308989ae80cb46f7b23a4fbbcebf4e983d453b7614e27cb02`
and theme archive SHA-256 is
`82842d3b540d230a8c5fbd141f42004b6c78f34e91fde8d308544f1b75d2ea6a`.

Release `0.1.50` changed the dark critical-state token to `#d90d2e`, giving
white label text a WCAG contrast ratio above 5:1, and made `color-contrast` an
explicit Axe regression failure. It was source commit `9cb67b9`; GitHub Actions
run `31738158030` produced `auto-v0.1.50`. The exact release ZIP SHA-256 is
`1ce01394c9d44304a4b76f84ffa09325e3de40c1d7e3c816f4d3bd98a39745a5`.
Before production installation, a verified database/theme backup was created at
`/home/infosecnexus/backups/anime-contrast-20260813T201813Z`; its database
SHA-256 is `7db6d2bc6fd3d887adaa9c7ce1a13bc4be1b9a33956617dad9c455911fbc9401`
and theme archive SHA-256 is
`aec1e68597e129e0bfda64399756f3ef37edcb30cf82d958130ea1bdfd741cb5`.

Final anonymous production verification on `0.1.50` found all 75 theme PHP
files syntax-clean and all 30 desktop/mobile Playwright checks passing. Mobile
Lighthouse scored 100 Performance, 100 Accessibility, 100 Best Practices, and
100 SEO with LCP 1.5 seconds, TBT 0 ms, and CLS 0. The clean desktop rerun also
scored four 100s with LCP 0.48 seconds, TBT 0 ms, and CLS 0. Public responses
served assets versioned `0.1.50` and retained CSP, HSTS, nosniff, DENY framing,
and strict-origin referrer headers. Lighthouse completed valid reports, though
the Windows Chrome launcher printed an `EPERM` warning while deleting its
temporary profile after each run.

The GitHub `releases/latest/download/infosecnexus-releases.json` edge response
briefly remained cached at `0.1.49` after `0.1.50` was published, while the same
URL with a cache-busting query returned `0.1.50`. For that release only, the
exact checksum-verified ZIP was installed through WordPress CLI. This was an
upstream edge-cache delay, not disabled theme updates; the updater option stayed
enabled and no content or theme settings were reset.

Release `0.1.51` introduced the circular back-to-top reading-progress control,
dynamic footer clearance, and a readable light-mode contact-form warning. It
was source commit `6346f01`; its release ZIP SHA-256 is
`4f60129eb4dbe2c38ece5af0ccd1d5efd30062a3e133cb8f9f5802453d2876e8`.
Release `0.1.52` then corrected a dark-mode cascade conflict so critical cards
retain their red edge and hover glow in both color modes. It was source commit
`d3543ff`; its release ZIP SHA-256 is
`ad4fe3f4f6b88f5fdba78890cd58f5d2754355c4f7ea77b2d1eb5c1b0149a7ea`.

The verified pre-`0.1.52` production backup is
`/home/infosecnexus/backups/ui-progress-20260814T073753Z`; its database SHA-256
is `4736e26ba00b7c0d7725c307c5e1adab9ae9e49d93b0501e3cd83606410ff289`
and its `0.1.51` theme archive SHA-256 is
`b7de094b100f11a68768ff64d14312202aaf3701c4bf4d7216966c20557654ce`.
The exact `0.1.52` ZIP was checksum-verified before installation, all 75 live
theme PHP files passed syntax checks, caches were flushed, and anonymous pages
served CSS and JavaScript versioned `0.1.52` with the existing security headers.

Final production verification passed all 34 desktop/mobile Playwright checks,
including keyboard behavior, reduced motion, no-JavaScript content, Axe,
forms, SEO/agent discovery, and the new UI regressions. JavaScript and CSS
linting passed, `npm audit --omit=optional --audit-level=high` found zero
vulnerabilities, the light contact warning passed Axe color contrast, and
critical cards retained their intended state in both themes. At the bottom of
the mobile live brief, the circular control measured 46 by 46 pixels, reported
100 percent reading progress, and remained about 15 pixels above the footer.
A fresh Lighthouse CLI attempt timed out without producing a report, so the
verified `0.1.50` four-100 Lighthouse run remains the latest valid performance
record rather than an unverified score being reported for `0.1.52`.

Release `0.1.53` keeps the permanent `/live-cybersecurity-brief/` article
visible as the first card on page one of each category it belongs to even as
its original publication date ages. The ordinary category query excludes that
post, so pagination cannot repeat it, while the latest-briefings sidebar pins
the same canonical article and labels it with its updated date. Page two was
verified to contain zero rolling-brief grid duplicates while retaining one
sidebar reference on both desktop and mobile. The source commit was `1d53863`;
GitHub Actions run `35853725897` produced release `auto-v0.1.53`. The release
ZIP SHA-256 is
`cf60aa397e81b43066733eb4cf9e7949ce01f34130183556edd529c993d4f3b3`.

Production now resolves to the migrated AWS host `13.234.204.79`. The site
remains at `/home/infosecnexus/htdocs/infosecnexus.com`, owned by the
`infosecnexus` system user; WP-CLI on this host is `/usr/bin/wp`. Never record
the SSH private key or credentials in this file. Before installing `0.1.53`, a
verified database/theme backup was created at
`/home/infosecnexus/backups/rolling-archive-20260923T113658Z`; its database
SHA-256 is
`3c1db8db0c94cf7619838d8ac1bb377fa3016fc7ad3db7603d11f1d781a45e6c`
and its `0.1.52` theme archive SHA-256 is
`89ebd6cb09452965f11ee998dfcb7d0c63410620a12e09357cbe385becbf0e25`.

The deployment checksum matched the signed release list, all 75 packaged PHP
files passed the production PHP 8.4 syntax check, and WordPress caches and
transients were cleared. Anonymous production served assets versioned
`0.1.53`. All 34 desktop/mobile Playwright checks passed, including the rolling
brief archive regression, unique responsive artwork, whole-card keyboard
behavior, persistent color mode, circular progress/back-to-top clearance,
light contact-warning contrast, no-JavaScript readability, Axe, secure forms,
SEO/agent discovery, and hidden internal notes. JavaScript and CSS linting and
`git diff --check` also passed. `npm audit --omit=optional` reported three
development-only advisories in `colord`, `fast-uri`, and `js-yaml`; no runtime
package was changed as part of this focused release.

Release `0.1.54` makes unattended newsroom attribution and keywords
deterministic. Cron publication now resolves a configured editorial user, with
the site administrator/editor/author as a guarded fallback, and writes that
user as `post_author`. It also assigns concise topic-aware native post tags and
the corresponding built-in, Yoast, Rank Math, and SEOPress focus-keyword
metadata. Tag archives remain usable for navigation but are `noindex, follow`
and excluded from the core XML sitemap so the keywords do not create thin
indexable archives.

The source commit is `a6b79d2`; GitHub Actions run `35967841127` produced
release `auto-v0.1.54`. The checksum-verified theme ZIP SHA-256 is
`95fd23f1cacb85e98b208aa8a015774e4f2c50e2e7aedc89dc4b607099359f3d`.
The pre-deployment database and `0.1.53` theme backup is
`/home/infosecnexus/backups/newsroom-metadata-20260924-124330`.

The production metadata migration checked all 54 active newsroom posts,
assigned 43 previously missing authors to WordPress user ID 1 (`Yash Patel`),
and refreshed focused keywords on all 54 without failures. Verification found
54 active newsroom posts, zero blank authors, exactly one permanent
`/live-cybersecurity-brief/`, and all 159 legacy retirement URLs still staged.
The staged URLs must not be permanently deleted until a fresh Search Console
indexed-URL export reconciles to zero retirement-inventory overlaps; after
that, use the existing guarded retirement finalization so their paths become
410 responses.

All 75 packaged PHP files passed production syntax checks. JavaScript and CSS
linting passed, caches were flushed, the tag archive returned a noindex
directive, and all 36 anonymous desktop/mobile Playwright checks passed,
including the new editorial-author, post-tag, and tag-sitemap regression.

The fresh Google Search Console `Indexed pages` export received on 2026-10-01
contained 40 indexed URLs. Exact normalized reconciliation against
`docs/retirement-inventory-2026-08-06.csv` found 16 retirement URLs still in
Google's indexed set, down from 141 in the 2026-08-11 export. Live header checks
confirmed that all 16 still return `200` with
`X-Robots-Tag: noindex, follow, noarchive`; their Search Console `Last crawled`
dates range from 2026-07-28 through 2026-08-06, so Google has not yet recorded a
post-staging recrawl for them. The other indexed rows comprise 23 current
indexable `200` URLs and one intentional `301` consolidation redirect, with no
live-state anomalies.

The durable audit is
`docs/search-console-indexed-reconciliation-2026-10-01.csv`; its SHA-256 is
`EC87891A589B0A0C3F3CA1656FABAE19A778F43ACC9431CFC098CA965B467951`.
No deletion, finalization, or `410` conversion was run at this checkpoint. Wait
for a later fresh `Indexed pages` URL export to show zero overlap with the
159-entry retirement inventory before running the guarded finalizer.

### SEO Targeting And Blog Hub (2026-10-07)

Release `0.1.55` added the first-party SEO targeting layer and crawlable Blog
hub. It introduced deterministic primary/supporting keyword metadata, native
post tags, an SEO editor panel and Posts-list keyword column, homepage and
article metadata/schema improvements, paginated canonical support, tag and
empty-search `noindex` rules, a static `/blog/` page template, Blog menu
navigation, and a `security.txt` endpoint. The source commit is `9a2957a`;
GitHub Actions run `37594665388` published `auto-v0.1.55`.

Release `0.1.56` fixed the static Blog page's page-number detection and
canonical URL so `/blog/page/2/` serves a distinct result set and canonicals to
itself. The source commit is `d70bb97`; GitHub Actions run `37634715154`
published `auto-v0.1.56`. The checksum-verified signed release ZIP SHA-256 is
`953ac2e1a1760302a4ec5977a5cb14cac7d459900d19704cd427dd57c22b195f`.

The pre-`0.1.55` production backup is
`/home/infosecnexus/backups/seo-targeting-20261007-141810`; its database
SHA-256 is
`c3ed4c6a365a392c4399d8b7314f8491f958e4e82f1f7c63f00f55e386b68491`
and theme archive SHA-256 is
`dcd99a4eb99ac1c14edab128bbd50ae783586bbbf31fce53f9807d0b2f667ff0`.
The pre-`0.1.56` backup is
`/home/infosecnexus/backups/blog-pagination-20261007-142619`; its database
SHA-256 is
`3732df4ef85a3b282b52774a33ffa02d127747d9859ab7f10d64c1602e9e9cb9`
and theme archive SHA-256 is
`9e4c33ffe9d7493788602f053288d1c40971f6fd3cd7e667b3a6d101581a732a`.

Production is running `0.1.56`. The Blog page is WordPress page ID `2658`.
Anonymous verification found `/blog/` and `/blog/page/2/` returning `200` with
self-referencing canonicals, 13 and 12 cards respectively, and zero post-card
overlap. The homepage title is `Cybersecurity News, Critical CVEs & Threat
Intelligence | InfoSecNexus`; Organization, WebSite/SearchAction, NewsArticle,
author, publisher, keyword, article-section, published-date, and modified-date
data are present. The page sitemap contains `/blog/`.

The metadata repair processed 69 published newsroom posts. After excluding the
159 staged retirement posts and five dated rolling briefs superseded by post
ID `1169`, the live SEO inventory contains 64 active newsroom posts with zero
missing authors, zero missing primary keywords, and zero duplicate primary
keyword phrases. Superseded post IDs `1089`, `1094`, `1124`, `1154`, and
`1165` return `301` to `/live-cybersecurity-brief/`; they must not be counted as
independent keyword targets. The 159 staged retirement URLs remain untouched
and must still reach zero overlap in a fresh Search Console indexed-URL export
before guarded deletion and `410` finalization.

CloudPanel's static `/.well-known/` location bypasses WordPress, so production
uses a real `/.well-known/security.txt` file in the document root. It declares
the editorial security contact, canonical URL, English preference, and a
2027-10-07 expiry. Recreate that static file after any server migration if the
document root itself is rebuilt.

Verification for `0.1.56` included JavaScript and CSS linting, `git diff
--check`, syntax checks for all 76 packaged PHP files on production PHP, exact
release-checksum verification, anonymous metadata/header checks, and all 38
desktop/mobile Playwright tests. Runtime npm dependencies report zero known
vulnerabilities. The development-only lint/test dependency tree currently has
13 audit findings (11 high and 2 moderate); resolving all of them may require a
breaking Stylelint toolchain update and was intentionally kept out of this SEO
release.

On 2026-10-09, Search Console temporarily showed `Couldn't fetch` for
`/wp-sitemap-posts-post-1.xml`. Direct anonymous and Googlebot-user-agent
checks returned `200 OK` with an `application/xml` content type, valid XML,
about 0.28-second TTFB, and 66 post URLs. Production access logs also recorded
a real Googlebot fetch of the parent sitemap at 14:21 IST and the post sitemap
at 14:23 IST, both with status `200`; there were no matching Nginx or PHP
errors. Treat the Search Console row as delayed/stale processing unless a
future live request or server log shows a non-200 response.

On 2026-10-10, Search Console confirmed that the delayed result had cleared.
The `/wp-sitemap.xml` index and all four child sitemaps show `Success`, with 84
discovered URLs: 66 posts, 7 pages, 10 categories, and 1 author URL. No sitemap
resubmission or code/configuration change is required for this incident.

Technical keyword mapping and indexability are now in place, but they cannot
guarantee human search traffic. The next evidence needed for query-to-page
optimization is Google Search Console **Performance > Search results** data,
exported separately as Queries and Pages for both the latest 28 days and 90
days. Coverage/Indexed-pages exports remain useful for the retirement audit but
do not show which search queries earn impressions or clicks.

### SEO Checker Reconciliation (2026-10-19)

The external PowerHost SEO Checker snapshot scored the site 95/100, with 85
passes, 10 warnings, and one failed check. Live verification separated the
actionable findings from checker limitations. Releases `0.1.57` and `0.1.58`
shortened the homepage search title to `Cybersecurity News & Critical CVE
Analysis | InfoSecNexus`, added a unique 149-character Blog-hub description,
kept paginated Blog canonicals self-referencing, and suppressed Elementor's
public generator/version meta tag without changing its admin setting. The
source commits are `f393aa0` and `042c36c`; GitHub Actions run `39223889997`
published `auto-v0.1.58`. Its theme ZIP SHA-256 is
`543dca988048ab3f204dacf5d6de2a598baff70686bb17d9b2d378b9343a1c23`.

Production is running `0.1.58`. Anonymous requests confirm the compact title,
the Blog-specific description, zero generator meta tags, the exact
`/blog/page/2/` canonical, and the existing CSP, HSTS, clickjacking,
content-sniffing, referrer, and permissions headers. A fresh first-party crawl
visited 300 HTML URLs without errors and reconciled all 73 post-sitemap URLs:
73 have real internal inlinks and zero are orphaned. The checker's 36 possible
orphans are therefore a false positive caused by its 20-page crawl limit; do
not add an all-post link dump merely to satisfy that warning.

The pre-deployment rollback set is
`/home/infosecnexus/backups/seo-audit-20261019T081207Z`. Its database SHA-256 is
`83e4fc21c233dbe64703ffefa4d5a828db70514eda6515989324891254f05011`,
the `0.1.56` theme archive SHA-256 is
`b020bd445d355ceb85953bc2318a30bcf1f53da821e0a4538551407d31e1aa48`,
and the `0.1.57` theme archive SHA-256 is
`32f7d52a80adf5e6174c1fc340905e3fe3198322fa09bbeb00a1bed7f2311f9a`.
All three verified successfully.

Verification passed JavaScript and CSS linting, release packaging, production
PHP 8.4 syntax checks for the changed PHP, `git diff --check`, and all 38 live
desktop/mobile Playwright tests. The shipped/runtime npm dependency set has
zero known vulnerabilities. Development-only lint/test dependencies still
report 13 advisories and require a separate toolchain-upgrade task; they are not
served by WordPress.

The remaining checker warnings are not theme defects. Authoritative DNS is
managed at Spaceship. SPF, DKIM, and DMARC now pass external checks. No CAA
record exists; adding a Let's Encrypt CAA restriction is optional, not a
vulnerability fix. A phone number, street address, or social-profile links must not be
invented solely for a score and should be added only when genuine public
business details are supplied. Human organic traffic still depends on search
demand, content quality, authority, and query-to-page matching; the next
required evidence remains the Search Console Queries and Pages exports for the
latest 28 and 90 days.

### SEO Checker Reconciliation And Release 0.1.59 (2026-10-21)

The PowerHost report scanned at `2026-10-21T11:53:48+05:30` scored 97/100
(Grade A): On-Page 100, Technical 100, Site-wide 93, Content 92, Performance
100, Security 100, Trust 88, Domain/DNS 98, and Social 93. It recorded 89
passes, six warnings, and one failure. Its actionable external item is the
absent CAA record. Optional public phone, address, and social-profile data must
remain absent unless genuine details are supplied. The text-to-HTML and
readability warnings describe the concise newsroom homepage, not an indexing
failure. Analytics remains an informational item until a real GA4 measurement
ID and consent decision are supplied.

The report's claimed 39 possible orphan pages are a scanner-limit false
positive. A first-party breadth-first crawl visited 500 same-origin HTML pages
without request errors and found all 76 post-sitemap URLs linked internally;
zero had no inlinks and zero had only one inlink. The live sitemap contains four
child sitemaps and 94 unique URLs: 76 posts, seven pages, ten categories, and
one author. It overlaps the 159-URL retirement inventory by zero URLs. Do not
add a link dump merely to satisfy limited third-party crawlers.

Release `0.1.59` exposes the permanent rolling brief's real WordPress modified
date on the homepage using semantic `time` markup. It also emits configured X,
GitHub, LinkedIn, and YouTube profiles in the Organization schema `sameAs`
array and marks the matching visible profile links with `rel=me`. No profile is
invented when the Customizer fields are empty. Source commit `c418382` produced
GitHub Actions run `39564273120` and release `auto-v0.1.59`; the release theme
ZIP SHA-256 is
`ec1397953cfb577fb8aa96f9638e5f2dd6ad9ae975842c16742f349d5df498fc`.

Before deployment, the verified rollback set was created at
`/home/infosecnexus/backups/seo-checker-20261021T074233Z`. Its database
SHA-256 is
`a557127be61703fd6ea9a9f257fe1abd623fc9dc4dbe475fe245dacee1eb58`, and
the `0.1.58` theme archive SHA-256 is
`35a52b9a82d9ab46b7d7cf442469d1b6a1e633e25cb1ce576d888f761152d973`.

Production is running `0.1.59`. Anonymous verification returned HTTP 200,
served versioned `0.1.59` assets, one H1, the homepage canonical, no generator
meta tag, and `Updated 21/10/2026` with a W3C datetime. The release passed
JavaScript and CSS linting, `git diff --check`, package generation, all 76 PHP
files on production PHP 8.4, a zero-vulnerability runtime npm audit, security
header checks, sitemap/retirement reconciliation, and all 38 live desktop and
mobile Playwright tests. Human traffic is not implied by this technical score;
query-to-page work still requires fresh Search Console Performance exports for
Queries and Pages over 28 and 90 days.

### Neumorphic Editorial Redesign And Releases 0.1.60-0.1.61 (2026-10-21)

Release `0.1.60` adds a reversible Neumorphic Editorial presentation layer in
`assets/css/neumorphic-editorial.css`, enqueued after the existing Anime
Editorial stylesheet. The existing WordPress templates, content queries,
forms, newsletter handling, search modal, rolling brief, SEO/schema output,
GitHub updater, dark/light preference, and 159 staged retirement URLs were not
replaced. The layer covers the newsroom, archives and search, single articles,
about/contact/legal pages, 404 view, shared navigation and footer, cards, and a
circular reading-progress/back-to-top control. Light and dark modes share the
same component system, and reduced-motion behavior remains supported.

Before editing, two rollback artifacts were created and verified locally:

- Theme ZIP:
  `.tmp/backups/infosecnexus-theme-pre-neumorphism-20261021-173138.zip`
  with SHA-256
  `19b9322fbf79f77d40916b1bbeba2d5c76ce5dd4bcbec848e0a1c377acc48036`.
- Complete Git bundle:
  `.tmp/backups/infosecnexus-repo-pre-neumorphism-20261021-173138.bundle`
  with SHA-256
  `adb1c86d73fbe3581e8bb5ae19a2746f618676a3b087ae1ac29f02cca67e28e6`.
  `git bundle verify` passed.

Validation passed JavaScript and CSS linting, `git diff --check`, package
inspection, the complete 38-test live desktop/mobile Playwright baseline, and
an 11-scenario desktop/mobile browser and Axe matrix that injected the local
presentation layer over production markup. The injected matrix found no
serious Axe violations, horizontal overflow, duplicate H1, or unexpected
console errors; search-modal focus restoration, mobile navigation, reduced
motion, and the scroll-progress control were exercised. PHP CLI and a local
WordPress runtime were unavailable on this workstation, so PHP linting and a
native local WordPress render were not run for this release. Existing
development-only npm audit advisories remain a separate toolchain-maintenance
task and are not shipped to site visitors.

Source commit `339a0c0f0859c162f1c96beb2d91456ca453b2c0` produced successful
GitHub Actions run `39614474854` and release `auto-v0.1.60`. The published theme
ZIP SHA-256 is
`4f26bc2c59c73273fcaf99bd91bdc76e9f94dfdc914ca0a991ada1585b88b2d6`,
and it matches the release `SHA256SUMS`. The tracked `dist/` release metadata,
SBOM, checksum file, and theme ZIP were synchronized from those exact release
assets.

Before production installation, a database and theme rollback backup was
created at
`/home/infosecnexus/backups/neumorphic-20261021T154018Z`. The compressed
database SHA-256 is
`2e6824fb364a988c974d7af76a746ef5eed24abf19d17ab985bf7770d3154a64`,
and the pre-deployment `0.1.59` theme archive SHA-256 is
`9a95a997f549bcdf0a5910b42fc4c751cfcab34e84a2542809dc0891da5d033c`.
Both archives passed integrity/listing checks, and the backup directory is
restricted to the site account.

Production was first updated to `0.1.60` through the native WordPress updater.
The full live test suite then found a keyboard-focus regression on archive card
links: 36 of 38 tests passed. Release `0.1.61` adds an explicit three-pixel cyan
`focus-visible` outline for those linked cards. Source commit
`9e9ecc7995942cc916fe63c59d990f95cc65d79f` produced successful GitHub Actions
run `39635809948` and release `auto-v0.1.61`. The exact release theme ZIP
SHA-256 is
`2a36062c07a0c41b0eb2048991873029656a56fadc52769eb8639ef62b8bdbdf`,
matching the published checksum. The tracked `dist/` theme, toolkit, and
Elementor ZIPs plus the manifest, SBOM, and checksum file were synchronized
from those exact release assets and all three ZIP hashes match `SHA256SUMS`.

Production was then updated from `0.1.60` to `0.1.61` through the native
WordPress updater and WordPress caches were flushed. Anonymous HTML returned
HTTP 200 with one H1, retained the hardened security headers, and loaded
`theme.css`, `anime-editorial.css`, and `neumorphic-editorial.css` versioned
`0.1.61`. Production PHP lint passed for the changed PHP files; JavaScript and
CSS linting and `git diff --check` passed. The complete live Playwright suite
passed 38 of 38 tests. A separate 11-scenario production visual/Axe matrix
covering home, category, single, contact, and 404 pages in light/dark desktop
and mobile modes found zero serious Axe violations, horizontal overflow,
duplicate/missing H1 failures, unexpected console-error scenarios, or failed
HTTP responses. The archive-card focus indicator was confirmed as a solid
three-pixel outline. The unrelated untracked `new_design/` reference pack was
not modified or included in either release.

### Shared Surface Polish And Release 0.1.62 (2026-10-22)

Release `0.1.62` refines the shared Neumorphic Editorial surfaces after the
initial rollout. Directional top-left double-bevel shadows and duplicate hero
borders were removed in favor of centered, restrained depth and a subtle inset
highlight. Critical story cards now use a neutral perimeter with a controlled
three-pixel red inset edge. Desktop navigation retains its simple active
underline without a clipped inset box, and the footer brand is unframed. The
same changes apply across home, archive/category/search, single, contact,
special/legal, and 404 views in both color modes.

Source commit `2a162f0` produced successful GitHub Actions run `39744210985`
and release `auto-v0.1.62`. The published package hashes are:

- Theme ZIP:
  `8002afd814d67cc167eb7c823783b63e995ffe387a313f31a46d1053049a291e`.
- Toolkit ZIP:
  `21a56b4d7bd2d6e1160f36bcc07f9ba878a6f37a029ef73a651ee7a8ade90f1f`.
- Elementor kit ZIP:
  `07abb030fac00be83364790cd05544fb9523a467af0be409c7c262be4aa80ba1`.

Before production deployment, a rollback set was created at
`/home/infosecnexus/backups/box-polish-20261022T075029Z`. Its database
SHA-256 is
`bb31ff5922135c97dfe837f9f174833e832eddfc77e0e9c95ef227190a651717`,
and its theme archive SHA-256 is
`36998995c8f84f5d078c054f59d4ca0990f5a04778bf76af482090f667b8c933`.
Both rollback artifacts are restricted to the site account.

Production on the current AWS host was updated from `0.1.61` to `0.1.62`
through the native WordPress updater, followed by a WordPress cache flush. All
76 deployed PHP files passed linting, JavaScript and CSS linting passed,
`git diff --check` passed, the runtime npm audit reported zero vulnerabilities,
and the complete live Playwright suite passed 38 of 38 tests. An additional
11-scenario production matrix covering home, category, single, contact, and
404 pages across desktop/mobile and light/dark modes found no horizontal
overflow, missing or duplicate H1, serious Axe violations, failed HTTP
responses, or unexpected console-error scenarios. Search-modal focus handling,
mobile navigation, visible card focus, and the circular back-to-top control
also passed. A final cache-bypass request confirmed that anonymous production
loads `neumorphic-editorial.css` version `0.1.62`, contains the new centered
shadow and critical inset-edge rules, and no longer contains the old top-left
shadow signature. The unrelated untracked `new_design/` reference pack was not
modified or released. WP-CLI still reports an unrelated Elementor PHP
deprecation notice during shutdown; it did not affect frontend rendering or
the test suite.

### Source-Backed Articles And Releases 0.1.63-0.1.64 (2026-11-04)

The complete Atlasum Linux IPv4 TCP article, including its version matrices,
technical explanation, checks, remediation, exploitation timeline, update log,
and sources, was reviewed as the owner's editorial benchmark. Its useful
structure and evidence standards were adopted across the newsroom without
copying its prose or pretending its tests were performed by InfoSecNexus.
The enduring policy is `docs/blog-editorial-standard.md`.

`class-live-intelligence.php` now uses content schema `12`. It preserves CISA
notes and direct advisory links, CNA descriptions and affected/status ranges,
CVSS authority/version/vector, NVD assessments, and GitHub affected/fixed
package information. Valid public CVE IDs receive cached CNA enrichment during
publication or CLI work; ordinary article reads do not fetch evidence. Articles
distinguish permanent remediation, interim controls, validation, source-event
dates, and confirmed exploitation. General announcements use their own neutral
structure, and grouped advisories preserve the scope of exploitation rather
than attributing attacks to every listed issue. Unknown facts remain unknown.

`class-newsroom-editorial.php` and the publishing integration store normalized
evidence, generated body hashes, and actual body-change history. A stored body
hash or the explicit `_infosecnexus_editorial_manual` flag protects subsequent
manual body edits. Initial adoption of hashless content requires recognized
legacy generation markers and checks for prior editor saves. Six existing
generated bodies with old `_edit_last` metadata were individually reviewed and
explicitly adopted with their current SHA256 hashes; this included the rolling
hub, whose old editor metadata would otherwise block future cron refreshes.
Do not broadly bypass this guard on other installations or future custom posts.

The historical upgrade command defaults to a dry run:

```bash
wp infosecnexus newsroom upgrade-articles
wp infosecnexus newsroom upgrade-articles --apply
```

The migration upgraded all 81 existing individual stories: 79 CVE articles and
two grouped Cisco hardening advisories. One current story refreshed through the
normal publication flow, while the first explicit migration updated 80 and
left that story unchanged. The permanent hub refreshed through its normal
source-backed publishing flow. The two Cisco stories received reviewed official
source snapshots from `docs/editorial-source-notes-2026-11-04.json`; these contain
six email-appliance and 13 firewall release rows, source dates, exact reported
fixes, and carefully scoped exploitation statements. They are dated evidence
snapshots, not a substitute for checking later vendor revisions.

The final audit found 82 active posts, all with schema `12`, matching generated
body hashes, structured version evidence, technical source detail, and actual
history. Seventy-one articles have structured scoring evidence; a numeric score
is not invented for the others. Exact before/after reconciliation preserved all
246 published post identities, titles, slugs, original publication dates,
featured-image IDs, categories, retirement flags, and supersession targets.
All 159 staged article bodies were unchanged. A historical rolling URL retained
its `301` to the permanent hub. No retirement finalization or deletion occurred.

Release `0.1.64` fixes a real repeat-refresh defect found during verification:
duplicate source URLs and CNA self-references could rotate reference labels and
needlessly rewrite three historical articles. URL attribution is now stable.
The final full migration rerun reported `checked=82`, `eligible=0`, `updated=0`,
`unchanged=81`, `skipped=1` (the rolling hub), and `failed=0`; complete article
audits before and after were identical, including body hashes, update times,
and history counts. Corrected deadlines and other rendered evidence fields are
also included in source fingerprints so real source changes are not missed.

The shared single-post reader now provides nested H2/H3 navigation with stable
unique anchors, gated-section navigation, truthful Published/Updated dates,
keyboard-scrollable code and version tables, multiline code copying, visible
source citations in Article schema, and a single history section outside the
gate. Light/dark article styles include accessible hover, current-section, and
keyboard-focus states. Existing ad/unlock behavior and no-JavaScript reading
remain functional.

Release records:

- `0.1.63`: source `2684738`, successful GitHub Actions run `41850310359`, theme
  ZIP SHA256 `9d2987d79895e7a2d7a0fe7a9a4b6e1e72e2a8b52263f9c96d477fde2470cd1d`.
- `0.1.64`: source `fa8bff4`, successful GitHub Actions run `41851924176`, theme
  ZIP SHA256 `64549a02cce448fa418bb57af2408a0c74a308bad7f4fc61f5fa952bc1c7223b`.

Both checksum-verified releases were installed through the native WordPress
updater. Before the first release, the database/theme rollback set was created
at `/home/infosecnexus/backups/editorial-20261104T142803Z`: database SHA256
`c122362621dd772cec3cff89c45eb0606b679f445194d575da212ce1c0060011`, and theme
SHA256 `6ad823cb7a618c0d9c733c399090f4a017737d428c00a2f712e07d4fa09b8e66`.
Before `0.1.64`, another verified set was created at
`/home/infosecnexus/backups/editorial-stability-20261104T145037Z`: database SHA256
`a6cf822a2ccee04198fef0525e249431d51185f79808fdf1e66a04a6aad035a5`, and theme
SHA256 `09e4a7cd15f79b0bba05f251bb6b9386068c23634b59140f9dd5ecaee1f5e87a`.
Backup directories and files are restricted to the site account.

Final validation passed all 77 deployed PHP syntax checks, focused PHPStan,
JavaScript/CSS linting, clean packaging, 63 source-evidence checks, stateful
editorial persistence fixtures, and whitespace checks. The five actual live
article scenarios covered WordPress at 1366/light and 390/dark, Cisco at
1366/dark and 390/light, and the rolling hub at 768/light. All returned `200`,
loaded `0.1.64` assets, had one H1 and unique guide targets, unlocked gated
subsections, exposed semantic dates and one history, and passed serious Axe,
overflow, and page-error checks. The WordPress article displays all 25 explicit
branch ranges plus the CNA default status, including 7.1; clipboard content
matches the complete multiline code after Windows CRLF normalization.

The complete anonymous Playwright suite passed 40 of 42 checks. Its two failures
are the same pre-existing homepage artwork-diversity checks seen in the initial
36-of-38 baseline: distinct generated photo attachments exist, but
`Anime_Design::manual_featured_image_id()` deliberately substitutes the common
critical-category artwork for generated images. This presentation policy was
not changed or hidden by weakening those tests. Future artwork work should
resolve that policy explicitly. The unrelated untracked `new_design/` pack was
untouched. Generated `dist/` changes were not committed, following the source
release policy; exact official packages and verification artifacts remain local
under `.tmp/release-0.1.63`, `.tmp/release-0.1.64`, and
`.tmp/editorial-live-final`.

## 19. Definition Of Done

A task is not complete merely because code was edited. For this project, done
normally means:

- The requested behavior is implemented in the correct theme-owned module.
- Existing site data and settings are preserved.
- Relevant linting and tests pass.
- Desktop and mobile visuals are inspected.
- Logged-out production behavior is verified when the task affects the live
  frontend.
- Forms, buttons, navigation, search, mail, and update behavior are exercised
  when touched.
- The theme version is bumped for code releases.
- GitHub release succeeds and the live site receives the intended version.
- Caches are purged and stale anonymous pages are checked.
- Secrets and generated artifacts remain outside Git.
- This project memory is updated when the enduring project state changes.
