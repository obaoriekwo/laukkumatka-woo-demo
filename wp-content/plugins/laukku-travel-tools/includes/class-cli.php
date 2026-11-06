<?php
/**
 * WP-CLI commands.
 *
 * @package LaukkuTravelTools
 */

namespace Laukku\TravelTools;

defined( 'ABSPATH' ) || exit;

/**
 * Store maintenance tools for Laukku Travel Tools.
 */
final class CLI {

	/**
	 * Audit the product catalogue for SEO and content problems.
	 *
	 * ## OPTIONS
	 *
	 * [--severity=<severity>]
	 * : Only show one severity.
	 * ---
	 * options:
	 *   - high
	 *   - medium
	 *   - low
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp laukku audit
	 *     wp laukku audit --severity=high
	 *     wp laukku audit --format=csv > audit.csv
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named args.
	 */
	public function audit( $args, $assoc_args ): void {
		$issues = Store_Audit::run();
		if ( ! empty( $assoc_args['severity'] ) ) {
			$issues = array_values( array_filter( $issues, static fn( $i ) => $i['severity'] === $assoc_args['severity'] ) );
		}
		$format = $assoc_args['format'] ?? 'table';

		if ( 'table' === $format ) {
			$counts = array_count_values( array_column( $issues, 'severity' ) );
			\WP_CLI::log( sprintf( 'High: %d  Medium: %d  Low: %d', $counts['high'] ?? 0, $counts['medium'] ?? 0, $counts['low'] ?? 0 ) );
		}

		\WP_CLI\Utils\format_items( $format, $issues, array( 'severity', 'id', 'sku', 'name', 'issue', 'detail' ) );
	}

	/**
	 * Recalculate which airlines every product fits (run after changing the airline list or bulk-importing products).
	 *
	 * ## EXAMPLES
	 *
	 *     wp laukku cabin-sync
	 *
	 * @subcommand cabin-sync
	 */
	public function cabin_sync(): void {
		$count = Cabin_Checker::sync_all();
		\WP_CLI::success( sprintf( 'Cabin fit recalculated for %d products.', $count ) );
	}

	/**
	 * Check if a size fits each airline.
	 *
	 * ## OPTIONS
	 *
	 * <size>
	 * : Size in cm, e.g. 55x40x20
	 *
	 * ## EXAMPLES
	 *
	 *     wp laukku fits 55x40x23
	 */
	public function fits( $args ): void {
		$dims = Store_Audit::extract_dims( $args[0] );
		if ( ! $dims ) {
			\WP_CLI::error( 'Give the size like 55x40x20.' );
		}
		$rows = array();
		foreach ( Cabin_Checker::airlines() as $key => $a ) {
			$rows[] = array(
				'airline' => $a['label'],
				'type'    => $a['type'],
				'limit'   => implode( 'x', $a['dims'] ),
				'fits'    => Cabin_Checker::fits( array_map( 'floatval', $dims ), $a['dims'] ) ? 'YES' : 'no',
			);
		}
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'airline', 'type', 'limit', 'fits' ) );
	}
}
