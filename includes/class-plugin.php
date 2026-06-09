<?php
namespace GMR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	private function includes() {
		require_once GMR_PLUGIN_DIR . 'includes/class-settings.php';
		require_once GMR_PLUGIN_DIR . 'includes/class-optin.php';
		require_once GMR_PLUGIN_DIR . 'includes/class-badge.php';
		require_once GMR_PLUGIN_DIR . 'includes/class-shortcodes.php';
		require_once GMR_PLUGIN_DIR . 'includes/class-admin-notices.php';
	}

	private function hooks() {
		load_plugin_textdomain( 'gm-reviews', false, dirname( GMR_PLUGIN_BASENAME ) . '/languages' );

		Settings::instance()->register();
		Optin::instance()->register();
		Badge::instance()->register();
		Shortcodes::register();
		Admin_Notices::register();
	}
}
