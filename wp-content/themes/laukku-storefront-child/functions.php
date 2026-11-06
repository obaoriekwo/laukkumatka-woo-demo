<?php
/**
 * Laukku Storefront Child – theme bootstrap.
 *
 * Principle: change Storefront through hooks and filters, not by copying
 * template files. That keeps parent-theme updates (security fixes, new
 * WooCommerce compatibility) painless.
 *
 * @package LaukkuChild
 */

defined( 'ABSPATH' ) || exit;

define( 'LAUKKU_CHILD_VERSION', '1.0.0' );

require_once __DIR__ . '/inc/store-info.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/woocommerce.php';
require_once __DIR__ . '/inc/homepage.php';

add_action(
	'after_setup_theme',
	static function () {
		load_child_theme_textdomain( 'laukku-child', get_stylesheet_directory() . '/languages' );
	}
);

/**
 * Assets. System font stack = no external font requests (faster, and no
 * visitor IP sent to a third party – relevant under GDPR).
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style(
			'laukku-child',
			get_stylesheet_directory_uri() . '/assets/css/main.css',
			array( 'storefront-style', 'storefront-woocommerce-style' ),
			LAUKKU_CHILD_VERSION
		);

		wp_enqueue_script(
			'laukku-child',
			get_stylesheet_directory_uri() . '/assets/js/main.js',
			array(),
			LAUKKU_CHILD_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			'laukku-child',
			'laukkuChild',
			array(
				'decrease' => __( 'Decrease quantity', 'laukku-child' ),
				'increase' => __( 'Increase quantity', 'laukku-child' ),
			)
		);
	},
	20
);

/**
 * Storefront Customizer defaults → brand palette. Store staff can still
 * change every colour in Appearance → Customize.
 */
add_filter(
	'storefront_setting_default_values',
	static function ( array $defaults ): array {
		return array_merge(
			$defaults,
			array(
				'storefront_heading_color'               => '#0b2a3c',
				'storefront_text_color'                  => '#3d4752',
				'storefront_accent_color'                => '#c2410c',
				'storefront_header_background_color'     => '#ffffff',
				'storefront_header_text_color'           => '#3d4752',
				'storefront_header_link_color'           => '#0b2a3c',
				'storefront_footer_background_color'     => '#0b2a3c',
				'storefront_footer_heading_color'        => '#ffffff',
				'storefront_footer_text_color'           => '#c9d4dd',
				'storefront_footer_link_color'           => '#ffffff',
				'storefront_button_background_color'     => '#eef2f5',
				'storefront_button_text_color'           => '#0b2a3c',
				'storefront_button_alt_background_color' => '#c2410c',
				'storefront_button_alt_text_color'       => '#ffffff',
			)
		);
	}
);
