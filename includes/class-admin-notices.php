<?php
namespace GMR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin_Notices {

	public static function register() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_render' ) );
	}

	public static function maybe_render() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'settings_page_gm-reviews' === $screen->id ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( '' !== (string) Settings::get( 'merchant_id' ) ) {
			return;
		}
		$url = admin_url( 'options-general.php?page=' . Settings::PAGE_SLUG );
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'GM Reviews:', 'gm-reviews' ); ?></strong>
				<?php esc_html_e( 'Your Google Merchant ID is not configured yet. The opt-in survey and rating badge will not be displayed until you set it.', 'gm-reviews' ); ?>
				<a href="<?php echo esc_url( $url ); ?>" class="button button-secondary" style="margin-left:8px;"><?php esc_html_e( 'Configure now', 'gm-reviews' ); ?></a>
			</p>
		</div>
		<?php
	}
}
