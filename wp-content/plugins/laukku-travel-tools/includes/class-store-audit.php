<?php
/**
 * Catalogue quality audit.
 *
 * Finds the content problems that hurt SEO, trust and conversion in a
 * large catalogue – the kind of issues spotted on the live store during
 * preparation for this work trial (title/URL size mismatches, duplicate
 * listings, missing alt texts, etc.).
 *
 * Available as WooCommerce → Store audit and as `wp laukku audit`.
 *
 * @package LaukkuTravelTools
 */

namespace Laukku\TravelTools;

defined( 'ABSPATH' ) || exit;

final class Store_Audit {

	private const TRANSIENT = 'laukku_tt_audit_results';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'admin_post_laukku_run_audit', array( __CLASS__, 'handle_run' ) );
	}

	/** Issue types: key => [severity, human label]. */
	public static function checks(): array {
		return array(
			'missing_image'       => array( 'high', __( 'No product image', 'laukku-travel-tools' ) ),
			'missing_price'       => array( 'high', __( 'No price – product cannot be bought', 'laukku-travel-tools' ) ),
			'bad_sale_price'      => array( 'high', __( 'Sale price is not lower than regular price', 'laukku-travel-tools' ) ),
			'title_slug_mismatch' => array( 'high', __( 'Size in title and in URL disagree', 'laukku-travel-tools' ) ),
			'title_dims_mismatch' => array( 'high', __( 'Size in title disagrees with the dimension fields', 'laukku-travel-tools' ) ),
			'duplicate_title'     => array( 'medium', __( 'Duplicate product title (possible duplicate listing)', 'laukku-travel-tools' ) ),
			'missing_dimensions'  => array( 'medium', __( 'Missing dimensions – cabin checker cannot run', 'laukku-travel-tools' ) ),
			'missing_alt'         => array( 'medium', __( 'Main image has no alt text', 'laukku-travel-tools' ) ),
			'missing_short_desc'  => array( 'medium', __( 'No short description', 'laukku-travel-tools' ) ),
			'thin_description'    => array( 'low', __( 'Description under 150 characters', 'laukku-travel-tools' ) ),
			'long_title'          => array( 'low', __( 'Title over 70 characters (truncated in Google)', 'laukku-travel-tools' ) ),
			'uncategorized'       => array( 'low', __( 'Only in the default "Uncategorized" category', 'laukku-travel-tools' ) ),
		);
	}

	/**
	 * Run every check over the published catalogue.
	 *
	 * @return array<int,array{id:int,sku:string,name:string,severity:string,check:string,issue:string,detail:string}>
	 */
	public static function run(): array {
		$checks = self::checks();
		$ids    = wc_get_products(
			array(
				'limit'  => -1,
				'status' => 'publish',
				'return' => 'ids',
			)
		);

		$issues      = array();
		$titles      = array();
		$default_cat = (int) get_option( 'default_product_cat' );

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$name = $product->get_name();
			$add  = static function ( string $check, string $detail = '' ) use ( &$issues, $checks, $product, $name ) {
				$issues[] = array(
					'id'       => $product->get_id(),
					'sku'      => (string) $product->get_sku(),
					'name'     => $name,
					'severity' => $checks[ $check ][0],
					'check'    => $check,
					'issue'    => $checks[ $check ][1],
					'detail'   => $detail,
				);
			};

			$titles[ self::normalise_title( $name ) ][] = $product->get_id();

			// Images.
			$image_id = (int) $product->get_image_id();
			if ( ! $image_id ) {
				$add( 'missing_image' );
			} elseif ( '' === trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) ) {
				$add( 'missing_alt', (string) get_the_title( $image_id ) );
			}

			// Price.
			if ( ! $product->is_type( 'variable' ) && ! $product->is_type( 'grouped' ) && '' === $product->get_regular_price() ) {
				$add( 'missing_price' );
			}
			$regular = (float) $product->get_regular_price();
			$sale    = $product->get_sale_price();
			if ( '' !== $sale && $regular > 0 && (float) $sale >= $regular ) {
				$add( 'bad_sale_price', $sale . ' ≥ ' . $regular );
			}

			// Size consistency: title vs slug vs fields.
			$title_dims = self::extract_dims( $name );
			$slug_dims  = self::extract_dims( $product->get_slug() );
			if ( $title_dims && $slug_dims && $title_dims !== $slug_dims ) {
				$add( 'title_slug_mismatch', implode( 'x', $title_dims ) . ' ≠ ' . implode( 'x', $slug_dims ) . ' (' . $product->get_slug() . ')' );
			}
			$field_dims = Luggage_Fields::dimensions_cm( $product );
			if ( $title_dims && $field_dims ) {
				$sorted = array_map( static fn( $d ) => (string) round( $d ), $field_dims );
				rsort( $sorted, SORT_NUMERIC );
				if ( $sorted !== $title_dims ) {
					$add( 'title_dims_mismatch', implode( 'x', $title_dims ) . ' ≠ ' . implode( 'x', $sorted ) );
				}
			}
			if ( ! $field_dims && Cabin_Checker::is_applicable( $product ) ) {
				$add( 'missing_dimensions' );
			}

			// Content.
			if ( '' === trim( wp_strip_all_tags( $product->get_short_description() ) ) ) {
				$add( 'missing_short_desc' );
			}
			$desc_len = mb_strlen( trim( wp_strip_all_tags( $product->get_description() ) ) );
			if ( $desc_len < 150 ) {
				$add( 'thin_description', (string) $desc_len );
			}
			if ( mb_strlen( $name ) > 70 ) {
				$add( 'long_title', (string) mb_strlen( $name ) );
			}
			$cats = $product->get_category_ids();
			if ( ! $cats || ( 1 === count( $cats ) && (int) $cats[0] === $default_cat ) ) {
				$add( 'uncategorized' );
			}
		}

		// Duplicate titles (second pass, needs the full list).
		foreach ( $titles as $group ) {
			if ( count( $group ) < 2 ) {
				continue;
			}
			foreach ( $group as $id ) {
				$product  = wc_get_product( $id );
				$issues[] = array(
					'id'       => $id,
					'sku'      => (string) $product->get_sku(),
					'name'     => $product->get_name(),
					'severity' => $checks['duplicate_title'][0],
					'check'    => 'duplicate_title',
					'issue'    => $checks['duplicate_title'][1],
					'detail'   => 'IDs ' . implode( ', ', $group ),
				);
			}
		}

		$order = array(
			'high'   => 0,
			'medium' => 1,
			'low'    => 2,
		);
		usort( $issues, static fn( $a, $b ) => array( $order[ $a['severity'] ], $a['name'] ) <=> array( $order[ $b['severity'] ], $b['name'] ) );

		return $issues;
	}

	/** "40x30x20cm", "40 x 30 x 20", "40×30×20" → ['40','30','20'] sorted desc. */
	public static function extract_dims( string $text ): ?array {
		if ( ! preg_match( '/(\d{1,3}(?:[.,]\d)?)\s*[x×\-]\s*(\d{1,3}(?:[.,]\d)?)\s*[x×\-]\s*(\d{1,3}(?:[.,]\d)?)/iu', $text, $m ) ) {
			return null;
		}
		$dims = array_map( static fn( $d ) => (string) round( (float) str_replace( ',', '.', $d ) ), array_slice( $m, 1, 3 ) );
		rsort( $dims, SORT_NUMERIC );
		return $dims;
	}

	private static function normalise_title( string $title ): string {
		$title = remove_accents( mb_strtolower( $title ) );
		return trim( preg_replace( '/[^a-z0-9]+/', ' ', $title ) );
	}

	/* ------------------------------------------------------------------ */
	/* Admin page                                                          */
	/* ------------------------------------------------------------------ */

	public static function menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Store audit', 'laukku-travel-tools' ),
			__( 'Store audit', 'laukku-travel-tools' ),
			'manage_woocommerce',
			'laukku-store-audit',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function handle_run(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'laukku-travel-tools' ), 403 );
		}
		check_admin_referer( 'laukku_run_audit' );
		set_transient(
			self::TRANSIENT,
			array(
				'time'   => time(),
				'issues' => self::run(),
			),
			DAY_IN_SECONDS
		);
		wp_safe_redirect( admin_url( 'admin.php?page=laukku-store-audit' ) );
		exit;
	}

	public static function render_page(): void {
		$data   = get_transient( self::TRANSIENT );
		$issues = is_array( $data ) ? $data['issues'] : null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
		$filter = isset( $_GET['severity'] ) ? sanitize_key( wp_unslash( $_GET['severity'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Store audit', 'laukku-travel-tools' ); ?></h1>
			<p><?php esc_html_e( 'Checks every published product for problems that hurt search visibility, customer trust and sales. Fix the red ones first.', 'laukku-travel-tools' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="laukku_run_audit">
				<?php wp_nonce_field( 'laukku_run_audit' ); ?>
				<?php submit_button( null === $issues ? __( 'Run audit', 'laukku-travel-tools' ) : __( 'Run again', 'laukku-travel-tools' ), 'primary', 'submit', false ); ?>
				<?php if ( is_array( $data ) ) : ?>
					<span class="description">
						<?php
						/* translators: %s: human time diff. */
						echo esc_html( sprintf( __( 'Last run %s ago.', 'laukku-travel-tools' ), human_time_diff( (int) $data['time'] ) ) );
						?>
						<?php esc_html_e( 'Tip: on big catalogues use WP-CLI: wp laukku audit --format=csv > audit.csv', 'laukku-travel-tools' ); ?>
					</span>
				<?php endif; ?>
			</form>

			<?php
			if ( null === $issues ) {
				echo '</div>';
				return;
			}

			$counts = array_count_values( array_column( $issues, 'severity' ) );
			$labels = array(
				''       => __( 'All', 'laukku-travel-tools' ),
				'high'   => __( 'High', 'laukku-travel-tools' ),
				'medium' => __( 'Medium', 'laukku-travel-tools' ),
				'low'    => __( 'Low', 'laukku-travel-tools' ),
			);
			echo '<ul class="subsubsub">';
			$links = array();
			foreach ( $labels as $key => $label ) {
				$count   = '' === $key ? count( $issues ) : ( $counts[ $key ] ?? 0 );
				$url     = add_query_arg( 'severity', $key, admin_url( 'admin.php?page=laukku-store-audit' ) );
				$links[] = sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( $url ), $filter === $key ? ' class="current" aria-current="page"' : '', esc_html( $label ), (int) $count );
			}
			echo implode( ' | </li>', $links ) . '</li></ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.

			if ( $filter ) {
				$issues = array_filter( $issues, static fn( $i ) => $i['severity'] === $filter );
			}
			$colours = array(
				'high'   => '#d63638',
				'medium' => '#dba617',
				'low'    => '#72aee6',
			);
			?>
			<table class="widefat striped" style="margin-top:1em">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Severity', 'laukku-travel-tools' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Product', 'laukku-travel-tools' ); ?></th>
						<th scope="col"><?php esc_html_e( 'SKU', 'laukku-travel-tools' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Issue', 'laukku-travel-tools' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Detail', 'laukku-travel-tools' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! $issues ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No issues found. Nice!', 'laukku-travel-tools' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $issues as $issue ) : ?>
					<tr>
						<td style="white-space:nowrap"><span style="display:inline-block;width:.7em;height:.7em;border-radius:50%;background:<?php echo esc_attr( $colours[ $issue['severity'] ] ); ?>" aria-hidden="true"></span> <?php echo esc_html( $labels[ $issue['severity'] ] ); ?></td>
						<td><a href="<?php echo esc_url( get_edit_post_link( $issue['id'] ) ); ?>"><?php echo esc_html( $issue['name'] ); ?></a></td>
						<td><?php echo esc_html( $issue['sku'] ); ?></td>
						<td><?php echo esc_html( $issue['issue'] ); ?></td>
						<td><code><?php echo esc_html( $issue['detail'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
