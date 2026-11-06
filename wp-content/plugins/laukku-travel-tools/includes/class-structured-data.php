<?php
/**
 * Richer Product structured data (JSON-LD) for Google rich results.
 *
 * WooCommerce already outputs Product + Offer markup. This extends it with
 * what luggage shoppers search for and what Google Merchant listings use:
 * brand, physical dimensions and weight, volume as additionalProperty, and
 * a MerchantReturnPolicy (Finnish 14-day return right) on every offer.
 *
 * @package LaukkuTravelTools
 */

namespace Laukku\TravelTools;

defined( 'ABSPATH' ) || exit;

final class Structured_Data {

	public static function init(): void {
		add_filter( 'woocommerce_structured_data_product', array( __CLASS__, 'extend' ), 10, 2 );
	}

	public static function extend( array $markup, \WC_Product $product ): array {
		$brand = self::brand_name( $product );
		if ( $brand ) {
			$markup['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand,
			);
		}

		$dims = Luggage_Fields::dimensions_cm( $product );
		if ( $dims ) {
			// schema.org: depth/width/height. UN/CEFACT unit code CMT = centimetre.
			$markup['depth']  = self::qv( $dims[0], 'CMT' );
			$markup['width']  = self::qv( $dims[1], 'CMT' );
			$markup['height'] = self::qv( $dims[2], 'CMT' );
		}

		if ( $product->get_weight() ) {
			$markup['weight'] = self::qv( (float) wc_get_weight( (float) $product->get_weight(), 'kg' ), 'KGM' );
		}

		$props  = array();
		$volume = $product->get_meta( Luggage_Fields::META_VOLUME );
		if ( '' !== $volume && null !== $volume ) {
			$props[] = array(
				'@type'    => 'PropertyValue',
				'name'     => 'Volume',
				'value'    => (float) $volume,
				'unitCode' => 'LTR',
			);
		}
		$wheels = (string) $product->get_meta( Luggage_Fields::META_WHEELS );
		if ( '' !== $wheels ) {
			$props[] = array(
				'@type' => 'PropertyValue',
				'name'  => 'Wheels',
				'value' => (int) $wheels,
			);
		}
		if ( $props ) {
			$markup['additionalProperty'] = $props;
		}

		$return_days = (int) get_option( 'laukku_tt_return_days', 14 );
		if ( $return_days > 0 && ! empty( $markup['offers'] ) && is_array( $markup['offers'] ) ) {
			$policy = array(
				'@type'                => 'MerchantReturnPolicy',
				'applicableCountry'    => WC()->countries->get_base_country(),
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
				'merchantReturnDays'   => $return_days,
				'returnMethod'         => 'https://schema.org/ReturnByMail',
			);
			foreach ( $markup['offers'] as $i => $offer ) {
				if ( is_array( $offer ) ) {
					$markup['offers'][ $i ]['hasMerchantReturnPolicy'] = $policy;
				}
			}
		}

		return $markup;
	}

	/**
	 * Brand from WooCommerce Brands (core since WC 9.6) or a "Brand"/"Merkki"
	 * product attribute as a fallback.
	 */
	public static function brand_name( \WC_Product $product ): string {
		if ( taxonomy_exists( 'product_brand' ) ) {
			$terms = get_the_terms( $product->get_id(), 'product_brand' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				return $terms[0]->name;
			}
		}
		foreach ( array( 'pa_merkki', 'pa_brand', 'merkki', 'brand' ) as $attr ) {
			$value = $product->get_attribute( $attr );
			if ( $value ) {
				return trim( explode( ',', $value )[0] );
			}
		}
		return '';
	}

	private static function qv( float $value, string $unit ): array {
		return array(
			'@type'    => 'QuantitativeValue',
			'value'    => round( $value, 2 ),
			'unitCode' => $unit,
		);
	}
}
