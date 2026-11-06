# Ivory and coral InfoSecNexus redesign

Release candidate `0.3.0`, November 6, 2026.

The latest supplied AHEAD reference supersedes the previous navy/cyan direction.
The requested design is a warm ivory page, near-black rounded headings, coral
orange accents, separate raised header capsules, an oval-window image hero,
five image-led highlight cards, four tactile topic cards, a compact featured
briefing/security desk row, latest stories/newsletter row and a full-bleed final
image banner. All text, navigation and controls are real responsive components.

Palette: `#f5f0e9` page, `#fbf8f3` panels, `#fffaf4` raised cards,
`#171922` ink, `#67615c` muted text and `#fa622d` coral. Small orange links use
a darker family shade for readable contrast. Bright primary buttons use bold
large white text; the compact mobile navigation retains accessible contrast.
Soft taupe shadows replace blue glows. A warm charcoal optional appearance
remains available through the footer/mobile appearance control; light is default.

## New identity

An original geometric IN monogram and matching InfoSecNexus wordmark replace
the old blue shield identity. Editable vector, transparent PNG and favicon sizes
live in `wp-content/themes/infosecnexus/assets/brand/`; its README describes them.

`inc/brand.php` supplies `Brand\install_logo()` and `Brand\install_site_icon()`.
The explicit WP-CLI deployment step installs these into the native WordPress
Customizer/media/Site Icon settings, idempotently, without fetching external
files. It also sets the light default. Existing editor-selected article images
remain unchanged. Future deliberate custom logo/icon selections are respected.

## Assets and implementation

Original warm photography-style art and transparent 3D topic icons are optimized
WebPs in `assets/ivory/`: 36 delivery files, approximately 888 KiB in total.
The largest hero is 50 KiB. The wide hero keeps negative space for actual HTML
headings; exact 16:9 social/article variants preserve image metadata contracts.
[ivory-assets.md](ivory-assets.md) records all prompts and built-in imagegen
provenance. Nunito Sans is self-hosted as a 31 KiB variable WOFF2; its OFL license
is bundled in `assets/fonts/`. A font preload reduces late typography changes.

`front-page.php` selects `template-parts/home/ivory.php`. `inc/ivory-media.php`
provides responsive image/icon helpers; the earlier cinematic media API delegates
to it so shared article/archive/social fallbacks also match. `ivory-home.css`
owns the homepage and `ivory-pages.css` covers all other page types, navigation,
forms, dialogs, consent UI and footer. Existing search/severity queries, article
guides, code copy, table scroll, retirement policies and mail handlers remain.

The homepage's native disclosure opens real keyword/topic/priority search.
Empty topic desks link to their genuine archives. Latest stories use real
publication dates and source excerpts; no fabricated reviews or metrics replace
the travel reference's testimonials. Motion respects reduced-motion and no-JS
visits retain complete content. The footer contains the appearance control so
the desktop header can match the reference's single search circle.

## Verification and preview

Local preview: http://localhost:8890/. Use `tools/preview-start.ps1` to resume,
`tools/preview-sync.ps1` after edits and `-ResetChecks` before repeated form tests.
Preview setup now applies the new native logo/icon and light default too.
Its database remains isolated, outbound mail blocked, and robots disallowed.

Verified: 64/64 desktop/mobile browser checks, 82 PHP syntax checks, focused
PHPStan and repository JS/CSS lint passed. Six homepage dark/light cases at
1440/768/390px and 22 shared-page cases passed with no serious/critical Axe
violations, document overflow or JS page errors. Real logo and favicon URLs
returned successful images. No production email/contact message is needed for
these checks. Reports and screenshots remain ignored under `.tmp/ivory-*` and
`.tmp/glass-home-review/`. Final deployment evidence belongs in PROJECT-MEMORY.md.
