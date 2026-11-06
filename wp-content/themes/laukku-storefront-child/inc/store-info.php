<?php
/**
 * Store details in one place – used by the footer and by LocalBusiness
 * structured data (helps "laukkukauppa Jyväskylä"-type local searches and
 * the Google Business Profile match).
 *
 * Values come from the public laukkumatka.fi footer. Verify before going
 * live; override with the `laukku_child_store_info` filter.
 *
 * @package LaukkuChild
 */

defined( 'ABSPATH' ) || exit;

function laukku_child_store_info(): array {
	return apply_filters(
		'laukku_child_store_info',
		array(
			'name'        => get_bloginfo( 'name' ),
			'legal_name'  => 'Triptek Ky',
			'business_id' => '2338193-4',
			'street'      => 'Vasarakatu 29',
			'locality'    => 'Jyväskylä',
			'country'     => 'FI',
			'location'    => __( 'In the K-Citymarket Seppälä building', 'laukku-child' ),
			// ISO weekday codes for schema.org + display labels.
			'hours'       => array(
				array(
					'days'  => array( 'Mo', 'Tu', 'We', 'Th', 'Fr' ),
					'label' => __( 'Mon–Fri', 'laukku-child' ),
					'open'  => '10:00',
					'close' => '18:00',
				),
				array(
					'days'  => array( 'Sa' ),
					'label' => __( 'Sat', 'laukku-child' ),
					'open'  => '10:00',
					'close' => '16:00',
				),
				array(
					'days'  => array( 'Su' ),
					'label' => __( 'Sun', 'laukku-child' ),
					'open'  => '12:00',
					'close' => '17:00',
				),
			),
		)
	);
}

/**
 * Store JSON-LD on the front page only (one canonical place for it).
 */
add_action(
	'wp_head',
	static function () {
		if ( ! is_front_page() ) {
			return;
		}
		$info  = laukku_child_store_info();
		$hours = array();
		$map   = array(
			'Mo' => 'Monday',
			'Tu' => 'Tuesday',
			'We' => 'Wednesday',
			'Th' => 'Thursday',
			'Fr' => 'Friday',
			'Sa' => 'Saturday',
			'Su' => 'Sunday',
		);
		foreach ( $info['hours'] as $row ) {
			$hours[] = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array_map( static fn( $d ) => 'https://schema.org/' . $map[ $d ], $row['days'] ),
				'opens'     => $row['open'],
				'closes'    => $row['close'],
			);
		}
		$data    = array(
			'@context'                  => 'https://schema.org',
			'@type'                     => 'Store',
			'name'                      => $info['name'],
			'legalName'                 => $info['legal_name'],
			'vatID'                     => 'FI' . str_replace( '-', '', $info['business_id'] ),
			'url'                       => home_url( '/' ),
			'address'                   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $info['street'],
				'addressLocality' => $info['locality'],
				'addressCountry'  => $info['country'],
			),
			'openingHoursSpecification' => $hours,
		);
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$data['logo'] = wp_get_attachment_image_url( $logo_id, 'full' );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}
);
