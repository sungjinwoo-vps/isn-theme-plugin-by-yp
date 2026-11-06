# Cinematic editorial artwork

Original decorative assets were generated with the built-in image generation
tool on November 6, 2026. They are illustrations, not documentary evidence,
screenshots, article facts, staff photographs, or replacement branding. The
existing InfoSecNexus identity and editor-selected featured images remain
authoritative.

The brief combines the second supplied reference's restrained fiber-light
background with the first reference's tactile blue 3D illustration style.
All text, navigation, cards, and forms remain real WordPress components.

## Final assets

Workspace destination: `wp-content/themes/infosecnexus/assets/cinematic/`.
`manifest.json` lists exact dimensions, byte sizes, and alpha-channel status.

| Key | Image | Responsive widths |
| --- | --- | --- |
| `hero` | Blue-cyan fiber light flow with dark left negative space | 640, 800, 1280, 1600 |
| `shield` / `critical` | Layered shield, padlock, and raised plinth | 256, 512, 960 |
| `cloud` | Rounded cloud and padlock | 256, 512, 960 |
| `linux` | Terminal console and deployment gears | 256, 512, 960 |
| `ai` | Neural-node processor | 256, 512, 960 |
| `network` | Switch and connected nodes | 256, 512, 960 |
| `newsletter` | Envelope and briefing letter | 256, 512, 960 |

Transparent illustration sources retain genuine alpha. The shield has a
16:17 intrinsic aspect ratio; the other illustrations are square. Exact 16:9
card variants at 640 and 1280 pixels preserve existing article/social-image
layout contracts. `windows` resolves to shield; `team` resolves to network
artwork, without implying that an illustration depicts real employees.

The largest hero source is approximately 70 KB. Most category placements use
11–21 KB 256-pixel sources. Larger illustration sources are available only for
appropriate responsive placements; below-fold artwork defaults to lazy loading.

## Prompt set

Every prompt used `stylized-concept`, a production website asset rather than a
UI mockup. Shared constraints were a midnight-navy setting, cobalt/azure blue
`#2788ff`, cyan `#43dff6`, navy `#122545`, and icy-white highlights; no orange,
red, purple, text, logos, watermarks, people, metrics, or screenshot components.
Transparent-object prompts requested real alpha, a centered complete object,
generous margins, tactile ceramic and translucent glass, broad smooth bevels,
restrained specular reflections, and subtle isolated shadows.

- **Hero:** Wide 16:9 cinematic navy background. A graceful bundle of thin blue
  and cyan optical fibers sweeps from the lower left through the lower center
  toward the right edge. Tiny glowing tips and distant bokeh provide restrained
  depth. The upper-left half remains dark negative space for live HTML text;
  the luminous strands occupy the right half and bottom third.
- **Shield:** A thick rounded layered blue ceramic/cyan-glass shield, with an
  inset closed white-blue padlock, standing on two stacked low rounded square
  blue plinths. Complete three-quarter isometric object with cyan rim light.
- **Cloud:** A rounded layered blue ceramic cloud with an integrated closed
  blue padlock and white-cyan shackle, rendered as a complete three-quarter
  isometric object.
- **Linux/DevOps:** A rounded blue terminal console with a white angle-chevron
  and short cursor dash, beside two beveled blue gears. No words or letters.
- **AI:** A rounded blue ceramic microprocessor with short beveled pins, a navy
  center, and an abstract luminous cyan connected-node pattern. No lettermark.
- **Network:** A rounded blue router/switch with three luminous cyan Ethernet
  port shapes and an arc of three connected rounded blue nodes behind it.
- **Newsletter:** A rounded closed blue envelope with an inset folded flap and
  a small icy-blue letter sheet peeking out, carrying abstract line marks only.

All seven generated outputs were visually inspected before use. Standard image
resizing and WebP encoding produced the responsive delivery files; the source
generations remain unchanged in the image tool's generated-image directory.
No fallback API/CLI image-generation workflow was used.
