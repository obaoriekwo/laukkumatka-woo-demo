<?php
/**
 * Settings: WooCommerce → Settings → Products → Travel tools.
 *
 * Uses the native WooCommerce Settings API so the screen looks and behaves
 * like the rest of WooCommerce (and store staff don't need to learn a new UI).
 *
 * @package LaukkuTravelTools
 */

namespace Laukku\TravelTools;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public static function init(): void {
		add_filter( 'woocommerce_get_sections_products', array( __CLASS__, 'section' ) );
		add_filter( 'woocommerce_get_settings_products', array( __CLASS__, 'settings' ), 10, 2 );
		add_action( 'woocommerce_update_options_products_laukku', array( Cabin_Checker::class, 'sync_all' ), 20 );
	}

	public static function section( array $sections ): array {
		$sections['laukku'] = __( 'Travel tools', 'laukku-travel-tools' );
		return $sections;
	}

	public static function settings( array $settings, string $section ): array {
		if ( 'laukku' !== $section ) {
			return $settings;
		}

		$cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'id=>name',
			)
		);

		return array(
			array(
				'title' => __( 'Travel tools', 'laukku-travel-tools' ),
				'type'  => 'title',
				'desc'  => __( 'Luggage-store features from the Laukku Travel Tools plugin.', 'laukku-travel-tools' ),
				'id'    => 'laukku_tt_options',
			),
			array(
				'title'   => __( 'Cabin-size badge', 'laukku-travel-tools' ),
				'desc'    => __( 'Show a "Cabin size" badge on product cards', 'laukku-travel-tools' ),
				'id'      => 'laukku_tt_show_badge',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'    => __( 'Check cabin size for', 'laukku-travel-tools' ),
				'desc'     => __( 'Categories that contain bags. Leave empty to check every product that has dimensions.', 'laukku-travel-tools' ),
				'desc_tip' => true,
				'id'       => 'laukku_tt_cabin_categories',
				'type'     => 'multiselect',
				'class'    => 'wc-enhanced-select',
				'options'  => is_array( $cats ) ? $cats : array(),
				'default'  => array(),
			),
			array(
				'title'    => __( 'Finder page', 'laukku-travel-tools' ),
				'desc'     => __( 'Page that contains the [laukku_cabin_finder] shortcode. Linked from product pages.', 'laukku-travel-tools' ),
				'desc_tip' => true,
				'id'       => 'laukku_tt_finder_page_id',
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'args'     => array( 'show_option_none' => __( '— None —', 'laukku-travel-tools' ) ),
			),
			array(
				'title'   => __( 'Free-shipping progress bar', 'laukku-travel-tools' ),
				'desc'    => __( 'Show in cart, mini-cart and checkout', 'laukku-travel-tools' ),
				'id'      => 'laukku_tt_show_shipping_bar',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'             => __( 'Fallback threshold (€)', 'laukku-travel-tools' ),
				'desc'              => __( 'Used only if no Free shipping method with a minimum amount is configured.', 'laukku-travel-tools' ),
				'desc_tip'          => true,
				'id'                => 'laukku_tt_free_shipping_fallback',
				'type'              => 'number',
				'default'           => '140',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1',
				),
			),
			array(
				'title'             => __( 'Return window (days)', 'laukku-travel-tools' ),
				'desc'              => __( 'Added to product structured data as a MerchantReturnPolicy. 0 = disabled.', 'laukku-travel-tools' ),
				'desc_tip'          => true,
				'id'                => 'laukku_tt_return_days',
				'type'              => 'number',
				'default'           => '14',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'laukku_tt_options',
			),
		);
	}
}
