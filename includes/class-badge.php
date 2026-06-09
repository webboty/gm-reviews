<?php
namespace GMR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Badge {

	private static $instance = null;
	private $printed         = false;
	private $dev_printed     = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register() {
		add_action( 'wp_footer', array( $this, 'maybe_print_footer' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'dev_mode_admin_notice' ) );
	}

	public function maybe_enqueue_assets() {
		if ( ! Settings::get( 'dev_mode' ) ) {
			return;
		}
		wp_register_style( 'gmr-dev-badge', false, array(), GMR_VERSION );
		wp_enqueue_style( 'gmr-dev-badge' );
		$css = $this->dev_css();
		wp_add_inline_style( 'gmr-dev-badge', $css );
	}

	public function dev_mode_admin_notice() {
		if ( ! Settings::get( 'dev_mode' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'settings_page_gm-reviews' === $screen->id ) {
			return;
		}
		$url = admin_url( 'options-general.php?page=gm-reviews' );
		echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'GM Reviews:', 'gm-reviews' ) . '</strong> ' . esc_html__( 'Preview mode is enabled. A mock badge is being shown instead of the real Google widget. Disable this on production.', 'gm-reviews' ) . ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'gm-reviews' ) . '</a></p></div>';
	}

	public function maybe_print_footer() {
		if ( ! Settings::get( 'badge_enabled' ) ) {
			return;
		}
		$pos = (string) Settings::get( 'badge_position' );
		if ( 'INLINE' === $pos ) {
			return;
		}
		if ( Settings::get( 'dev_mode' ) ) {
			echo $this->print_dev_badge( $pos );
			return;
		}
		$this->print_badge();
	}

	public function print_badge() {
		if ( $this->printed ) {
			return;
		}
		$merchant_id = (string) Settings::get( 'merchant_id' );
		if ( '' === $merchant_id ) {
			return;
		}
		$this->printed = true;

		$position = (string) Settings::get( 'badge_position' );
		$region   = (string) Settings::get( 'badge_region' );

		$config = array(
			'merchant_id' => (int) $merchant_id,
		);
		if ( 'INLINE' !== $position && '' !== $position ) {
			$config['position'] = $position;
		}
		if ( '' !== $region ) {
			$config['region'] = $region;
		}

		$css = $this->wrapper_position_css( $position );
		?>
		<style id="gmr-merchant-widget-position">
		#google-merchantwidget-iframe-wrapper{<?php echo $css; ?>}
		</style>
		<script id="gmr-merchant-widget" src="https://www.gstatic.com/shopping/merchant/merchantwidget.js" defer></script>
		<script>
		(function(){
			var s = document.getElementById('gmr-merchant-widget');
			if (!s) return;
			var run = function(){
				merchantwidget.start(<?php echo wp_json_encode( $config ); ?>);
				var applyPos = function(){
					var w = document.getElementById('google-merchantwidget-iframe-wrapper');
					if (w) {
						w.style.cssText += ';<?php echo esc_attr( $css ); ?>';
					}
				};
				applyPos();
				setTimeout(applyPos, 200);
				setTimeout(applyPos, 1000);
			};
			if (s.addEventListener) {
				s.addEventListener('load', run);
			} else if (s.attachEvent) {
				s.attachEvent('onload', run);
			} else {
				window.addEventListener('load', run);
			}
		})();
		</script>
		<?php
	}

	private function wrapper_position_css( $position ) {
		switch ( $position ) {
			case 'TOP_LEFT':
				return 'top:20px !important;left:20px !important;bottom:auto !important;right:auto !important;';
			case 'TOP_RIGHT':
				return 'top:20px !important;right:20px !important;bottom:auto !important;left:auto !important;';
			case 'BOTTOM_LEFT':
				return 'bottom:20px !important;left:20px !important;top:auto !important;right:auto !important;';
			case 'INLINE':
				return '';
			case 'BOTTOM_RIGHT':
			default:
				return 'bottom:20px !important;right:20px !important;top:auto !important;left:auto !important;';
		}
	}

	public function print_dev_badge( $position = 'BOTTOM_RIGHT', $args = array() ) {
		if ( $this->dev_printed ) {
			return '';
		}
		$this->dev_printed = true;

		$rating  = '' !== (string) ( $args['rating'] ?? '' ) ? (string) $args['rating']  : (string) Settings::get( 'dev_rating' );
		$count   = '' !== (string) ( $args['count']  ?? '' ) ? (string) $args['count']   : (string) Settings::get( 'dev_review_count' );
		$id      = 'gmr-dev-badge-' . wp_generate_uuid4();
		$style   = $this->dev_position_style( $position );
		$rating  = esc_html( $rating );
		$count   = esc_html( $count );

		ob_start();
		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="gmr-dev-badge" role="button" tabindex="0" aria-label="<?php echo esc_attr( sprintf( __( 'Google rating %1$s from %2$s reviews (preview)', 'gm-reviews' ), $rating, $count ) ); ?>" style="<?php echo esc_attr( $style ); ?>">
			<span class="gmr-dev-badge-g">G</span>
			<span class="gmr-dev-badge-text">
				<span class="gmr-dev-badge-rating"><?php echo $rating; ?></span>
				<span class="gmr-dev-badge-stars" aria-hidden="true">
					<svg viewBox="0 0 80 16" width="80" height="16" focusable="false"><path fill="#fbbc04" d="M8 0l2.5 6.2L17 7l-5 4.4 1.5 6.6L8 14.5 2.5 18l1.5-6.6L-1 7l6.5-.8L8 0zm16 0l2.5 6.2L33 7l-5 4.4 1.5 6.6L24 14.5 18.5 18l1.5-6.6L15 7l6.5-.8L24 0zm16 0l2.5 6.2L49 7l-5 4.4 1.5 6.6L40 14.5 34.5 18l1.5-6.6L31 7l6.5-.8L40 0zm16 0l2.5 6.2L65 7l-5 4.4 1.5 6.6L56 14.5 50.5 18l1.5-6.6L47 7l6.5-.8L56 0zm16 0l2.5 6.2L81 7l-5 4.4 1.5 6.6L72 14.5 66.5 18l1.5-6.6L63 7l6.5-.8L72 0z"/></svg>
				</span>
				<span class="gmr-dev-badge-count"><?php echo $count; ?></span>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function dev_position_style( $position ) {
		$base = 'position:fixed;z-index:2147483000;background:#fff;color:#202124;font:500 14px/1.2 -apple-system,BlinkMacSystemFont,Roboto,Helvetica,Arial,sans-serif;border-radius:24px;box-shadow:0 2px 8px rgba(0,0,0,.18);padding:8px 14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;user-select:none;';
		switch ( $position ) {
			case 'TOP_LEFT':
				return $base . 'top:20px;left:20px;';
			case 'TOP_RIGHT':
				return $base . 'top:20px;right:20px;';
			case 'BOTTOM_LEFT':
				return $base . 'bottom:20px;left:20px;';
			case 'INLINE':
				return 'position:relative;z-index:1;background:#fff;color:#202124;font:500 14px/1.2 -apple-system,BlinkMacSystemFont,Roboto,Helvetica,Arial,sans-serif;border-radius:24px;box-shadow:0 2px 8px rgba(0,0,0,.12);padding:8px 14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;user-select:none;';
			case 'BOTTOM_RIGHT':
			default:
				return $base . 'bottom:20px;right:20px;';
		}
	}

	private function dev_css() {
		return self::dev_css_public();
	}

	public static function dev_css_public() {
		return '.gmr-dev-badge .gmr-dev-badge-g{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;background:linear-gradient(135deg,#4285f4,#34a853,#fbbc04,#ea4335);color:#fff;font-weight:700;border-radius:50%;font-size:14px;line-height:1;}'
			. '.gmr-dev-badge .gmr-dev-badge-text{display:inline-flex;align-items:center;gap:6px;}'
			. '.gmr-dev-badge .gmr-dev-badge-rating{font-weight:600;}'
			. '.gmr-dev-badge .gmr-dev-badge-stars{display:inline-flex;align-items:center;line-height:0;}'
			. '.gmr-dev-badge .gmr-dev-badge-stars svg{display:block;}'
			. '.gmr-dev-badge .gmr-dev-badge-count{color:#5f6368;}'
			. '.gmr-dev-badge:hover{box-shadow:0 4px 12px rgba(0,0,0,.22);}';
	}
}
