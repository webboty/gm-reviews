=== GM Reviews ===
Contributors: tonyhartmann
Tags: google, reviews, google merchant reviews, seller ratings, woocommerce, opt-in survey, funnelkit, breakdance
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrates the Google Customer Reviews (seller ratings) program into WooCommerce. Auto-injects the opt-in survey on the WC order-received page and on any custom URL (e.g. a FunnelKit thank-you page), and renders the Google customer reviews badge site-wide. Per-site merchant ID, badge position and region, opt-in delivery days, GTIN pass-through, dev/preview mode for staging, and shortcodes for inline placement.

== Description ==

**GM Reviews** is a drop-in integration for the official Google Customer Reviews (seller ratings) program. It is built for multi-site operators who run the same stack on many stores and need a single, predictable place to configure everything.

**What it does**

* **Opt-in survey** — fires the official Google opt-in popup so customers get an email from Google asking them to rate the order. Auto-injected on the WooCommerce `order-received` (thank-you) page, and optionally on any custom URL (e.g. a FunnelKit thank-you template) via a per-site URL pattern list.
* **Seller-rating badge** — renders the Google customer reviews badge site-wide (any of the four corners) so the public 4.7★ rating shows on every page.
* **Per-site merchant ID** — each site gets its own merchant ID, configured in Settings. No code edits, no env vars, no child-theme hacks.
* **Shortcodes** for inline placement anywhere a shortcode is supported (Breakdance elements, Gutenberg, classic editor, Elementor text widgets, etc.).

**Settings (Settings > Google Reviews)**

* **Merchant configuration**
  * Google Merchant ID (numeric)
  * Enable rating badge (with position: bottom-left, bottom-right, top-left, top-right, or inline-only)
  * Badge region (ISO-2 country code)
  * Opt-in min/max delivery days (used to compute the estimated delivery date sent to Google)
  * Include product GTINs in the opt-in payload (read from product meta `_gmr_gtin` or `_gtin`)
* **Opt-in page targeting**
  * One URL path per line for any custom thank-you pages (FunnelKit, custom templates, etc.). Wildcards (`*`) supported.
* **Development & preview**
  * Preview mode renders a fully-styled mock badge with a configurable rating/count so the placement can be validated on local/staging without Google's eligibility gating.

**Shortcodes**

* `[gm_reviews_badge]` — outputs the rating badge in its current page. Use anywhere you want the badge to appear (e.g. inside a Breakdance element).
* `[gm_reviews_badge dev="1" rating="4.7" count="1,264"]` — force the preview badge for a single shortcode, with custom rating/count.
* `[gm_reviews_optin]` — outputs the opt-in survey script. Use on a FunnelKit thank-you page or any custom thank-you template. The opt-in script is also auto-injected on the WooCommerce order-received page.
* `[gm_reviews_optin order_id="12345"]` — output the opt-in script for a specific order.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/gm-reviews/`, or install via the WordPress plugin uploader.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > Google Reviews** and enter your Google Merchant Center ID.
4. (Optional) Add custom URL patterns under **Opt-in page targeting** if you use a custom thank-you page.
5. Make sure the Google Customer Reviews program is enabled for your merchant in Google Merchant Center.

== Frequently Asked Questions ==

= Does this work without WooCommerce? =

The opt-in auto-injection on the order-received page requires WooCommerce. The shortcodes and the badge work without WooCommerce — you'll just need to place the opt-in shortcode manually on whatever page acts as your thank-you page.

= Why don't I see the badge on staging? =

The Google customer reviews badge widget only renders the rating for verified Google Merchant domains. On local/staging sites, enable **Preview mode** in Settings > Google Reviews to see a mock badge with your test rating.

= Where do I get the Merchant ID? =

It's in Google Merchant Center. Click the gear icon (top right) and the numeric ID is shown there.

== Changelog ==

= 1.2.0 =
* New "Opt-in page targeting" section with URL pattern list (with wildcards) so the opt-in survey can also fire on custom thank-you pages (e.g. FunnelKit).
* Fix: the Google merchant widget always anchored its iframe at `right: 0` of the wrapper, which made the badge invisible for `BOTTOM_LEFT`, `TOP_LEFT`, and `TOP_RIGHT`. The plugin now injects CSS to anchor the iframe to the correct edge.
* Fix: validate opt-in payload before sending to Google. Skip orders with missing email/country/etc. so the survey endpoint doesn't return 400.

= 1.1.0 =
* Dev/preview mode for local & staging sites.
* Position fix and JS retry for the merchant widget wrapper.

= 1.0.0 =
* Initial release.
