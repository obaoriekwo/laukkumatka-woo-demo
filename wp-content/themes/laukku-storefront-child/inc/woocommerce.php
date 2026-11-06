<?php
/**
 * WooCommerce presentation tweaks.
 *
 * @package LaukkuChild
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/* -------------------------------------------------------------------------
 * Catalogue
 * ---------------------------------------------------------------------- */

/** 4 columns on desktop (CSS makes it 2 on phones), 24 per page (divisible by 2, 3, 4). */
add_filter( 'loop_shop_columns', static fn() => 4, 20 );
add_filter( 'storefront_loop_columns', static fn() => 4 );
add_filter( 'loop_shop_per_page', static fn() => 24, 20 );

/** Sale badge shows the actual saving: "-21 %" instead of "Sale!". */
add_filter(
	'woocommerce_sale_flash',
	static function ( $html, $post, $product ) {
		$percent = laukku_child_discount_percent( $product );
		if ( ! $percent ) {
			return $html;
		}
		/* translators: %d: discount percentage. */
		$label = sprintf( __( 'Save %d %%', 'laukku-child' ), $percent );
		return '<span class="onsale" aria-label="' . esc_attr( $label ) . '">−' . (int) $percent . '&nbsp;%</span>';
	},
	10,
	3
);

function laukku_child_discount_percent( WC_Product $product ): int {
	if ( $product->is_type( 'variable' ) ) {
		$max = 0;
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && $child->is_on_sale() ) {
				$max = max( $max, laukku_child_discount_percent( $child ) );
			}
		}
		return $max;
	}
	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();
	return ( $regular > 0 && $sale > 0 && $sale < $regular ) ? (int) round( ( 1 - $sale / $regular ) * 100 ) : 0;
}

/* -------------------------------------------------------------------------
 * Single product
 * ---------------------------------------------------------------------- */

/** Full-width product, cart and checkout pages: no distractions where people decide and pay. */
add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_product() || is_cart() || is_checkout() ) {
			$classes   = array_diff( $classes, array( 'left-sidebar', 'right-sidebar' ) );
			$classes[] = 'storefront-full-width-content';
		}
		return $classes;
	}
);
add_filter(
	'is_active_sidebar',
	static function ( $active, $index ) {
		return ( 'sidebar-1' === $index && ( is_product() || is_cart() || is_checkout() ) ) ? false : $active;
	},
	10,
	2
);

/** Reassurance right under the buy button – where the purchase decision happens. */
add_action(
	'woocommerce_after_add_to_cart_form',
	static function () {
		global $product;
		$items = array(
			'truck'  => __( 'Delivered in 1–3 working days', 'laukku-child' ),
			'return' => __( '14-day return right', 'laukku-child' ),
			'store'  => __( 'Pick up and try at our Jyväskylä shop', 'laukku-child' ),
		);
		if ( $product instanceof WC_Product && ! $product->is_in_stock() ) {
			unset( $items['truck'] );
		}
		echo '<ul class="laukku-assurance">';
		foreach ( $items as $icon => $text ) {
			printf( '<li class="laukku-assurance__item laukku-assurance__item--%s">%s</li>', esc_attr( $icon ), esc_html( $text ) );
		}
		echo '</ul>';
	}
);

/** Clearer tab names; hide the reviews tab when there is nothing to show yet and reviews are off. */
add_filter(
	'woocommerce_product_tabs',
	static function ( array $tabs ): array {
		if ( isset( $tabs['additional_information'] ) ) {
			$tabs['additional_information']['title'] = __( 'Specifications', 'laukku-child' );
		}
		return $tabs;
	},
	98
);

/** Related products: one full row of 4. */
add_filter(
	'woocommerce_output_related_products_args',
	static function ( array $args ): array {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	},
	20
);
add_filter(
	'storefront_related_products_args',
	static function ( array $args ): array {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);
