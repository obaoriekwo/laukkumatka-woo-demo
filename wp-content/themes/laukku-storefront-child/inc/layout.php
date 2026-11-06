<?php
/**
 * Site-wide layout: trust bar, footer store block, credits, fonts.
 *
 * @package LaukkuChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trust bar above the header – the three things that make a Finnish shopper
 * choose a small store over a marketplace: fast domestic delivery, free
 * shipping threshold, and a real shop they can visit.
 */
add_action(
	'storefront_before_header',
	static function () {
		if ( ! function_exists( 'wc_price' ) ) {
			return;
		}
		$threshold = class_exists( '\Laukku\TravelTools\Free_Shipping_Bar' )
			? \Laukku\TravelTools\Free_Shipping_Bar::threshold()
			: 140;
		$items     = apply_filters(
			'laukku_child_trust_items',
			array(
				/* translators: %s: free shipping threshold, e.g. 140 €. */
				sprintf( __( 'Free delivery over %s', 'laukku-child' ), wp_strip_all_tags( wc_price( $threshold, array( 'decimals' => 0 ) ) ) ),
				__( 'Ships in 1–3 working days from Jyväskylä', 'laukku-child' ),
				__( '14-day return right', 'laukku-child' ),
				__( 'Visit our shop in Seppälä, Jyväskylä', 'laukku-child' ),
			)
		);
		?>
		<div class="laukku-trustbar">
			<ul class="laukku-trustbar__list col-full">
				<?php foreach ( $items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	},
	5
);

/** Storefront loads Google Fonts – we use a system font stack instead. */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_dequeue_style( 'storefront-fonts' );
	},
	30
);

/** Footer: store details between the widget area and the copyright line. */
add_action(
	'storefront_footer',
	static function () {
		$info = laukku_child_store_info();
		?>
		<section class="laukku-footer-store" aria-labelledby="laukku-footer-store-title">
			<div>
				<h2 id="laukku-footer-store-title" class="laukku-footer-store__title"><?php esc_html_e( 'Visit the shop', 'laukku-child' ); ?></h2>
				<address>
					<?php echo esc_html( $info['street'] ); ?>, <?php echo esc_html( $info['locality'] ); ?><br>
					<?php echo esc_html( $info['location'] ); ?>
				</address>
			</div>
			<div>
				<h2 class="laukku-footer-store__title"><?php esc_html_e( 'Opening hours', 'laukku-child' ); ?></h2>
				<dl class="laukku-hours">
					<?php foreach ( $info['hours'] as $row ) : ?>
						<div><dt><?php echo esc_html( $row['label'] ); ?></dt><dd><?php echo esc_html( $row['open'] . '–' . $row['close'] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			</div>
			<div>
				<h2 class="laukku-footer-store__title"><?php esc_html_e( 'Online shop', 'laukku-child' ); ?></h2>
				<p><?php esc_html_e( 'Open 24/7. Orders ship from our Jyväskylä warehouse.', 'laukku-child' ); ?></p>
			</div>
		</section>
		<?php
	},
	15
);

/** Remove "Built with WooCommerce", keep the privacy-policy link. */
add_filter( 'storefront_credit_link', '__return_false' );

/** Copyright with the company details – year is always current. */
add_filter(
	'storefront_copyright_text',
	static function () {
		$info = laukku_child_store_info();
		return sprintf( '© %1$s %2$s · %3$s · Y-tunnus %4$s', gmdate( 'Y' ), $info['name'], $info['legal_name'], $info['business_id'] );
	}
);
