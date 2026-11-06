<?php
/**
 * Storefront homepage template (Template: Homepage) sections.
 *
 * Order: hero (page content) → categories → "will it fit?" teaser →
 * featured → on sale → newest. "Popular" (rating based) is removed because
 * it is empty until the store collects reviews.
 *
 * @package LaukkuChild
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		remove_action( 'homepage', 'storefront_popular_products', 50 );
		remove_action( 'homepage', 'storefront_best_selling_products', 70 );
		remove_action( 'homepage', 'storefront_on_sale_products', 60 );
		add_action( 'homepage', 'storefront_on_sale_products', 45 );
		remove_action( 'homepage', 'storefront_woocommerce_brands_homepage_section', 80 ); // Needs brand logos first.
		remove_action( 'homepage', 'storefront_recent_products', 30 );
		add_action( 'homepage', 'storefront_recent_products', 55 );
	}
);

add_filter(
	'storefront_product_categories_args',
	static fn( $args ) => array_merge(
		$args,
		array(
			'title'   => __( 'Shop by category', 'laukku-child' ),
			'limit'   => 4,
			'columns' => 4,
			'orderby' => 'menu_order',
		)
	)
);
add_filter(
	'storefront_featured_products_args',
	static fn( $args ) => array_merge(
		$args,
		array(
			'title'   => __( 'Our picks for your next trip', 'laukku-child' ),
			'limit'   => 4,
			'columns' => 4,
		)
	)
);
add_filter(
	'storefront_on_sale_products_args',
	static fn( $args ) => array_merge(
		$args,
		array(
			'title'   => __( 'On sale now', 'laukku-child' ),
			'limit'   => 4,
			'columns' => 4,
		)
	)
);
add_filter(
	'storefront_recent_products_args',
	static fn( $args ) => array_merge(
		$args,
		array(
			'title'   => __( 'New arrivals', 'laukku-child' ),
			'limit'   => 4,
			'columns' => 4,
		)
	)
);

/** "Will it fit?" teaser linking to the cabin-size finder page. */
add_action(
	'homepage',
	static function () {
		$page_id = (int) get_option( 'laukku_tt_finder_page_id' );
		if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
			return;
		}
		?>
		<section class="laukku-teaser" aria-labelledby="laukku-teaser-title">
			<div class="laukku-teaser__text">
				<h2 id="laukku-teaser-title"><?php esc_html_e( 'Flying soon? Avoid the gate fee.', 'laukku-child' ); ?></h2>
				<p><?php esc_html_e( 'Pick your airline and ticket type – we show every bag that fits the cabin limits.', 'laukku-child' ); ?></p>
			</div>
			<a class="button alt" href="<?php echo esc_url( get_permalink( $page_id ) ); ?>"><?php esc_html_e( 'Find a bag that fits', 'laukku-child' ); ?></a>
		</section>
		<?php
	},
	25
);
