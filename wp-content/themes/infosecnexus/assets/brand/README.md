# InfoSecNexus ivory identity

Original geometric IN monogram and wordmark authored as SVG for the November 6,
2026 ivory/orange redesign. The mark combines an ink letter stem, a small orange
node and an orange diagonal connecting the two stems of N. It is not a stock
shield or a copy of the supplied AHEAD identity.

- Ink: `#171922`; orange: `#fa622d`; icon tile: `#fbf8f3`.
- `monogram.svg`: transparent 64px vector mark. Inline header rendering uses
  `currentColor` for the ink portion so it remains legible in either color mode.
- `logo.svg`: 520 x 96 transparent standalone identity.
- `logo.png`: 1040 x 192 transparent native WordPress/publisher logo.
- `site-icon.svg`: vector source for the rounded icon tile.
- `site-icon-{512,192,180,48,32,16}.png`: real PNG icon variants.
- `favicon.ico`: ICO containing the 16, 32 and 48px PNG variants.

PNG files were rendered directly from these SVGs with Chromium. The ICO is a
standard multi-image container. No image generation API or external raster
illustration was used for the identity.

`InfoSecNexus\Theme\Brand::install_logo()` and `install_site_icon()` import the
packaged PNGs into native WordPress media when explicitly called through WP-CLI.
Each method reuses its marked attachment on repeat runs. They do not delete the
previous identity or alter content attachments. Editor-selected replacement
logos and icons continue to work after the managed identity is installed.
