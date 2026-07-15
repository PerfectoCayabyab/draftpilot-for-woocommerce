<?php
/**
 * Admin menu + React app bootstrap.
 *
 * @package CopyPilot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the CopyPilot page under WooCommerce and enqueues the app.
 */
class CopyPilot_Admin {

	/**
	 * Hook everything.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * WooCommerce → CopyPilot.
	 */
	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'CopyPilot', 'copypilot-for-woocommerce' ),
			__( 'CopyPilot', 'copypilot-for-woocommerce' ),
			'manage_woocommerce',
			'copypilot',
			array( $this, 'render_page' )
		);
	}

	/**
	 * App root element.
	 */
	public function render_page() {
		echo '<div id="copypilot-root"></div>';
	}

	/**
	 * Enqueue the built app only on our page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( 'woocommerce_page_copypilot' !== $hook ) {
			return;
		}

		$asset_file = COPYPILOT_PLUGIN_DIR . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script(
			'copypilot-admin',
			COPYPILOT_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style( 'wp-components' );

		$style = COPYPILOT_PLUGIN_DIR . 'build/style-index.css';
		if ( file_exists( $style ) ) {
			wp_enqueue_style(
				'copypilot-admin',
				COPYPILOT_PLUGIN_URL . 'build/style-index.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_set_script_translations( 'copypilot-admin', 'copypilot-for-woocommerce' );
	}
}
