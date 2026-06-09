<?php
namespace GMR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Shortcodes {

	public static function register() {
		add_shortcode( 'gm_reviews_badge',  array( __CLASS__, 'badge' ) );
		add_shortcode( 'gm_reviews_optin',  array( __CLASS__, 'optin' ) );
	}

	public static function badge( $atts = array() ) {
		$atts      = shortcode_atts( array(
			'position' => 'INLINE',
			'region'   => '',
			'dev'      => '',
			'rating'   => '',
			'count'    => '',
		), $atts, 'gm_reviews_badge' );

		$dev_forced = in_array( strtolower( (string) $atts['dev'] ), array( '1', 'true', 'yes' ), true );
		$is_dev     = $dev_forced || (bool) Settings::get( 'dev_mode' );

		if ( $is_dev ) {
			$position = '' !== $atts['position'] ? $atts['position'] : 'INLINE';
			// Ensure CSS is loaded for dev badge
			wp_register_style( 'gmr-dev-badge', false, array(), GMR_VERSION );
			wp_enqueue_style( 'gmr-dev-badge' );
			wp_add_inline_style( 'gmr-dev-badge', Badge::instance()->dev_css_public() );
			return Badge::instance()->print_dev_badge( $position, array(
				'rating' => (string) $atts['rating'],
				'count'  => (string) $atts['count'],
			) );
		}

		$merchant_id = (string) Settings::get( 'merchant_id' );
		if ( '' === $merchant_id ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<div class="gmr-shortcode-notice" style="padding:8px;border:1px solid #ccd0d4;background:#fff8e1;">' . esc_html__( 'GM Reviews: merchant ID is not configured. Set it under Settings > Google Reviews.', 'gm-reviews' ) . '</div>';
			}
			return '';
		}

		$position = '' !== $atts['position'] ? $atts['position'] : (string) Settings::get( 'badge_position' );
		$region   = '' !== $atts['region']   ? $atts['region']   : (string) Settings::get( 'badge_region' );

		$config = array(
			'merchant_id' => (int) $merchant_id,
		);
		if ( 'INLINE' !== $position && '' !== $position ) {
			$config['position'] = $position;
		}
		if ( '' !== $region ) {
			$config['region'] = $region;
		}

		$id    = 'gmr-merchant-widget-' . wp_generate_uuid4();
		ob_start();
		?>
		<script id="<?php echo esc_attr( $id ); ?>" src="https://www.gstatic.com/shopping/merchant/merchantwidget.js" defer></script>
		<script>
		(function(){
			var s = document.getElementById(<?php echo wp_json_encode( $id ); ?>);
			if (!s) return;
			var run = function(){ merchantwidget.start(<?php echo wp_json_encode( $config ); ?>); };
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
		return (string) ob_get_clean();
	}

	public static function optin( $atts = array() ) {
		$atts = shortcode_atts( array(
			'order_id' => 0,
		), $atts, 'gm_reviews_optin' );

		$order_id = absint( $atts['order_id'] );
		if ( ! $order_id && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			$order_id = absint( get_query_var( 'order-received' ) );
		}
		if ( ! $order_id && function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			global $wp;
			if ( ! empty( $wp->query_vars['order-received'] ) ) {
				$order_id = absint( $wp->query_vars['order-received'] );
			}
		}
		return Optin::instance()->render( $order_id ?: null );
	}
}
