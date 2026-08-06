# InfoSecNexus Project Memory

> Future Codex and project owner: read this file completely before changing the
> project. Then run the startup checklist in the next section. This is the
> authoritative continuity handoff for the current project state.

Last updated: 2026-08-05 (Asia/Kolkata)

## 1. Current Snapshot

- Project: InfoSecNexus WordPress cybersecurity newsroom.
- Live site: `https://infosecnexus.com`
- Repository: `https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp`
- Main release branch: `stable`
- Repository visibility: public, which permits unauthenticated WordPress update
  downloads. Moving it to private requires an authenticated update service.
- Current theme version: `0.1.36`
- Current known release: `auto-v0.1.36`
- Current known code commit: `2b5ee35` (`Add hardened production Nginx config`)
- WordPress was running version 7.0.2 at this checkpoint.
- The live site is theme-first. The InfoSecNexus Toolkit features are bundled
  into the theme and the separate toolkit plugin is not required on this site.
- Latest known verification: the complete desktop/mobile Playwright suite
  passed, live anonymous pages were checked, and the latest Lighthouse
  checkpoint reached 100 in Performance, Accessibility, Best Practices, and
  SEO with all available agentic checks passing. Re-test after every release;
  scores are not permanent guarantees.

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

## 18. Rolling Newsroom And Content Retirement (2026-08-06)

The `stable` branch and production site are on InfoSecNexus `0.1.40`. GitHub
release `auto-v0.1.40` publishes the native WordPress update manifest and ZIP
assets. The live update manifest was verified to advertise theme and optional
legacy bridge version `0.1.40`.

The old category-per-day generator was replaced with a source-driven newsroom:

- One canonical rolling cybersecurity brief is created per site day and updated
  in place throughout that day. It is frozen when the date changes.
- At most two separate breaking posts are allowed per day, and only for
  officially confirmed active exploitation.
- Items are merged by CVE/advisory identity and canonical URL before writing.
- Direct sources include CISA KEV and advisories, NIST NVD, SonicWall PSIRT,
  Palo Alto, Cisco, WordPress, GitHub Advisory Database, Ubuntu, Microsoft,
  GitHub Security, and OpenAI.
- Source failures retain a last-good copy, record health, and can alert after
  repeated failures. Cache purge runs after publication so anonymous visitors
  receive the same posts as administrators.
- Production status checked on 2026-08-06 at 18:00:39 UTC contained 120
  normalized items. All 12 configured source checks were healthy and fresh;
  SonicWall PSIRT supplied one current item.

The first live rolling post is ID `1089` at
`/2026-08-06-live-cybersecurity-brief/`. The first confirmed breaking post is
ID `1091` at
`/cve-2026-63077-cve-2026-63077-jetbrains-teamcity-deserialization-of-untrusted-data/`.
Both use distinct real WebP editorial images and appear in the two-day
`/news-sitemap.xml`. The core post sitemap contains current newsroom posts and
does not contain staged legacy posts. Nginx was adjusted so `.xml` requests
reach WordPress instead of the static-file 404 handler.

The first unattended day rollover was verified immediately afterward: server
cron published post ID `1094`, `/2026-08-07-live-cybersecurity-brief/`, at
2026-08-07 00:00:19 in the WordPress site timezone. The news sitemap then
contained exactly the new rolling brief, the previous rolling brief, and the
confirmed breaking post, with no staged legacy URLs.

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

Production backups made before this migration are stored under
`/home/infosecnexus/backups/newsroom-20260806T170923Z`. A separate pre-`0.1.40`
theme archive is under
`/home/infosecnexus/backups/homepage-20260806T180434Zn`. Confirm archive and
database checksums before using either rollback point.

Verification for this checkpoint included JavaScript and CSS linting, PHP
syntax checks, PHPStan level 3 with zero errors on the changed frontend and
artwork modules, logged-out header/sitemap/retirement checks, desktop and mobile
visual inspection, and 14 of 14 live Playwright tests. The server's temporary
test scripts, test directories, and uploaded release ZIPs were removed from
`/tmp` after verification.

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
