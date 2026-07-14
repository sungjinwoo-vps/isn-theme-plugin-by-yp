# Feature Parity Matrix

This matrix is a clean-room inventory for InfoSecNexus. It records original feature names, component ownership, status, test evidence, and limitations. It does not claim complete parity with any third-party product.

Status values: Planned, In Progress, Implemented, Tested, Deferred, Not Applicable.

| Area | Status | Evidence | Limitations |
| --- | --- | --- | --- |
| Theme foundation | Implemented | `wp-content/themes/infosecnexus` | Remote activation test pending. |
| Toolkit foundation | Implemented | `wp-content/plugins/infosecnexus-toolkit` | Remote activation test pending. |
| Global design tokens | Implemented | `inc/customizer.php`, `assets/css/theme.css` | Advanced visual token presets deferred. |
| Light/dark/system color mode | Implemented | `assets/js/theme.js` | Manual switch persists per browser. |
| Header builder | Implemented | `inc/header-builder.php` | Text-list Customizer UI, not drag/drop. |
| Footer builder | Implemented | `inc/footer-builder.php` | Text-list Customizer UI, not drag/drop. |
| Blog and single templates | Implemented | `archive.php`, `single.php`, `front-page.php` | Full magazine builder controls deferred. |
| Conditions/content blocks | Implemented | `class-conditions.php`, `class-content-blocks.php` | JSON rules UI, visual rule builder deferred. |
| Elementor locations | Implemented | `inc/elementor.php` | Elementor Pro rendering depends on legal install. |
| Elementor widgets | Implemented | `includes/elementor/widgets.php` | Styling controls are foundational. |
| Live search | Implemented | `class-search.php`, `assets/js/toolkit.js` | Taxonomy filter UI deferred. |
| Accessibility foundations | Implemented | templates, CSS, JS dialogs | Axe/Lighthouse execution pending. |
| WooCommerce presentation | Implemented | `inc/woocommerce.php`, toolkit WooCommerce module | Advanced commerce modules deferred. |
| Packaging | Implemented | `tools/package.ps1` | Clean WP install validation pending. |

See `docs/FEATURE-PARITY.csv` for line-item detail.

