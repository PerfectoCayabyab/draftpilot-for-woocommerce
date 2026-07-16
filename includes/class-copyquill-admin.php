<?php
/**
 * Admin menu + React app bootstrap.
 *
 * @package Copyquill
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Copyquill page under WooCommerce and enqueues the app.
 */
class Copyquill_Admin {

	/**
	 * Hook everything.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * WooCommerce → Copyquill.
	 */
	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Copyquill', 'copyquill-for-woocommerce' ),
			__( 'Copyquill', 'copyquill-for-woocommerce' ),
			'manage_woocommerce',
			'copyquill',
			array( $this, 'render_page' )
		);
	}

	/**
	 * App root element.
	 */
	public function render_page() {
		echo '<div id="copyquill-root"></div>';
	}

	/**
	 * Enqueue the built app only on our page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( 'woocommerce_page_copyquill' !== $hook ) {
			return;
		}

		$asset_file = COPYQUILL_PLUGIN_DIR . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script(
			'copyquill-admin',
			COPYQUILL_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style( 'wp-components' );

		$style = COPYQUILL_PLUGIN_DIR . 'build/style-index.css';
		if ( file_exists( $style ) ) {
			wp_enqueue_style(
				'copyquill-admin',
				COPYQUILL_PLUGIN_URL . 'build/style-index.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_set_script_translations( 'copyquill-admin', 'copyquill-for-woocommerce' );
	}
}
