<?php
/**
 * Plugin Name:       Laukku Travel Tools
 * Description:       Luggage-specific features for a WooCommerce bag & luggage store: cabin-size checker per airline, "will it fit?" product finder, free-shipping progress bar, richer product structured data and a catalogue quality audit (admin page + WP-CLI).
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * Author:            Oba Oriekwo
 * License:           GPL-2.0-or-later
 * Text Domain:       laukku-travel-tools
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   10.3
 *
 * @package LaukkuTravelTools
 */

defined( 'ABSPATH' ) || exit;

define( 'LAUKKU_TT_VERSION', '1.0.0' );
define( 'LAUKKU_TT_FILE', __FILE__ );
define( 'LAUKKU_TT_DIR', plugin_dir_path( __FILE__ ) );
define( 'LAUKKU_TT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage
 * and the block-based Cart & Checkout. The plugin never touches order
 * tables directly, so HPOS is safe.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

/**
 * Boot after all plugins are loaded so we can safely check for WooCommerce.
 */
add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'laukku-travel-tools', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Laukku Travel Tools requires WooCommerce to be installed and active.', 'laukku-travel-tools' ) . '</p></div>';
				}
			);
			return;
		}

		require_once LAUKKU_TT_DIR . 'includes/class-luggage-fields.php';
		require_once LAUKKU_TT_DIR . 'includes/class-cabin-checker.php';
		require_once LAUKKU_TT_DIR . 'includes/class-free-shipping-bar.php';
		require_once LAUKKU_TT_DIR . 'includes/class-structured-data.php';
		require_once LAUKKU_TT_DIR . 'includes/class-store-audit.php';
		require_once LAUKKU_TT_DIR . 'includes/class-settings.php';

		\Laukku\TravelTools\Luggage_Fields::init();
		\Laukku\TravelTools\Cabin_Checker::init();
		\Laukku\TravelTools\Free_Shipping_Bar::init();
		\Laukku\TravelTools\Structured_Data::init();
		\Laukku\TravelTools\Store_Audit::init();
		\Laukku\TravelTools\Settings::init();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once LAUKKU_TT_DIR . 'includes/class-cli.php';
			\WP_CLI::add_command( 'laukku', \Laukku\TravelTools\CLI::class );
		}
	}
);

/**
 * Front-end styles. Loaded site-wide on purpose: it is ~3 KB, and product
 * cards (with badges and spec lines) can appear anywhere – widgets, blocks,
 * shortcodes, the 404 page, the mini-cart.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'laukku-tt', LAUKKU_TT_URL . 'assets/css/frontend.css', array(), LAUKKU_TT_VERSION );
	}
);

/**
 * Settings link on the Plugins screen.
 */
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( array $links ): array {
		$url = admin_url( 'admin.php?page=wc-settings&tab=products&section=laukku' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'laukku-travel-tools' ) . '</a>' );
		return $links;
	}
);
