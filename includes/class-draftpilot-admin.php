<?php
/**
 * Admin menu + React app bootstrap.
 *
 * @package DraftPilot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the DraftPilot page under WooCommerce and enqueues the app.
 */
class DraftPilot_Admin {

	/**
	 * Hook everything.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * WooCommerce → DraftPilot.
	 */
	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'DraftPilot', 'draftpilot-for-woocommerce' ),
			__( 'DraftPilot', 'draftpilot-for-woocommerce' ),
			'manage_woocommerce',
			'draftpilot',
			array( $this, 'render_page' )
		);
	}

	/**
	 * App root element.
	 */
	public function render_page() {
		echo '<div id="draftpilot-root"></div>';
	}

	/**
	 * Enqueue the built app only on our page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( 'woocommerce_page_draftpilot' !== $hook ) {
			return;
		}

		$asset_file = DRAFTPILOT_PLUGIN_DIR . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script(
			'draftpilot-admin',
			DRAFTPILOT_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style( 'wp-components' );

		$style = DRAFTPILOT_PLUGIN_DIR . 'build/style-index.css';
		if ( file_exists( $style ) ) {
			wp_enqueue_style(
				'draftpilot-admin',
				DRAFTPILOT_PLUGIN_URL . 'build/style-index.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_set_script_translations( 'draftpilot-admin', 'draftpilot-for-woocommerce' );
	}
}
