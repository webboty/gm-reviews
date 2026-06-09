<?php
namespace GMR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	const OPTION_KEY = 'gmr_settings';
	const PAGE_SLUG  = 'gm-reviews';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function defaults() {
		return array(
			'merchant_id'           => '',
			'badge_enabled'         => 1,
			'badge_position'        => 'BOTTOM_RIGHT',
			'badge_region'          => 'US',
			'optin_min_delivery'    => 7,
			'optin_max_delivery'    => 60,
			'optin_include_gtins'   => 0,
			'dev_mode'              => 0,
			'dev_rating'            => '4.7',
			'dev_review_count'      => '1,264',
		);
	}

	public static function get( $key = null ) {
		$opts = wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
		if ( null === $key ) {
			return $opts;
		}
		return isset( $opts[ $key ] ) ? $opts[ $key ] : null;
	}

	public function register() {
		add_action( 'admin_menu',            array( $this, 'add_menu' ) );
		add_action( 'admin_init',            array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . GMR_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	public function add_menu() {
		add_options_page(
			__( 'Google Reviews', 'gm-reviews' ),
			__( 'Google Reviews', 'gm-reviews' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'gmr_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'gmr_main',
			__( 'Merchant configuration', 'gm-reviews' ),
			function () {
				echo '<p>' . esc_html__( 'Configure your Google Merchant Reviews integration. The merchant ID is required for both the opt-in survey and the rating badge. The opt-in script is auto-injected on the WooCommerce order-received (thank-you) page automatically.', 'gm-reviews' ) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field( 'merchant_id', __( 'Google Merchant ID', 'gm-reviews' ), array( $this, 'field_merchant_id' ), self::PAGE_SLUG, 'gmr_main' );
		add_settings_field( 'badge_enabled', __( 'Enable rating badge', 'gm-reviews' ), array( $this, 'field_badge_enabled' ), self::PAGE_SLUG, 'gmr_main' );
		add_settings_field( 'badge_position', __( 'Badge position', 'gm-reviews' ), array( $this, 'field_badge_position' ), self::PAGE_SLUG, 'gmr_main' );
		add_settings_field( 'badge_region', __( 'Badge region', 'gm-reviews' ), array( $this, 'field_badge_region' ), self::PAGE_SLUG, 'gmr_main' );
		add_settings_field( 'optin_min_delivery', __( 'Min delivery days', 'gm-reviews' ), array( $this, 'field_optin_min_delivery' ), self::PAGE_SLUG, 'gmr_main' );
		add_settings_field( 'optin_max_delivery', __( 'Max delivery days', 'gm-reviews' ), array( $this, 'field_optin_max_delivery' ), self::PAGE_SLUG, 'gmr_main' );
		add_settings_field( 'optin_include_gtins', __( 'Include product GTINs', 'gm-reviews' ), array( $this, 'field_optin_include_gtins' ), self::PAGE_SLUG, 'gmr_main' );

		add_settings_section(
			'gmr_dev',
			__( 'Development & preview', 'gm-reviews' ),
			function () {
				echo '<p>' . esc_html__( 'Use these options on local/staging sites where the real Google widget will not render (because the request origin is not a verified Google Merchant domain). The preview badge mimics the look of the real badge and shows your test rating.', 'gm-reviews' ) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field( 'dev_mode', __( 'Enable preview mode', 'gm-reviews' ), array( $this, 'field_dev_mode' ), self::PAGE_SLUG, 'gmr_dev' );
		add_settings_field( 'dev_rating', __( 'Preview rating value', 'gm-reviews' ), array( $this, 'field_dev_rating' ), self::PAGE_SLUG, 'gmr_dev' );
		add_settings_field( 'dev_review_count', __( 'Preview review count', 'gm-reviews' ), array( $this, 'field_dev_review_count' ), self::PAGE_SLUG, 'gmr_dev' );
	}

	public function sanitize( $input ) {
		$defaults = self::defaults();
		$out      = array();

		$out['merchant_id'] = isset( $input['merchant_id'] ) ? preg_replace( '/\D+/', '', (string) $input['merchant_id'] ) : '';

		$out['badge_enabled']  = ! empty( $input['badge_enabled'] ) ? 1 : 0;
		$out['optin_include_gtins'] = ! empty( $input['optin_include_gtins'] ) ? 1 : 0;
		$out['dev_mode']            = ! empty( $input['dev_mode'] ) ? 1 : 0;

		$rating = isset( $input['dev_rating'] ) ? trim( (string) $input['dev_rating'] ) : $defaults['dev_rating'];
		if ( ! preg_match( '/^[0-5](\.[0-9])?$/', $rating ) ) {
			$rating = $defaults['dev_rating'];
		}
		$out['dev_rating'] = $rating;

		$count = isset( $input['dev_review_count'] ) ? trim( (string) $input['dev_review_count'] ) : $defaults['dev_review_count'];
		$count = substr( preg_replace( '/[^0-9,]/', '', $count ), 0, 20 );
		$out['dev_review_count'] = '' !== $count ? $count : $defaults['dev_review_count'];

		$valid_positions = array( 'BOTTOM_LEFT', 'BOTTOM_RIGHT', 'TOP_LEFT', 'TOP_RIGHT', 'INLINE' );
		$pos = isset( $input['badge_position'] ) ? sanitize_text_field( $input['badge_position'] ) : $defaults['badge_position'];
		$out['badge_position'] = in_array( $pos, $valid_positions, true ) ? $pos : $defaults['badge_position'];

		$region = isset( $input['badge_region'] ) ? strtoupper( sanitize_text_field( $input['badge_region'] ) ) : $defaults['badge_region'];
		$out['badge_region'] = preg_match( '/^[A-Z]{2}$/', $region ) ? $region : $defaults['badge_region'];

		$out['optin_min_delivery'] = max( 0, (int) ( isset( $input['optin_min_delivery'] ) ? $input['optin_min_delivery'] : $defaults['optin_min_delivery'] ) );
		$out['optin_max_delivery'] = max( $out['optin_min_delivery'], (int) ( isset( $input['optin_max_delivery'] ) ? $input['optin_max_delivery'] : $defaults['optin_max_delivery'] ) );

		add_settings_error( 'gmr_settings', 'gmr_settings_saved', __( 'Google Reviews settings saved.', 'gm-reviews' ), 'updated' );

		return $out;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Google Reviews', 'gm-reviews' ); ?></h1>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'gmr_settings_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
			<hr/>
			<h2><?php esc_html_e( 'Shortcodes', 'gm-reviews' ); ?></h2>
			<p><code>[gm_reviews_badge]</code> &mdash; <?php esc_html_e( 'Outputs the rating badge in its current page (use the shortcode anywhere you want the badge to appear, e.g. inside Breakdance).', 'gm-reviews' ); ?></p>
			<p><code>[gm_reviews_optin]</code> &mdash; <?php esc_html_e( 'Outputs the opt-in survey script. Use on a FunnelKit thank-you page or any custom thank-you template. The opt-in script is also auto-injected on the WooCommerce order-received page.', 'gm-reviews' ); ?></p>
			<p><code>[gm_reviews_badge dev="1"]</code> &mdash; <?php esc_html_e( 'Force the preview badge for a single shortcode, even if dev mode is disabled globally.', 'gm-reviews' ); ?></p>
		</div>
		<?php
	}

	public function field_merchant_id() {
		$val = self::get( 'merchant_id' );
		printf(
			'<input type="text" id="gmr_merchant_id" name="%1$s[merchant_id]" value="%2$s" class="regular-text" inputmode="numeric" pattern="[0-9]*" placeholder="110551914" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val )
		);
		echo '<p class="description">' . esc_html__( 'Numeric Google Merchant Center ID. You can find it in your Google Merchant Center account.', 'gm-reviews' ) . '</p>';
	}

	public function field_badge_enabled() {
		$val = self::get( 'badge_enabled' );
		printf(
			'<label><input type="checkbox" name="%1$s[badge_enabled]" value="1" %2$s /> %3$s</label>',
			esc_attr( self::OPTION_KEY ),
			checked( 1, $val, false ),
			esc_html__( 'Inject the Google customer reviews badge on the frontend.', 'gm-reviews' )
		);
		echo '<p class="description">' . esc_html__( 'When enabled, the badge is shown on every frontend page. You can also use the [gm_reviews_badge] shortcode to position it manually.', 'gm-reviews' ) . '</p>';
	}

	public function field_badge_position() {
		$val   = self::get( 'badge_position' );
		$items = array(
			'BOTTOM_LEFT'  => __( 'Bottom left', 'gm-reviews' ),
			'BOTTOM_RIGHT' => __( 'Bottom right', 'gm-reviews' ),
			'TOP_LEFT'     => __( 'Top left', 'gm-reviews' ),
			'TOP_RIGHT'    => __( 'Top right', 'gm-reviews' ),
			'INLINE'       => __( 'Inline (shortcode only)', 'gm-reviews' ),
		);
		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[badge_position]">';
		foreach ( $items as $k => $label ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $k ), selected( $val, $k, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	public function field_badge_region() {
		$val = self::get( 'badge_region' );
		printf(
			'<input type="text" id="gmr_badge_region" name="%1$s[badge_region]" value="%2$s" class="small-text" maxlength="2" placeholder="US" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val )
		);
		echo '<p class="description">' . esc_html__( 'Two-letter ISO country code (e.g. US, GB, DE). Leave blank to use the default region.', 'gm-reviews' ) . '</p>';
	}

	public function field_optin_min_delivery() {
		$val = self::get( 'optin_min_delivery' );
		printf(
			'<input type="number" min="0" step="1" name="%1$s[optin_min_delivery]" value="%2$d" class="small-text" />',
			esc_attr( self::OPTION_KEY ),
			(int) $val
		);
		echo '<p class="description">' . esc_html__( 'Minimum days from today to use as the estimated delivery date.', 'gm-reviews' ) . '</p>';
	}

	public function field_optin_max_delivery() {
		$val = self::get( 'optin_max_delivery' );
		printf(
			'<input type="number" min="0" step="1" name="%1$s[optin_max_delivery]" value="%2$d" class="small-text" />',
			esc_attr( self::OPTION_KEY ),
			(int) $val
		);
		echo '<p class="description">' . esc_html__( 'Maximum days from today to use as the estimated delivery date (capped by the min value).', 'gm-reviews' ) . '</p>';
	}

	public function field_optin_include_gtins() {
		$val = self::get( 'optin_include_gtins' );
		printf(
			'<label><input type="checkbox" name="%1$s[optin_include_gtins]" value="1" %2$s /> %3$s</label>',
			esc_attr( self::OPTION_KEY ),
			checked( 1, $val, false ),
			esc_html__( 'Send product GTINs to Google in the opt-in payload (recommended for product reviews).', 'gm-reviews' )
		);
	}

	public function field_dev_mode() {
		$val = self::get( 'dev_mode' );
		printf(
			'<label><input type="checkbox" name="%1$s[dev_mode]" value="1" %2$s /> %3$s</label>',
			esc_attr( self::OPTION_KEY ),
			checked( 1, $val, false ),
			esc_html__( 'Render a visible preview badge instead of (or in addition to) the real Google widget. Recommended for local/staging only.', 'gm-reviews' )
		);
		echo '<p class="description">' . esc_html__( 'When enabled, the plugin injects a static, fully-styled mock badge in your chosen position so you can confirm the placement, styling, and click target. Disable on production.', 'gm-reviews' ) . '</p>';
	}

	public function field_dev_rating() {
		$val = self::get( 'dev_rating' );
		printf(
			'<input type="text" id="gmr_dev_rating" name="%1$s[dev_rating]" value="%2$s" class="small-text" maxlength="3" placeholder="4.7" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val )
		);
		echo '<p class="description">' . esc_html__( 'Value between 0 and 5 (one decimal place).', 'gm-reviews' ) . '</p>';
	}

	public function field_dev_review_count() {
		$val = self::get( 'dev_review_count' );
		printf(
			'<input type="text" id="gmr_dev_review_count" name="%1$s[dev_review_count]" value="%2$s" class="regular-text" placeholder="1,264" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val )
		);
		echo '<p class="description">' . esc_html__( 'A display string, e.g. "1,264".', 'gm-reviews' ) . '</p>';
	}

	public function plugin_action_links( $links ) {
		$url  = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'gm-reviews' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}
}
