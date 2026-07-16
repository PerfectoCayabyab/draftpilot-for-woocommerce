<?php
/**
 * Plugin Name:       Copyquill for WooCommerce
 * Plugin URI:        https://github.com/PerfectoCayabyab/copyquill-for-woocommerce
 * Description:       AI product copywriter for WooCommerce. Generate product descriptions, short descriptions, and SEO meta with Google Gemini — nothing is published until you approve it in the review queue.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Perfecto II Cayabyab
 * Author URI:        https://perfectocayabyab.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       copyquill-for-woocommerce
 *
 * WC requires at least: 8.0
 * WC tested up to:      10.9
 *
 * @package Copyquill
 */

defined( 'ABSPATH' ) || exit;

define( 'COPYQUILL_VERSION', '1.0.0' );
define( 'COPYQUILL_PLUGIN_FILE', __FILE__ );
define( 'COPYQUILL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COPYQUILL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once COPYQUILL_PLUGIN_DIR . 'includes/class-copyquill-drafts.php';
require_once COPYQUILL_PLUGIN_DIR . 'includes/class-copyquill-settings.php';
require_once COPYQUILL_PLUGIN_DIR . 'includes/class-copyquill-gemini-client.php';
require_once COPYQUILL_PLUGIN_DIR . 'includes/class-copyquill-generator.php';
require_once COPYQUILL_PLUGIN_DIR . 'includes/class-copyquill-rest-controller.php';
require_once COPYQUILL_PLUGIN_DIR . 'includes/class-copyquill-admin.php';

register_activation_hook( __FILE__, array( 'Copyquill_Drafts', 'install' ) );

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
function copyquill_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__( 'Copyquill for WooCommerce requires WooCommerce to be installed and active.', 'copyquill-for-woocommerce' )
				);
			}
		);
		return;
	}

	Copyquill_Drafts::maybe_upgrade();
	new Copyquill_Admin();
	add_action(
		'rest_api_init',
		function () {
			$controller = new Copyquill_REST_Controller();
			$controller->register_routes();
		}
	);
}
add_action( 'plugins_loaded', 'copyquill_init' );
