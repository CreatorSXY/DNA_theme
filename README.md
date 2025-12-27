# DNA WordPress Theme

Minimal, WooCommerce-first WordPress theme for **Design n’ Aesthetics (DNA)**. The theme ships with pre-built page templates, curated WooCommerce integrations, and compiled CSS/JS assets so you can activate it without a build step.

## Requirements

- WordPress 6.x
- PHP 8.0+
- WooCommerce 7.x or newer (the theme replaces most default WooCommerce styling)

## Installation

1. Copy or clone this repository into `wp-content/themes/dna` (or keep the folder name consistent with the theme slug).
2. Activate **DNA** from **Appearance → Themes**.
3. Assign your primary navigation to the **Primary** menu location so the header and drawer render correctly.
4. In **WooCommerce → Settings → Products → Permalinks**, set the **Product category base** to `line` to match the built-in URLs and templates.

> The theme disables WooCommerce’s bundled styles and relies on its own CSS (`assets/css/*.css`) and JS (`assets/js/*.js`).

## Core behavior

- **Custom WooCommerce framing:** The theme removes WooCommerce’s default wrappers, breadcrumbs, and styles, replacing them with DNA-branded markup and layouts. It also adds live cart counts to menus and custom account endpoints for “Billing & Shipping” plus “Return & Exchange.”
- **Line-first taxonomy routing:** Product categories are treated as “Lines.” The theme expects `/line/{slug}` URLs and will route requests to the matching `product_cat` taxonomy even when a WordPress page exists for that slug.
- **Global contact drawer:** Every page includes a slide-in contact form (`template-parts/contact-popup.php`) that is toggled by the “CONTACT” button.

## Homepage setup

The bundled `front-page.php` template uses the Customizer to feature up to three products:

1. Go to **Appearance → Customize → Homepage**.
2. Select products for “Homepage product 1/2/3.”
3. If fewer than three are selected, the template falls back to the latest published products.

## Line landing pages

Use the **DNA Line Landing** page template (`page-line-landing.php`) to create landing pages for each product line:

1. Create a new page (slug should mirror the product category slug, e.g., `montessori`).
2. Choose **DNA Line Landing** under **Page Attributes → Template**.
3. Add body content—the first paragraph becomes the lead; subsequent paragraphs become the body copy.
4. Optionally set a custom field `dna_line_category` to point to a different product category slug if the page slug and category differ.
5. The template will render up to three products from the matched category, with placeholders if none exist.

## Template map

- `front-page.php` — Homepage hero and featured products grid.
- `page-line-landing.php` — Line landing pages that auto-pull products from matching categories.
- `page-line.php` — Simple “Line” index page for browsing collections.
- `page-montessori-line.php` and `page-montessori.php` — Pre-styled Montessori marketing pages.
- `page-philosophy.php` — Philosophy/mission page layout.
- `page-b2b.php` — Business inquiries CTA page.
- `page-privacy.php` and `page-refund-returns.php` — Legal/policy templates aligned to theme styles.
- `taxonomy-product_cat.php` and `taxonomy-product_cat-montessori.php` — Product category archive overrides.
- `woocommerce.php` — Wrapper for WooCommerce content areas.

## Assets

CSS and JS are already compiled in `assets/`. There is no build pipeline in this repository; adjust styles/scripts directly or replace the compiled assets. Cache-busting is handled via `filemtime`-based versioning in `functions.php` whenever files change.

