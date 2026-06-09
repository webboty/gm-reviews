<?php
/**
 * Uninstall handler for GM Reviews.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'gmr_settings' );
