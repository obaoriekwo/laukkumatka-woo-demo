<?php
/**
 * Cabin-size checker.
 *
 * Answers the #1 question luggage shoppers have – "will this fit as hand
 * luggage on my flight?" – directly on the product page, on product cards
 * and through a [laukku_cabin_finder] shortcode that lists every bag that
 * fits a chosen airline or the customer's own measurements.
 *
 * Fit is calculated by sorting both the bag's and the airline's dimensions
 * from largest to smallest and comparing pairwise, so a bag rotated sideways
 * still counts. Results are pre-computed into post meta (_laukku_fits) on
 * product save, so the finder is a cheap meta query instead of a PHP loop
 * over the whole catalogue.
 *
 * @package LaukkuTravelTools
 */

namespace Laukku\TravelTools;

defined( 'ABSPATH' ) || exit;

final class Cabin_Checker {

	public const META_FITS = '_laukku_fits';

	public static function init(): void {
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_single' ), 25 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_badge' ), 9 );
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'sync_product' ) );
		add_shortcode( 'laukku_cabin_finder', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Airline hand-luggage limits in centimetres.
	 *
	 * IMPORTANT: example values for the demo. Airlines change their rules –
	 * verify before using in production. Filter `laukku_tt_airlines` lets
	 * the shop maintain this list from a theme or mu-plugin.
	 *
	 * type: 'cabin' = overhead-bin bag, 'personal' = under-the-seat item.
	 */
	public static function airlines(): array {
		$airlines = array(
			'finnair_cabin'    => array(
				'label' => 'Finnair',
				'type'  => 'cabin',
				'dims'  => array( 56, 45, 25 ),
				'kg'    => 8,
			),
			'finnair_personal' => array(
				'label' => 'Finnair',
				'type'  => 'personal',
				'dims'  => array( 40, 30, 15 ),
				'kg'    => null,
			),
			'sas_cabin'        => array(
				'label' => 'SAS',
				'type'  => 'cabin',
				'dims'  => array( 55, 40, 23 ),
				'kg'    => 8,
			),
			'norwegian_cabin'  => array(
				'label' => 'Norwegian',
				'type'  => 'cabin',
				'dims'  => array( 55, 40, 23 ),
				'kg'    => 10,
			),
			'ryanair_priority' => array(
				'label' => 'Ryanair',
				'type'  => 'cabin',
				'dims'  => array( 55, 40, 20 ),
				'kg'    => 10,
			),
			'ryanair_free'     => array(
				'label' => 'Ryanair',
				'type'  => 'personal',
				'dims'  => array( 40, 30, 20 ),
				'kg'    => null,
			),
			'wizz_priority'    => array(
				'label' => 'Wizz Air',
				'type'  => 'cabin',
				'dims'  => array( 55, 40, 23 ),
				'kg'    => 10,
			),
			'wizz_free'        => array(
				'label' => 'Wizz Air',
				'type'  => 'personal',
				'dims'  => array( 40, 30, 20 ),
				'kg'    => null,
			),
			'easyjet_free'     => array(
				'label' => 'easyJet',
				'type'  => 'personal',
				'dims'  => array( 45, 36, 20 ),
				'kg'    => 15,
			),
		);
		return apply_filters( 'laukku_tt_airlines', $airlines );
	}

	public static function type_label( string $type ): string {
		return 'personal' === $type
			? __( 'under-seat bag (free)', 'laukku-travel-tools' )
			: __( 'cabin bag', 'laukku-travel-tools' );
	}

	/** Does a bag ($dims, cm) fit inside a limit ($limit, cm)? Rotation allowed. */
	public static function fits( array $dims, array $limit ): bool {
		rsort( $dims, SORT_NUMERIC );
		rsort( $limit, SORT_NUMERIC );
		foreach ( $dims as $i => $d ) {
			if ( $d > $limit[ $i ] + 0.001 ) {
				return false;
			}
		}
		return true;
	}

	/** @return array<string,bool> airline key => fits */
	public static function results_for( \WC_Product $product ): array {
		$dims = Luggage_Fields::dimensions_cm( $product );
		if ( ! $dims ) {
			return array();
		}
		$out = array();
		foreach ( self::airlines() as $key => $airline ) {
			$out[ $key ] = self::fits( $dims, $airline['dims'] );
		}
		return $out;
	}

	/** Only bags are checked – not cables, tents or travel pillows. */
	public static function is_applicable( \WC_Product $product ): bool {
		static $allowed = null, $cache_key = null;
		$option         = (array) get_option( 'laukku_tt_cabin_categories', array() );
		$key            = implode( ',', $option );
		if ( null === $allowed || $key !== $cache_key ) {
			$cache_key = $key;
			$allowed   = array_filter( array_map( 'absint', $option ) );
			// Selecting a parent category includes all its sub-categories.
			foreach ( $allowed as $term_id ) {
				$children = get_term_children( $term_id, 'product_cat' );
				if ( is_array( $children ) ) {
					$allowed = array_merge( $allowed, $children );
				}
			}
			$allowed = array_unique( $allowed );
		}
		if ( ! $allowed ) {
			return true; // No restriction configured: check everything with dimensions.
		}
		$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		return (bool) array_intersect( $allowed, wc_get_product_term_ids( $product_id, 'product_cat' ) );
	}

	/**
	 * Recompute and store which airlines a product fits.
	 * Writes post meta directly to avoid re-triggering a product save.
	 */
	public static function sync_product( \WC_Product $product ): void {
		$id = $product->get_id();
		if ( ! $id || $product->is_type( 'variation' ) ) {
			return;
		}
		delete_post_meta( $id, self::META_FITS );
		if ( ! self::is_applicable( $product ) ) {
			return;
		}
		foreach ( self::results_for( $product ) as $key => $fits ) {
			if ( $fits ) {
				add_post_meta( $id, self::META_FITS, $key );
			}
		}
	}

	/** Re-sync the whole catalogue (used by WP-CLI and after settings change). */
	public static function sync_all(): int {
		$ids = wc_get_products(
			array(
				'limit'  => -1,
				'return' => 'ids',
				'status' => array( 'publish', 'private', 'draft' ),
			)
		);
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product ) {
				self::sync_product( $product );
			}
		}
		return count( $ids );
	}

	/** Product card badge: "Cabin size". */
	public static function render_badge(): void {
		global $product;
		if ( 'yes' !== get_option( 'laukku_tt_show_badge', 'yes' ) || ! $product instanceof \WC_Product ) {
			return;
		}
		$fits     = (array) get_post_meta( $product->get_id(), self::META_FITS, false );
		$airlines = self::airlines();
		foreach ( $fits as $key ) {
			if ( isset( $airlines[ $key ] ) && 'cabin' === $airlines[ $key ]['type'] ) {
				echo '<span class="laukku-badge laukku-badge--cabin"><span class="laukku-badge__text">' . esc_html__( 'Cabin size', 'laukku-travel-tools' ) . '</span></span>';
				return;
			}
		}
	}

	/** "Will it fit?" panel in the single product summary. */
	public static function render_single(): void {
		global $product;
		if ( ! $product instanceof \WC_Product || ! self::is_applicable( $product ) ) {
			return;
		}
		$results = self::results_for( $product );
		if ( ! $results ) {
			return;
		}
		$airlines  = self::airlines();
		$fit_names = array();
		foreach ( $results as $key => $ok ) {
			if ( $ok && 'cabin' === $airlines[ $key ]['type'] ) {
				$fit_names[] = $airlines[ $key ]['label'];
			}
		}
		$fit_names = array_unique( $fit_names );
		$dims      = Luggage_Fields::dimensions_cm( $product );
		$finder    = (int) get_option( 'laukku_tt_finder_page_id' );
		?>
		<details class="laukku-cabin" <?php echo $fit_names ? '' : 'open'; ?>>
			<summary class="laukku-cabin__summary">
				<span class="laukku-cabin__icon" aria-hidden="true">✈</span>
				<?php if ( $fit_names ) : ?>
					<?php
					/* translators: %s: comma separated airline names. */
					echo esc_html( sprintf( __( 'Fits as cabin bag: %s', 'laukku-travel-tools' ), implode( ', ', $fit_names ) ) );
					?>
				<?php else : ?>
					<?php esc_html_e( 'Check-in size – too big for most cabin limits', 'laukku-travel-tools' ); ?>
				<?php endif; ?>
			</summary>
			<div class="laukku-cabin__body">
				<p class="laukku-cabin__dims">
					<?php
					/* translators: %s: bag dimensions. */
					echo esc_html( sprintf( __( 'Bag size: %s (incl. wheels and handles)', 'laukku-travel-tools' ), Luggage_Fields::format_dimensions( $dims ) ) );
					?>
				</p>
				<table class="laukku-cabin__table">
					<caption class="screen-reader-text"><?php esc_html_e( 'Airline hand-luggage size limits compared with this bag', 'laukku-travel-tools' ); ?></caption>
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Airline', 'laukku-travel-tools' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Limit', 'laukku-travel-tools' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Fits?', 'laukku-travel-tools' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $results as $key => $ok ) : ?>
						<?php $a = $airlines[ $key ]; ?>
						<tr class="<?php echo $ok ? 'is-fit' : 'is-nofit'; ?>">
							<th scope="row"><?php echo esc_html( $a['label'] ); ?> <small><?php echo esc_html( self::type_label( $a['type'] ) ); ?></small></th>
							<td><?php echo esc_html( Luggage_Fields::format_dimensions( $a['dims'] ) ); ?></td>
							<td>
								<?php if ( $ok ) : ?>
									<span aria-hidden="true">✓</span> <?php esc_html_e( 'Yes', 'laukku-travel-tools' ); ?>
								<?php else : ?>
									<span aria-hidden="true">✕</span> <?php esc_html_e( 'No', 'laukku-travel-tools' ); ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="laukku-cabin__note">
					<?php esc_html_e( 'Airlines change their rules and fares differ – always confirm with your airline before flying.', 'laukku-travel-tools' ); ?>
					<?php if ( $finder ) : ?>
						<a href="<?php echo esc_url( get_permalink( $finder ) ); ?>"><?php esc_html_e( 'Find more bags that fit', 'laukku-travel-tools' ); ?> →</a>
					<?php endif; ?>
				</p>
			</div>
		</details>
		<?php
	}

	/**
	 * [laukku_cabin_finder columns="4" limit="24" title="…"]
	 * title="" hides the visible heading (keeps it for screen readers) –
	 * useful when the page title already asks the question.
	 *
	 * Plain GET form – works without JavaScript, results are shareable
	 * and bookmarkable (?lentoyhtio=finnair_cabin).
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'columns' => 4,
				'limit'   => 24,
				'title'   => __( 'Will it fit on the plane?', 'laukku-travel-tools' ),
			),
			$atts,
			'laukku_cabin_finder'
		);

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only public search form.
		$selected = isset( $_GET['lentoyhtio'] ) ? sanitize_key( wp_unslash( $_GET['lentoyhtio'] ) ) : '';
		$custom   = array();
		foreach ( array( 'p', 'l', 'k' ) as $k ) {
			$custom[ $k ] = isset( $_GET[ 'koko_' . $k ] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_GET[ 'koko_' . $k ] ) ) ) : 0;
		}
		// phpcs:enable

		$airlines = self::airlines();
		$uid      = wp_unique_id( 'laukku-finder-' );

		ob_start();
		?>
		<div class="laukku-finder">
			<form class="laukku-finder__form" method="get" action="<?php echo esc_url( get_permalink() ); ?>" role="search" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
				<h2 id="<?php echo esc_attr( $uid ); ?>-title" class="<?php echo '' === $atts['title'] ? 'screen-reader-text' : 'laukku-finder__title'; ?>"><?php echo esc_html( '' === $atts['title'] ? __( 'Will it fit on the plane?', 'laukku-travel-tools' ) : $atts['title'] ); ?></h2>
				<div class="laukku-finder__row">
					<label for="<?php echo esc_attr( $uid ); ?>-airline"><?php esc_html_e( 'Choose airline and ticket type', 'laukku-travel-tools' ); ?></label>
					<select id="<?php echo esc_attr( $uid ); ?>-airline" name="lentoyhtio">
						<option value=""><?php esc_html_e( '— Select —', 'laukku-travel-tools' ); ?></option>
						<?php foreach ( $airlines as $key => $a ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>>
								<?php echo esc_html( $a['label'] . ' – ' . self::type_label( $a['type'] ) . ' (' . implode( '×', $a['dims'] ) . ' cm)' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<fieldset class="laukku-finder__custom">
					<legend><?php esc_html_e( '…or enter your own size limit (cm)', 'laukku-travel-tools' ); ?></legend>
					<?php
					$labels = array(
						'p' => __( 'Length', 'laukku-travel-tools' ),
						'l' => __( 'Width', 'laukku-travel-tools' ),
						'k' => __( 'Height', 'laukku-travel-tools' ),
					);
					foreach ( $labels as $k => $label ) :
						?>
						<label>
							<span><?php echo esc_html( $label ); ?></span>
							<input type="number" inputmode="decimal" min="0" step="0.5" name="koko_<?php echo esc_attr( $k ); ?>" value="<?php echo $custom[ $k ] ? esc_attr( (string) $custom[ $k ] ) : ''; ?>">
						</label>
					<?php endforeach; ?>
				</fieldset>
				<button type="submit" class="button"><?php esc_html_e( 'Show bags that fit', 'laukku-travel-tools' ); ?></button>
			</form>
			<div class="laukku-finder__results" aria-live="polite">
				<?php echo self::finder_results( $selected, $custom, (int) $atts['columns'], (int) $atts['limit'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce shortcode output. ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function finder_results( string $airline_key, array $custom, int $columns, int $limit ): string {
		$airlines   = self::airlines();
		$has_custom = $custom['p'] > 0 && $custom['l'] > 0 && $custom['k'] > 0;

		if ( $has_custom ) {
			$limit_dims = array( $custom['p'], $custom['l'], $custom['k'] );
			$ids        = self::ids_fitting_custom( $limit_dims );
			/* translators: %s: dimensions. */
			$heading = sprintf( __( 'Bags that fit inside %s', 'laukku-travel-tools' ), Luggage_Fields::format_dimensions( $limit_dims ) );
		} elseif ( $airline_key && isset( $airlines[ $airline_key ] ) ) {
			$ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => $limit,
					'no_found_rows'  => true,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => self::META_FITS,
							'value' => $airline_key,
						),
					),
				)
			);
			$a   = $airlines[ $airline_key ];
			/* translators: 1: airline name, 2: bag type. */
			$heading = sprintf( __( 'Bags that fit %1$s as %2$s', 'laukku-travel-tools' ), $a['label'], self::type_label( $a['type'] ) );
		} else {
			return '';
		}

		if ( ! $ids ) {
			return '<p class="woocommerce-info">' . esc_html__( 'No bags found for that size. Try a larger limit or a different ticket type.', 'laukku-travel-tools' ) . '</p>';
		}

		$ids = array_slice( array_map( 'absint', $ids ), 0, $limit );

		return '<h3 class="laukku-finder__heading">' . esc_html( $heading ) . ' <span class="count">(' . count( $ids ) . ')</span></h3>'
			. do_shortcode( sprintf( '[products ids="%s" columns="%d" limit="%d" orderby="price" order="ASC"]', implode( ',', $ids ), $columns, $limit ) );
	}

	/** Custom size: compare against every product that has dimensions. */
	private static function ids_fitting_custom( array $limit_dims ): array {
		$candidates = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Upper bound keeps this request cheap on big catalogues.
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_length',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'DECIMAL',
					),
				),
			)
		);
		$out        = array();
		foreach ( $candidates as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || ! self::is_applicable( $product ) ) {
				continue;
			}
			$dims = Luggage_Fields::dimensions_cm( $product );
			if ( $dims && self::fits( $dims, $limit_dims ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}
}
