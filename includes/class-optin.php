<?php
namespace GMR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Optin {

	private static $instance = null;
	private $rendered        = false;
	private $auto_rendered   = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register() {
		add_action( 'woocommerce_thankyou', array( $this, 'maybe_auto_render' ), 20 );
		add_action( 'woocommerce_order_completed', array( $this, 'store_optin_payload' ) );
	}

	public function maybe_auto_render( $order_id ) {
		if ( ! Settings::get( 'optin_enabled' ) ) {
			return;
		}
		if ( $this->auto_rendered ) {
			return;
		}
		$this->auto_rendered = true;
		$this->render( $order_id );
	}

	public function store_optin_payload( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$email   = $order->get_billing_email();
		$country = $order->get_shipping_country() ?: $order->get_billing_country();
		$min     = (int) Settings::get( 'optin_min_delivery' );
		$max     = (int) Settings::get( 'optin_max_delivery' );
		$days    = max( $min, $max );
		if ( $days <= 0 ) {
			$days = 14;
		}
		$eta = gmdate( 'Y-m-d', strtotime( '+' . $days . ' days' ) );

		$gtins = array();
		if ( Settings::get( 'optin_include_gtins' ) ) {
			foreach ( $order->get_items() as $item ) {
				if ( ! method_exists( $item, 'get_product' ) ) {
					continue;
				}
				$product = $item->get_product();
				if ( ! $product ) {
					continue;
				}
				$gtin = $product->get_meta( '_gmr_gtin', true );
				if ( ! $gtin ) {
					$gtin = $product->get_meta( '_gtin', true );
				}
				if ( $gtin ) {
					$gtins[] = array( 'gtin' => (string) $gtin );
				}
			}
		}

		set_transient( 'gmr_optin_' . $order_id, array(
			'order_id'    => (string) $order_id,
			'email'       => (string) $email,
			'country'     => (string) $country,
			'eta'         => $eta,
			'gtins'       => $gtins,
		), DAY_IN_SECONDS );
	}

	public function get_payload( $order_id = null ) {
		$merchant_id = (string) Settings::get( 'merchant_id' );
		if ( '' === $merchant_id ) {
			return null;
		}

		$payload = array(
			'merchant_id' => (int) $merchant_id,
		);

		if ( $order_id ) {
			$cached = get_transient( 'gmr_optin_' . $order_id );
			if ( $cached ) {
				$payload['order_id']                = $cached['order_id'];
				$payload['email']                   = $cached['email'];
				$payload['delivery_country']        = $cached['country'];
				$payload['estimated_delivery_date'] = $cached['eta'];
				if ( ! empty( $cached['gtins'] ) ) {
					$payload['products'] = $cached['gtins'];
				}
			} else {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$payload['order_id']                = (string) $order_id;
					$payload['email']                   = (string) $order->get_billing_email();
					$payload['delivery_country']        = (string) ( $order->get_shipping_country() ?: $order->get_billing_country() );
					$min                                = (int) Settings::get( 'optin_min_delivery' );
					$max                                = (int) Settings::get( 'optin_max_delivery' );
					$days                               = max( $min, $max );
					if ( $days <= 0 ) {
						$days = 14;
					}
					$payload['estimated_delivery_date'] = gmdate( 'Y-m-d', strtotime( '+' . $days . ' days' ) );
				}
			}
		} else {
			$payload['order_id']                = 'ORDER_ID';
			$payload['email']                   = 'CUSTOMER_EMAIL';
			$payload['delivery_country']        = 'COUNTRY_CODE';
			$payload['estimated_delivery_date'] = 'YYYY-MM-DD';
		}

		return $payload;
	}

	public function render( $order_id = null ) {
		if ( $this->rendered ) {
			return '';
		}
		$payload = $this->get_payload( $order_id );
		if ( ! $payload ) {
			return '';
		}
		$this->rendered = true;

		ob_start();
		?>
		<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
		<script>
		window.renderOptIn = function() {
			window.gapi.load('surveyoptin', function() {
				window.gapi.surveyoptin.render(<?php echo wp_json_encode( $payload ); ?>);
			});
		};
		</script>
		<?php
		return (string) ob_get_clean();
	}
}
