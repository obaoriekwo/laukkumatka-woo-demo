<?php
/**
 * Free-shipping progress bar ("Add 23,10 € more for free delivery").
 *
 * The threshold is read from the store's own Free shipping method
 * (WooCommerce → Settings → Shipping), so marketing text and checkout can
 * never disagree. A fallback value in the plugin settings is used only if no
 * free-shipping method with a minimum amount exists.
 *
 * Shown in: classic cart, block cart (server-rendered), mini-cart (updated
 * automatically through WooCommerce cart fragments) and checkout.
 *
 * @package LaukkuTravelTools
 */

namespace Laukku\TravelTools;

defined( 'ABSPATH' ) || exit;

final class Free_Shipping_Bar {

	public static function init(): void {
		add_action( 'woocommerce_before_cart', array( __CLASS__, 'output' ), 5 );
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'output' ), 5 );
		add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( __CLASS__, 'output' ) );
		add_filter( 'render_block_woocommerce/cart', array( __CLASS__, 'prepend_to_block' ) );
		add_filter( 'render_block_woocommerce/checkout', array( __CLASS__, 'prepend_to_block' ) );
	}

	/**
	 * Lowest "free shipping" minimum amount across the shipping zones,
	 * cached until shipping settings change.
	 */
	public static function threshold(): float {
		$cached = get_transient( 'laukku_tt_free_threshold' );
		if ( false !== $cached ) {
			return (float) $cached;
		}

		$threshold = 0.0;
		$zones     = \WC_Shipping_Zones::get_zones();
		$zones[]   = array( 'zone_id' => 0 ); // "Rest of the world".

		foreach ( $zones as $zone_data ) {
			$zone = new \WC_Shipping_Zone( (int) $zone_data['zone_id'] );
			foreach ( $zone->get_shipping_methods( true ) as $method ) {
				if ( 'free_shipping' !== $method->id ) {
					continue;
				}
				$requires = $method->get_option( 'requires' );
				$min      = (float) $method->get_option( 'min_amount', 0 );
				if ( $min > 0 && in_array( $requires, array( 'min_amount', 'either', 'both' ), true ) ) {
					$threshold = $threshold > 0 ? min( $threshold, $min ) : $min;
				}
			}
		}

		if ( $threshold <= 0 ) {
			$threshold = (float) get_option( 'laukku_tt_free_shipping_fallback', 140 );
		}

		$threshold = (float) apply_filters( 'laukku_tt_free_shipping_threshold', $threshold );
		set_transient( 'laukku_tt_free_threshold', $threshold, DAY_IN_SECONDS );
		return $threshold;
	}

	/** Cart value that counts toward the threshold (same rule as WC: after discounts, incl. tax if prices are shown incl. tax). */
	private static function cart_total(): float {
		$cart = WC()->cart;
		if ( ! $cart ) {
			return 0.0;
		}
		$total = (float) $cart->get_displayed_subtotal();
		if ( $cart->display_prices_including_tax() ) {
			$total -= (float) $cart->get_discount_total() + (float) $cart->get_discount_tax();
		} else {
			$total -= (float) $cart->get_discount_total();
		}
		return max( 0.0, $total );
	}

	public static function html(): string {
		if ( 'yes' !== get_option( 'laukku_tt_show_shipping_bar', 'yes' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return '';
		}
		$threshold = self::threshold();
		if ( $threshold <= 0 ) {
			return '';
		}
		$total     = self::cart_total();
		$remaining = max( 0.0, $threshold - $total );
		$percent   = (int) min( 100, round( ( $total / $threshold ) * 100 ) );

		if ( $remaining > 0 ) {
			$message = sprintf(
				/* translators: %s: amount of money still needed. */
				esc_html__( 'Add %s more to get free delivery!', 'laukku-travel-tools' ),
				'<strong>' . wp_strip_all_tags( wc_price( $remaining ) ) . '</strong>'
			);
		} else {
			$message = '<strong>' . esc_html__( 'Your order ships for free!', 'laukku-travel-tools' ) . '</strong>';
		}

		return sprintf(
			'<div class="laukku-ship-bar%1$s" role="status">
				<p class="laukku-ship-bar__msg">%2$s</p>
				<div class="laukku-ship-bar__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%3$d" aria-label="%4$s">
					<span class="laukku-ship-bar__fill" style="width:%3$d%%"></span>
				</div>
			</div>',
			$remaining > 0 ? '' : ' is-complete',
			$message, // Built from escaped parts above.
			$percent,
			esc_attr__( 'Progress towards free delivery', 'laukku-travel-tools' )
		);
	}

	public static function output(): void {
		echo self::html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in html().
	}

	public static function prepend_to_block( string $content ): string {
		return self::html() . $content;
	}
}

/** Clear cached threshold whenever shipping settings change. */
$laukku_tt_flush_threshold = static function () {
	delete_transient( 'laukku_tt_free_threshold' );
};
add_action( 'woocommerce_shipping_zone_method_status_toggled', $laukku_tt_flush_threshold );
add_action( 'woocommerce_after_shipping_zone_object_save', $laukku_tt_flush_threshold );
add_action( 'woocommerce_shipping_zone_method_added', $laukku_tt_flush_threshold );
add_action( 'woocommerce_shipping_zone_method_deleted', $laukku_tt_flush_threshold );
// Instance settings live in options named woocommerce_free_shipping_{id}_settings.
add_action(
	'updated_option',
	static function ( $option ) use ( $laukku_tt_flush_threshold ) {
		if ( str_starts_with( (string) $option, 'woocommerce_free_shipping_' ) || 'laukku_tt_free_shipping_fallback' === $option ) {
			$laukku_tt_flush_threshold();
		}
	}
);
