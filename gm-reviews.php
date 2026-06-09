<?php
/**
 * Plugin Name:       GM Reviews
 * Plugin URI:        https://github.com/mpp-amz-reviews/gm-reviews
 * Description:       Collects Google Merchant Reviews (seller ratings) via the official opt-in API and displays the Google customer reviews badge on your store. Settings page, shortcodes, and auto-injection.
 * Version:           1.0.0
 * Author:            MPP
 * License:           GPL-2.0-or-later
 * Text Domain:       gm-reviews
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GMR_VERSION', '1.0.0' );
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
