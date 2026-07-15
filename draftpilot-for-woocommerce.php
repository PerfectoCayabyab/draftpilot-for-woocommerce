<?php
/**
 * Plugin Name:       DraftPilot for WooCommerce
 * Plugin URI:        https://github.com/PerfectoCayabyab/draftpilot-for-woocommerce
 * Description:       AI product copywriter for WooCommerce. Generate product descriptions, short descriptions, and SEO meta with Google Gemini — nothing is published until you approve it in the review queue.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Perfecto II Cayabyab
 * Author URI:        https://perfectocayabyab.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       draftpilot-for-woocommerce
 *
 * WC requires at least: 8.0
 * WC tested up to:      10.0
 *
 * @package DraftPilot
 */

defined( 'ABSPATH' ) || exit;

define( 'DRAFTPILOT_VERSION', '1.0.0' );
define( 'DRAFTPILOT_PLUGIN_FILE', __FILE__ );
define( 'DRAFTPILOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DRAFTPILOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once DRAFTPILOT_PLUGIN_DIR . 'includes/class-draftpilot-drafts.php';
require_once DRAFTPILOT_PLUGIN_DIR . 'includes/class-draftpilot-settings.php';
require_once DRAFTPILOT_PLUGIN_DIR . 'includes/class-draftpilot-gemini-client.php';
require_once DRAFTPILOT_PLUGIN_DIR . 'includes/class-draftpilot-generator.php';
require_once DRAFTPILOT_PLUGIN_DIR . 'includes/class-draftpilot-rest-controller.php';
require_once DRAFTPILOT_PLUGIN_DIR . 'includes/class-draftpilot-admin.php';

register_activation_hook( __FILE__, array( 'DraftPilot_Drafts', 'install' ) );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Boot the plugin once all plugins are loaded, so we can check for WooCommerce.
 */
function draftpilot_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__( 'DraftPilot for WooCommerce requires WooCommerce to be installed and active.', 'draftpilot-for-woocommerce' )
				);
			}
		);
		return;
	}

	DraftPilot_Drafts::maybe_upgrade();
	new DraftPilot_Admin();
	add_action(
		'rest_api_init',
		function () {
			$controller = new DraftPilot_REST_Controller();
			$controller->register_routes();
		}
	);
}
add_action( 'plugins_loaded', 'draftpilot_init' );
