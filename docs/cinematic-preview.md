# InfoSecNexus cinematic glass preview

Review build: `0.2.0-preview.1`; deployed release: `0.2.0`, November 6, 2026.

**Working preview: http://localhost:8890/**

This is an isolated WordPress implementation with real public content. The
initial preview was completed without a production deployment. The owner then
explicitly requested production installation on November 6, 2026. Release
[`0.2.0`](https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp/releases/tag/auto-v0.2.0)
is now deployed at **https://infosecnexus.com/** through the native updater.
See `PROJECT-MEMORY.md` for the verified production state and rollback checkpoint.

## Design and page coverage

The homepage combines the supplied cinematic glass reference's serif headline,
fiber light, floating panel and discovery bar with the other reference's rounded
sections, raised editorial cards, original 3D category illustrations, briefing,
newsletter and final call to action. The existing shield logo and InfoSecNexus
wordmark are preserved. Blue and cyan form the visual accents; red identifies
actual critical severity rather than category membership or KEV membership.

The shared system covers the blog hub, paginated archives, category and other
archives, search results, individual articles, About, Contact, all existing legal
pages and 404. Articles retain the source-backed content structure, accessible
article guide, version tables, code copy controls, update history and citations.
The existing read-more interaction still opens the complete article.

Animations use native CSS and JavaScript: slow light flow and particles, a
staggered hero entrance, restrained desktop pointer parallax, 6px card lifts,
button/arrow feedback, 600ms section reveals and sticky glass navigation.
Mobile omits pointer parallax. Reduced-motion and JavaScript-disabled visits
retain visible content. No animation library, video or WebGL was added.

Search submits real WordPress queries. Topic and recorded priority filters
combine with the keyword, including an empty keyword. Priority filtering uses
indexed evidence lookups, respects numeric score boundaries and keeps known
exploitation separate from CVSS severity. Existing retirement exclusions remain
part of the same query. Every story card and topic points to its actual route.
Topics without active stories link to their genuine archive.

## Open or refresh the preview

From the repository in Windows PowerShell:

```powershell
.\tools\preview-start.ps1
# After changing theme files:
.\tools\preview-sync.ps1
# Before repeating the browser form tests:
.\tools\preview-start.ps1 -ResetChecks
```

The existing ignored SSH key and `.tmp/cinematic-preview-connection.json` are
required on this workstation. Keep their values in secure records. The preview
uses a loopback-only PHP server and a local SSH tunnel, with a separate SQLite
database outside its document root. It can be restarted with the first command.
If the temporary remote preview was cleared, that command rebuilds it from a
read-only public-content export. `-RefreshContent` explicitly refreshes the
snapshot; normal restarts do not overwrite preview content.

The snapshot contains 247 published posts, six public pages and 247 attachment
records. There are 83 active archive stories; 159 retired and five superseded
posts retain their original identities and policies. Public author names and
allowlisted metadata are copied; production passwords, subscribers, contact
submissions, SMTP settings and other private options are not copied.

Preview pages are marked noindex. Preview cron, automatic updates, content/art
generation, advertising, outbound server HTTP and mail delivery are disabled.
The preview default is dark; the existing user-selectable light mode also works.

## Forms and remaining configuration

The existing newsletter backend is connected, including signed form tokens,
validation and confirmation workflow. Preview delivery deliberately returns
HTTP 503 with the real `mail-delayed` configuration message. It never reports
an email was sent when mail is unavailable. Contact retains browser validation,
its signed browser challenge, honeypot and rate limits.

To test actual delivery on a future staging installation, configure the existing
Toolkit SMTP/mail integration with staging credentials and remove that staging
installation's mail blocker deliberately. Production mail configuration was
neither read into this preview nor changed. No new vendor/API service is needed
for this redesign. The owner subsequently authorized production installation;
production keeps its existing mail provider and configuration.

## Validation and artifacts

Browser checks cover desktop and mobile, navigation/focus, actual topic/priority
search, pagination, canonical URLs, article controls, sitemaps, form handling,
motion reduction, disabled JavaScript and responsive overflow. Visual/Axe review
covers six homepage combinations at 1440/768/390px in dark/light plus 15 shared
page scenarios. These are lab checks, not a claim about production Core Web
Vitals or mail deliverability.

Verified results: **64/64 browser tests passed**, 79 theme PHP files passed syntax
checks, focused PHPStan passed, JS/CSS lint passed, 63 source-evidence checks and
28 discovery SQL checks passed, and editorial persistence fixtures passed.
All 21 visual/Axe scenarios passed with no serious/critical violations, page
errors or document overflow. Article screenshots include the unlocked body.

After installation, the actual production suite passed **62 applicable tests**;
two preview-only form postbacks were skipped. Eleven live visual/Axe scenarios
and 45 safe internal GET links passed. All 79 deployed PHP files passed syntax
checks. Before/after hashes confirm unchanged published identities, bodies,
dates, images, categories, retirement state and Toolkit configuration. Production
retains its active Zoho Mail plugin and advertising configuration, with no
preview mail/HTTP blockers. No test message was sent.
The previously empty assigned production footer menu was populated with the
existing Blog, About and Contact pages and verified on desktop and mobile.

Local review artifacts are in `.tmp/cinematic-preview/`: theme ZIP, checksum,
browser/visual reports and screenshots. The theme ZIP contains only the theme;
it does not contain the preview database, credentials or contact/subscriber data.
Original artwork prompts, provenance, delivery sizes and manifest details are
documented in [cinematic-assets.md](cinematic-assets.md).

## Changed files

- `wp-content/themes/infosecnexus/front-page.php`: complete responsive homepage.
- `inc/cinematic-design.php`: evidence-backed discovery and display helpers.
- `inc/cinematic-media.php` and `assets/cinematic/`: responsive original artwork.
- `assets/css/cinematic-glass.css`, `assets/css/cinematic-pages.css`: homepage
  and shared page design, light mode, responsive rules and motion preferences.
- `assets/js/cinematic-glass.js`, `assets/js/theme.js`: restrained motion,
  scroll/header behavior and accessible modal opening/closing.
- `inc/header-builder.php`, `inc/footer-builder.php`: real navigation and footer.
- `template-parts/card/post-card.php`, `page-blog.php`, `inc/anime-design.php`:
  shared story cards, topic links, artwork fallbacks and true severity accents.
- `inc/toolkit/class-newsletter.php`: optional intro rendering to avoid duplicate
  newsletter headings while retaining the existing backend.
- `inc/assets.php`, `functions.php`, `style.css`, `readme.txt`: theme wiring,
  responsive preload and preview version.
- `tools/preview-*`: isolated public-content preview, restart/sync and guarded
  test preparation tools.
- `tests/e2e/cinematic.spec.js`, `tests/e2e/infosecnexus.spec.js`,
  `tests/php/cinematic-discovery.php`: meaningful behavior/SQL regression checks.

All `inc/`, `assets/` and template paths above are relative to the theme directory.
The pre-existing untracked `new_design/` folder was left untouched.
