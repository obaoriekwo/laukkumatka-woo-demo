<?php
/**
 * Configure a fresh WooCommerce install as a Finnish luggage store demo.
 *
 * Run with WP-CLI:  wp eval-file bin/setup-store.php <path-to-sample-data>
 * Idempotent: products are matched by SKU, pages by slug, so it can be re-run.
 *
 * @package LaukkuDemo
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride

if ( ! defined( 'WP_CLI' ) || ! class_exists( 'WooCommerce' ) ) {
	echo "Run via WP-CLI with WooCommerce active.\n";
	return;
}

$data_dir = rtrim( $args[0] ?? '', '/' );
if ( ! $data_dir || ! is_readable( $data_dir . '/products.json' ) ) {
	WP_CLI::error( 'Usage: wp eval-file bin/setup-store.php <path-to-sample-data>' );
}
$catalogue = json_decode( file_get_contents( $data_dir . '/products.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* -------------------------------------------------------------------------
 * 1. Store settings – Finland
 * ---------------------------------------------------------------------- */
WP_CLI::log( '→ Store settings (Finland, EUR, VAT 25.5 %)' );
$options = array(
	'blogname'                                 => 'Laukkumatka DEMO',
	'blogdescription'                          => 'Laukut ja matkatarvikkeet edullisesti – myymälä Jyväskylässä',
	'woocommerce_default_country'              => 'FI',
	'woocommerce_store_address'                => 'Vasarakatu 29',
	'woocommerce_store_city'                   => 'Jyväskylä',
	'woocommerce_currency'                     => 'EUR',
	'woocommerce_currency_pos'                 => 'right_space',
	'woocommerce_price_thousand_sep'           => ' ',
	'woocommerce_price_decimal_sep'            => ',',
	'woocommerce_price_num_decimals'           => '2',
	'woocommerce_weight_unit'                  => 'kg',
	'woocommerce_dimension_unit'               => 'cm',
	'woocommerce_calc_taxes'                   => 'yes',
	'woocommerce_prices_include_tax'           => 'yes',
	'woocommerce_tax_display_shop'             => 'incl',
	'woocommerce_tax_display_cart'             => 'incl',
	'woocommerce_tax_based_on'                 => 'base',
	'woocommerce_allowed_countries'            => 'specific',
	'woocommerce_specific_allowed_countries'   => array( 'FI' ),
	'woocommerce_ship_to_countries'            => '',
	'woocommerce_coming_soon'                  => 'no',   // WC 9.1+ hides new stores by default.
	'woocommerce_enable_reviews'               => 'yes',
	'woocommerce_manage_stock'                 => 'yes',
	'woocommerce_onboarding_profile'           => array( 'skipped' => true ),
	'woocommerce_task_list_hidden'             => 'yes',
	'woocommerce_show_marketplace_suggestions' => 'no',
	'permalink_structure'                      => '/%postname%/',
	'blog_public'                              => '0',   // Demo: ask search engines not to index it.
	'laukku_demo_notice'                       => 'yes', // Show the "unofficial demo" banner.
	'woocommerce_permalinks'                   => array(
		'product_base'  => '/tuote',
		'category_base' => 'tuoteosasto',
		'tag_base'      => 'avainsana',
	),
);
foreach ( $options as $key => $value ) {
	update_option( $key, $value );
}

// VAT 25.5 % (Finland's general rate since 1.9.2024).
global $wpdb;
$has_rate = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate_class = ''", 'FI' ) );
if ( ! $has_rate ) {
	WC_Tax::_insert_tax_rate(
		array(
			'tax_rate_country'  => 'FI',
			'tax_rate'          => '25.5000',
			'tax_rate_name'     => 'ALV',
			'tax_rate_priority' => 1,
			'tax_rate_shipping' => 1,
			'tax_rate_order'    => 0,
			'tax_rate_class'    => '',
		)
	);
}

/* -------------------------------------------------------------------------
 * 2. Shipping: Finland zone, flat rate, pick-up, free over 140 €
 * ---------------------------------------------------------------------- */
WP_CLI::log( '→ Shipping zone "Suomi"' );
$zone = null;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'Suomi' === $z['zone_name'] ) {
		$zone = new WC_Shipping_Zone( $z['id'] );
	}
}
if ( ! $zone ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'Suomi' );
	$zone->add_location( 'FI', 'country' );
	$zone->save();

	$flat = $zone->add_shipping_method( 'flat_rate' );
	update_option(
		"woocommerce_flat_rate_{$flat}_settings",
		array(
			'title'      => 'Pakettiautomaatti / noutopiste',
			'tax_status' => 'taxable',
			'cost'       => '6.90',
		)
	);
	$free = $zone->add_shipping_method( 'free_shipping' );
	update_option(
		"woocommerce_free_shipping_{$free}_settings",
		array(
			'title'            => 'Ilmainen toimitus',
			'requires'         => 'min_amount',
			'min_amount'       => '140',
			'ignore_discounts' => 'no',
		)
	);
	$pickup = $zone->add_shipping_method( 'local_pickup' );
	update_option(
		"woocommerce_local_pickup_{$pickup}_settings",
		array(
			'title'      => 'Nouto myymälästä (Jyväskylä, Seppälä)',
			'tax_status' => 'none',
			'cost'       => '0',
		)
	);
}
delete_transient( 'laukku_tt_free_threshold' );

// Demo payment method so the checkout can be tested end-to-end.
update_option(
	'woocommerce_cod_settings',
	array(
		'enabled'      => 'yes',
		'title'        => 'Testimaksu (demo)',
		'description'  => 'Demokauppa – tilauksia ei toimiteta.',
		'instructions' => '',
	)
);

/* -------------------------------------------------------------------------
 * 3. Categories (hierarchical, with SEO descriptions)
 * ---------------------------------------------------------------------- */
WP_CLI::log( '→ Product categories' );
$cat_ids = array();
foreach ( $catalogue['categories'] as $i => $cat ) {
	$existing = get_term_by( 'slug', $cat['slug'], 'product_cat' );
	$parent   = isset( $cat['parent'] ) ? ( $cat_ids[ $cat['parent'] ] ?? 0 ) : 0;
	if ( $existing ) {
		$term_id = $existing->term_id;
		wp_update_term(
			$term_id,
			'product_cat',
			array(
				'description' => $cat['description'],
				'parent'      => $parent,
			)
		);
	} else {
		$result  = wp_insert_term(
			$cat['name'],
			'product_cat',
			array(
				'slug'        => $cat['slug'],
				'description' => $cat['description'],
				'parent'      => $parent,
			)
		);
		$term_id = is_wp_error( $result ) ? 0 : $result['term_id'];
	}
	update_term_meta( $term_id, 'order', $i );
	$cat_ids[ $cat['slug'] ] = $term_id;
}

// Cabin checker only for bag categories (sub-categories are included automatically).
update_option( 'laukku_tt_cabin_categories', array( (string) $cat_ids['matkalaukut'], (string) $cat_ids['laukut-ja-reput'] ) );

/* -------------------------------------------------------------------------
 * 4. Products (WooCommerce CRUD API – no direct post/meta writes)
 * ---------------------------------------------------------------------- */
WP_CLI::log( '→ Products' );
$image_for = static function ( string $sku, string $name, bool $with_alt ) use ( $data_dir ): int {
	$file = $data_dir . '/images/' . $sku . '.png';
	if ( ! is_readable( $file ) ) {
		return 0;
	}
	$existing = get_posts(
		array(
			'post_type'   => 'attachment',
			'meta_key'    => '_laukku_demo_sku', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $sku,              // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	$tmp = wp_tempnam( $sku . '.png' );
	copy( $file, $tmp );
	$id = media_handle_sideload(
		array(
			'name'     => sanitize_title( $name ) . '.png',
			'tmp_name' => $tmp,
		),
		0,
		$name
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $sku . ': ' . $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_laukku_demo_sku', $sku );
	if ( $with_alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $name );
	}
	return (int) $id;
};

foreach ( $catalogue['products'] as $row ) {
	$id      = wc_get_product_id_by_sku( $row['sku'] );
	$product = $id ? wc_get_product( $id ) : new WC_Product_Simple();

	$product->set_name( $row['name'] );
	$product->set_sku( $row['sku'] );
	$product->set_status( 'publish' );
	$product->set_featured( ! empty( $row['featured'] ) );
	$product->set_short_description( $row['short'] );
	$product->set_description( wpautop( $row['description'] ) );
	$product->set_regular_price( $row['regular'] );
	$product->set_sale_price( $row['sale'] ?? '' );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( $row['stock'] );
	$product->set_category_ids( array( $cat_ids[ $row['category'] ] ) );
	$product->set_weight( $row['weight'] ?? '' );
	if ( ! empty( $row['dims'] ) ) {
		$product->set_length( (string) $row['dims'][0] );
		$product->set_width( (string) $row['dims'][1] );
		$product->set_height( (string) $row['dims'][2] );
	}
	if ( ! empty( $row['slug'] ) ) {
		$product->set_slug( $row['slug'] );
	}
	foreach ( array(
		'volume'   => '_laukku_volume_l',
		'wheels'   => '_laukku_wheels',
		'shell'    => '_laukku_shell',
		'warranty' => '_laukku_warranty_years',
	) as $field => $meta ) {
		if ( isset( $row[ $field ] ) ) {
			$product->update_meta_data( $meta, (string) $row[ $field ] );
		}
	}
	// Seeded issue: the strap image has no alt text.
	$image_id = $image_for( $row['sku'], $row['name'], 'ACC-STRAP' !== $row['sku'] );
	if ( $image_id ) {
		$product->set_image_id( $image_id );
	}
	$product_id = $product->save();

	if ( ! empty( $row['brand'] ) && taxonomy_exists( 'product_brand' ) ) {
		wp_set_object_terms( $product_id, $row['brand'], 'product_brand' );
	}
	WP_CLI::log( sprintf( '   %-16s %s', $row['sku'], $row['name'] ) );
}

// Category thumbnails: first product image in each category (placeholder art otherwise).
foreach ( $cat_ids as $slug => $term_id ) {
	$first = wc_get_products(
		array(
			'category' => array( $slug ),
			'limit'    => 1,
			'orderby'  => 'menu_order',
			'order'    => 'ASC',
		)
	);
	if ( ! $first ) {
		$child_slugs = wp_list_pluck(
			get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'parent'     => $term_id,
					'hide_empty' => false,
				)
			),
			'slug'
		);
		$first       = $child_slugs ? wc_get_products(
			array(
				'category' => $child_slugs,
				'limit'    => 1,
			)
		) : array();
	}
	if ( $first && $first[0]->get_image_id() ) {
		update_term_meta( $term_id, 'thumbnail_id', $first[0]->get_image_id() );
	}
}

/* -------------------------------------------------------------------------
 * 5. Pages: homepage, cabin finder, menu
 * ---------------------------------------------------------------------- */
WP_CLI::log( '→ Pages and menu' );

// Make sure the WooCommerce pages (shop, cart, checkout, my account) exist.
if ( wc_get_page_id( 'shop' ) <= 0 || wc_get_page_id( 'cart' ) <= 0 ) {
	WC_Install::create_pages();
}
$upsert_page = static function ( string $slug, string $title, string $content, string $template = '' ): int {
	$page = get_page_by_path( $slug );
	$args = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $content,
	);
	if ( $page ) {
		$args['ID'] = $page->ID;
	}
	$id = wp_insert_post( $args );
	if ( $template ) {
		update_post_meta( $id, '_wp_page_template', $template );
	}
	return (int) $id;
};

$finder_id = $upsert_page(
	'sopiiko-laukku-koneeseen',
	'Sopiiko laukku koneeseen?',
	"<!-- wp:paragraph -->\n<p>Valitse lentoyhtiö ja lipputyyppi tai syötä omat mitat – näytämme kaikki laukut, jotka mahtuvat käsimatkatavaroiden kokorajoihin. Mitat sisältävät pyörät ja kahvat.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[laukku_cabin_finder columns=\"4\" title=\"\"]\n<!-- /wp:shortcode -->",
	'template-fullwidth.php'
);

// Remove WordPress sample content ("Hello world!", "Sample Page").
foreach ( array( get_page_by_path( 'hello-world', OBJECT, 'post' ), get_page_by_path( 'sample-page' ) ) as $sample ) {
	if ( $sample ) {
		wp_delete_post( $sample->ID, true );
	}
}
update_option( 'laukku_tt_finder_page_id', $finder_id );

$shop_url  = wc_get_page_permalink( 'shop' );
$hero_html = '<!-- wp:html -->
<section class="laukku-hero">
	<h1>Laukut ja matkatarvikkeet edullisesti</h1>
	<p>Yli 5 000 tuotetta matkalaukuista pakkauskuutioihin. Lähetämme Jyväskylästä 1–3 arkipäivässä – tai tule sovittamaan laukkua myymäläämme Seppälään.</p>
	<a class="button alt" href="' . esc_url( $shop_url ) . '">Selaa tuotteita</a>
	<a class="button is-ghost" href="' . esc_url( get_permalink( $finder_id ) ) . '">Sopiiko laukku koneeseen?</a>
</section>
<!-- /wp:html -->';
$home_id   = $upsert_page( 'etusivu', 'Etusivu', $hero_html, 'template-homepage.php' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );

// Primary menu.
$menu_name = 'Päävalikko';
$menu      = wp_get_nav_menu_object( $menu_name );
$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu( $menu_name );
foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
	wp_delete_post( $item->ID, true );
}
foreach ( array( 'matkalaukut', 'laukut-ja-reput', 'lompakot', 'pakkaaminen', 'matkatarvikkeet' ) as $slug ) {
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-type'      => 'taxonomy',
			'menu-item-object'    => 'product_cat',
			'menu-item-object-id' => $cat_ids[ $slug ],
			'menu-item-status'    => 'publish',
		)
	);
}
wp_update_nav_menu_item(
	$menu_id,
	0,
	array(
		'menu-item-type'      => 'post_type',
		'menu-item-object'    => 'page',
		'menu-item-object-id' => $finder_id,
		'menu-item-title'     => '✈ Sopiiko koneeseen?',
		'menu-item-status'    => 'publish',
	)
);
$locations             = get_theme_mod( 'nav_menu_locations', array() );
$locations['primary']  = $menu_id;
$locations['handheld'] = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

// Storefront: hide the "welcome" admin notice.
update_option( 'storefront_nux_dismissed', true );

/* -------------------------------------------------------------------------
 * 6. Finish
 * ---------------------------------------------------------------------- */
if ( class_exists( '\Laukku\TravelTools\Cabin_Checker' ) ) {
	$n = \Laukku\TravelTools\Cabin_Checker::sync_all();
	WP_CLI::log( "→ Cabin fit calculated for {$n} products" );
}
if ( function_exists( 'wc_update_product_lookup_tables' ) ) {
	wc_update_product_lookup_tables();
}
wc_delete_product_transients();
// The product/category URL bases changed above, but WooCommerce registered its
// taxonomies with the old bases at the start of this request. Deleting the
// cached rules makes WordPress rebuild them – with the new bases – on the next
// page load (flush_rewrite_rules() here would rebuild them with the old ones).
delete_option( 'rewrite_rules' );

WP_CLI::success( 'Demo store ready. Try: wp laukku audit' );
