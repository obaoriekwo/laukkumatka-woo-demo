<?php
/**
 * Luggage-specific product data: volume, wheels, shell type, warranty.
 *
 * Adds a "Luggage" tab to the WooCommerce product editor, saves through the
 * WC_Product CRUD API (so it works with any data store) and shows the values
 * on the front end: a short spec line on product cards and extra rows in the
 * "Additional information" tab.
 *
 * Dimensions and weight deliberately use WooCommerce's built-in shipping
 * fields instead of duplicating them – one source of truth.
 *
 * @package LaukkuTravelTools
 */

namespace Laukku\TravelTools;

defined( 'ABSPATH' ) || exit;

final class Luggage_Fields {

	public const META_VOLUME   = '_laukku_volume_l';
	public const META_WHEELS   = '_laukku_wheels';
	public const META_SHELL    = '_laukku_shell';
	public const META_WARRANTY = '_laukku_warranty_years';

	public static function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );

		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'render_card_specs' ), 7 );
		add_filter( 'woocommerce_display_product_attributes', array( __CLASS__, 'additional_information_rows' ), 10, 2 );
	}

	/** Option lists are filterable so the shop can extend them without code changes here. */
	public static function shell_options(): array {
		return apply_filters(
			'laukku_tt_shell_options',
			array(
				''     => __( '— Not specified —', 'laukku-travel-tools' ),
				'hard' => __( 'Hard shell', 'laukku-travel-tools' ),
				'soft' => __( 'Soft shell (fabric)', 'laukku-travel-tools' ),
			)
		);
	}

	public static function wheel_options(): array {
		return array(
			''  => __( '— Not specified —', 'laukku-travel-tools' ),
			'0' => __( 'No wheels', 'laukku-travel-tools' ),
			'2' => __( '2 wheels', 'laukku-travel-tools' ),
			'4' => __( '4 spinner wheels', 'laukku-travel-tools' ),
		);
	}

	public static function add_tab( array $tabs ): array {
		$tabs['laukku_luggage'] = array(
			'label'    => __( 'Luggage', 'laukku-travel-tools' ),
			'target'   => 'laukku_luggage_data',
			'class'    => array(),
			'priority' => 65,
		);
		return $tabs;
	}

	public static function render_panel(): void {
		echo '<div id="laukku_luggage_data" class="panel woocommerce_options_panel hidden">';
		echo '<div class="options_group">';

		woocommerce_wp_text_input(
			array(
				'id'                => self::META_VOLUME,
				'label'             => __( 'Volume (litres)', 'laukku-travel-tools' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '0.5',
					'min'  => '0',
				),
				'desc_tip'          => true,
				'description'       => __( 'Shown on product cards and in structured data.', 'laukku-travel-tools' ),
			)
		);

		woocommerce_wp_select(
			array(
				'id'      => self::META_WHEELS,
				'label'   => __( 'Wheels', 'laukku-travel-tools' ),
				'options' => self::wheel_options(),
			)
		);

		woocommerce_wp_select(
			array(
				'id'      => self::META_SHELL,
				'label'   => __( 'Shell type', 'laukku-travel-tools' ),
				'options' => self::shell_options(),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => self::META_WARRANTY,
				'label'             => __( 'Warranty (years)', 'laukku-travel-tools' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
				),
			)
		);

		echo '</div>';
		echo '<p class="form-field"><em>' . esc_html__( 'Tip: fill in length, width and height on the Shipping tab – the cabin-size checker uses them.', 'laukku-travel-tools' ) . '</em></p>';
		echo '</div>';
	}

	/**
	 * Save via CRUD. Nonce + capability are already verified by WooCommerce
	 * before this hook fires.
	 */
	public static function save( \WC_Product $product ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$volume = isset( $_POST[ self::META_VOLUME ] ) ? wc_format_decimal( wp_unslash( $_POST[ self::META_VOLUME ] ) ) : '';
		$wheels = isset( $_POST[ self::META_WHEELS ] ) ? sanitize_key( wp_unslash( $_POST[ self::META_WHEELS ] ) ) : '';
		$shell  = isset( $_POST[ self::META_SHELL ] ) ? sanitize_key( wp_unslash( $_POST[ self::META_SHELL ] ) ) : '';
		$warr   = isset( $_POST[ self::META_WARRANTY ] ) ? absint( $_POST[ self::META_WARRANTY ] ) : '';
		// phpcs:enable

		$product->update_meta_data( self::META_VOLUME, $volume );
		$product->update_meta_data( self::META_WHEELS, array_key_exists( $wheels, self::wheel_options() ) ? $wheels : '' );
		$product->update_meta_data( self::META_SHELL, array_key_exists( $shell, self::shell_options() ) ? $shell : '' );
		$product->update_meta_data( self::META_WARRANTY, $warr ? (string) $warr : '' );
	}

	/**
	 * Dimensions in centimetres, regardless of the store's dimension unit.
	 *
	 * @return float[]|null [length, width, height] or null if incomplete.
	 */
	public static function dimensions_cm( \WC_Product $product ): ?array {
		$dims = array( $product->get_length(), $product->get_width(), $product->get_height() );
		foreach ( $dims as $d ) {
			if ( '' === $d || ! is_numeric( $d ) || (float) $d <= 0 ) {
				return null;
			}
		}
		return array_map( static fn( $d ) => (float) wc_get_dimension( (float) $d, 'cm' ), $dims );
	}

	/** Human readable "55 × 40 × 20 cm". */
	public static function format_dimensions( array $dims ): string {
		$parts = array_map( static fn( $d ) => wc_format_localized_decimal( (string) round( $d, 1 ) ), $dims );
		/* translators: %s: dimensions joined with a multiplication sign. */
		return sprintf( __( '%s cm', 'laukku-travel-tools' ), implode( ' × ', $parts ) );
	}

	/** Collect the spec facts that exist for a product. */
	public static function spec_items( \WC_Product $product ): array {
		$items = array();
		$dims  = self::dimensions_cm( $product );
		if ( $dims ) {
			$items['dimensions'] = self::format_dimensions( $dims );
		}
		$volume = $product->get_meta( self::META_VOLUME );
		if ( '' !== $volume && null !== $volume ) {
			/* translators: %s: volume in litres. */
			$items['volume'] = sprintf( __( '%s L', 'laukku-travel-tools' ), wc_format_localized_decimal( $volume ) );
		}
		$wheels = (string) $product->get_meta( self::META_WHEELS );
		if ( '' !== $wheels && '0' !== $wheels ) {
			/* translators: %d: number of wheels. */
			$items['wheels'] = sprintf( _n( '%d wheel', '%d wheels', (int) $wheels, 'laukku-travel-tools' ), (int) $wheels );
		}
		return $items;
	}

	/** Compact spec line under the product title on archive cards. */
	public static function render_card_specs(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$items = self::spec_items( $product );
		if ( ! $items ) {
			return;
		}
		echo '<p class="laukku-card-specs">' . esc_html( implode( ' · ', $items ) ) . '</p>';
	}

	/** Extra rows in the "Additional information" tab. */
	public static function additional_information_rows( array $attributes, \WC_Product $product ): array {
		$volume = $product->get_meta( self::META_VOLUME );
		if ( '' !== $volume && null !== $volume ) {
			$attributes['laukku_volume'] = array(
				'label' => __( 'Volume', 'laukku-travel-tools' ),
				/* translators: %s: volume in litres. */
				'value' => esc_html( sprintf( __( '%s L', 'laukku-travel-tools' ), wc_format_localized_decimal( $volume ) ) ),
			);
		}
		$wheels  = (string) $product->get_meta( self::META_WHEELS );
		$options = self::wheel_options();
		if ( '' !== $wheels && isset( $options[ $wheels ] ) ) {
			$attributes['laukku_wheels'] = array(
				'label' => __( 'Wheels', 'laukku-travel-tools' ),
				'value' => esc_html( $options[ $wheels ] ),
			);
		}
		$shell   = (string) $product->get_meta( self::META_SHELL );
		$options = self::shell_options();
		if ( '' !== $shell && isset( $options[ $shell ] ) ) {
			$attributes['laukku_shell'] = array(
				'label' => __( 'Shell type', 'laukku-travel-tools' ),
				'value' => esc_html( $options[ $shell ] ),
			);
		}
		$warranty = (int) $product->get_meta( self::META_WARRANTY );
		if ( $warranty > 0 ) {
			$attributes['laukku_warranty'] = array(
				'label' => __( 'Warranty', 'laukku-travel-tools' ),
				/* translators: %d: number of years. */
				'value' => esc_html( sprintf( _n( '%d year', '%d years', $warranty, 'laukku-travel-tools' ), $warranty ) ),
			);
		}
		return $attributes;
	}
}
