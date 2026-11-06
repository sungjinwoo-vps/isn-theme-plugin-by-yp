# Warm ivory artwork

Original imagery for the November 6, 2026 InfoSecNexus redesign was generated
using the built-in `image_gen` tool. No API key, fallback CLI, stock-image
download or copied travel photograph was used. The supplied AHEAD screenshot
guided the cream materials, warm daylight, soft shadows, oval cabin window and
coral accent. It was not used as a screenshot background or flattened interface.

These are decorative conceptual illustrations, not evidence of an incident,
an actual customer, physical product, facility or security claim. Website copy,
navigation, articles, forms and controls remain real WordPress components.

## Workspace assets

All runtime files are in `wp-content/themes/infosecnexus/assets/ivory/`.
The machine-readable `manifest.json` records exact dimensions, byte sizes and
transparency for every WebP.

| Asset | Delivery widths | Composition |
| --- | --- | --- |
| `hero-{width}.webp` | 640, 800, 1280, 1600 | Wide ivory cabin and oval server-hall portal, empty left side |
| `hero-card-{width}.webp` | 640, 1280 | Exact 16:9 portal-focused variant for article/social-image contracts |
| `{topic}-{width}.webp` | 320, 640, 1280 | Exact 16:9 topic-card image |
| `{topic}-portrait.webp` | 640 | Original 4:3 composition for compact card placement |
| `icon-{topic}-{width}.webp` | 256, 512 | Square 3D object with genuine transparent alpha |

Topic keys are `critical`, `cloud`, `linux`, `ai` and `network`. Topic card crops
retain their complete main object; only peripheral architecture or foreground
plinth is cropped. Hero's wide composition is retained separately from its
16:9 card version. The icon outputs preserve the original generated alpha.
No synthetic background removal was used.

All eleven original generated assets were visually inspected before resizing.
Standard Lanczos resizing, subject-aware framing and WebP encoding produced the
runtime variants. Original generated PNGs remain unchanged in the image tool's
generated-image directory; runtime code consumes only the workspace WebPs.

## Final prompt set

### Hero

Use case: `stylized-concept`. Asset type: responsive production website hero
background, not a website screenshot or UI mockup. One ultra-polished wide
landscape 3D architectural editorial illustration, approximately 2.3:1. A
luxurious softly sculpted ivory interior inspired by the curved walls and oval
window of a premium airplane cabin. The entire left 58 percent is nearly plain
warm ivory with extremely soft daylight and curving cream surfaces, leaving
negative space for real HTML headline and buttons. On the right, one tall oval
window/portal with a thick softly glowing ivory frame opens into a sophisticated
dark navy server hall, tiny warm coral status lights and pale natural daylight.
At the far right is a cropped navy upholstered sculptural seat; at lower right
is a cream tabletop, blank dark notebook and tiny coral tab. Dominant colors:
`#f5f0e9`, `#fbf8f3`, pale oatmeal and warm taupe; charcoal/navy only in the portal
and seat; limited `#fa622d` accents. Matte ivory architecture, tactile fabric,
smooth rounded bevels, soft broad shadows and warm morning sun. Bright, refined,
airy photographic realism with gentle 3D tactility. No text, typography, logos,
interface, cards, buttons, overall image border, people, watermark or cyan glass
objects. Left portion stays empty and very light for black type.

### Five editorial topic images

Shared prompt: Use case `product-mockup`. One landscape editorial topic-card
photograph/3D illustration, approximately 4:3, for a cream-and-orange cybersecurity
website. Premium tactile photographic 3D realism, warm morning sunlight, broad
taupe shadows, matte ivory ceramic and subtle brushed metal. Cream `#f5f0e9`,
off-white `#fbf8f3`, coral-orange `#fa622d` as the only saturated accent and
charcoal `#171922` details. A refined editorial product/lifestyle photo with a
complete centered subject and comfortable breathing room, no neon cyber art,
text, typography, logo, watermark, frame, interface, people or blue/cyan lighting.

- **Critical:** Sculpted ivory padlock with a brushed silver shackle on a low
  cream stone platform, small coral warning triangle beside it with an embossed
  exclamation shape, dark keyhole, three-quarter camera and cream architecture.
- **Cloud:** Soft off-white ceramic cloud hovering above a neat miniature ivory
  server enclosure, tiny coral lock on its front, dark vents, shallow depth of
  field and a sunlit linen backdrop.
- **Linux:** Open charcoal laptop on an ivory desk with abstract white terminal
  lines and no legible words/numbers, small coral gear, cream ceramic coffee cup
  and softly lit curved wall, three-quarter camera.
- **AI:** Tactile ivory processor on a rounded plinth, charcoal connector pins,
  coral connected-node pattern embossed on its face, natural shadows and a
  three-quarter close view.
- **Network:** Compact cream network router, dark ethernet openings, coral cable
  looping into the foreground and two cream satellite nodes behind it, rounded
  forms, brushed metal and a warm pale-beige room.

### Five transparent category icons

Shared prompt: Use case `stylized-concept`. One small isolated 3D category icon
for a warm ivory/coral editorial website, not a screenshot or interface.
Complete centered object on a genuinely transparent background, square with at
least 15 percent clear margins. Matte ivory ceramic, rounded bevels, brushed
silver, coral `#fa622d` and tiny charcoal `#171922` details. Warm studio lighting
and delicate self-shadow, no opaque floor/backdrop, text, wordmark, watermark,
blue, cyan, purple, extra decoration or bounding box.

- **Critical:** Ivory padlock with silver shackle, small inset coral shield plate
  and dark keyhole.
- **Cloud:** Rounded ivory cloud with small coral padlock attached at its lower
  center and gentle dimensional contours.
- **Linux:** Charcoal terminal console with an ivory abstract chevron/dash and
  small coral gear at its lower right; no legible text.
- **AI:** Ivory square processor with charcoal short pins and coral connected
  neural-node pattern raised on its face.
- **Network:** Rounded cream router with three dark port shapes, a coral cable
  looping below and two connected ivory nodes above, simplified silhouette.
