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
		add_action( 'woocommerce_thankyou', array( $this, 'auto_render' ), 20 );
		add_action( 'woocommerce_order_completed', array( $this, 'store_optin_payload' ) );

		// Also auto-fire on any URL matching the configured opt-in URL patterns
		// (e.g. a custom FunnelKit thank-you page that is not a WC endpoint).
		add_action( 'wp_footer', array( $this, 'maybe_auto_render_by_url' ), 5 );
	}

	public function maybe_auto_render_by_url() {
		if ( is_admin() ) {
			return;
		}
		if ( $this->auto_rendered ) {
			return;
		}
		if ( ! self::current_url_matches_patterns() ) {
			return;
		}
		$this->auto_rendered = true;
		echo $this->render( null );
	}

	public static function current_url_matches_patterns() {
		$raw = (string) Settings::get( 'optin_url_patterns' );
		if ( '' === trim( $raw ) ) {
			return false;
		}
		$path = self::current_request_path();
		if ( '' === $path ) {
			return false;
		}
		$path_lower = strtolower( $path );
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( self::path_matches_pattern( $path_lower, strtolower( $line ) ) ) {
				return true;
			}
		}
		return false;
	}

	private static function current_request_path() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}
		$uri = (string) $_SERVER['REQUEST_URI'];
		$qpos = strpos( $uri, '?' );
		if ( false !== $qpos ) {
			$uri = substr( $uri, 0, $qpos );
		}
		return '/' . ltrim( $uri, '/' );
	}

	private static function path_matches_pattern( $path, $pattern ) {
		// Quote regex special chars except * (any chars) and ? (single char).
		$regex = '#^' . str_replace( array( '\\*', '\\?' ), array( '.*', '.' ), preg_quote( $pattern, '#' ) ) . '#';
		return (bool) preg_match( $regex, $path );
	}

	public function auto_render( $order_id ) {
		if ( $this->auto_rendered ) {
			return;
		}
		$this->auto_rendered = true;
		echo $this->render( $order_id );
	}

	public function store_optin_payload( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$email   = trim( (string) $order->get_billing_email() );
		$country = strtoupper( (string) ( $order->get_shipping_country() ?: $order->get_billing_country() ) );

		// Skip if we don't have a valid email or 2-letter country code. Google's
		// survey trigger endpoint returns 400 Bad Request for any order missing
		// required fields, so we never even cache these.
		if ( '' === $email || ! is_email( $email ) ) {
			$this->log_skip( $order_id, 'store: invalid email: "' . $email . '"', array() );
			return;
		}
		if ( ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
			$this->log_skip( $order_id, 'store: invalid country: "' . $country . '"', array() );
			return;
		}

		$min  = (int) Settings::get( 'optin_min_delivery' );
		$max  = (int) Settings::get( 'optin_max_delivery' );
		$days = max( $min, $max );
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
			'email'       => $email,
			'country'     => $country,
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

			// Sanitize: drop the opt-in entirely if required fields are missing/invalid
			// to avoid Google's 400 Bad Request on the survey trigger endpoint.
			$required = array( 'order_id', 'email', 'delivery_country', 'estimated_delivery_date' );
			foreach ( $required as $field ) {
				if ( ! isset( $payload[ $field ] ) || '' === (string) $payload[ $field ] ) {
					$this->log_skip( $order_id, 'missing field: ' . $field, $payload );
					return null;
				}
			}
			if ( ! is_email( $payload['email'] ) ) {
				$this->log_skip( $order_id, 'invalid email', $payload );
				return null;
			}
			if ( ! preg_match( '/^[A-Z]{2}$/', strtoupper( (string) $payload['delivery_country'] ) ) ) {
				$this->log_skip( $order_id, 'invalid country code: ' . $payload['delivery_country'], $payload );
				return null;
			}
			$payload['delivery_country'] = strtoupper( $payload['delivery_country'] );
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $payload['estimated_delivery_date'] ) ) {
				$this->log_skip( $order_id, 'invalid date: ' . $payload['estimated_delivery_date'], $payload );
				return null;
			}
		} else {
			$payload['order_id']                = 'ORDER_ID';
			$payload['email']                   = 'CUSTOMER_EMAIL';
			$payload['delivery_country']        = 'COUNTRY_CODE';
			$payload['estimated_delivery_date'] = 'YYYY-MM-DD';
		}

		return $payload;
	}

	private function log_skip( $order_id, $reason, $payload ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( '[GM Reviews] Skipping opt-in for order %d: %s. Payload: %s', $order_id, $reason, wp_json_encode( $payload ) ) );
		}
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

	public function is_rendered() {
		return $this->rendered;
	}

	public function reset_render_flag() {
		$this->rendered      = false;
		$this->auto_rendered = false;
	}
}
