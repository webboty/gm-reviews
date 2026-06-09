<?php
/**
 * Plugin Name:       GM Reviews
 * Plugin URI:        https://github.com/webboty/gm-reviews
 * Description:       Integrates the Google Customer Reviews (seller ratings) program into WooCommerce. Auto-injects the opt-in survey on the WC order-received page and on any custom URL (e.g. a FunnelKit thank-you page), and renders the Google customer reviews badge site-wide. Per-site merchant ID, badge position and region, opt-in delivery days, GTIN pass-through, dev/preview mode for staging, and shortcodes for inline placement.
 * Version:           1.2.0
 * Author:            Tony Hartmann
 * Author URI:        https://github.com/webboty
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gm-reviews
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * Update URI:        https://github.com/webboty/gm-reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GMR_VERSION', '1.2.0' );
define( 'GMR_PLUGIN_FILE', __FILE__ );
define( 'GMR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GMR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GMR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once GMR_PLUGIN_DIR . 'includes/class-plugin.php';

if ( ! function_exists( 'GMR' ) ) {
	function GMR() {
		return \GMR\Plugin::instance();
	}
}

GMR();
